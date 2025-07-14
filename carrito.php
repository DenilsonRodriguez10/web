<?php
// Tienda_Virtual/carrito.php

session_start();
require_once 'config/database.php';
include_once 'includes/header.php';

$carrito = $_SESSION['carrito'] ?? [];
$total_carrito = 0;

// ... (El resto de la lógica de actualizar/eliminar es la misma) ...

// Lógica para eliminar un ítem del carrito
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['item_key'])) {
    $item_key_to_remove = $_GET['item_key'];
    if (isset($carrito[$item_key_to_remove])) {
        // Opcional: Si se guarda una imagen personalizada, podrías borrarla del servidor aquí
        // if (!empty($carrito[$item_key_to_remove]['url_imagen_personalizada'])) {
        //     $file_path = $carrito[$item_key_to_remove]['url_imagen_personalizada'];
        //     if (file_exists($file_path)) {
        //         unlink($file_path);
        //     }
        // }
        unset($carrito[$item_key_to_remove]);
        $_SESSION['carrito'] = $carrito;
        header('Location: carrito.php');
        exit();
    }
}

// Lógica para actualizar la cantidad de un ítem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_key_to_update = $_POST['item_key'];
    $new_quantity = (int)$_POST['new_quantity'];

    if (isset($carrito[$item_key_to_update]) && $new_quantity >= 1) {
        $carrito[$item_key_to_update]['cantidad'] = $new_quantity;
        $_SESSION['carrito'] = $carrito;
        header('Location: carrito.php');
        exit();
    } elseif (isset($carrito[$item_key_to_update]) && $new_quantity < 1) {
        // Si la cantidad es 0 o menos, eliminar el ítem
        // if (!empty($carrito[$item_key_to_update]['url_imagen_personalizada'])) {
        //     $file_path = $carrito[$item_key_to_update]['url_imagen_personalizada'];
        //     if (file_exists($file_path)) {
        //         unlink($file_path);
        //     }
        // }
        unset($carrito[$item_key_to_update]);
        $_SESSION['carrito'] = $carrito;
        header('Location: carrito.php');
        exit();
    }
}

?>

<div class="carrito-container">
    <h2>Tu Carrito de Compras</h2>

    <?php if (empty($carrito)): ?>
        <p class="carrito-vacio">Tu carrito está vacío. ¡Explora nuestros productos y añade algunos!</p>
        <p><a href="index.php" class="btn-volver">Volver a la tienda</a></p>
    <?php else: ?>
        <div class="carrito-items">
            <?php foreach ($carrito as $item_key => $item):
                $subtotal_item = $item['precio_unitario'] * $item['cantidad'];
                $total_carrito += $subtotal_item;
            ?>

            ?>

<div class="carrito-acciones">
    <a href="index.php" class="btn">Continuar Comprando</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="checkout.php" class="btn btn-primary">Proceder al Pago</a>
    <?php else: ?>
        <div class="mensaje-atencion">
            Debes <a href="login.php">iniciar sesión</a> o <a href="registro.php">registrarte</a> para proceder al pago.
        </div>
        <?php endif; ?>
