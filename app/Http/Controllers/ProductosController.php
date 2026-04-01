<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Facades\Config;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;

class ProductosController extends Controller
{
    /**
     * Obtener los productos que se usaran en el ecommerce
     * En uso
     */
    public function obtenerInfoProductos( Request $request ) {
        $pagina  = $request->input('pagina', 1);
        $cantidad = $request->input('cantidad', 12);
        $buscar   = $request->input('descripcion', null);

        try {
            $skip = ($pagina * $cantidad) - $cantidad;
            
            // Obtiene la empresa obtenida y configurada por el tenant
            $empresaId = Config::get('app.empresa_id');

            // Pasamos el parámetro $buscar a ambos métodos
            $items = Producto::obtenerInfoProductos($skip, $cantidad, $buscar, $empresaId);
            $total = Producto::contarTotalActivos($buscar, $empresaId);

            return response()->json([
                'estado'   => true,
                'data'     => ProductoResource::collection($items),
                'cantidad' => $total,
                'mensaje'  => 'Productos obtenidos con éxito'
            ], 200);

        } catch(\Throwable $e) {
            return response()->json([
                'estado'  => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Se obtiene el detalle de un producto específicio seleccionado
     */
    public function detalleProducto(Request $request) {
        $id = $request->query('idProd'); // Capturamos el ?id= de la URL

        try {
            if (!$id) throw new \Exception("Producto no proporcionado.");
            
            $prodsSugeridos = 8;

            // Traemos el producto con TODAS sus relaciones de una vez (Eager Loading)
            $producto = Producto::with([
                'categoria',
                'imagenes',
                'cargueinventario'
            ])
            ->where('id', $id)
            ->where('mostrarencatalogo', 1)
            ->first();

            if (!$producto) {
                return response()->json(['estado' => false, 'mensaje' => 'Producto no encontrado'], 404);
            }

            // --- LÓGICA TOP VENTAS ---
            // Contamos cuántas unidades se han vendido en TOTAL en toda la historia de Miggo
            $totalVendido = \DB::table('prefacturasdetalles')
                ->where('cargueinventario_id', $producto->cargueinventario->id)
                ->sum('cantidad');

            // Si se han vendido más de 50 unidades, lo bautizamos como Top Ventas
            $esTopVentas = ($totalVendido > 50);

            // Obtiene la empresa obtenida y configurada por el tenant
            $empresaId = Config::get('app.empresa_id');
            
            $sugeridos = Producto::obtenerSugeridos($producto, $prodsSugeridos, $empresaId);

            return response()->json([
                'estado' => true,
                'data'   => new ProductoResource($producto),
                'sugeridos' => ProductoResource::collection($sugeridos), // Usamos el mismo Resource
                'es_top_ventas' => $esTopVentas, // <--- Nueva variable
                'ventas_reales' => $totalVendido,
                'mensaje' => 'Detalle y sugeridos obtenidos'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['estado' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

}
