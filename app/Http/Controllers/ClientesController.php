<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientesController extends Controller
{

    /**
     * Realiza el login del cliente
     * En uso
     */
    public function loginCliente(Request $request) {
        $email = $request->input('user');
        $password = $request->input('password');

        try {
            // 1. Validación de entrada
            if (empty($email) || empty($password)) {
                return response()->json(['estado' => false, 'mensaje' => 'Debe ingresar usuario y contraseña'], 200);
            }
            
            // Obtiene la empresa obtenida y configurada por el tenant
            $empresaId = Config::get('app.empresa_id');

            // 2. Buscar cliente
            $cliente = Cliente::obtenerClientePorEmail($email, $empresaId);

            // 3. Verificar existencia
            if (!$cliente) {
                return response()->json(['estado' => false, 'mensaje' => 'Los datos de acceso no son correctos.'], 200);
            }

            // 4. Verificar contraseña (Bcrypt)
            if (!password_verify($password, $cliente->password)) {
                return response()->json(['estado' => false, 'mensaje' => 'El usuario y/o la contraseña no son correctos'], 200);
            }

            // 5. Todo OK: Guardar sesión
            // Guardamos el objeto transformado por el Resource para tener consistencia
            $dataCliente = new ClienteResource($cliente);

            $request->session()->put('user', $dataCliente);
            return response()->json([
                'estado'  => true,
                'data'    => $dataCliente,
                'mensaje' => '¡Bienvenido, ' . $cliente->nombre . '!'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'estado'  => false,
                'mensaje' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el perfil del cliente logueado para el Checkout
     */
    public function perfil(Request $request)
    {
        try {
            // Suponiendo que usas el sistema de autenticación de Laravel (Sanctum/Session)
            // Si usas una sesión personalizada, ajusta a: $clienteId = session('cliente_id');
            $cliente = $request->session()->get('user');

            if (!$cliente) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'No hay una sesión activa de cliente'
                ], 401);
            }

            return response()->json([
                'estado'  => true,
                'cliente' => new ClienteResource($cliente)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'estado'  => false,
                'mensaje' => 'Error al obtener el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza la dirección o teléfono si el cliente le da a "Cambiar" en el checkout
     */
    public function actualizarDatos(Request $request)
    {
        $cliente = Auth::user();

        $request->validate([
            'direccion' => 'required|string',
            'celular'   => 'required|string',
            'ciudadesmiggo_id' => 'nullable|integer'
        ]);

        $cliente->update([
            'direccion' => $request->direccion,
            'celular'   => $request->celular,
            'ciudadesmiggo_id' => $request->ciudadesmiggo_id
        ]);

        return response()->json([
            'estado' => true,
            'mensaje' => 'Información actualizada correctamente',
            'cliente' => new ClienteResource($cliente)
        ]);
    }


}
