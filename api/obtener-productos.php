<?php
/**
 * EXPRESSATECH CARGO - API Obtener Productos (CORREGIDO)
 */

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tipo = $_GET['tipo'] ?? 'general';
    
    // Solo devolver productos si es 4Life
    if ($tipo === '4life') {
        $productos = queryAll(
            "SELECT id, nombre_producto FROM catalogo_4life WHERE activo = 1 ORDER BY orden ASC, nombre_producto ASC"
        );
    } else {
        // Para otras empresas, devolver array vacío (usarán texto libre)
        $productos = [];
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'tipo' => $tipo,
        'total' => count($productos)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>