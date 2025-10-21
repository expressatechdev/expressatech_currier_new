<?php
/**
 * EXPRESSATECH CARGO - API Registrar Pago
 * Procesa el registro de un pago por parte del cliente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Solo clientes autenticados
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Capturar datos
    $clienteId = intval($_POST['cliente_id'] ?? 0);
    $envioId = intval($_POST['envio_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodo = sanitize($_POST['metodo'] ?? '');
    $tasaBinance = isset($_POST['tasa_binance']) ? floatval($_POST['tasa_binance']) : null;
    $referencia = sanitize($_POST['referencia'] ?? '');
    $comentarios = sanitize($_POST['comentarios'] ?? '');
    
    logError("Registrando pago - Cliente: $clienteId, Envío: $envioId, Monto: $monto, Método: $metodo");
    
    // Verificar que el usuario actual es el dueño del envío
    $currentUser = getCurrentUser();
    if ($clienteId != $currentUser['id']) {
        throw new Exception('No autorizado para este envío');
    }
    
    // Validaciones
    if ($monto <= 0) {
        throw new Exception('El monto debe ser mayor a 0');
    }
    
    if (empty($metodo)) {
        throw new Exception('Debes seleccionar un método de pago');
    }
    
    $metodosValidos = ['Zelle', 'Zinli', 'Dólares Banesco', 'Binance USDT', 'Bolívares'];
    if (!in_array($metodo, $metodosValidos)) {
        throw new Exception('Método de pago inválido');
    }
    
    // Verificar que el envío existe y pertenece al cliente
    $envio = queryOne("
        SELECT costo_calculado, saldo_pendiente 
        FROM envios 
        WHERE id = ? AND cliente_id = ?
    ", [$envioId, $clienteId]);
    
    if (!$envio) {
        throw new Exception('Envío no encontrado');
    }
    
    if ($envio['costo_calculado'] <= 0) {
        throw new Exception('Este envío no tiene costo asignado');
    }
    
    // Calcular saldo real (considerando pagos ya registrados)
    $pagosRegistrados = queryOne("
        SELECT COALESCE(SUM(monto), 0) as total 
        FROM pagos 
        WHERE envio_id = ? AND estado IN ('Pendiente', 'Verificado')
    ", [$envioId]);
    
    $saldoReal = $envio['costo_calculado'] - $pagosRegistrados['total'];
    
    if ($monto > $saldoReal) {
        throw new Exception("El monto excede el saldo pendiente ($" . number_format($saldoReal, 2) . ")");
    }
    
    // Procesar upload de comprobante
    $comprobanteUrl = '';
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        logError("Procesando comprobante...");
        $uploadResult = uploadFile($_FILES['comprobante'], 'pago_' . time());
        if ($uploadResult['success']) {
            $comprobanteUrl = $uploadResult['url'];
            logError("Comprobante subido: " . $comprobanteUrl);
        } else {
            throw new Exception('Error al subir comprobante: ' . $uploadResult['message']);
        }
    } else {
        throw new Exception('El comprobante de pago es obligatorio');
    }
    
    // Insertar pago
    $sql = "INSERT INTO pagos (
        cliente_id, 
        envio_id, 
        monto, 
        metodo, 
        tasa_binance, 
        referencia, 
        comprobante_url, 
        estado,
        notas_verificacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', ?)";
    
    $result = execute($sql, [
        $clienteId,
        $envioId,
        $monto,
        $metodo,
        $tasaBinance,
        $referencia,
        $comprobanteUrl,
        $comentarios
    ]);
    
    if (!$result) {
        throw new Exception('Error al registrar el pago');
    }
    
    $pagoId = $db->lastInsertId();
    
    logError("Pago registrado con ID: $pagoId");
    
    // Actualizar saldo pendiente del envío (solo si el pago es verificado automáticamente)
    // Por ahora dejamos pendiente hasta que el admin verifique
    
    // Notificar al admin (preparado para futuro)
    // notificarAdminNuevoPago($pagoId);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pago registrado exitosamente',
        'pago_id' => $pagoId
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    logError("ERROR al registrar pago: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>