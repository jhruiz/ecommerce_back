<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estadopedido extends Model {
    protected $connection = 'tenant';

    protected $table = 'estadopedidos';

    public $timestamps = false;
}
