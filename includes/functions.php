<?php
/**
 * EXPRESSATECH CARGO - Funciones del Sistema
 * Funciones reutilizables para operaciones comunes
 */

// Prevenir acceso directo
if (!defined('EXPRESSATECH_ACCESS')) {
    die('Acceso directo no permitido');
}

// =====================================================
// FUNCIONES DE USUARIOS
// =====================================================

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT id, nombre, apellido, email, telefono, tipo, tipo_cliente, margen_personalizado 
            FROM usuarios WHERE id = ?";
    return queryOne($sql, [$_SESSION['user_id']]);
}

/**
 * Obtener todos los clientes
 */
function getAllClientes() {
    $sql = "SELECT id, nombre, apellido, email, telefono, tipo_cliente, fecha_registro 
            FROM usuarios 
            WHERE tipo = 'cliente' AND activo = 1 
            ORDER BY nombre, apellido";
    return queryAll($sql);
}

/**
 * Crear nuevo usuario
 */
function createUser($nombre, $apellido, $email, $telefono, $password, $tipo = 'cliente') {
    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
    }
    
    if (!validEmail($email)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres'];
    }
    
    // Verificar si el email ya existe
    $existing = queryOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Este email ya está registrado'];
    }
    
    // Hash de contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario
    $sql = "INSERT INTO usuarios (nombre, apellido, email, telefono, password, tipo) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        execute($sql, [$nombre, $apellido, $email, $telefono, $hashedPassword, $tipo]);
        return ['success' => true, 'message' => 'Usuario registrado exitosamente', 'user_id' => lastInsertId()];
    } catch (Exception $e) {
        logError("Error al crear usuario: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al registrar usuario'];
    }
}

/**
 * Autenticar usuario
 */
function authenticateUser($email, $password) {
    $sql = "SELECT id, nombre, apellido, email, password, tipo FROM usuarios WHERE email = ? AND activo = 1";
    $user = queryOne($sql, [$email]);
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
    }
    
    // Iniciar sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_apellido'] = $user['apellido'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_tipo'] = $user['tipo'];
    $_SESSION['last_activity'] = time();
    
    return ['success' => true, 'message' => 'Login exitoso', 'tipo' => $user['tipo']];
}

/**
 * Cerrar sesión
 */
function logout() {
    session_unset();
    session_destroy();
    redirect('/login.php');
}

// =====================================================
// FUNCIONES DE CATÁLOGO 4LIFE
// =====================================================

/**
 * Obtener todos los productos 4Life
 */
function getProductos4Life() {
    $sql = "SELECT id, nombre_producto FROM catalogo_4life WHERE activo = 1 ORDER BY orden, nombre_producto";
    return queryAll($sql);
}

/**
 * Verificar si una empresa requiere productos 4Life
 */
function requiereProductos4Life($empresaCompra) {
    return (stripos($empresaCompra, '4life') !== false || stripos($empresaCompra, '4 life') !== false);
}

// =====================================================
// FUNCIONES DE ENVÍOS
// =====================================================

/**
 * Crear nuevo envío
 */
function createEnvio($data) {
    // Validaciones básicas
    $required = ['cliente_id', 'fecha_compra', 'empresa_compra'];
    foreach ($required as $field) {
        if (empty($data[$field]) && $field !== 'cliente_id') {
            return ['success' => false, 'message' => 'Faltan campos obligatorios'];
        }
    }
    
    $sql = "INSERT INTO envios (
        cliente_id, fecha_compra, tracking_original, empresa_compra, 
        factura_url, destinatario_nombre, es_envio_admin, es_costo_cero
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        execute($sql, [
            $data['cliente_id'] ?? null,
            $data['fecha_compra'],
            $data['tracking_original'] ?? null,
            $data['empresa_compra'],
            $data['factura_url'] ?? null,
            $data['destinatario_nombre'] ?? null,
            $data['es_envio_admin'] ?? false,
            $data['es_costo_cero'] ?? false
        ]);
        
        $envioId = lastInsertId();
        
        return ['success' => true, 'message' => 'Envío registrado exitosamente', 'envio_id' => $envioId];
    } catch (Exception $e) {
        logError("Error al crear envío: " . $e->getMessage(), $data);
        return ['success' => false, 'message' => 'Error al registrar envío'];
    }
}

