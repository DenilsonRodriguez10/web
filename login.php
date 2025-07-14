<?php
// Tienda_Virtual/login.php

session_start();
// echo "Debug: Sesión iniciada.<br>"; // DEBUG - Descomentar solo si necesitas depurar sesión_start() en una página en blanco.

require_once 'config/database.php'; // Asegúrate de que esta ruta sea correcta

$mensaje_error = '';
$mensaje_confirmacion = '';

// LÓGICA DE PROCESAMIENTO POST (DEBE IR ANTES DE CUALQUIER SALIDA HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo "Debug: Procesando POST.<br>"; // DEBUG
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $mensaje_error = "Por favor, introduce tu correo electrónico y contraseña.";
        // echo "Debug: Campos vacíos.<br>"; // DEBUG
    } else {
        try {
            // echo "Debug: Intentando preparar consulta.<br>"; // DEBUG
            $stmt = $pdo->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // echo "Debug: Consulta ejecutada.<br>"; // DEBUG

            if ($user && password_verify($password, $user['password'])) {
                // echo "Debug: Contraseña verificada. Estableciendo sesión.<br>"; // DEBUG
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['nombre'];

                if (isset($_SESSION['mensaje'])) {
                    $mensaje_confirmacion = $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']);
                    $_SESSION['mensaje_tipo'] = $_SESSION['mensaje_tipo'] ?? 'info'; // Guardar el tipo para después
                }
                
                // Redirigir al usuario - ¡AQUÍ ES DONDE SUCEDE!
                // echo "Debug: Sesión establecida. Intentando redirigir a index.php.<br>"; // DEBUG
                header("Location: index.php");
                exit(); // IMPRESCINDIBLE después de header()
            } else {
                $mensaje_error = "Correo electrónico o contraseña incorrectos.";
                // echo "Debug: Credenciales incorrectas.<br>"; // DEBUG
            }
        } catch (PDOException $e) {
            $mensaje_error = "Error al intentar iniciar sesión: " . $e->getMessage();
            // echo "Debug: Excepción PDO: " . $e->getMessage() . "<br>"; // DEBUG
        }
    }
}
// echo "Debug: Final del script PHP de procesamiento.<br>"; // DEBUG

// Si el usuario ya está logueado DESPUÉS del intento de POST (o si ya lo estaba antes del POST)
// Y no se redirigió desde el bloque de éxito del POST
if (isset($_SESSION['user_id'])) {
    // Si la redirección desde POST no se ejecutó por alguna razón (ej. error en headers), esto lo atraparía.
    // O si llegó aquí porque ya estaba logueado antes de enviar el formulario.
    header("Location: index.php");
    exit();
}

// Ahora, y solo ahora, incluimos el header que puede tener HTML
include_once 'includes/header.php';
?>

<div class="container login-container">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-confirmacion error">
            <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
    <?php endif; ?>

    <?php 
    // Mostrar mensaje de redirección de la sesión si existe (viene de checkout.php)
    if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje-confirmacion <?php echo $_SESSION['mensaje_tipo'] ?? 'info'; ?>">
            <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
        </div>
        <?php 
        unset($_SESSION['mensaje']); // Eliminar el mensaje después de mostrarlo
        unset($_SESSION['mensaje_tipo']);
        ?>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
    </form>
    <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>.</p>
</div>

<?php include_once 'includes/footer.php'; // Incluye el pie de página de tu sitio ?>

<style>
    /* ... (tus estilos existentes) ... */
    .login-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 30px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background-color: #fff;
    }
    .login-container h2 {
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
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Asegura que el padding no aumente el ancho total */
    }
    .btn-primary {
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    .login-container p {
        text-align: center;
        margin-top: 20px;
        color: #666;
    }
    .login-container p a {
        color: #007bff;
        text-decoration: none;
    }
    .login-container p a:hover {
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
    .mensaje-confirmacion.warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
</style>