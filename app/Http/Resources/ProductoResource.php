<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        // $this representa al objeto Producto individual
        return [
            'id'               => $this->id,
            'codigo'           => $this->codigo,
            'referencia'       => $this->referencia,
            'nombre'           => $this->descripcion,
            'desc_extensa'     => $this->desc_extensa,
            'marca'            => $this->marca,
            'categoria'        => $this->categoria->descripcion ?? 'General',
            'existenciaactual' => $this->cargueinventario->existenciaactual ?? 0,
            // Datos de inventario y precio
            'precio_venta'      => $this->cargueinventario->precioventa ?? 0,
            'precio_maximo'      => $this->cargueinventario->preciomaximo ?? 0,

            // Mapeamos las imágenes para que el Front reciba la URL real de Miggo
            'imagenes'    => $this->imagenes->map(function($img) {
                return [
                    'url' => "{$this->empresa_id}/{$img->url}"
                ];
            }),

            'mostrarencatalogo' => (int)$this->mostrarencatalogo,
        ];
    }
}
