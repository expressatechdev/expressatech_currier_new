<?php
/**
 * Test de procesamiento de envío
 * Para diagnosticar el error 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Procesamiento de Envío</h1>";

define('EXPRESSATECH_ACCESS', true);

echo "<p>✓ Constante definida</p>";

require_once 'includes/config.php';
echo "<p>✓ Config cargado</p>";

require_once 'includes/functions.php';
echo "<p>✓ Functions cargado</p>";

// Simular que estamos logueados
$_SESSION['user_id'] = 1;
$_SESSION['user_tipo'] = 'cliente';

echo "<p>✓ Sesión simulada</p>";

// Simular datos POST
$_POST = [
    'cliente_id' => 1,
    'fecha_compra' => '2025-01-20',
    'tracking_original' => 'TEST123',
    'empresa_compra' => '4Life',
    'destinatario_nombre' => '',
    'productos' => [
        1 => [
            'nombre' => 'Transfer Factor Plus',
            'cantidad' => 2,
            'detalle' => 'Test'
        ]
    ]
];

echo "<p>✓ Datos POST simulados</p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Intentar crear envío
echo "<h2>Creando envío...</h2>";

try {
    $db = getDB();
    echo "<p>✓ Conexión a BD obtenida</p>";
    
    $db->beginTransaction();
    echo "<p>✓ Transacción iniciada</p>";
    
    // Crear envío
    $envioResult = createEnvio([
        'cliente_id' => $_POST['cliente_id'],
        'fecha_compra' => $_POST['fecha_compra'],
        'tracking_original' => $_POST['tracking_original'],
        'empresa_compra' => $_POST['empresa_compra'],
        'factura_url' => '',
        'destinatario_nombre' => $_POST['destinatario_nombre'],
        'es_envio_admin' => false
    ]);
    
    echo "<p>Resultado de createEnvio:</p>";
    echo "<pre>";
    print_r($envioResult);
    echo "</pre>";
    
    if (!$envioResult['success']) {
        throw new Exception($envioResult['message']);
    }
    
    $envioId = $envioResult['envio_id'];
    echo "<p style='color:green;'>✓ Envío creado con ID: $envioId</p>";
    
    // Agregar productos
    $productos = [];
    foreach ($_POST['productos'] as $prod) {
        $productos[] = [
            'nombre' => $prod['nombre'],
            'cantidad' => $prod['cantidad'],
            'detalle' => $prod['detalle']
        ];
    }
    
    echo "<p>Productos a agregar:</p>";
    echo "<pre>";
    print_r($productos);
    echo "</pre>";
    
    $productosResult = addProductosEnvio($envioId, $productos);
    
    echo "<p>Resultado de addProductosEnvio:</p>";
    echo "<pre>";
    print_r($productosResult);
    echo "</pre>";
    
    if (!$productosResult['success']) {
        throw new Exception($productosResult['message']);
    }
    
    echo "<p style='color:green;'>✓ Productos agregados</p>";
    
    $db->commit();
    echo "<p style='color:green;font-size:20px;'>✅ ÉXITO TOTAL</p>";
    
    // Obtener tracking
    $tracking = queryOne("SELECT tracking_interno FROM envios WHERE id = ?", [$envioId]);
    echo "<p><strong>Tracking interno:</strong> " . $tracking['tracking_interno'] . "</p>";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Verificar logs</h2>";
echo "<p>Revisar archivo: logs/app.log</p>";
?>