<?php
// Tienda_Virtual/checkout.php

session_start(); // ¡Siempre inicia la sesión!

if (!isset($_SESSION['user_id'])) {
    $_SESSION['mensaje'] = "Debes iniciar sesión para finalizar tu compra.";
    $_SESSION['mensaje_tipo'] = "warning"; // Puedes usar 'danger' o 'info' también para el estilo
    header("Location: login.php"); // Redirige a la página de login
    exit();
}

require_once 'config/database.php';
include_once 'includes/header.php';

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo "<p>Tu carrito está vacío. Agrega productos para continuar.</p>";
    include_once 'includes/footer.php';
    exit();
}



// Redirigir si el carrito está vacío
if (empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit();
}

$carrito = $_SESSION['carrito'];
$total_carrito = 0;
foreach ($carrito as $item) {
    $total_carrito += $item['precio_unitario'] * $item['cantidad'];
}

// Opcional: Si el usuario está logueado, precargar sus datos de envío
$user_id = $_SESSION['user_id'] ?? null;
$nombre_usuario = $_SESSION['user_nombre'] ?? '';
$direccion_envio = '';
$telefono_contacto = '';
$email_contacto = $_SESSION['user_email'] ?? '';

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT nombre, apellido, email, direccion_envio, telefono FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        if ($user_data) {
            $nombre_usuario = htmlspecialchars($user_data['nombre'] . ' ' . ($user_data['apellido'] ?? ''));
            $email_contacto = htmlspecialchars($user_data['email']);
            $direccion_envio = htmlspecialchars($user_data['direccion_envio'] ?? '');
            $telefono_contacto = htmlspecialchars($user_data['telefono'] ?? '');
        }
    } catch (PDOException $e) {
        error_log("Error al cargar datos del usuario en checkout: " . $e->getMessage());
    }
}

$mensaje = '';
$mensaje_tipo = '';

// Lógica de procesamiento del formulario de checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceder_pago'])) {
    $direccion_envio_form = trim($_POST['direccion_envio']);
    $telefono_contacto_form = trim($_POST['telefono_contacto']);
    $metodo_pago = $_POST['metodo_pago'] ?? ''; // 'paypal' o 'tarjeta'

    // Si el método de pago es tarjeta, recuperamos los datos simulados
    $numero_tarjeta_sim = $_POST['numero_tarjeta'] ?? '';
    $fecha_vencimiento_sim = $_POST['fecha_vencimiento'] ?? '';
    $cvc_sim = $_POST['cvc'] ?? '';
    $nombre_tarjeta_sim = $_POST['nombre_tarjeta'] ?? '';

    // Validaciones básicas para los datos de la tarjeta simulada
    if ($metodo_pago === 'tarjeta') {
        if (empty($numero_tarjeta_sim) || empty($fecha_vencimiento_sim) || empty($cvc_sim) || empty($nombre_tarjeta_sim)) {
            $mensaje = 'Por favor, completa todos los datos de la tarjeta para la simulación.';
            $mensaje_tipo = 'error';
        } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $numero_tarjeta_sim))) {
            $mensaje = 'Número de tarjeta inválido (debe tener 16 dígitos).';
            $mensaje_tipo = 'error';
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $fecha_vencimiento_sim)) {
            $mensaje = 'Formato de fecha de vencimiento inválido (MM/AA).';
            $mensaje_tipo = 'error';
        } elseif (!preg_match('/^\d{3,4}$/', $cvc_sim)) {
            $mensaje = 'CVC inválido (3 o 4 dígitos).';
            $mensaje_tipo = 'error';
        }
    }


    if (empty($direccion_envio_form) || empty($telefono_contacto_form) || empty($metodo_pago)) {
        $mensaje = 'Por favor, completa todos los campos de envío y selecciona un método de pago.';
        $mensaje_tipo = 'error';
    } else {
        // Guarda el pedido en la base de datos con estado "pendiente"
        try {
            $pdo->beginTransaction();

            $sql_pedido = "INSERT INTO pedidos (usuario_id, total_pedido, direccion_envio, telefono_contacto, metodo_pago, estado)
                           VALUES (?, ?, ?, ?, ?, 'pendiente')"; // Inicialmente 'pendiente'
            $stmt_pedido = $pdo->prepare($sql_pedido);
            $stmt_pedido->execute([
                $user_id,
                $total_carrito,
                $direccion_envio_form,
                $telefono_contacto_form,
                $metodo_pago
            ]);
            $pedido_id = $pdo->lastInsertId();

            // Guardar los ítems del carrito en items_pedido
            $sql_item_pedido = "INSERT INTO items_pedido (pedido_id, producto_id, cantidad, precio_unitario_en_pedido, texto_personalizado, color_personalizado)
                                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_item_pedido = $pdo->prepare($sql_item_pedido);

            foreach ($carrito as $item) {
                $stmt_item_pedido->execute([
                    $pedido_id,
                    $item['id'],
                    $item['cantidad'],
                    $item['precio_unitario'],
                    $item['texto_personalizado'],
                    $item['color_personalizado']
                ]);
            }

            // Si llegamos aquí, el pedido se guardó correctamente.
            // Ahora simulamos el pago.

            // Actualiza el estado del pedido a 'pagado' inmediatamente para la simulación
            $stmt_update_estado = $pdo->prepare("UPDATE pedidos SET estado = 'pagado' WHERE id = ?");
            $stmt_update_estado->execute([$pedido_id]);

            $pdo->commit();

            // Limpia el carrito de la sesión
            unset($_SESSION['carrito']);

            // Redirige a la página de confirmación de forma simulada
            header('Location: confirmacion.php?order_id=' . $pedido_id . '&status=simulated_success');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensaje = 'Error al procesar tu pedido en la base de datos: ' . $e->getMessage();
            $mensaje_tipo = 'error';
            error_log("Database Error during checkout: " . $e->getMessage());
        }
    }
}
?>

