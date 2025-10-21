<?php
/**
 * EXPRESSATECH CARGO - API Procesar Envío (VERSIÓN CORREGIDA)
 */

// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Log de inicio
logError("=== INICIO PROCESAR ENVIO ===");
logError("POST data: " . json_encode($_POST));
logError("FILES data: " . json_encode(array_keys($_FILES)));

// Verificar que esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Obtener datos del envío
    $clienteId = $_POST['cliente_id'] ?? $_SESSION['user_id'];
    $fechaCompra = $_POST['fecha_compra'] ?? '';
    $trackingOriginal = $_POST['tracking_original'] ?? '';
    $empresaCompra = $_POST['empresa_compra'] ?? '';
    $destinatarioNombre = $_POST['destinatario_nombre'] ?? '';
    $facturaUrl = '';
    
    logError("Datos capturados - Cliente: $clienteId, Fecha: $fechaCompra, Empresa: $empresaCompra");
    
    // Validaciones básicas
    if (empty($fechaCompra) || empty($empresaCompra)) {
        throw new Exception('Faltan datos obligatorios del envío');
    }
    
    // Procesar archivo de factura si existe
    if (isset($_FILES['factura']) && $_FILES['factura']['error'] === UPLOAD_ERR_OK) {
        logError("Procesando archivo de factura...");
        $uploadResult = uploadFile($_FILES['factura'], 'factura_' . time());
        if ($uploadResult['success']) {
            $facturaUrl = $uploadResult['url'];
            logError("Factura subida: " . $facturaUrl);
        } else {
            logError("Error subiendo factura: " . $uploadResult['message']);
        }
    }
    
    // Crear envío
    $envioResult = createEnvio([
        'cliente_id' => $clienteId,
        'fecha_compra' => $fechaCompra,
        'tracking_original' => $trackingOriginal,
        'empresa_compra' => $empresaCompra,
        'factura_url' => $facturaUrl,
        'destinatario_nombre' => $destinatarioNombre,
        'es_envio_admin' => isAdmin()
    ]);
    
    if (!$envioResult['success']) {
        throw new Exception($envioResult['message']);
    }
    
    $envioId = $envioResult['envio_id'];
    logError("Envío creado con ID: $envioId");
    
    // Procesar productos
    $productos = [];
    
    // Verificar estructura de productos
    if (isset($_POST['productos']) && is_array($_POST['productos'])) {
        logError("Productos recibidos: " . json_encode($_POST['productos']));
        
        foreach ($_POST['productos'] as $index => $productoData) {
            // Verificar que tenga los campos necesarios
            if (isset($productoData['nombre']) && isset($productoData['cantidad'])) {
                if (!empty($productoData['nombre']) && !empty($productoData['cantidad'])) {
                    $productos[] = [
                        'nombre' => trim($productoData['nombre']),
                        'cantidad' => intval($productoData['cantidad']),
                        'detalle' => isset($productoData['detalle']) ? trim($productoData['detalle']) : ''
                    ];
                    logError("Producto agregado: " . $productoData['nombre'] . " x" . $productoData['cantidad']);
                }
            }
        }
    }
    
    logError("Total productos procesados: " . count($productos));
    
    if (empty($productos)) {
        throw new Exception('Debes agregar al menos un producto');
    }
    
    // Guardar productos
    $productosResult = addProductosEnvio($envioId, $productos);
    
    if (!$productosResult['success']) {
        throw new Exception($productosResult['message']);
    }
    
    // Programar notificación de confirmación
    programarNotificacion($envioId, 'confirmacion_registro');
    
    $db->commit();
    
    $trackingInterno = queryOne("SELECT tracking_interno FROM envios WHERE id = ?", [$envioId])['tracking_interno'];
    
    logError("=== ENVIO COMPLETADO EXITOSAMENTE ===");
    
    echo json_encode([
        'success' => true,
        'message' => 'Envío registrado exitosamente',
        'envio_id' => $envioId,
        'tracking_interno' => $trackingInterno
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    logError("ERROR: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>