<?php
// Tienda_Virtual/cancelacion.php

session_start();
include_once 'includes/header.php';

$pedido_id = $_GET['order_id'] ?? 'N/A'; // Recupera el ID del pedido si se pasó

?>
<div class="cancelacion-container">
    <h2>Pago Cancelado o Fallido</h2>
    <div class="mensaje-confirmacion error">
        Tu pago ha sido cancelado o no se pudo procesar. <br>
        Por favor, revisa los detalles de tu pedido y vuelve a intentarlo.
    </div>
    <?php if ($pedido_id !== 'N/A'): ?>
        <p>Si lo deseas, puedes intentar finalizar el pedido #<?php echo htmlspecialchars($pedido_id); ?> de nuevo.</p>
    <?php endif; ?>
    <p><a href="carrito.php" class="btn-volver">Volver al Carrito</a></p>
    <p><a href="index.php" class="btn-volver">Volver a la Tienda</a></p>
</div>

<style>
    /* Estilos para la página de cancelación (similar a confirmacion) */
    .cancelacion-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .cancelacion-container h2 {
        color: #dc3545; /* Rojo para indicar cancelación/error */
        margin-bottom: 30px;
        font-size: 2.2em;
    }

    .cancelacion-container p {
        font-size: 1.1em;
        color: #495057;
        margin-bottom: 15px;
    }

    .btn-volver {
        display: inline-block;
        background-color: #6c757d; /* Gris para volver */
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin: 10px; /* Margen para separar los botones */
        transition: background-color 0.3s ease;
    }

    .btn-volver:hover {
        background-color: #5a6268;
    }

    .mensaje-confirmacion.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<?php include_once 'includes/footer.php'; ?>