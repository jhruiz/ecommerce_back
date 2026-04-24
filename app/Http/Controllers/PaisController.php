<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaisController extends Controller
{
    public function obtenerPaises()
    {
        try {
            // Traemos id y nombre (que en tu JS mapeas como descripcion)
            // Usamos select para que la respuesta sea ligera
            $paises = Pais::select('id', 'nombre as descripcion')
                          ->orderBy('nombre', 'asc')
                          ->get();

            return response()->json([
                'estado' => true,
                'data'   => $paises
            ]);

        } catch (\Exception $e) {
            Log::error("Error al obtener países: " . $e->getMessage());

            return response()->json([
                'estado' => false,
                'mensaje' => 'No se pudo cargar la lista de países',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
