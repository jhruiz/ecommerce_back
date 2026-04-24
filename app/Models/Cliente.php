<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $connection = 'tenant';
    protected $table = 'clientes';
    public $timestamps = false;

    // "La lista blanca": campos que permitimos guardar masivamente
    protected $fillable = [
        'nit',
        'nombre',
        'direccion',
        'telefono',
        'ciudade_id',
        'email',
        'celular',
        'usuario_id',
        'estado_id',
        'created',
        'empresa_id',
        'tipoidentificacione_id',
        'password',
        'ciudadesmiggo_id'
    ];

    // Ocultar la contraseña cuando el modelo se convierta a Array o JSON
    protected $hidden = [
        'password',
    ];

    // Relación con la ciudad
    public function ciudad() {
        return $this->belongsTo(Ciudad::class, 'ciudadesmiggo_id');
    }

    /**
     * Busca un cliente activo por su email y empresa
     */
    public static function obtenerClientePorEmail($email, $empresaId) {
        return self::where('email', $email)
                ->where('empresa_id', $empresaId)
                ->with('ciudad')
                ->first();
    }
}
