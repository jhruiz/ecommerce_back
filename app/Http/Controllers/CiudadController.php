<?php

namespace App\Http\Controllers;

use App\Models\Ciudad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CiudadController extends Controller
{
    public function obtenerCiudades(Request $request)
    {
        try {
            $dptoId = $request->input('dptoId');

            if (!$dptoId) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'El ID del departamento es obligatorio'
                ], 400);
            }

            // Filtramos por departamento y renombramos 'nombre' a 'descripcion' para tu JS
            $ciudades = Ciudad::where('departamento_id', $dptoId)
                              ->select('id', 'nombre as descripcion')
                              ->orderBy('nombre', 'asc')
                              ->get();

            return response()->json([
                'estado' => true,
                'data'   => $ciudades
            ]);

        } catch (\Exception $e) {
            Log::error("Error al obtener ciudades: " . $e->getMessage());
            return response()->json([
                'estado' => false,
                'mensaje' => 'Error al cargar ciudades',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
