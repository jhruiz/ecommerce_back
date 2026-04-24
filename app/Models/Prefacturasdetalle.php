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

    public static function obtenerDetalleCompleto($prefacturaId) {
        return Prefacturasdetalle::select(
                'productos.descripcion as desc_item',
                'prefacturasdetalles.cantidad',
                'prefacturasdetalles.costoventa as vlr_item',
                'prefacturasdetalles.impuesto as vlr_impuesto'
            )
            ->join('cargueinventarios', 'prefacturasdetalles.cargueinventario_id', '=', 'cargueinventarios.id')
            ->join('productos', 'cargueinventarios.producto_id', '=', 'productos.id')
            ->where('prefacturasdetalles.prefactura_id', '=', $prefacturaId)
            ->get();
    }
}
