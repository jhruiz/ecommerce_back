<?php

namespace App\Http\Controllers;

use App\Models\Prefactura;
use App\Models\Prefacturasdetalle;
use App\Models\Estadopedido;
use Illuminate\Support\Facades\Config;
use App\Http\Resources\PrefacturaItemResource;
use App\Mail\Pedidos;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class PrefacturasController extends Controller
{

    /**
     * Tu lógica de número de pedido adaptada a objeto
     */
    private function generarNumeroPedido($prefactura) {
        $fecha = date('His'); // Hora, Minutos, Segundos de ahora
        return $prefactura->id . $prefactura->usuario_id . $fecha;
    }


    /**
     * Se guarda la prefactura
     */
    public function guardarPrefactura(Request $request) {

        // 1. Obtener datos de la sesión
        $clienteSession = $request->session()->get('user');

        if (!$clienteSession) {
            return response()->json(['estado' => false, 'mensaje' => 'Debe iniciar sesión para comprar.'], 401);
        }

        $producto_id = $request->input('item_id');
        $cantidad    = $request->input('cantidad', 1);

        // Iniciamos transacción para asegurar que todo se guarde o nada se guarde
        \DB::beginTransaction();

        try {
            // --- PARTE 1: CABECERA DE PREFACTURA ---

            // Buscamos si ya existe una prefactura activa (eliminar=0 y orden del estado=0)
            $prefactura = Prefactura::where('cliente_id', $clienteSession->id)
                ->where('eliminar', 0)
                ->whereHas('estadoPedidoRelacion', function($query) {
                    $query->where('orden', 0);
                })
                ->first();

            if (!$prefactura) {
                $estadoInicial = Estadopedido::where('orden', 0)->first();

                if (!$estadoInicial) {
                    throw new \Exception('Error de configuración: No existe estado con orden 0');
                }

                $empresa = \DB::table('empresas')->where('id', $clienteSession->empresa_id)->first();
                $esFactura = ($empresa && $empresa->syncdian == 1) ? 1 : 0;

                $nuevaPrefactura = new Prefactura();
                $nuevaPrefactura->usuario_id          = $clienteSession->usuario_id;
                $nuevaPrefactura->cliente_id          = $clienteSession->id;
                $nuevaPrefactura->created             = date('Y-m-d H:i:s');
                $nuevaPrefactura->observacion         = 'Compra por Marketplace';
                $nuevaPrefactura->estadoprefactura_id = 2; // INICIADA
                $nuevaPrefactura->estadopedido_id     = $estadoInicial->id;
                $nuevaPrefactura->esfactura           = $esFactura;
                $nuevaPrefactura->eliminar            = 0;
                $nuevaPrefactura->save();

                // Generar y guardar número de pedido
                $nuevaPrefactura->numeropedido = $this->generarNumeroPedido($nuevaPrefactura);
                $nuevaPrefactura->save();

                $prefactura = $nuevaPrefactura;
            }

            // --- PARTE 2: LÓGICA DE INVENTARIO Y DETALLE ---

            // Validar si el producto existe y si maneja inventario
            $producto = \DB::table('productos')->where('id', $producto_id)->first();
            if (!$producto) {
                throw new \Exception('El producto no existe.');
            }

            $cargue = \DB::table('cargueinventarios')
                ->where('producto_id', $producto_id)
                ->where('empresa_id', $clienteSession->empresa_id)
                ->first();

            if (!$cargue) {
                throw new \Exception('El producto no tiene inventario cargado.');
            }

            // DESCUENTO DE STOCK (Solo si producto.inventario == 1)
            if ($producto->inventario == 1) {
                if ($cargue->existenciaactual < $cantidad) {
                    throw new \Exception('Stock insuficiente. Unidades disponible: ' . $cargue->existenciaactual);
                }

                // Descontamos del inventario físicamente
                \DB::table('cargueinventarios')
                    ->where('id', $cargue->id)
                    ->decrement('existenciaactual', $cantidad);
            }

            // Obtener Impuestos (IVA '01' e Impoconsumo '04')
            $impuestos = \DB::table('cargueinventarios_impuestos')
                ->join('impuestos', 'cargueinventarios_impuestos.impuesto_id', '=', 'impuestos.id')
                ->join('taxes', 'impuestos.tax_id', '=', 'taxes.id')
                ->where('cargueinventario_id', $cargue->id)
                ->select('taxes.code', 'impuestos.valor')
                ->get();

            $iva = 0;
            $impoconsumo = 0;

            foreach ($impuestos as $imp) {
                if ($imp->code == '01') {
                    $iva = $imp->valor;
                } elseif ($imp->code == '04') {
                    $impoconsumo = $imp->valor;
                }
            }

            // Guardar o Actualizar el Detalle
            $detalleExistente = Prefacturasdetalle::where('prefactura_id', $prefactura->id)
                ->where('cargueinventario_id', $cargue->id)
                ->first();

            $mensaje = '';

            if ($detalleExistente) {
                $detalleExistente->cantidad += $cantidad;

                if($detalleExistente->cantidad <= 0) {
                    $detalleExistente->delete();
                    $mensaje = 'Producto eliminado con éxito';
                } else {
                    $detalleExistente->save();
                    $mensaje = 'Producto añadido con éxito';
                }

            } else {
                Prefacturasdetalle::create([
                    'cantidad'            => $cantidad,
                    'costoventa'          => $cargue->precioventa,
                    'cargueinventario_id' => $cargue->id,
                    'prefactura_id'       => $prefactura->id,
                    'descuento'           => 0,
                    'porcentaje'          => 0,
                    'impuesto'            => $iva,
                    'impoconsumo'         => $impoconsumo,
                    'incbolsa'            => 0
                ]);
            }

            // Si llegamos aquí, todo salió bien
            \DB::commit();

            return response()->json([
                'estado' => true,
                'prefactura_id' => $prefactura->id,
                'numeropedido' => $prefactura->numeropedido,
                'mensaje' => $mensaje,
                'totalItems' => Prefacturasdetalle::where('prefactura_id', $prefactura->id)->count()
            ]);

        } catch (\Throwable $e) {
            // Si hay error, revertimos cualquier cambio (incluyendo el descuento de stock)
            \DB::rollBack();
            return response()->json(['estado' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }


    /**
     * Obtiene los productos del carrito activo para un usuario específico
     */
    public function obtenerCarrito(Request $request) {
        try {
            $clienteSession = $request->session()->get('user');
            $clienteId = $clienteSession->id;

            $prefactura = Prefactura::where('cliente_id', $clienteId)
                ->where('eliminar', 0)
                ->whereHas('estadoPedidoRelacion', function($query) {
                    $query->where('orden', 0);
                })
                // IMPORTANTE: Asegúrate de que estas relaciones existan en los modelos
                ->with(['detalles.cargueinventario.producto.imagenes'])
                ->first();

            if (!$prefactura) {
                return response()->json(['estado' => true, 'items' => [], 'total' => '$ 0']);
            }

            // Cálculos de totales (Usando tu lógica de impuestos)
            $subtotal = 0;
            $iva = 0;
            foreach ($prefactura->detalles as $det) {
                // Aquí llamarías a tu función de cálculo que definimos antes
                $calc = $this->calcularValoresItem([
                    'precioVenta' => $det->costoventa,
                    'unidadesProd' => $det->cantidad,
                    'prcIVA' => $det->porcentaje / 100,
                    'prcINC' => $det->impoconsumo / 100,
                    'porcentajeDesc' => $det->descuento,
                    'valBolsa' => $det->incbolsa
                ]);
                $subtotal += $calc['valorBaseUnitario'];
                $iva += $calc['valorIVA'];
            }

            return response()->json([
                'estado'      => true,
                'items'       => PrefacturaItemResource::collection($prefactura->detalles),
                'subtotal'    => '$ ' . number_format($subtotal, 0, ',', '.'),
                'iva'         => '$ ' . number_format($iva, 0, ',', '.'),
                'total'       => '$ ' . number_format($subtotal + $iva, 0, ',', '.'),
                'totalLineas' => $prefactura->detalles->count()
            ]);

        } catch (\Exception $e) {
            return response()->json(['estado' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Tu lógica de CakePHP adaptada a Laravel
     */
    private function calcularValoresItem($valores) {
        $precioVentaTotal = (float)($valores['precioVenta'] * $valores['unidadesProd']);
        $factorRetiro     = 1 + $valores['prcIVA'] + $valores['prcINC'];

        // Valor Base (sin impuestos)
        $valorBaseInicial = $precioVentaTotal / $factorRetiro;

        // Descuentos (si aplican)
        $valorBaseNueva = $valorBaseInicial;
        if ($valores['porcentajeDesc'] > 0) {
            $factorDescuento = 1 - ($valores['porcentajeDesc'] / 100);
            $valorBaseNueva = $valorBaseInicial * $factorDescuento;
        }

        $iva = $valorBaseNueva * $valores['prcIVA'];
        $inc = $valorBaseNueva * $valores['prcINC'];

        return [
            "valorBaseUnitario"   => $valorBaseNueva,
            "valorIVA"            => $iva,
            "valorINC"            => $inc,
            "varorINCBolsa"       => (float)($valores['valBolsa'] * $valores['unidadesProd']),
            "precioUnitarioFinal" => $valorBaseNueva + $iva + $inc
        ];
    }

    /**
     * Obtiene la compra activa de un cliente
     * En uso
     */
    public function obtenerCantidadItems( Request $request ) {
        $resp = array( 'estado' => false, 'data' => null, 'mensaje' => '' );
        $clienteId = $request['userId'];

        try {

            //obtiene el pedido activo para un cliente y su detalle
            $infoPed = Prefactura::obtenerPedidoActivoCliente( $clienteId );

            if( !empty( $infoPed['0']->id ) ) {
                $resp['estado'] = true;
                $resp['data'] = $infoPed;
            } else {
                $resp['mensaje'] = 'No fue posible obtener los registros';
            }

        }catch(Throwable $e) {
            $resp = array( 'estado' => false, 'data' => null, 'mensaje' => 'Se presentó un error' );
        }

        return $resp;
    }


    public function finalizarCompra(Request $request)
    {
        $clienteSession = $request->session()->get('user');

        // 1. Buscamos la prefactura (la que ya tiene los productos separados)
        $prefactura = Prefactura::where('cliente_id', $clienteSession->id)
            ->where('eliminar', 0)
            ->whereHas('estadoPedidoRelacion', function($query) {
                $query->where('orden', 0);
            })
            ->first();

        if (!$prefactura) {
            return response()->json(['estado' => false, 'mensaje' => 'No hay orden activa'], 404);
        }

        // 2. Buscamos el ID del estado "Validación de pago" (Orden 1)
        $estadoValidacion = Estadopedido::where('orden', 1)->first();

        // 3. Actualizamos la prefactura: ponemos el estado 1 y guardamos el método
        $prefactura->update([
            'estadopedido_id' => $estadoValidacion->id,
            'metodopago' => $request->metodo_pago
        ]);

        // 4. Si es Wompi, preparamos la firma ahí mismo
        $data_pago = null;
        if ($request->metodo_pago === 'wompi') {

            $montoCentavos = $request->total * 100;
            $referencia = $prefactura->numeropedido; // Tu referencia real
            $integrityKey = Config::get('wompi.integrity_key');

            $firma = hash('sha256', $referencia . $montoCentavos . "COP" . $integrityKey);

            $data_pago = [
                'public_key' => Config::get('wompi.pub_key'),
                'referencia' => $referencia,
                'monto'      => $montoCentavos,
                'firma'      => $firma
            ];
        }

        // 5. Respondemos TODO en un solo paquete
        return response()->json([
            'estado'    => true,
            'metodo'    => $request->metodo_pago,
            'order_id'  => $prefactura->numeropedido,
            'data_pago' => $data_pago, // Solo traerá datos si es Wompi
            'cliente'   => [
                'email'   => $clienteSession->email,
                'nombre'  => $clienteSession->nombre,
                'celular' => $clienteSession->celular
            ]
        ]);
    }

    // 1. Obtener listado de prefacturas (pedidos) del cliente
    public function obtenerPedidosCliente(Request $request) {
        $userId = $request->input('userId');

        $pedidos = Prefactura::obtenerPedidosPorUsuario($userId);

        return response()->json(['estado' => true, 'data' => $pedidos]);
    }


    public function obtenerDetallePedido(Request $request) {
        $prefacturaId = $request->input('pedidoId');

        $cabecera = Prefactura::find($prefacturaId);
        $detalles = Prefacturasdetalle::obtenerDetalleCompleto($prefacturaId);

        $subtotal = 0;
        $ivaTotal = 0;

        foreach ($detalles as $item) {
            $base = $item->cantidad * (float)$item->vlr_item;
            $subtotal += $base;
            $ivaTotal += ($base * ($item->vlr_impuesto / 100));
        }

        return response()->json([
            'estado' => true,
            'data' => $detalles,
            'cabecera' => $cabecera,
            'ttles' => [
                '2' => $subtotal,
                '3' => $ivaTotal,
                '4' => $subtotal + $ivaTotal
            ]
        ]);
    }

    /**
     * Obtiene el listado de estados para construir el Timeline
     */
    public function obtenerEstados() {
        try {
            // Usamos el modelo directamente con tu estilo de consulta
            $estados = EstadoPedido::select('id', 'descripcion', 'orden', 'fontawesome')
                ->orderBy('orden', 'asc')
                ->get();

            return response()->json([
                'estado' => true,
                'data' => $estados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => false,
                'mensaje' => 'Error al obtener estados: ' . $e->getMessage()
            ], 500);
        }
    }

    public function webhookWompi(Request $request) {
        Log::info($request->all());
        // IMPORTANTE: Wompi envía una estructura donde 'event' dice qué pasó
        $evento = $request->input('event');

        // Solo procesamos si el evento es una transacción terminada
        if ($evento == 'transaction.updated') {
            $data = $request->input('data.transaction');

            $referencia = $data['reference'];
            $idWompi = $data['id'];
            $estadoWompi = $data['status'];

            // Buscamos el pedido
            $pedido = Prefactura::where('numeropedido', $referencia)->first();

            if ($pedido) {
                // Verificamos el estado que viene de Wompi
                if ($estadoWompi == 'APPROVED') {
                    // AQUÍ: Usa el ID que corresponda a "PAGADO" en tu tabla estadopedidos
                    $pedido->estadopedido_id = 5;
                    $pedido->observacion = "Pago Aprobado Wompi ID: " . $idWompi;
                } else if (in_array($estadoWompi, ['DECLINED', 'VOIDED', 'ERROR'])) {
                    // AQUÍ: Usa el ID que corresponda a "RECHAZADO"
                    $pedido->estadopedido_id = 7;
                    $pedido->observacion = "Pago fallido/rechazado Wompi ID: " . $idWompi . " Estado: " . $estadoWompi;
                }

                $pedido->save();
            }
        }

        // Siempre responder 200 a Wompi para que no siga reintentando el envío
        return response()->json(['status' => 'ok'], 200);
    }





    /*
    * Envia correo de creación de cliente
    */
    public function enviarCorreoPedido($userId, $pedidoId) {

        $items = Pedidosdetalle::obtenerDetallesPedido( $pedidoId );

        // se genera el número del pdido
        $numeroPedido = $this->obtenerNumeroPedido($items);

        $subTtal = 0;
        $dcto = 0;
        $subTtalNeto = 0;
        $iva = 0;
        $ttalPagar = 0;

        // se procesa la información de cada item
        foreach( $items as $key => $val ) {

            //verifica si tiene el iva incluido para calcularlo
            if ( $val->impto_incluido == 1 ) {
                $items[$key]->baseTtal = $val->vlr_item * $val->cantidad;
                $items[$key]->tasaImp = ( $val->vlr_impuesto/100 ) + 1;
                $items[$key]->vlrBase = number_format($val->vlr_item / $items[$key]->tasaImp, 2, '.', '');
                $items[$key]->vlrBaseTtal = number_format(($val->vlr_item / $items[$key]->tasaImp) * $val->cantidad, 2, '.', '');
                $items[$key]->vlrIva = number_format($val->vlr_item - $items[$key]->vlrBase, 2, '.', '');
            } else {
                $items[$key]->baseTtal = $val->vlr_item * $val->cantidad;
                $items[$key]->tasaImp = ( $val->vlr_impuesto/100 ) + 1;
                $items[$key]->vlrBase = number_format($val->vlr_item, 2, '.', '');
                $items[$key]->vlrBaseTtal = number_format(($val->vlr_item / $items[$key]->tasaImp) * $val->cantidad, 2, '.', '');
                $items[$key]->vlrIv = number_format($val->vlr_item * $val->vlr_item, 2, '.', '');
            }

            $subTtal += $items[$key]->vlrBase * $val->cantidad;
            $iva += $items[$key]->vlrIva * $val->cantidad;
        }

        $items->iva = $iva;
        $items->subTtalNeto = $subTtal - $dcto;
        $items->ttalPagar = number_format($items->subTtalNeto + $iva, 2, '.', '');
        $items->numeroPedido = $numeroPedido;

        //obtiene la información de los usuarios a los que debe enviarle el correo de creacion de tercero
        $nombre = 'infpedido';
        $correos = Configuraciondato::obtenerConfiguracion($nombre)['0']->valor;

        // //se obtienen los email configurados para enviar el correo (destinatarios)
        $arrMails = explode(",", $correos);

        // Mail::to($pedido['0']->email)->send(new Pedidos((object) $pedido));
        Mail::to('jaiber.ruiz@hotmail.com')->send(new Pedidos((object) $items));
        //se envian los correos configurados
        foreach($arrMails as $m) {
            sleep(2);
            Mail::to($m)->send(new Pedidos((object) $items));
        }
    }

    public function whnotifications( $id, Request $request ) {
        Pedido::actualizarRequest($request->all());
    }

}
