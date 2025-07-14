<?php
// Tienda_Virtual/factura.php

session_start();
require_once 'config/database.php';
include_once 'includes/header.php'; // Puedes usar un header más simple para la factura si no quieres la navegación

$pedido = null;
$items_pedido = [];
$mensaje = '';

if (isset($_GET['pedido_id'])) {
    $pedido_id = $_GET['pedido_id'];

    try {
        // Obtener detalles del pedido
        $stmt_pedido = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt_pedido->execute([$pedido_id]);
        $pedido = $stmt_pedido->fetch();

        if ($pedido) {
            // Obtener ítems del pedido
            // NOTA: Asegúrate que tu tabla items_pedido tenga las columnas 'url_imagen_personalizada' y 'tamanio_elegido'
            $stmt_items = $pdo->prepare("SELECT ip.*, p.nombre as producto_nombre, p.imagen
                                        FROM items_pedido ip
                                        JOIN productos p ON ip.producto_id = p.id
                                        WHERE ip.pedido_id = ?");
            $stmt_items->execute([$pedido_id]);
            $items_pedido = $stmt_items->fetchAll();

            // Opcional: Obtener datos del usuario si está logueado
            $usuario_info = null;
            if ($pedido['usuario_id']) {
                $stmt_user = $pdo->prepare("SELECT nombre, apellido, email FROM usuarios WHERE id = ?");
                $stmt_user->execute([$pedido['usuario_id']]);
                $usuario_info = $stmt_user->fetch();
            }
        } else {
            $mensaje = 'Pedido no encontrado.';
        }
    } catch (PDOException $e) {
        $mensaje = 'Error de base de datos al cargar la factura: ' . $e->getMessage();
    }
} else {
    $mensaje = 'No se ha especificado un ID de pedido para la factura.';
}

?>

<div class="factura-container">
    <?php if ($mensaje): ?>
        <div class="mensaje-confirmacion error">
            <?php echo $mensaje; ?>
        </div>
    <?php elseif ($pedido): ?>
        <div class="factura-header">
            <h2>Factura de Compra</h2>
            <p><strong>Pedido #<?php echo htmlspecialchars($pedido['id']); ?></strong></p>
            <p>Fecha del Pedido: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
        </div>

        <div class="factura-seccion">
            <h3>Información del Cliente</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario_info['nombre'] ?? 'Invitado'); ?> <?php echo htmlspecialchars($usuario_info['apellido'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario_info['email'] ?? 'No disponible'); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['telefono_contacto']); ?></p>
        </div>

        <div class="factura-seccion">
            <h3>Dirección de Envío</h3>
            <p><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></p>
        </div>

        <div class="factura-seccion">
            <h3>Detalles del Pedido</h3>
            <table class="factura-items">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Personalización</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items_pedido as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                            <td>
                                <?php
                                $personalizacion_str = [];
                                if ($item['texto_personalizado']) {
                                    $personalizacion_str[] = 'Texto: "' . htmlspecialchars($item['texto_personalizado']) . '"';
                                }
                                if ($item['color_personalizado']) {
                                    $personalizacion_str[] = 'Color: ' . htmlspecialchars($item['color_personalizado']);
                                }
                                if ($item['tamanio_elegido']) {
                                    $personalizacion_str[] = 'Tamaño: ' . htmlspecialchars($item['tamanio_elegido']);
                                }
                                if ($item['url_imagen_personalizada']) {
                                    $personalizacion_str[] = 'Imagen: <a href="' . htmlspecialchars($item['url_imagen_personalizada']) . '" target="_blank">Ver</a>';
                                }
                                echo empty($personalizacion_str) ? 'N/A' : implode('<br>', $personalizacion_str);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                            <td>$<?php echo number_format($item['precio_unitario_en_pedido'], 2); ?></td>
                            <td>$<?php echo number_format($item['precio_unitario_en_pedido'] * $item['cantidad'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="total-label">Total del Pedido:</td>
                        <td class="total-amount">$<?php echo number_format($pedido['total_pedido'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="factura-footer">
            <p>Método de Pago: <?php echo htmlspecialchars(ucfirst($pedido['metodo_pago'])); ?></p>
            <p>Estado del Pedido: <strong><?php echo htmlspecialchars(ucfirst($pedido['estado'])); ?></strong></p>
            <p>¡Gracias por tu compra!</p>
        </div>
        <button onclick="window.print()" class="btn-print">Imprimir Factura</button>
        <p><a href="index.php" class="btn-volver">Volver a la tienda</a></p>

    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>

<style>
    /* Estilos para la factura */
    .factura-container {
        max-width: 800px;
        margin: 30px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        font-family: Arial, sans-serif;
        color: #333;
    }

    .factura-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 15px;
    }

    .factura-header h2 {
        color: #007bff;
        font-size: 2.5em;
        margin-bottom: 10px;
    }

    .factura-header p {
        font-size: 1.1em;
        margin: 5px 0;
    }

    .factura-seccion {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .factura-seccion h3 {
        color: #555;
        font-size: 1.4em;
        margin-bottom: 15px;
    }

    .factura-seccion p {
        margin: 5px 0;
        line-height: 1.6;
    }

    .factura-items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .factura-items th, .factura-items td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    .factura-items th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #333;
    }

    .factura-items tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .factura-items .total-label {
        text-align: right;
        font-size: 1.3em;
        font-weight: bold;
        padding-top: 20px;
        border: none;
    }

    .factura-items .total-amount {
        font-size: 1.4em;
        font-weight: bold;
        color: #28a745;
        padding-top: 20px;
        border: none;
    }

    .factura-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #007bff;
    }

    .factura-footer p {
        font-size: 1.1em;
        margin: 5px 0;
    }

    .btn-print {
        background-color: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    .btn-print:hover {
        background-color: #5a6268;
    }

    /* Ocultar botón de imprimir en la versión impresa */
    @media print {
        .btn-print, .btn-volver, header, footer {
            display: none;
        }
        .factura-container {
            box-shadow: none;
            border: none;
            margin: 0;
            padding: 0;
        }
    }
</style>

<?php include_once 'includes/footer.php'; ?>