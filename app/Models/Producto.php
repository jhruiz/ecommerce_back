<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $connection = 'tenant';

    protected $table = 'productos';
    public $timestamps = false;

    // --- RELACIONES EXISTENTES ---
    public function categoria() { return $this->belongsTo(Categoria::class, 'categoria_id'); }
    public function imagenes() { return $this->hasMany(Imagenesitem::class, 'producto_id'); }
    public function cargueinventario() { return $this->hasOne(Cargueinventario::class, 'producto_id'); }
    public function palabrasClaves() { return $this->hasMany(Palabrasclave::class, 'producto_id'); }

    /**
     * Función unificada para obtener productos o buscarlos
     */
    public static function obtenerInfoProductos($skip, $cantidad, $buscar = null) {
        $query = self::with([
                'categoria',
                'imagenes',
                'cargueinventario.impuestos'
            ])
            ->where('mostrarencatalogo', 1)
            ->where('empresa_id', 40);

        // Si hay algo que buscar, aplicamos los filtros
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('descripcion', 'LIKE', "%$buscar%") // Usamos descripción porque en tu Resource veo que ese es el "nombre"
                ->orWhere('codigo', 'LIKE', "%$buscar%")
                ->orWhere('referencia', 'LIKE', "%$buscar%")
                ->orWhereHas('palabrasclaves', function($sub) use ($buscar) {
                    $sub->where('palabra', 'LIKE', "%$buscar%");
                });
            });
        }

        return $query->skip($skip)->take($cantidad)->get();
    }

    /**
     * Contar total contemplando la búsqueda (necesario para el paginador)
     */
    public static function contarTotalActivos($buscar = null) {
        $query = self::where('mostrarencatalogo', 1)->where('empresa_id', 40);

            if ($buscar) {
                $query->where(function($q) use ($buscar) {
                    $q->where('descripcion', 'LIKE', "%$buscar%")
                    ->orWhere('codigo', 'LIKE', "%$buscar%")
                    ->orWhereHas('palabrasClaves', function($sub) use ($buscar) {
                        $sub->where('palabra', 'LIKE', "%$buscar%");
                    });
                });
            }

        return $query->count();
    }

    /**
     * Obtener productos relacionados
     */
    public static function obtenerSugeridos($productoActual, $cantidad = 4) {
        return self::with(['imagenes', 'cargueinventario'])
            ->where('categoria_id', $productoActual->categoria_id) // Misma categoría
            ->where('id', '!=', $productoActual->id)            // Excluir el actual
            ->where('mostrarencatalogo', 1)
            ->where('empresa_id', 40)
            ->inRandomOrder()                                    // Aleatorio para que varíe
            ->take($cantidad)
            ->get();
    }
}
