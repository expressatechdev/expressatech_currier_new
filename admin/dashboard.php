<?php
/**
 * EXPRESSATECH CARGO - Dashboard Admin
 * Panel principal del administrador
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar que esté logueado y sea admin
requireAdmin();

// Variables para el header
$pageTitle = 'Panel de Administración';
$pageSubtitle = 'Control total del sistema logístico';

// Obtener estadísticas generales
$stats = getEstadisticasGenerales();

// Obtener envíos pendientes de verificación
$enviosPendientes = queryAll("
    SELECT 
        e.*,
        u.nombre,
        u.apellido,
        u.email,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE e.estado IN ('En tránsito', 'Recibido en Miami')
    ORDER BY e.fecha_registro DESC
    LIMIT 10
");

// Obtener pagos pendientes de verificación
$pagosPendientes = getPagosPendientes();

// Obtener clientes morosos
$clientesMorosos = getClientesMorosos();

// Obtener consolidados activos
$consolidadosActivos = queryAll("
    SELECT 
        c.*,
        COUNT(DISTINCT e.id) as total_envios,
        COUNT(DISTINCT e.cliente_id) as total_clientes
    FROM consolidados c
    LEFT JOIN envios e ON c.id = e.consolidado_id
    WHERE c.estado IN ('Abierto', 'En Tránsito')
    GROUP BY c.id
    ORDER BY c.fecha_creacion DESC
    LIMIT 5
");

// Productos más enviados (top 5)
$productosTop = queryAll("
    SELECT * FROM vista_productos_top LIMIT 5
");

// Incluir header
include '../includes/header.php';
?>

<!-- Stats Cards Principales -->
<div class="stats-grid">
    <!-- Total en Caja -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total en Caja</span>
            <span class="stat-icon">💰</span>
        </div>
        <div class="stat-value"><?php echo formatMoney($stats['total_caja']); ?></div>
        <div class="stat-change positive">
            ✓ Pagos verificados
        </div>
    </div>
    
    <!-- Pendiente por Cobrar -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Por Cobrar</span>
            <span class="stat-icon">⏳</span>
        </div>
        <div class="stat-value"><?php echo formatMoney($stats['total_pendiente']); ?></div>
        <div class="stat-change <?php echo $stats['total_pendiente'] > 0 ? 'negative' : 'positive'; ?>">
            <?php echo $stats['clientes_morosos']; ?> cliente(s) con saldo
        </div>
    </div>
    
    <!-- Envíos en Tránsito -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">En Tránsito</span>
            <span class="stat-icon">🚚</span>
        </div>
        <div class="stat-value"><?php echo $stats['envios_transito']; ?></div>
        <div class="stat-change">
            envíos activos
        </div>
    </div>
    
    <!-- Consolidados Activos -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Consolidados</span>
            <span class="stat-icon">📊</span>
        </div>
        <div class="stat-value"><?php echo $stats['consolidados_activos']; ?></div>
        <div class="stat-change">
            activos
        </div>
    </div>
</div>

<!-- Botones de Acción Rápida -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Acciones Rápidas</h2>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <a href="/admin/envios.php?action=nuevo" class="btn btn-primary" style="text-align: center;">
            ➕ Registrar Envío
        </a>
        <a href="/admin/consolidados.php?action=nuevo" class="btn btn-primary" style="text-align: center;">
            📦 Crear Consolidado
        </a>
        <a href="/admin/verificar-pagos.php" class="btn btn-secondary" style="text-align: center;">
            💰 Verificar Pagos
        </a>
        <a href="/admin/reportes.php" class="btn btn-secondary" style="text-align: center;">
            📈 Ver Reportes
        </a>
    </div>
</div>

<!-- Grid de 2 Columnas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
    
    <!-- Envíos Pendientes -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⚠️ Envíos Recientes</h2>
            <a href="/admin/envios.php" class="btn btn-secondary">Ver Todos</a>
        </div>
        
        <?php if (empty($enviosPendientes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✓</div>
                <p>No hay envíos pendientes</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tracking</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enviosPendientes as $envio): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($envio['tracking_interno']); ?></strong>
                                    <br><small style="color: var(--gris-text);">
                                        <?php echo formatDate($envio['fecha_registro']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($envio['nombre']): ?>
                                        <?php echo htmlspecialchars($envio['nombre'] . ' ' . $envio['apellido']); ?>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?php echo $envio['estado']; ?>
                                    </span>
                                </td>
                                <td><?php echo $envio['total_productos']; ?> items</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagos Pendientes -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">💳 Pagos por Verificar</h2>
            <a href="/admin/verificar-pagos.php" class="btn btn-secondary">Ver Todos</a>
        </div>
        
        <?php if (empty($pagosPendientes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✓</div>
                <p>No hay pagos pendientes</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tracking</th>
                            <th>Monto</th>
                            <th>Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagosPendientes as $pago): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pago['nombre'] . ' ' . $pago['apellido']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pago['tracking_interno']); ?></strong>
                                </td>
                                <td>
                                    <strong style="color: var(--amarillo-primary);">
                                        <?php echo formatMoney($pago['monto']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $pago['metodo']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clientes Morosos -->
<?php if (!empty($clientesMorosos)): ?>
<div class="card" style="background: rgba(244, 67, 54, 0.1); border: 2px solid var(--danger);">
    <div class="card-header">
        <h2 class="card-title">⚠️ Clientes con Saldo Pendiente</h2>
        <span class="badge badge-danger"><?php echo count($clientesMorosos); ?> cliente(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Email</th>
                    <th>Envíos Pendientes</th>
                    <th>Total Deuda</th>
                    <th>Días sin Pagar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientesMorosos as $moroso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($moroso['nombre'] . ' ' . $moroso['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($moroso['email']); ?></td>
                        <td><?php echo $moroso['envios_pendientes']; ?></td>
                        <td>
                            <strong style="color: var(--danger);">
                                <?php echo formatMoney($moroso['total_deuda']); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="badge <?php echo $moroso['dias_sin_pagar'] > 30 ? 'badge-danger' : 'badge-warning'; ?>">
                                <?php echo $moroso['dias_sin_pagar']; ?> días
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Consolidados Activos -->
<?php if (!empty($consolidadosActivos)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Consolidados Activos</h2>
        <a href="/admin/consolidados.php" class="btn btn-secondary">Ver Todos</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Costo Total</th>
                    <th>Productos</th>
                    <th>Envíos</th>
                    <th>Clientes</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consolidadosActivos as $cons): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cons['numero_consolidado']); ?></strong></td>
                        <td><?php echo formatMoney($cons['costo_total']); ?></td>
                        <td><?php echo $cons['total_productos']; ?></td>
                        <td><?php echo $cons['total_envios']; ?></td>
                        <td><?php echo $cons['total_clientes']; ?></td>
                        <td>
                            <span class="badge badge-info"><?php echo $cons['estado']; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Productos Más Enviados -->
<?php if (!empty($productosTop)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏆 Productos Más Enviados</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Unidades Totales</th>
                    <th>Envíos</th>
                    <th>Clientes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productosTop as $index => $prod): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--amarillo-primary);"><?php echo $index + 1; ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($prod['nombre_producto']); ?></td>
                        <td><strong><?php echo $prod['total_unidades']; ?></strong></td>
                        <td><?php echo $prod['total_envios']; ?></td>
                        <td><?php echo $prod['total_clientes']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>