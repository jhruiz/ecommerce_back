<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $connection = 'tenant';

    protected $table = 'categorias';
    public $timestamps = false; // Ya que usas 'created' y no 'created_at'

    /**
     * Obtener categorías de productos activas
     */
    public static function obtenerCategoriasEcommerce()
    {
        return self::where('mostrarencatalogo', 1)
            ->where('servicio', 0)
            ->orderBy('descripcion', 'asc')
            ->get();
    }
}
