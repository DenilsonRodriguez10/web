<?php
// Tienda_Virtual/producto.php

session_start();

require_once 'config/database.php';
include_once 'includes/header.php';

$producto = null;
$mensaje = '';
$mensaje_tipo = '';

// 1. Obtener detalles del producto y sus opciones de personalización
if (isset($_GET['id'])) {
    $producto_id = $_GET['id'];

    // Realiza un LEFT JOIN con opciones_personalizables para obtener todas las configuraciones
    $sql = "SELECT p.*, op.opciones_color_disponibles, op.opciones_tamanio_disponibles, op.permite_subir_imagen, op.max_longitud_texto
            FROM productos p
            LEFT JOIN opciones_personalizables op ON p.id = op.producto_id
            WHERE p.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
}

// Si el producto no se encuentra, muestra un mensaje y termina
if (!$producto) {
    echo "<p>Producto no encontrado.</p>";
    include_once 'includes/footer.php';
    exit();
}

// Prepara las variables para las opciones de personalización
// Si el producto no es personalizable, estas variables serán vacías o falsas
$opciones_color_disponibles = $producto['es_personalizable'] && !empty($producto['opciones_color_disponibles']) ? explode(',', $producto['opciones_color_disponibles']) : [];
$opciones_tamanio_disponibles = $producto['es_personalizable'] && !empty($producto['opciones_tamanio_disponibles']) ? explode(',', $producto['opciones_tamanio_disponibles']) : [];
$permite_subir_imagen = $producto['es_personalizable'] && ($producto['permite_subir_imagen'] ?? FALSE); // Usa ?? FALSE para manejar NULL si no hay entrada en opciones_personalizables
$max_longitud_texto = $producto['es_personalizable'] && ($producto['max_longitud_texto'] ?? 0) > 0 ? $producto['max_longitud_texto'] : 0;


