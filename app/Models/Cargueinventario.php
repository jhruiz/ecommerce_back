<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargueinventario extends Model
{
    protected $connection = 'tenant';

    protected $table = 'cargueinventarios';

    // Si la tabla no tiene los campos created_at y updated_at
    public $timestamps = false;

    public function impuestos() {
        return $this->belongsToMany(
            Impuesto::class,
            'cargueinventarios_impuestos', // Tabla intermedia
            'cargueinventario_id',         // Llave foránea en la intermedia que apunta a inventario
            'impuesto_id'                  // Llave foránea en la intermedia que apunta a impuestos
        );
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

}
