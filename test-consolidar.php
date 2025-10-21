<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('EXPRESSATECH_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Test de Consolidación</h1>";

// Simular admin logueado
$_SESSION['user_id'] = 1;
$_SESSION['user_tipo'] = 'admin';

// IDs de prueba
$consolidadoId = 1; // Cambia al ID de tu consolidado
$enviosIds = [5, 6]; // Cambia a IDs de envíos reales en "Recibido en Miami"

echo "<h2>Probando asignarEnviosConsolidado...</h2>";
echo "<p>Consolidado: $consolidadoId</p>";
echo "<p>Envíos: " . implode(', ', $enviosIds) . "</p>";

try {
    $result = asignarEnviosConsolidado($consolidadoId, $enviosIds);
    
    echo "<h3>Resultado:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color:green; font-size:20px;'>✅ ÉXITO</p>";
    } else {
        echo "<p style='color:red; font-size:20px;'>❌ ERROR: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h3>EXCEPTION:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>