// 2. Lógica para procesar el formulario de añadir al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    
    // Variables para almacenar las personalizaciones del cliente
    $texto_personalizado = null;
    $color_personalizado = null;
    $tamanio_elegido = null;
    $url_imagen_personalizada = null;

    // Solo procesar las personalizaciones si el producto es personalizable
    if ($producto['es_personalizable']) {
        // Recoger personalizaciones del formulario
        $texto_personalizado = isset($_POST['texto_personalizado']) ? trim($_POST['texto_personalizado']) : null;
        $color_personalizado = isset($_POST['color_personalizado']) ? trim($_POST['color_personalizado']) : null;
        $tamanio_elegido = isset($_POST['tamanio_elegido']) ? trim($_POST['tamanio_elegido']) : null;

        // Validaciones básicas para las personalizaciones
        if ($max_longitud_texto > 0 && $texto_personalizado && strlen($texto_personalizado) > $max_longitud_texto) {
            $mensaje = "El texto excede la longitud máxima permitida de " . $max_longitud_texto . " caracteres.";
            $mensaje_tipo = 'error';
        } elseif (!empty($opciones_color_disponibles) && $color_personalizado && !in_array($color_personalizado, array_map('trim', $opciones_color_disponibles))) {
            $mensaje = "El color seleccionado no es válido.";
            $mensaje_tipo = 'error';
        } elseif (!empty($opciones_tamanio_disponibles) && $tamanio_elegido && !in_array($tamanio_elegido, array_map('trim', $opciones_tamanio_disponibles))) {
            $mensaje = "El tamaño seleccionado no es válido.";
            $mensaje_tipo = 'error';
        }

        // Manejo de la subida de imagen
        if ($permite_subir_imagen) {
            // Verificar si se ha subido un archivo y no hay errores
            if (isset($_FILES['imagen_personalizada']) && $_FILES['imagen_personalizada']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/personalizadas/';
                
                // Crea el directorio si no existe y asegúrate de permisos
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // 0777 para desarrollo, considera permisos más restrictivos en producción
                }

                $file_name = uniqid('img_') . '_' . basename($_FILES['imagen_personalizada']['name']);
                $target_file = $upload_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Validar tipo de archivo (solo imágenes)
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($imageFileType, $allowed_types)) {
                    $mensaje = "Solo se permiten imágenes JPG, JPEG, PNG y GIF.";
                    $mensaje_tipo = 'error';
                } 
                // Validar tamaño del archivo (máximo 5MB)
                elseif ($_FILES['imagen_personalizada']['size'] > 5000000) {
                    $mensaje = "La imagen es demasiado grande (máx. 5MB).";
                    $mensaje_tipo = 'error';
                } 
                else {
                    // Mueve el archivo subido a la carpeta de destino
                    if (move_uploaded_file($_FILES['imagen_personalizada']['tmp_name'], $target_file)) {
                        $url_imagen_personalizada = $target_file; // Guarda la ruta relativa
                    } else {
                        $mensaje = "Hubo un error al subir la imagen. Por favor, inténtalo de nuevo.";
                        $mensaje_tipo = 'error';
                    }
                }
            } 
            // Si la personalización de imagen es permitida pero no se subió ningún archivo, y no es un error diferente a "no file"
            elseif ($_FILES['imagen_personalizada']['error'] !== UPLOAD_ERR_NO_FILE) {
                $mensaje = "Error al subir la imagen: " . $_FILES['imagen_personalizada']['error'];
                $mensaje_tipo = 'error';
            }
            // Si permite_subir_imagen es TRUE pero no se seleccionó ninguna imagen (UPLOAD_ERR_NO_FILE), 
            // y no es un campo obligatorio, simplemente $url_imagen_personalizada seguirá siendo NULL.
        }
    }

    // Validar que la cantidad sea al menos 1
    if ($cantidad < 1) {
        $cantidad = 1; 
        $mensaje = "La cantidad debe ser al menos 1.";
        $mensaje_tipo = 'error';
    }

    // Si no hay errores de validación, añadir el producto al carrito de la sesión
    if ($mensaje_tipo !== 'error') {
        // Crea una clave única para el ítem del carrito.
        // Esto es crucial para productos personalizables: si el mismo producto
        // se añade con diferentes personalizaciones, debe tratarse como un ítem diferente.
        $item_key_data = [
            $producto['id'],
            $texto_personalizado,
            $color_personalizado,
            $tamanio_elegido,
            $url_imagen_personalizada // Incluye la URL de la imagen en la clave para su unicidad
        ];
        // Utiliza md5(serialize()) para crear un hash único de las opciones de personalización
        $item_key = $producto['id'] . '_' . md5(serialize($item_key_data));

        // Inicializa el carrito si no existe en la sesión
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // Si el ítem ya existe en el carrito (misma personalización), actualiza la cantidad
        if (isset($_SESSION['carrito'][$item_key])) {
            $_SESSION['carrito'][$item_key]['cantidad'] += $cantidad;
            $mensaje = "Cantidad actualizada para **'" . htmlspecialchars($producto['nombre']) . "'** en el carrito.";
        } else {
            // Añade el nuevo ítem al carrito con todas sus personalizaciones
            $_SESSION['carrito'][$item_key] = [
                'id' => $producto['id'], // ID del producto de la BD
                'nombre' => $producto['nombre'],
                'precio_unitario' => $producto['precio'],
                'cantidad' => $cantidad,
                'imagen' => $producto['imagen'], // Ruta de la imagen principal del producto
                'es_personalizable' => $producto['es_personalizable'],
                'texto_personalizado' => $texto_personalizado,
                'color_personalizado' => $color_personalizado,
                'tamanio_elegido' => $tamanio_elegido,
                'url_imagen_personalizada' => $url_imagen_personalizada // Ruta de la imagen subida por el cliente
            ];
            $mensaje = "Producto **'" . htmlspecialchars($producto['nombre']) . "'** añadido al carrito.";
        }
        $mensaje_tipo = 'success';
    }
}
?>

