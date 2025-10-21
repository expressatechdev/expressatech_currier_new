<?php
/**
 * EXPRESSATECH CARGO - API Asignar Costo
 * Asigna costo a un envío y genera saldo pendiente
 */

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

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
    $envioId = $_POST['envio_id'] ?? 0;
    $costo = $_POST['costo'] ?? 0;
    $notas = $_POST['notas'] ?? '';
    
    if (empty($envioId) || $costo <= 0) {
        throw new Exception('Datos inválidos');
    }
    
    // Actualizar envío con el costo
    $sql = "UPDATE envios SET costo_calculado = ?, saldo_pendiente = ?, notas_admin = ? WHERE id = ?";
    $result = execute($sql, [$costo, $costo, $notas, $envioId]);
    
    if (!$result) {
        throw new Exception('Error al asignar costo');
    }
    
    // Programar notificación de costo asignado
    programarNotificacion($envioId, 'costo_asignado');
    
    echo json_encode([
        'success' => true,
        'message' => 'Costo asignado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>