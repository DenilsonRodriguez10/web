<?php
// Tienda_Virtual/index.php

session_start();
require_once 'config/database.php';
include_once 'includes/header.php';

$productos = [];
$search_query = $_GET['search'] ?? '';

if (!empty($search_query)) {
    $stmt = $pdo->prepare("SELECT p.*, op.opciones_color_disponibles, op.opciones_tamanio_disponibles, op.permite_subir_imagen, op.max_longitud_texto
                           FROM productos p
                           LEFT JOIN opciones_personalizables op ON p.id = op.producto_id
                           WHERE p.nombre ILIKE ? OR p.descripcion ILIKE ?
                           ORDER BY p.nombre ASC");
    $stmt->execute(['%' . $search_query . '%', '%' . $search_query . '%']);
    $productos = $stmt->fetchAll();
} else {
    // Esta es la consulta principal que lista todos los productos
    $stmt = $pdo->query("SELECT p.*, op.opciones_color_disponibles, op.opciones_tamanio_disponibles, op.permite_subir_imagen, op.max_longitud_texto
                         FROM productos p
                         LEFT JOIN opciones_personalizables op ON p.id = op.producto_id
                         ORDER BY p.nombre ASC");
    $productos = $stmt->fetchAll();
}
?>

<div class="main-content">
    <div class="search-bar">
        <form action="index.php" method="GET">
            <input type="text" name="search" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <h2>Nuestros Productos</h2>

    <div class="productos-grid">
        <?php if (empty($productos)): ?>
            <p class="no-productos">No se encontraron productos.</p>
        <?php else: ?>
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                    <p><?php echo htmlspecialchars($producto['descripcion_corta'] ?? (strlen($producto['descripcion']) > 100 ? substr($producto['descripcion'], 0, 100) . '...' : $producto['descripcion'])); ?></p>
                    <p class="precio">$<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></p>
                    <?php if ($producto['es_personalizable']): ?>
                        <span class="badge personalizable">Personalizable</span>
                    <?php endif; ?>
                    <a href="producto.php?id=<?php echo htmlspecialchars($producto['id']); ?>" class="btn-ver-mas">Ver Más</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>