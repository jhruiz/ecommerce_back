<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = ['paisemiggos_id', 'descripcion', 'codigo'];

    // Relación inversa: Un departamento pertenece a un país
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'paisemiggos_id');
    }

    // Relación: Un departamento tiene muchas ciudades
    public function ciudades()
    {
        return $this->hasMany(Ciudad::class, 'departamento_id');
    }
}
