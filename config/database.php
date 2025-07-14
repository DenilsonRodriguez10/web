<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_PORT', '5433');
define('DB_NAME', 'mi_tienda_db'); // Asegúrate de que este sea el nombre de tu BD en PostgreSQL
define('DB_USER', 'postgres');     // Usuario de PostgreSQL (comúnmente 'postgres')
define('DB_PASS', 'corazon'); // ¡¡¡CAMBIA ESTO CON LA CONTRASEÑA REAL DE TU USUARIO POSTGRES!!!

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Obtener filas como arrays asociativos
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>