/**
 * Agregar productos a un envío
 */
function addProductosEnvio($envioId, $productos) {
    if (empty($productos) || !is_array($productos)) {
        return ['success' => false, 'message' => 'No hay productos para agregar'];
    }
    
    $sql = "INSERT INTO productos (envio_id, nombre_producto, cantidad, detalle) VALUES (?, ?, ?, ?)";
    
    try {
        $db = getDB();
        
        // NO iniciar nueva transacción si ya hay una activa
        // $db->beginTransaction(); // COMENTAR O ELIMINAR ESTA LÍNEA
        
        $stmt = $db->prepare($sql);
        
        $productosAgregados = 0;
        foreach ($productos as $producto) {
            if (empty($producto['nombre']) || empty($producto['cantidad'])) {
                continue;
            }
            
            $result = $stmt->execute([
                $envioId,
                $producto['nombre'],
                $producto['cantidad'],
                $producto['detalle'] ?? null
            ]);
            
            if ($result) {
                $productosAgregados++;
            }
        }
        
        // $db->commit(); // COMENTAR O ELIMINAR ESTA LÍNEA
        
        if ($productosAgregados === 0) {
            return ['success' => false, 'message' => 'No se agregó ningún producto válido'];
        }
        
        return ['success' => true, 'message' => "Productos agregados exitosamente ($productosAgregados items)"];
        
    } catch (Exception $e) {
        // if (isset($db)) { $db->rollBack(); } // COMENTAR O ELIMINAR
        logError("Error al agregar productos: " . $e->getMessage(), ['envio_id' => $envioId, 'productos' => $productos]);
        return ['success' => false, 'message' => 'Error al agregar productos: ' . $e->getMessage()];
    }
}

/**
 * Obtener envíos de un cliente
 */
