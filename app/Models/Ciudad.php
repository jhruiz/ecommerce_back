<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudadesmiggos';

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo',
        'codefacturador'
    ];

    // Relación inversa: Una ciudad pertenece a un departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }
}
