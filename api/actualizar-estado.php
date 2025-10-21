<?php
/**
 * EXPRESSATECH CARGO - API Actualizar Estado
 * Cambia el estado de un envío y envía notificaciones
 */

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Solo admin puede actualizar estados
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
    $envioId = $_POST['envio_id'] ?? 0;
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    
    if (empty($envioId) || empty($nuevoEstado)) {
        throw new Exception('Faltan datos requeridos');
    }
    
    // Actualizar estado
    $result = updateEstadoEnvio($envioId, $nuevoEstado, true);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>