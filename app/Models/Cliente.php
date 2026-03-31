<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $connection = 'tenant';

    protected $table = 'clientes';
    public $timestamps = false;

    // Relación con la ciudad
    public function ciudad() {
        return $this->belongsTo(Ciudadesmiggo::class, 'ciudadesmiggo_id');
    }

    /**
     * Busca un cliente activo por su email
     */
    public static function obtenerClientePorEmail($email) {
        return self::where('email', $email)
                ->where('empresa_id', 40)
                ->with('ciudad') // CARGA LA CIUDAD DE UNA VEZ
                ->first();
    }
}
