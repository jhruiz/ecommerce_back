<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imagenesitem extends Model
{
    protected $connection = 'tenant';

    protected $table = 'imagenesitems';

    // Si la tabla no tiene los campos created_at y updated_at
    public $timestamps = false;
}
