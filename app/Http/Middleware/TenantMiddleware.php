<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
{
    public function handle($request, Closure $next)
    {

        $origin = $request->headers->get('origin');
        //$origin = 'localhost';

        if (!$origin) {
            return response()->json(['error' => 'Origen de peticion no identificado'], 400);
        }

        // 2. Limpiar el protocolo (https://)
        $host = str_replace(['https://', 'http://'], '', $origin);

        // 3. Extraer el primer segmento (el identificador)
        $identifier = explode('.', $host)[0];

        // 2. Buscar el Tenant ÚNICO en la DB Landlord
        $tenant = \App\Models\Tenant::where('identifier', $identifier)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Subdominio no registrado'], 404);
        }

        // 3. Configurar la conexión 'tenant' al vuelo
        Config::set('database.connections.tenant', [
            'driver'    => 'mysql',
            'host'      => $tenant->db_host,
            'port'      => $tenant->db_port,
            'database'  => $tenant->db_name,
            'username'  => $tenant->db_username,
            'password'  => $tenant->db_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        // 4. Establecer conexión y cargar empresa_id para los filtros
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        // Guardamos el empresa_id en el config para usarlo en Global Scopes si es necesario
        Config::set('app.empresa_id', $tenant->empresa_id);

        // 5. Cargar llaves de Wompi (usando el typo 'integiry' de tu DB)
        Config::set('wompi.pub_key', $tenant->pub_key);
        Config::set('wompi.integrity_key', $tenant->integiry_key);

        return $next($request);
    }
}
