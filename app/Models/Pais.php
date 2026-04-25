<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    // Laravel por defecto busca "pais", así que forzamos el nombre de tu tabla
    protected $table = 'paisesmiggos';

    protected $fillable = ['descripcion', 'codigo'];

    // Relación: Un país tiene muchos departamentos
    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'pais_id');
    }
}
