<?php
// Tienda_Virtual/confirmacion.php

session_start();
require_once 'config/database.php';
// require_once 'config/stripe_config.php'; // ¡COMENTA o ELIMINA esta línea!
include_once 'includes/header.php';

$mensaje = '';
$mensaje_tipo = 'error';
$pedido_id = $_GET['order_id'] ?? null;
$simulated_status = $_GET['status'] ?? null; // Capturamos el nuevo parámetro de estado

if ($pedido_id && $simulated_status === 'simulated_success') {
    // Para la simulación, asumimos que el pago fue exitoso
    $mensaje = '¡Gracias por tu compra! Tu pago ha sido procesado exitosamente.';
    $mensaje_tipo = 'success';

    // Opcional: Podrías verificar aquí el estado del pedido en la DB si quieres ser más riguroso
    // y asegurarte de que se actualizó a 'pagado' en checkout.php.
    // Esto es más un control de tu propia lógica que de la pasarela de pago.

    // Limpia el carrito de la sesión si no se hizo antes (ya se hizo en checkout.php)
    unset($_SESSION['carrito']);

} else {
    $mensaje = 'No se pudo confirmar la transacción simulada. Información faltante o estado incorrecto.';
    $mensaje_tipo = 'error';
}
?>

<div class="confirmacion-container">
    <h2>Estado de tu Pedido</h2>

    <div class="mensaje-confirmacion <?php echo $mensaje_tipo; ?>">
        <?php echo $mensaje; ?>
    </div>

    <?php if ($mensaje_tipo === 'success' && $pedido_id): ?>
        <p>Tu número de pedido es: <strong>#<?php echo htmlspecialchars($pedido_id); ?></strong></p>
        <p>Puedes ver tu factura aquí: <a href="factura.php?pedido_id=<?php echo htmlspecialchars($pedido_id); ?>">Ver Factura</a></p>
    <?php endif; ?>

    <p><a href="index.php" class="btn-volver">Volver a la tienda</a></p>
</div>

<?php include_once 'includes/footer.php'; ?>