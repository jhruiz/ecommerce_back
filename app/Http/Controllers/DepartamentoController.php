<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepartamentoController extends Controller
{
    public function obtenerDepartamentos(Request $request)
    {
        try {
            $paisId = $request->input('paisId');

            if (!$paisId) {
                return response()->json([
                    'estado' => false,
                    'mensaje' => 'El ID del país es obligatorio'
                ], 400);
            }

            // Buscamos los departamentos que pertenezcan a ese país
            $departamentos = Departamento::where('paisemiggos_id', $paisId)
                                        ->select('id', 'descripcion')
                                        ->orderBy('descripcion', 'asc')
                                        ->get();

            return response()->json([
                'estado' => true,
                'data'   => $departamentos
            ]);

        } catch (\Exception $e) {
            Log::error("Error al obtener departamentos: " . $e->getMessage());
            return response()->json([
                'estado' => false,
                'mensaje' => 'Error al cargar departamentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
