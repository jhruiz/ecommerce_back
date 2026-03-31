<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrefacturaItemResource extends JsonResource
{
    public function toArray($request)
    {
        // Accedemos al cargue y luego al producto
        $cargue = $this->cargueinventario;
        $producto = $cargue?->producto;
        $totalFila = $this->cantidad * $this->costoventa;

        return [
            'id'         => $this->id,
            'producto_id' => $producto?->id,
            'nombre'     => $producto?->descripcion ?? 'Producto no encontrado',
            'cantidad'   => $this->cantidad,
            'precio_fmt' => '$ ' . number_format($this->costoventa, 0, ',', '.'),
            'total_fila_fmt' => '$ ' . number_format($totalFila, 0, ',', '.'),
            // Buscamos la primera imagen del producto si existe
            'imagen'     => $producto?->imagenes?->first()
                            ? $producto->empresa_id . '/' . $producto->imagenes->first()->url
                            : 'no-image-placeholder.jpg',
            // Agregamos esto para debuggear en el JSON si el cargue existe
            'debug_cargue_id' => $this->cargueinventario_id,
        ];
    }
}