function getEnviosCliente($clienteId, $estado = null) {
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_productos
            FROM envios e
            WHERE e.cliente_id = ?";
    
    $params = [$clienteId];
    
    if ($estado) {
        $sql .= " AND e.estado = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY e.fecha_registro DESC";
    
    return queryAll($sql, $params);
}

/**
 * Obtener productos de un envío
 */
function getProductosEnvio($envioId) {
    $sql = "SELECT * FROM productos WHERE envio_id = ? ORDER BY id";
    return queryAll($sql, [$envioId]);
}

/**
 * Actualizar estado de envío
 */
function updateEstadoEnvio($envioId, $nuevoEstado, $notificar = true) {
    // Campos de fecha según el estado
    $campoFecha = match($nuevoEstado) {
        'Recibido en Miami' => 'fecha_llegada_miami',
        'Consolidado' => 'fecha_consolidacion',
        'En camino a Venezuela' => 'fecha_salida_miami',
        'Llegada a Aduana' => 'fecha_llegada_aduana',
        'Llegada a Puerto Ordaz' => 'fecha_llegada_puerto_ordaz',
        'Entregado' => 'fecha_entrega_final',
        default => null
    };
    
    $sql = "UPDATE envios SET estado = ?";
    $params = [$nuevoEstado];
    
    if ($campoFecha) {
        $sql .= ", $campoFecha = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $envioId;
    
    try {
        execute($sql, $params);
        
        // Programar notificación si es necesario
        if ($notificar) {
            programarNotificacion($envioId, $nuevoEstado);
        }
        
        return ['success' => true, 'message' => 'Estado actualizado'];
    } catch (Exception $e) {
        logError("Error al actualizar estado: " . $e->getMessage(), ['envio_id' => $envioId, 'estado' => $nuevoEstado]);
        return ['success' => false, 'message' => 'Error al actualizar estado'];
    }
}

// =====================================================
// FUNCIONES DE CONSOLIDADOS
// =====================================================

/**
 * Crear nuevo consolidado
 */
function createConsolidado($data) {
    $sql = "INSERT INTO consolidados (
        numero_consolidado, costo_courier, costo_recoleccion, 
        costo_logistica, costo_manejo, margen_ganancia, notas
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        execute($sql, [
            $data['numero_consolidado'],
            $data['costo_courier'],
            $data['costo_recoleccion'] ?? 0,
            $data['costo_logistica'] ?? 0,
            $data['costo_manejo'] ?? 0,
            $data['margen_ganancia'] ?? 30,
            $data['notas'] ?? null
        ]);
        
        return ['success' => true, 'consolidado_id' => lastInsertId()];
    } catch (Exception $e) {
        logError("Error al crear consolidado: " . $e->getMessage(), $data);
        return ['success' => false, 'message' => 'Error al crear consolidado'];
    }
}

/**
 * Asignar envíos a un consolidado
 */
function asignarEnviosConsolidado($consolidadoId, $enviosIds) {
    if (empty($enviosIds)) {
        return ['success' => false, 'message' => 'No hay envíos para asignar'];
    }
    
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Actualizar envíos
        $placeholders = str_repeat('?,', count($enviosIds) - 1) . '?';
        $sql = "UPDATE envios 
                SET consolidado_id = ?, estado = 'Consolidado', fecha_consolidacion = NOW()
                WHERE id IN ($placeholders)";
        
        $params = array_merge([$consolidadoId], $enviosIds);
        $result = execute($sql, $params);
        
        if (!$result) {
            throw new Exception('Error al actualizar envíos');
        }
        
        // Calcular costos del consolidado
        $calcResult = calcularCostosConsolidado($consolidadoId);
        
        if (!$calcResult['success']) {
            throw new Exception($calcResult['message']);
        }
        
        $db->commit();
        
        return [
            'success' => true, 
            'message' => 'Envíos consolidados exitosamente',
            'datos' => $calcResult['datos'] ?? []
        ];
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        logError("Error al asignar envíos a consolidado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al consolidar envíos: ' . $e->getMessage()];
    }
}

/**
 * Calcular costos de un consolidado y asignar a envíos
 */
function calcularCostosConsolidado($consolidadoId) {
    try {
        $db = getDB();
        
        // Obtener datos del consolidado
        $consolidado = queryOne("SELECT * FROM consolidados WHERE id = ?", [$consolidadoId]);
        
        if (!$consolidado) {
            return ['success' => false, 'message' => 'Consolidado no encontrado'];
        }
        
        // Contar total de productos
        $result = queryOne("
            SELECT COUNT(DISTINCT e.id) as total_envios, SUM(p.cantidad) as suma_productos
            FROM productos p
            INNER JOIN envios e ON p.envio_id = e.id
            WHERE e.consolidado_id = ?
        ", [$consolidadoId]);
        
        $totalProductos = $result['suma_productos'] ?? 0;
        
        if ($totalProductos == 0) {
            return ['success' => false, 'message' => 'No hay productos en este consolidado'];
        }
        
        // Calcular costo por producto
        $costoTotal = $consolidado['costo_total'];
        $costoPorProducto = $costoTotal / $totalProductos;
        
        // Calcular precio de venta con margen base
        $margenBase = $consolidado['margen_ganancia'];
        $precioVentaBase = $costoPorProducto * (1 + ($margenBase / 100));
        
        // Actualizar consolidado
        execute("
            UPDATE consolidados 
            SET total_productos = ?,
                costo_por_producto = ?,
                precio_venta_producto = ?
            WHERE id = ?
        ", [$totalProductos, $costoPorProducto, $precioVentaBase, $consolidadoId]);
        
        // Actualizar precio de productos y calcular costo de cada envío
        $envios = queryAll("
            SELECT DISTINCT e.id, e.cliente_id, u.margen_personalizado
            FROM envios e
            LEFT JOIN usuarios u ON e.cliente_id = u.id
            WHERE e.consolidado_id = ?
        ", [$consolidadoId]);
        
        $totalFacturado = 0;
        
        foreach ($envios as $envio) {
            $envioId = $envio['id'];
            $clienteId = $envio['cliente_id'];
            
            // Verificar si el cliente tiene margen personalizado
            $margenCliente = $margenBase;
            $precioVenta = $precioVentaBase;
            
            if ($clienteId && $envio['margen_personalizado'] !== null) {
                $margenCliente = $envio['margen_personalizado'];
                $precioVenta = $costoPorProducto * (1 + ($margenCliente / 100));
            }
            
            // Actualizar precio unitario de productos de este envío
            execute("
                UPDATE productos 
                SET precio_unitario = ?
                WHERE envio_id = ?
            ", [$precioVenta, $envioId]);
            
            // Calcular costo total del envío
            $costoEnvio = queryOne("
                SELECT SUM(cantidad * precio_unitario) as total
                FROM productos
                WHERE envio_id = ?
            ", [$envioId]);
            
            $costoCalculado = $costoEnvio['total'] ?? 0;
            $totalFacturado += $costoCalculado;
            
            // Actualizar costo calculado del envío
            execute("
                UPDATE envios 
                SET costo_calculado = ?,
                    margen_aplicado = ?,
                    saldo_pendiente = ?
                WHERE id = ?
            ", [
                $costoCalculado,
                $margenCliente,
                $costoCalculado, // saldo_pendiente inicial = costo_calculado
                $envioId
            ]);
            
            // Programar notificación de costo asignado
            if ($clienteId) {
                programarNotificacion($envioId, 'costo_asignado');
            }
        }
        
        // Calcular total facturado del consolidado
        execute("UPDATE consolidados SET total_facturado = ? WHERE id = ?", [
            $totalFacturado,
            $consolidadoId
        ]);
        
        return [
            'success' => true,
            'message' => 'Costos calculados exitosamente',
            'datos' => [
                'total_productos' => $totalProductos,
                'costo_por_producto' => $costoPorProducto,
                'precio_venta' => $precioVentaBase,
                'total_facturado' => $totalFacturado
            ]
        ];
        
    } catch (Exception $e) {
        logError("Error al calcular costos: " . $e->getMessage(), ['consolidado_id' => $consolidadoId]);
        return ['success' => false, 'message' => 'Error al calcular costos: ' . $e->getMessage()];
    }
}

/**
 * Obtener consolidados
 */
function getConsolidados($estado = null) {
    $sql = "SELECT * FROM vista_resumen_consolidados";
    $params = [];
    
    if ($estado) {
        $sql .= " WHERE estado = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY fecha_creacion DESC";
    
    return queryAll($sql, $params);
}

// =====================================================
// FUNCIONES DE PAGOS
// =====================================================

/**
 * Registrar pago
 */
function registrarPago($data) {
    $sql = "INSERT INTO pagos (
        cliente_id, envio_id, monto, metodo, tasa_binance, 
        referencia, comprobante_url
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        execute($sql, [
            $data['cliente_id'],
            $data['envio_id'],
            $data['monto'],
            $data['metodo'],
            $data['tasa_binance'] ?? null,
            $data['referencia'] ?? null,
            $data['comprobante_url'] ?? null
        ]);
        
        return ['success' => true, 'pago_id' => lastInsertId()];
    } catch (Exception $e) {
        logError("Error al registrar pago: " . $e->getMessage(), $data);
        return ['success' => false, 'message' => 'Error al registrar pago'];
    }
}

/**
 * Verificar pago
 */
function verificarPago($pagoId, $adminId, $estado = 'Verificado', $notas = null) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Actualizar estado del pago
        execute("
            UPDATE pagos 
            SET estado = ?, fecha_verificacion = NOW(), verificado_por = ?, notas_verificacion = ?
            WHERE id = ?
        ", [$estado, $adminId, $notas, $pagoId]);
        
        // Si está verificado, actualizar saldo del envío
        if ($estado === 'Verificado') {
            $pago = queryOne("SELECT envio_id, monto FROM pagos WHERE id = ?", [$pagoId]);
            
            execute("
                UPDATE envios 
                SET saldo_pendiente = saldo_pendiente - ?
                WHERE id = ?
            ", [$pago['monto'], $pago['envio_id']]);
        }
        
        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        logError("Error al verificar pago: " . $e->getMessage(), ['pago_id' => $pagoId]);
        return ['success' => false, 'message' => 'Error al verificar pago'];
    }
}

/**
 * Obtener pagos pendientes
 */
function getPagosPendientes() {
    $sql = "SELECT p.*, u.nombre, u.apellido, e.tracking_interno
            FROM pagos p
            INNER JOIN usuarios u ON p.cliente_id = u.id
            INNER JOIN envios e ON p.envio_id = e.id
            WHERE p.estado = 'Pendiente'
            ORDER BY p.fecha_registro DESC";
    return queryAll($sql);
}

// =====================================================
// FUNCIONES DE NOTIFICACIONES
// =====================================================

/**
 * Programar notificación
 */
function programarNotificacion($envioId, $tipo) {
    // Obtener datos del envío y cliente
    $envio = queryOne("
        SELECT e.*, u.email, u.nombre, u.apellido
        FROM envios e
        LEFT JOIN usuarios u ON e.cliente_id = u.id
        WHERE e.id = ?
    ", [$envioId]);
    
    if (!$envio || !$envio['email']) {
        return false;
    }
    
    // Definir asunto y mensaje según el tipo
    $templates = [
        'confirmacion_registro' => [
            'asunto' => 'Confirmación de Registro de Envío',
            'mensaje' => "Hola {$envio['nombre']}, tu envío con tracking {$envio['tracking_interno']} ha sido registrado exitosamente."
        ],
        'llegada_miami' => [
            'asunto' => 'Tu Paquete Llegó a Miami',
            'mensaje' => "¡Buenas noticias! Tu envío {$envio['tracking_interno']} ha llegado a nuestras instalaciones en Miami."
        ],
        'costo_asignado' => [
            'asunto' => 'Costo de Envío Asignado',
            'mensaje' => "El costo de tu envío {$envio['tracking_interno']} ha sido calculado. Total a pagar: $" . number_format($envio['costo_calculado'], 2)
        ],
        'salida_miami' => [
            'asunto' => 'Tu Paquete Salió de Miami',
            'mensaje' => "Tu envío {$envio['tracking_interno']} está en camino a Venezuela."
        ],
        'llegada_aduana' => [
            'asunto' => 'Paquete en Aduana Venezuela',
            'mensaje' => "Tu envío {$envio['tracking_interno']} ha llegado a la aduana en Venezuela."
        ],
        'llegada_puerto_ordaz' => [
            'asunto' => '¡Tu Paquete Llegó a Puerto Ordaz!',
            'mensaje' => "Tu envío {$envio['tracking_interno']} está listo para retiro en Puerto Ordaz."
        ]
    ];
    
    if (!isset($templates[$tipo])) {
        return false;
    }
    
    $template = $templates[$tipo];
    
    try {
        execute("
            INSERT INTO notificaciones (cliente_id, envio_id, tipo, asunto, mensaje, email_destino, fecha_programada)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $envio['cliente_id'],
            $envioId,
            $tipo,
            $template['asunto'],
            $template['mensaje'],
            $envio['email']
        ]);
        
        return true;
    } catch (Exception $e) {
        logError("Error al programar notificación: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// FUNCIONES DE REPORTES
// =====================================================

/**
 * Obtener clientes morosos
 */
function getClientesMorosos() {
    return queryAll("SELECT * FROM vista_clientes_morosos");
}

/**
 * Obtener productos más enviados
 */
function getProductosTop($limit = 20) {
    return queryAll("SELECT * FROM vista_productos_top LIMIT ?", [$limit]);
}

/**
 * Obtener estadísticas generales
 */
function getEstadisticasGenerales() {
    $stats = [];
    
    // Total en caja (pagos verificados)
    $stats['total_caja'] = queryOne("SELECT SUM(monto) as total FROM pagos WHERE estado = 'Verificado'")['total'] ?? 0;
    
    // Total pendiente
    $stats['total_pendiente'] = queryOne("SELECT SUM(saldo_pendiente) as total FROM envios WHERE saldo_pendiente > 0")['total'] ?? 0;
    
    // Envíos en tránsito
    $stats['envios_transito'] = queryOne("SELECT COUNT(*) as total FROM envios WHERE estado NOT IN ('Entregado', 'Pendiente por retiro')")['total'] ?? 0;
    
    // Clientes morosos
    $stats['clientes_morosos'] = queryOne("SELECT COUNT(DISTINCT id) as total FROM vista_clientes_morosos")['total'] ?? 0;
    
    // Consolidados activos
    $stats['consolidados_activos'] = queryOne("SELECT COUNT(*) as total FROM consolidados WHERE estado IN ('Abierto', 'En Tránsito')")['total'] ?? 0;
    
    return $stats;
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

/**
 * Subir archivo
 */
function uploadFile($file, $prefix = '') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir archivo'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande (máx. 5MB)'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    // Crear carpeta si no existe
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generar nombre único
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'url' => '/assets/uploads/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Error al mover archivo'];
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatear moneda
 */
function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

?>