<?php
/**
 * EXPRESSATECH CARGO - API Cambiar Estado Consolidado
 * Cambia el estado de un consolidado y actualiza envíos relacionados
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

// Leer JSON del body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

try {
    $consolidadoId = $data['consolidado_id'] ?? 0;
    $nuevoEstado = $data['nuevo_estado'] ?? '';
    
    if (empty($consolidadoId) || empty($nuevoEstado)) {
        throw new Exception('Datos incompletos');
    }
    
    // Validar estado
    $estadosValidos = ['Abierto', 'Cerrado', 'En Tránsito', 'Entregado'];
    if (!in_array($nuevoEstado, $estadosValidos)) {
        throw new Exception('Estado inválido');
    }
    
    $db = getDB();
    $db->beginTransaction();
    
    // Actualizar estado del consolidado
    execute("UPDATE consolidados SET estado = ? WHERE id = ?", [$nuevoEstado, $consolidadoId]);
    
    // Si marca en tránsito, actualizar envíos
    if ($nuevoEstado === 'En Tránsito') {
        execute("
            UPDATE consolidados 
            SET fecha_salida_miami = NOW() 
            WHERE id = ?
        ", [$consolidadoId]);
        
        execute("
            UPDATE envios 
            SET estado = 'En camino a Venezuela', fecha_salida_miami = NOW()
            WHERE consolidado_id = ?
        ", [$consolidadoId]);
        
        // Notificar a clientes
        $envios = queryAll("SELECT id FROM envios WHERE consolidado_id = ?", [$consolidadoId]);
        foreach ($envios as $envio) {
            programarNotificacion($envio['id'], 'salida_miami');
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>