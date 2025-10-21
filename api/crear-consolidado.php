<?php
/**
 * EXPRESSATECH CARGO - API Crear Consolidado
 * Crea un nuevo consolidado con los costos especificados
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
    // Capturar datos
    $numeroConsolidado = sanitize($_POST['numero_consolidado'] ?? '');
    $costoCourier = floatval($_POST['costo_courier'] ?? 0);
    $costoRecoleccion = floatval($_POST['costo_recoleccion'] ?? 0);
    $costoLogistica = floatval($_POST['costo_logistica'] ?? 0);
    $costoManejo = floatval($_POST['costo_manejo'] ?? 0);
    $margenGanancia = floatval($_POST['margen_ganancia'] ?? 30);
    $notas = sanitize($_POST['notas'] ?? '');
    
    // Validaciones
    if (empty($numeroConsolidado)) {
        throw new Exception('El número de consolidado es obligatorio');
    }
    
    if ($costoCourier <= 0) {
        throw new Exception('El costo del courier debe ser mayor a 0');
    }
    
    // Verificar que no exista un consolidado con ese número
    $existe = queryOne("SELECT id FROM consolidados WHERE numero_consolidado = ?", [$numeroConsolidado]);
    if ($existe) {
        throw new Exception('Ya existe un consolidado con ese número');
    }
    
    // Crear consolidado
    $result = createConsolidado([
        'numero_consolidado' => $numeroConsolidado,
        'costo_courier' => $costoCourier,
        'costo_recoleccion' => $costoRecoleccion,
        'costo_logistica' => $costoLogistica,
        'costo_manejo' => $costoManejo,
        'margen_ganancia' => $margenGanancia,
        'notas' => $notas
    ]);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Consolidado creado exitosamente',
        'consolidado_id' => $result['consolidado_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>