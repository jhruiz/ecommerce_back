<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido</title>
    <style>
        /* Estilo general */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px 30px;
        }

        h1, h2, h3 {
            text-align: center;
            color: #555;
        }

        .order-info {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .order-info span {
            font-weight: bold;
            color: #444;
        }

        /* Estilo de la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #0056b3;
            color: white;
            text-transform: uppercase;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
        }

        tfoot th, tfoot td {
            font-weight: bold;
            background-color: #eef1f7;
            color: #333;
        }

        /* Mensaje final */
        .footer-message {
            margin-top: 20px;
            text-align: center;
            font-size: 18px;
            color: #666;
        }

    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <h1>Pedido procesado exitosamente</h1>
        <h2>Detalles del pedido</h2>

        <!-- Información del pedido -->
        <div class="order-info">
            <p>Número del pedido: <span>{{ $data->numeroPedido }}</span></p>
            <p>Fecha del pedido: <span>{{ $data['0']->fechapedido }}</span></p>
        </div>

        <!-- Tabla de detalles -->
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Cantidad</th>
                    <th>Valor Unitario</th>
                    <th>Impuesto (%)</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $dat)
                <tr>
                    <td style="text-align: left;">{{ $dat->desc_item }}</td>
                    <td>{{ $dat->cantidad }}</td>
                    <td>${{ number_format($dat->vlr_item, 2) }}</td>
                    <td>{{ $dat->vlr_impuesto }}%</td>
                    <td>${{ number_format($dat->baseTtal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align: right;">Subtotal Neto</th>
                    <td>${{ number_format($data->subTtalNeto, 2) }}</td>
                </tr>
                <tr>
                    <th colspan="4" style="text-align: right;">IVA</th>
                    <td>${{ number_format($data->iva, 2) }}</td>
                </tr>
                <tr>
                    <th colspan="4" style="text-align: right;">Total a Pagar</th>
                    <td>${{ number_format($data->ttalPagar, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Mensaje final -->
        <div class="footer-message">
            <p>Fue un gusto atenderte!</p>
        </div>
    </div>
</body>
</html>
