<?php
// Tienda_Virtual/registro.php

session_start();
// echo "Debug: Sesión iniciada (registro).<br>"; // DEBUG - Descomentar solo si necesitas depurar session_start() en una página en blanco.

require_once 'config/database.php'; // Asegúrate de que esta ruta sea correcta

$mensaje_error = '';
$mensaje_exito = '';

// LÓGICA DE PROCESAMIENTO POST (DEBE IR ANTES DE CUALQUIER SALIDA HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo "Debug: Procesando POST (registro).<br>"; // DEBUG
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $mensaje_error = "Todos los campos son obligatorios.";
        // echo "Debug: Campos vacíos (registro).<br>"; // DEBUG
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del correo electrónico no es válido.";
        // echo "Debug: Email inválido (registro).<br>"; // DEBUG
    } elseif ($password !== $confirm_password) {
        $mensaje_error = "Las contraseñas no coinciden.";
        // echo "Debug: Contraseñas no coinciden (registro).<br>"; // DEBUG
    } elseif (strlen($password) < 6) { // Ejemplo de validación de longitud de contraseña
        $mensaje_error = "La contraseña debe tener al menos 6 caracteres.";
        // echo "Debug: Contraseña muy corta (registro).<br>"; // DEBUG
    } else {
        try {
            // echo "Debug: Verificando email existente (registro).<br>"; // DEBUG
            // Verificar si el correo electrónico ya está registrado
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $mensaje_error = "Este correo electrónico ya está registrado. Por favor, inicia sesión o usa otro correo.";
                // echo "Debug: Email ya registrado (registro).<br>"; // DEBUG
            } else {
                // Hashear la contraseña de forma segura
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // echo "Debug: Hash de contraseña creado (registro).<br>"; // DEBUG

                // Insertar el nuevo usuario en la base de datos
                // echo "Debug: Insertando nuevo usuario (registro).<br>"; // DEBUG
                // Asegúrate de que esta línea inserta en 'password', NO en 'contrasena_hash'
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $email, $hashed_password]);
                // echo "Debug: Usuario insertado (registro).<br>"; // DEBUG

                // Registro exitoso, iniciar sesión automáticamente al nuevo usuario
                $_SESSION['user_id'] = $pdo->lastInsertId(); // Obtener el ID del último registro insertado
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $nombre;

                $mensaje_exito = "¡Registro exitoso! Has iniciado sesión automáticamente."; // Este mensaje no se mostrará si redirige.

                // Redirigir al usuario al inicio o a una página de bienvenida - ¡AQUÍ ES DONDE SUCEDE!
                // echo "Debug: Registro exitoso. Intentando redirigir a index.php (registro).<br>"; // DEBUG
                header("Location: index.php");
                exit(); // IMPRESCINDIBLE después de header()
            }
        } catch (PDOException $e) {
            $mensaje_error = "Error al intentar registrar el usuario: " . $e->getMessage();
            // echo "Debug: Excepción PDO (registro): " . $e->getMessage() . "<br>"; // DEBUG
        }
    }
}
// echo "Debug: Final del script PHP de procesamiento (registro).<br>"; // DEBUG

// Si el usuario ya está logueado DESPUÉS del intento de POST (o si ya lo estaba antes del POST)
// Y no se redirigió desde el bloque de éxito del POST
if (isset($_SESSION['user_id'])) {
    // Esto atraparía casos donde ya estaba logueado o donde la redirección del POST falló antes del HTML.
    header("Location: index.php");
    exit();
}

// Ahora, y solo ahora, incluimos el header que puede tener HTML
include_once 'includes/header.php';
?>

<div class="container registro-container">
    <h2>Crear una Cuenta</h2>

    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-confirmacion error">
            <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensaje_exito)): ?>
        <div class="mensaje-confirmacion success">
            <?php echo htmlspecialchars($mensaje_exito); ?>
        </div>
    <?php endif; ?>

    <form action="registro.php" method="POST">
        <div class="form-group">
            <label for="nombre">Nombre Completo:</label>
            <input type="text" id="nombre" name="nombre" required autocomplete="name" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>
    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
</div>

<?php include_once 'includes/footer.php'; // Incluye el pie de página de tu sitio ?>

<style>
    /* ... (tus estilos existentes) ... */
    .registro-container {
        max-width: 450px; /* Un poco más ancho para más campos */
        margin: 50px auto;
        padding: 30px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background-color: #fff;
    }
    .registro-container h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #333;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .btn-primary {
        width: 100%;
        padding: 10px;
        background-color: #28a745; /* Un verde para registro */
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #218838;
    }
    .registro-container p {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }
    .registro-container p a {
        color: #28a745; /* Color del enlace acorde al botón */
        text-decoration: none;
    }
    .registro-container p a:hover {
        text-decoration: underline;
    }
    .mensaje-confirmacion {
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        text-align: center;
        font-size: 0.9em;
    }
    .mensaje-confirmacion.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .mensaje-confirmacion.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>