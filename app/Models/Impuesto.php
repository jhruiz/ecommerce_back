<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $connection = 'tenant';

    protected $table = 'impuestos'; // Tu tabla de impuestos

    // Si la tabla no tiene los campos created_at y updated_at
    public $timestamps = false;

}