<div class="checkout-container">
    <h2>Finalizar Compra</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje-confirmacion <?php echo $mensaje_tipo; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="resumen-pedido">
        <h3>Resumen de tu Pedido</h3>
        <?php foreach ($carrito as $item_key => $item): ?>
            <div class="item-resumen">
                <p>
                    <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                    <?php if ($item['es_personalizable']): ?>
                        (<?php
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
                            // Mostrar URL de imagen si está presente
                            if ($item['url_imagen_personalizada']) {
                                $personalizaciones_display[] = 'Imagen Subida: <a href="' . htmlspecialchars($item['url_imagen_personalizada']) . '" target="_blank">Ver</a>';
                            }
                            echo empty($personalizaciones_display) ? 'Sin personalización específica.' : implode(', ', $personalizaciones_display);
                        ?>)
                    <?php endif; ?>
                    x <?php echo htmlspecialchars($item['cantidad']); ?>
                    - $<?php echo number_format($item['precio_unitario'], 2); ?> c/u
                </p>
                <span>$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></span>
            </div>
        <?php endforeach; ?>
        <p class="total-final">Total a Pagar: <span>$<?php echo number_format($total_carrito, 2); ?></span></p>
    </div>

    <form action="checkout.php" method="POST" class="checkout-form">
        <h3>Información de Envío</h3>
        <label for="direccion_envio">Dirección de Envío:</label>
        <textarea id="direccion_envio" name="direccion_envio" rows="3" required><?php echo htmlspecialchars($direccion_envio); ?></textarea>

        <label for="telefono_contacto">Teléfono de Contacto:</label>
        <input type="tel" id="telefono_contacto" name="telefono_contacto" value="<?php echo htmlspecialchars($telefono_contacto); ?>" required>

        <h3>Método de Pago</h3>
        <div class="payment-options" onchange="togglePaymentFields()">
            <label class="payment-option">
                <input type="radio" name="metodo_pago" value="tarjeta" id="radio_tarjeta" checked required>
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Pagar con Tarjeta" class="stripe-logo"> Tarjeta de Crédito/Débito
            </label>
             
            </label>
        </div>

        <div id="card_payment_fields" class="card-fields-container">
            <label for="nombre_tarjeta">Nombre en la Tarjeta:</label>
            <input type="text" id="nombre_tarjeta" name="nombre_tarjeta" placeholder="Nombre Apellido" value="<?php echo htmlspecialchars($nombre_tarjeta_sim ?? ''); ?>">

            <label for="numero_tarjeta">Número de Tarjeta:</label>
            <input type="text" id="numero_tarjeta" name="numero_tarjeta" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" value="<?php echo htmlspecialchars($numero_tarjeta_sim ?? ''); ?>">
            

            <div class="flex-group">
                <div>
                    <label for="fecha_vencimiento">Fecha de Vencimiento (MM/AA):</label>
                    <input type="text" id="fecha_vencimiento" name="fecha_vencimiento" placeholder="MM/AA" maxlength="5" value="<?php echo htmlspecialchars($fecha_vencimiento_sim ?? ''); ?>">
                </div>
                <div>
                    <label for="cvc">CVC:</label>
                    <input type="text" id="cvc" name="cvc" placeholder="XXX" maxlength="4" value="<?php echo htmlspecialchars($cvc_sim ?? ''); ?>">
                </div>
            </div>
        </div>

        <button type="submit" name="proceder_pago" class="btn-checkout-final">Proceder al Pago</button>
    </form>
