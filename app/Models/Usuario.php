<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';

    // Relación: Un país tiene muchos departamentos
    public static function obtenerUsuario($empresaId)
    {
        return Usuario::where('empresa_id', $empresaId)->first(['id']);
    }
}





