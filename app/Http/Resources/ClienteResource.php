<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'email'     => $this->email,
            'identificacion' => $this->nit,
            'celular'   => $this->celular,
            'direccion' => $this->direccion,
            'ciudad'    => $this->ciudad->nombre ?? 'No definida',
            'estado'    => $this->estado_id,
            'empresa_id'=> $this->empresa_id
        ];
    }
}