</div>

<style>
    /* ... (Mantén los estilos anteriores de checkout.php) ... */

    /* Nuevos estilos para el formulario de tarjeta */
    .card-fields-container {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        background-color: #fcfcfc;
    }

    .card-fields-container label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }

    .card-fields-container input[type="text"] {
        width: calc(100% - 22px);
        padding: 12px;
        margin-bottom: 15px; /* Ajuste para separar los campos de tarjeta */
        border: 1px solid #ced4da;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 1em;
    }

    .card-fields-container .flex-group {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }

    .card-fields-container .flex-group > div {
        flex: 1;
    }

    .card-fields-container .flex-group input {
        width: calc(100% - 22px);
    }

    .card-fields-container small {
        display: block;
        color: #6c757d;
        font-size: 0.85em;
        margin-top: -10px;
        margin-bottom: 20px;
    }

    /* Ocultar el contenedor del formulario de tarjeta por defecto si no es seleccionado */
    .card-fields-container.hidden {
        display: none;
    }
</style>

<script>
    // JavaScript para mostrar/ocultar los campos de tarjeta
    function togglePaymentFields() {
        const cardRadio = document.getElementById('radio_tarjeta');
        const cardFields = document.getElementById('card_payment_fields');

        if (cardRadio.checked) {
            cardFields.classList.remove('hidden');
            // Hacer los campos requeridos cuando están visibles
            cardFields.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            cardFields.classList.add('hidden');
            // Hacer los campos no requeridos cuando están ocultos
            cardFields.querySelectorAll('input').forEach(input => input.required = false);
        }
    }

    // Ejecutar al cargar la página para establecer el estado inicial
    document.addEventListener('DOMContentLoaded', togglePaymentFields);

    // Formatear el número de tarjeta
    document.getElementById('numero_tarjeta').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Eliminar no dígitos
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        e.target.value = formattedValue;
    });

    // Formatear la fecha de vencimiento (MM/AA)
    document.getElementById('fecha_vencimiento').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Eliminar no dígitos
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
</script>