</div>
                <div class="carrito-item">
                    <div class="item-imagen">
                        <?php if (!empty($item['url_imagen_personalizada'])): ?>
                            <img src="<?php echo htmlspecialchars($item['url_imagen_personalizada']); ?>" alt="Imagen personalizada" class="img-personalizada">
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                        <?php if ($item['es_personalizable']): ?>
                            <p class="personalizacion-info">
                                <?php
                                $personalizaciones_display = [];
                                if ($item['texto_personalizado']) {
                                    $personalizaciones_display[] = 'Texto: "' . htmlspecialchars($item['texto_personalizado']) . '"';
                                }
                                if ($item['color_personalizado']) {
                                    $personalizaciones_display[] = 'Color: ' . htmlspecialchars($item['color_personalizado']);
                                }
                                if ($item['tamanio_elegido']) {
                                    $personalizaciones_display[] = 'Tamaño: ' . htmlspecialchars($item['tamanio_elegido']);
                                }
                                if (!empty($personalizaciones_display)) {
                                    echo implode('<br>', $personalizaciones_display);
                                } else {
                                    echo 'Sin personalización específica.';
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                        <p>Precio Unitario: $<span class="precio-unitario"><?php echo number_format($item['precio_unitario'], 2); ?></span></p>
                        <form action="carrito.php" method="POST" class="cantidad-form">
                            <input type="hidden" name="item_key" value="<?php echo htmlspecialchars($item_key); ?>">
                            <label for="cantidad_<?php echo htmlspecialchars($item_key); ?>">Cantidad:</label>
                            <input type="number" id="cantidad_<?php echo htmlspecialchars($item_key); ?>" name="new_quantity" value="<?php echo htmlspecialchars($item['cantidad']); ?>" min="1" class="cantidad-input">
                            <button type="submit" name="update_quantity" class="btn-update">Actualizar</button>
                        </form>
                        <p class="item-subtotal">Subtotal: $<?php echo number_format($subtotal_item, 2); ?></p>
                        <a href="carrito.php?action=remove&item_key=<?php echo htmlspecialchars($item_key); ?>" class="btn-remove">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="carrito-resumen">
            <p class="total-carrito">Total del Carrito: <span>$<?php echo number_format($total_carrito, 2); ?></span></p>
            <a href="checkout.php" class="btn-checkout">Proceder al Pago</a>
            <a href="index.php" class="btn-continuar-comprando">Continuar Comprando</a>
        </div>
    <?php endif; ?>
</div>
<style>
    /* Estilos para el carrito (ya existentes, solo añadir el estilo para imagen personalizada) */
    .img-personalizada {
        border: 2px dashed #007bff; /* Un borde para distinguirla */
        padding: 5px;
    }
</style>

<?php include_once 'includes/footer.php'; ?>

<style>
    /* Estilos para el carrito */
    .carrito-container {
        max-width: 900px;
        margin: 50px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .carrito-container h2 {
        color: #007bff;
        text-align: center;
        margin-bottom: 30px;
        font-size: 2.5em;
    }

    .carrito-vacio {
        text-align: center;
        font-size: 1.2em;
        color: #6c757d;
        margin-bottom: 20px;
    }

    .btn-volver {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    .btn-volver:hover {
        background-color: #0056b3;
    }

    .carrito-item {
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #fdfdfd;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .item-imagen {
        flex-shrink: 0;
        margin-right: 20px;
    }

    .item-imagen img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #eee;
    }

    .item-info {
        flex-grow: 1;
    }

    .item-info h3 {
        margin-top: 0;
        margin-bottom: 5px;
        color: #343a40;
        font-size: 1.5em;
    }

    .personalizacion-info {
        font-size: 0.9em;
        color: #555;
        margin-bottom: 10px;
    }

    .item-info p {
        margin-bottom: 5px;
        color: #6c757d;
    }

    .precio-unitario {
        font-weight: bold;
        color: #28a745;
    }

    .cantidad-form {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .cantidad-form label {
        margin-right: 10px;
        font-weight: 600;
        color: #495057;
    }

    .cantidad-input {
        width: 60px;
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        text-align: center;
        font-size: 1em;
    }

    .btn-update {
        background-color: #17a2b8; /* Info blue */
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
        transition: background-color 0.3s ease;
    }

    .btn-update:hover {
        background-color: #138496;
    }

    .btn-remove {
        background-color: #dc3545; /* Red */
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
        display: inline-block;
        margin-top: 10px;
    }

    .btn-remove:hover {
        background-color: #c82333;
    }

    .item-subtotal {
        font-size: 1.1em;
        font-weight: bold;
        color: #343a40;
        margin-top: 10px;
    }

    .carrito-resumen {
        border-top: 2px solid #eee;
        padding-top: 20px;
        margin-top: 30px;
        text-align: right;
    }

    .total-carrito {
        font-size: 1.8em;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 20px;
    }

    .total-carrito span {
        font-size: 1.2em;
        color: #28a745;
    }

    .btn-checkout, .btn-continuar-comprando {
        display: inline-block;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1em;
        margin-left: 15px;
        transition: background-color 0.3s ease;
    }

    .btn-checkout {
        background-color: #28a745; /* Green */
        color: white;
    }

    .btn-checkout:hover {
        background-color: #218838;
    }

    .btn-continuar-comprando {
        background-color: #6c757d; /* Gray */
        color: white;
    }

    .btn-continuar-comprando:hover {
        background-color: #5a6268;
    }

    @media (max-width: 768px) {
        .carrito-item {
            flex-direction: column;
            align-items: flex-start;
        }
        .item-imagen {
            margin-bottom: 15px;
            margin-right: 0;
        }
        .cantidad-form {
            flex-direction: column;
            align-items: flex-start;
        }
        .cantidad-form label {
            margin-bottom: 5px;
        }
        .btn-update {
            margin-left: 0;
            margin-top: 10px;
        }
        .carrito-resumen {
            text-align: center;
        }
        .btn-checkout, .btn-continuar-comprando {
            display: block;
            margin: 10px auto;
            width: 80%;
        }
    }
</style>

<?php include_once 'includes/footer.php'; ?>