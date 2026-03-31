<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prefacturasdetalle extends Model {
    protected $connection = 'tenant';

    protected $table = 'prefacturasdetalles';

    public $timestamps = false;

    protected $fillable = [
        'cantidad', 'costoventa', 'cargueinventario_id',
        'prefactura_id', 'descuento', 'porcentaje',
        'impuesto', 'impoconsumo', 'incbolsa'
    ];

    public function cargueinventario()
    {
        return $this->belongsTo(Cargueinventario::class, 'cargueinventario_id');
    }
}