<div class="producto-detalle">
    <div class="imagen">
        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
    </div>
    <div class="info">
        <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
        <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
        <p class="precio">$<?php echo htmlspecialchars(number_format($producto['precio'], 2)); ?></p>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-confirmacion <?php echo $mensaje_tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="producto.php?id=<?php echo $producto['id']; ?>" enctype="multipart/form-data">
            <?php if ($producto['es_personalizable']): ?>
                <h3>Personaliza tu producto:</h3>

                <?php if (!empty($opciones_tamanio_disponibles)): ?>
                    <label for="tamanio_elegido">Selecciona un Tamaño:</label>
                    <select id="tamanio_elegido" name="tamanio_elegido" required>
                        <option value="">-- Elige un tamaño --</option>
                        <?php foreach ($opciones_tamanio_disponibles as $tamanio): ?>
                            <?php $tamanio_clean = trim($tamanio); ?>
                            <option value="<?php echo htmlspecialchars($tamanio_clean); ?>"><?php echo htmlspecialchars($tamanio_clean); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                <?php endif; ?>

                <?php if (!empty($opciones_color_disponibles)): ?>
                    <label for="color_personalizado">Selecciona un Color:</label>
                    <select id="color_personalizado" name="color_personalizado" onchange="updateColorPreview(this.value)" required>
                        <option value="">-- Elige un color --</option>
                        <?php foreach ($opciones_color_disponibles as $color): ?>
                            <?php $color_clean = trim($color); ?>
                            <option value="<?php echo htmlspecialchars($color_clean); ?>"><?php echo htmlspecialchars($color_clean); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="colorPreview" style="display: inline-block; width: 20px; height: 20px; border: 1px solid #ccc; vertical-align: middle; margin-left: 10px;"></span>
                    <br><br>
                <?php endif; ?>

                <?php if ($max_longitud_texto > 0): ?>
                    <label for="texto_personalizado">Detalles (máx. <?php echo $max_longitud_texto; ?> caracteres):</label>
                    <input type="text" id="texto_personalizado" name="texto_personalizado" maxlength="<?php echo $max_longitud_texto; ?>" placeholder="Escribe tu texto aquí">
                    <br><br>
                <?php endif; ?>

                    <?php if ($permite_subir_imagen): ?>
                        <label for="imagen_personalizada">Sube tu Imagen:</label>
                        <input type="file" id="imagen_personalizada" name="imagen_personalizada" accept="image/jpeg, image/png, image/gif">
                        <small>Solo se permiten imágenes JPG, PNG, GIF (máx. 5MB).</small>
                        <br><br>
                    <?php endif; ?>

                    

            <?php endif; /* Fin de es_personalizable */ ?>

            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" name="cantidad" value="1" min="1" required>
            <br>
            <input type="submit" name="add_to_cart" value="Añadir al Carrito">
        </form>
    </div>
</div>
form
<?php if ($producto['es_personalizable'] && !empty($opciones_color_disponibles)): ?>
<script>
    // Función para actualizar la vista previa del color seleccionado
    function updateColorPreview(color) {
        const preview = document.getElementById('colorPreview');
        if (color && color !== "") {
            // Usa el color directamente para el fondo
            preview.style.backgroundColor = color.toLowerCase(); 
            preview.style.border = '1px solid #333'; // Agrega un borde para mejor visibilidad
        } else {
            // Vuelve al estado por defecto si no hay color seleccionado
            preview.style.backgroundColor = '#f0f0f0'; 
            preview.style.border = '1px solid #ccc';
        }
    }

    // Ejecuta la función al cargar la página para establecer el estado inicial de la vista previa
    document.addEventListener('DOMContentLoaded', () => {
        const colorSelect = document.getElementById('color_personalizado');
        if (colorSelect && colorSelect.value) {
            updateColorPreview(colorSelect.value);
        }
    });
</script>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>    