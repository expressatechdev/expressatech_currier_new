<?php
/**
 * EXPRESSATECH CARGO - API Asignar Envíos a Consolidado
 * Asigna envíos seleccionados y calcula costos automáticamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Log function
function debugLog($message) {
    $logFile = __DIR__ . '/../logs/consolidado-debug.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . $message . PHP_EOL, FILE_APPEND);
}

debugLog("=== INICIO ASIGNAR ENVIOS ===");

// Solo admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $consolidadoId = intval($_POST['consolidado_id'] ?? 0);
    $enviosJson = $_POST['envios'] ?? '[]';
    
    debugLog("Consolidado ID: $consolidadoId");
    debugLog("Envios JSON: $enviosJson");
    
    $enviosIds = json_decode($enviosJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    debugLog("Envios IDs: " . implode(',', $enviosIds));
    
    if (empty($consolidadoId) || empty($enviosIds)) {
        throw new Exception('Datos insuficientes');
    }
    
    // Verificar que el consolidado existe
    $consolidado = queryOne("SELECT * FROM consolidados WHERE id = ?", [$consolidadoId]);
    if (!$consolidado) {
        throw new Exception('Consolidado no encontrado');
    }
    
    debugLog("Consolidado encontrado: " . $consolidado['numero_consolidado']);
    
    // Asignar envíos y calcular costos
    $result = asignarEnviosConsolidado($consolidadoId, $enviosIds);
    
    debugLog("Resultado: " . json_encode($result));
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Envíos asignados y costos calculados',
        'total_productos' => $result['datos']['total_productos'] ?? 0,
        'total_facturado' => $result['datos']['total_facturado'] ?? 0
    ]);
    
    debugLog("=== FIN EXITOSO ===");
    
} catch (Exception $e) {
    debugLog("ERROR: " . $e->getMessage());
    debugLog("Trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>