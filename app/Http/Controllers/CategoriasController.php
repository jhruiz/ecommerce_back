<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Http\Resources\CategoriaResource;
use Illuminate\Http\Request;

class CategoriasController extends Controller
{
    public function index()
    {
        try {
            $categorias = Categoria::obtenerCategoriasEcommerce();

            return response()->json([
                'estado' => true,
                'data'   => CategoriaResource::collection($categorias),
                'mensaje'=> 'Categorías obtenidas con éxito'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'estado' => false,
                'mensaje'=> 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
