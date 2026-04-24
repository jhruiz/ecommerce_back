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

    /**
     * Crea un nuevo cliente o actualiza uno existente (Suscripción)
     */
    public function crearUsuario(Request $request)
    {
        try {
            // 1. Obtener la empresa del Tenant
            $empresaId = Config::get('app.empresa_id');

            // 2. Buscar si el cliente ya existe para esta empresa
            // Buscamos por email y empresa_id para evitar duplicados
            $cliente = Cliente::where('email', $request->email)
                            ->where('empresa_id', $empresaId)
                            ->first();

            // Preparar la data (Limpiamos y organizamos)
            $data = [
                'nombre'           => $request->nombres . ' ' . $request->apellidos,
                'nit'              => $request->identificacion,
                'direccion'        => $request->direccion,
                'telefono'         => $request->telefono,
                'celular'          => $request->celular,
                'ciudadesmiggo_id' => $request->ciudad,
                'ciudade_id'       => 0, // Valor por defecto si no lo usas
                'empresa_id'       => $empresaId,
                'estado_id'        => 1, // Activo
                'usuario_id'       => 1, // Usuario que lo crea (puedes dejarlo en 1)
                'tipoidentificacione_id' => 3, // Cédula por defecto
            ];

            // Solo actualizamos la contraseña si el usuario ingresó una
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($cliente) {
                // CASO A: El cliente ya existe, actualizamos sus datos y "activamos" su cuenta con la clave
                $cliente->update($data);
                $mensaje = '¡Bienvenido de nuevo! Tus datos han sido actualizados y tu cuenta está lista.';
            } else {
                // CASO B: Cliente nuevo
                $data['email'] = $request->email;
                $data['created'] = now();
                $cliente = Cliente::create($data);
                $mensaje = '¡Registro exitoso! Tu cuenta ha sido creada correctamente.';
            }

            // 3. Loguear automáticamente al usuario
            // $dataCliente = new ClienteResource($cliente);
            // $request->session()->put('user', $dataCliente);

            return response()->json([
                'estado'  => true,
                'mensaje' => $mensaje,
                'data'    => $dataCliente
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'estado'  => false,
                'mensaje' => 'Error al procesar el registro: ' . $e->getMessage()
            ], 500);
        }
    }


}