<?php include_once 'includes/footer.php'; ?>
<style>
    /* ... (Mantén los estilos anteriores de checkout.php) ... */

    /* Nuevos estilos para el formulario de tarjeta */
    .card-fields-container {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        background-color: #fcfcfc;
    }

    .card-fields-container label {
        margin-bottom: 5px;
    }

    .card-fields-container input[type="text"] {
        margin-bottom: 15px; /* Ajuste para separar los campos de tarjeta */
    }

    .card-fields-container .flex-group {
        display: flex;
        gap: 20px;
    }

    .card-fields-container .flex-group > div {
        flex: 1;
    }

    .card-fields-container .flex-group input {
        width: calc(100% - 22px);
    }

    .card-fields-container small {
        display: block;
        color: #6c757d;
        font-size: 0.85em;
        margin-top: -10px;
        margin-bottom: 20px;
    }

    /* Ocultar el contenedor del formulario de tarjeta por defecto si no es seleccionado */
    .card-fields-container.hidden {
        display: none;
    }
</style>

<script>
    // JavaScript para mostrar/ocultar los campos de tarjeta
    function togglePaymentFields() {
        const cardRadio = document.getElementById('radio_tarjeta');
        const cardFields = document.getElementById('card_payment_fields');

        if (cardRadio.checked) {
            cardFields.classList.remove('hidden');
            // Hacer los campos requeridos cuando están visibles
            cardFields.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            cardFields.classList.add('hidden');
            // Hacer los campos no requeridos cuando están ocultos
            cardFields.querySelectorAll('input').forEach(input => input.required = false);
        }
    }

    // Ejecutar al cargar la página para establecer el estado inicial
    document.addEventListener('DOMContentLoaded', togglePaymentFields);

    // Formatear el número de tarjeta
    document.getElementById('numero_tarjeta').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Eliminar no dígitos
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        e.target.value = formattedValue;
    });

    // Formatear la fecha de vencimiento (MM/AA)
    document.getElementById('fecha_vencimiento').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Eliminar no dígitos
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
</script>

<?php include_once 'includes/footer.php'; ?>

<style>
    /* Estilos adicionales para checkout.php */
    .checkout-container {
        max-width: 700px;
        margin: 50px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .checkout-container h2 {
        color: #007bff;
        text-align: center;
        margin-bottom: 30px;
        font-size: 2.5em;
    }

    .resumen-pedido, .checkout-form {
        background-color: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .resumen-pedido h3, .checkout-form h3 {
        color: #343a40;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-top: 0;
        margin-bottom: 20px;
    }

    .item-resumen {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #eee;
    }

    .item-resumen:last-child {
        border-bottom: none;
    }

    .item-resumen p {
        margin: 0;
        font-size: 0.95em;
        color: #555;
    }

    .item-resumen span {
        font-weight: bold;
        color: #28a745;
    }

    .total-final {
        font-size: 1.5em;
        font-weight: bold;
        text-align: right;
        margin-top: 20px;
        color: #007bff;
    }

    .total-final span {
        color: #28a745;
        font-size: 1.2em;
    }

    .checkout-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #495057;
    }

    .checkout-form input[type="text"],
    .checkout-form input[type="tel"],
    .checkout-form textarea {
        width: calc(100% - 22px);
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 1em;
    }

    .checkout-form input[type="text"]:focus,
    .checkout-form input[type="tel"]:focus,
    .checkout-form textarea:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .payment-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 30px;
    }

    .payment-option {
        display: flex;
        align-items: center;
        border: 1px solid #ced4da;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .payment-option:hover {
        background-color: #f0f0f0;
        border-color: #007bff;
    }

    .payment-option input[type="radio"] {
        margin-right: 15px;
        transform: scale(1.5); /* Agrandar el radio button */
        cursor: pointer;
    }

    .payment-option img {
        height: 30px; /* Ajusta el tamaño de los logos */
        margin-right: 10px;
        vertical-align: middle;
    }

    .paypal-logo {
        height: 25px; /* Ajuste específico para PayPal */
    }

    .stripe-logo {
        height: 30px; /* Ajuste específico para Stripe */
    }

    .btn-checkout-final {
        background-color: #28a745;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.2em;
        font-weight: 600;
        transition: background-color