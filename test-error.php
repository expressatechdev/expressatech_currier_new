<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Errores - Expressatech Cargo</h1>";

// 1. Test de definición de constante
define('EXPRESSATECH_ACCESS', true);
echo "<p>✅ Constante definida correctamente</p>";

// 2. Test de inclusión de config
echo "<h2>Test de Config.php</h2>";
try {
    require_once 'includes/config.php';
    echo "<p>✅ Config.php cargado correctamente</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error en config.php: " . $e->getMessage() . "</p>";
}

// 3. Test de conexión a base de datos
echo "<h2>Test de Conexión MySQL</h2>";
try {
    $db = getDB();
    echo "<p>✅ Conexión a base de datos exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

// 4. Test de sesiones
echo "<h2>Test de Sesiones</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✅ Sesiones funcionando</p>";
} else {
    echo "<p style='color:red'>❌ Problema con sesiones</p>";
}

// 5. Test de functions.php
echo "<h2>Test de Functions.php</h2>";
try {
    require_once 'includes/functions.php';
    echo "<p>✅ Functions.php cargado correctamente</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error en functions.php: " . $e->getMessage() . "</p>";
}

// 6. Información del servidor
echo "<h2>Información del Servidor</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// 7. Test de permisos de escritura
echo "<h2>Test de Permisos</h2>";
$uploadDir = 'assets/uploads/';
if (is_writable($uploadDir)) {
    echo "<p>✅ Directorio de uploads es escribible</p>";
} else {
    echo "<p style='color:red'>❌ Directorio de uploads NO es escribible</p>";
}

echo "<hr>";
echo "<p><strong>Si ves este mensaje, el archivo PHP básico funciona.</strong></p>";
?>