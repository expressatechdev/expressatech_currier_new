<?php
/**
 * EXPRESSATECH CARGO - API Verificar Pago
 * Permite al admin aprobar o rechazar pagos
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
    $pagoId = $data['pago_id'] ?? 0;
    $nuevoEstado = $data['nuevo_estado'] ?? '';
    
    if (empty($pagoId) || empty($nuevoEstado)) {
        throw new Exception('Datos incompletos');
    }
    
    // Validar estado
    if (!in_array($nuevoEstado, ['Verificado', 'Rechazado'])) {
        throw new Exception('Estado inválido');
    }
    
    $db = getDB();
    $db->beginTransaction();
    
    // Obtener datos del pago
    $pago = queryOne("SELECT * FROM pagos WHERE id = ?", [$pagoId]);
    
    if (!$pago) {
        throw new Exception('Pago no encontrado');
    }
    
    if ($pago['estado'] !== 'Pendiente') {
        throw new Exception('Este pago ya fue procesado');
    }
    
    $adminId = $_SESSION['user_id'];
    
    // Actualizar estado del pago
    execute("
        UPDATE pagos 
        SET estado = ?, 
            fecha_verificacion = NOW(), 
            verificado_por = ?
        WHERE id = ?
    ", [$nuevoEstado, $adminId, $pagoId]);
    
    // Si se verificó el pago, actualizar saldo del envío
    if ($nuevoEstado === 'Verificado') {
        // Calcular nuevo saldo
        $totalPagadoVerificado = queryOne("
            SELECT COALESCE(SUM(monto), 0) as total 
            FROM pagos 
            WHERE envio_id = ? AND estado = 'Verificado'
        ", [$pago['envio_id']]);
        
        $envio = queryOne("
            SELECT costo_calculado 
            FROM envios 
            WHERE id = ?
        ", [$pago['envio_id']]);
        
        $nuevoSaldo = $envio['costo_calculado'] - $totalPagadoVerificado['total'];
        
        // Actualizar saldo del envío
        execute("
            UPDATE envios 
            SET saldo_pendiente = ? 
            WHERE id = ?
        ", [$nuevoSaldo, $pago['envio_id']]);
        
        logError("Pago $pagoId verificado. Nuevo saldo del envío {$pago['envio_id']}: $nuevoSaldo");
        
        // Actualizar total_pagado del consolidado si existe
        $envioData = queryOne("SELECT consolidado_id FROM envios WHERE id = ?", [$pago['envio_id']]);
        if ($envioData['consolidado_id']) {
            $totalPagadoConsolidado = queryOne("
                SELECT COALESCE(SUM(p.monto), 0) as total
                FROM pagos p
                INNER JOIN envios e ON p.envio_id = e.id
                WHERE e.consolidado_id = ? AND p.estado = 'Verificado'
            ", [$envioData['consolidado_id']]);
            
            execute("
                UPDATE consolidados 
                SET total_pagado = ? 
                WHERE id = ?
            ", [$totalPagadoConsolidado['total'], $envioData['consolidado_id']]);
        }
        
        // Notificar al cliente (preparado para futuro)
        // programarNotificacion($pago['envio_id'], 'pago_verificado');
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pago ' . ($nuevoEstado === 'Verificado' ? 'verificado' : 'rechazado') . ' exitosamente'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    logError("ERROR al verificar pago: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>