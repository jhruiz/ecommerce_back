<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prefactura extends Model
{
    protected $connection = 'tenant';

    protected $table = 'prefacturas';

    // Si la tabla no tiene los campos created_at y updated_at
    public $timestamps = false;

    protected $fillable = [
        'usuario_id', 'cliente_id', 'created', 'observacion',
        'estadoprefactura_id', 'estadopedido_id', 'numeropedido', 'esfactura'
    ];

    // Relación para llegar a la tabla estadopedidos
    public function estadoPedidoRelacion() {
        return $this->belongsTo(Estadopedido::class, 'estadopedido_id');
    }

    public function detalles()
    {
        return $this->hasMany(Prefacturasdetalle::class, 'prefactura_id');
    }

    /**
     * Obtiene un pedido específico de un cliente que se encuentre activo
     * En uso
     */
    public static function obtenerPedidoActivoCliente($clienteId) {
      $data = Prefactura::select()
                    ->join('clientes', 'clientes.id', '=', 'prefacturas.cliente_id')
                    ->join('prefacturasdetalles', 'prefacturasdetalles.prefactura_id', '=', 'prefacturas.id')
                    ->where('prefacturas.cliente_id', '=', $clienteId)
                    ->where('prefacturas.estadopedido_id', '=', 6)
                    ->get();

      return $data;
    }

    public static function obtenerPedidosPorUsuario($userId) {
        return Prefactura::select(
                'prefacturas.id',
                'prefacturas.numeropedido',
                'prefacturas.fechaorden as fecha',
                'estadopedidos.descripcion as estado_nombre',
                'prefacturas.estadopedido_id'
            )
            ->join('estadopedidos', 'estadopedidos.id', '=', 'prefacturas.estadopedido_id')
            ->where('prefacturas.cliente_id', '=', $userId)
            ->where('prefacturas.eliminar', '=', 0)
            ->orderBy('prefacturas.id', 'desc')
            ->get();
    }

}
