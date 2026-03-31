<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Palabrasclave extends Model {
    protected $connection = 'tenant';

    protected $table = 'palabrasclaves';
}
