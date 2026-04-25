<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Agrupamos todas las rutas que requieren identificación de tienda/base de datos
Route::middleware(['tenant'])->group(function () {

    // Rutas de Cliente
    Route::post('cliente/login', 'App\Http\Controllers\ClientesController@loginCliente');
    Route::post('cliente/logout', 'App\Http\Controllers\ClientesController@logout');
    Route::get('cliente/perfilcliente', 'App\Http\Controllers\ClientesController@perfil');
    Route::post('cliente/actualizarperfil', 'App\Http\Controllers\ClientesController@actualizarDatos');
    Route::post('cliente/crearactualizar', 'App\Http\Controllers\ClientesController@crearUsuario');

    // Rutas de Productos y Categorías
    Route::get('productos/obtener', 'App\Http\Controllers\ProductosController@obtenerInfoProductos');
    Route::get('producto/obtenerdetalle', 'App\Http\Controllers\ProductosController@detalleProducto');
    Route::get('categorias/obtener', 'App\Http\Controllers\CategoriasController@index');

    // Rutas de Pedidos / Carrito
    Route::get('pedido/cantidaditems', 'App\Http\Controllers\PrefacturasController@obtenerCantidadItems');
    Route::post('pedidos/guardar', 'App\Http\Controllers\PrefacturasController@guardarPrefactura');
    Route::get('pedido/obtenercarritoactivo', 'App\Http\Controllers\PrefacturasController@obtenerCarrito');

    // Rutas para detalle de pedidos
    Route::get('prefactura/obtenerpedidocliente', 'App\Http\Controllers\PrefacturasController@obtenerPedidosCliente');
    Route::get('pedidodetalle/obtenerdetalle', 'App\Http\Controllers\PrefacturasController@obtenerDetallePedido');
    Route::get('estadopedidos/obtener', 'App\Http\Controllers\PrefacturasController@obtenerEstados');

    // LA RUTA MAESTRA: Finalizar Compra (Wompi o Contraentrega)
    Route::post('pedidos/finalizar-compra', 'App\Http\Controllers\PrefacturasController@finalizarCompra');

    Route::get('paises/obtener', 'App\Http\Controllers\PaisController@obtenerPaises');
    Route::get('departamentos/obtener', 'App\Http\Controllers\DepartamentoController@obtenerDepartamentos');
    Route::get('ciudades/obtener', 'App\Http\Controllers\CiudadController@obtenerCiudades');

});

// Esta ruta es la de por defecto de Laravel, puedes dejarla fuera o dentro según necesites
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
