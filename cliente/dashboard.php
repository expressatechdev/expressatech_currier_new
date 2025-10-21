<?php
/**
 * EXPRESSATECH CARGO - Dashboard Cliente
 * Panel principal del cliente
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar que esté logueado y sea cliente
requireLogin();
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

// Variables para el header
$pageTitle = 'Dashboard';
$pageSubtitle = 'Bienvenido a tu panel de control';

// Obtener datos del usuario
$currentUser = getCurrentUser();
$clienteId = $currentUser['id'];

// Obtener estadísticas del cliente
$statsEnvios = queryOne("
    SELECT 
        COUNT(*) as total_envios,
        SUM(CASE WHEN estado NOT IN ('Entregado') THEN 1 ELSE 0 END) as envios_activos,
        SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as envios_entregados
    FROM envios 
    WHERE cliente_id = ?
", [$clienteId]);

$statsFinanzas = queryOne("
    SELECT 
        COALESCE(SUM(costo_calculado), 0) as total_facturado,
        COALESCE(SUM(saldo_pendiente), 0) as total_pendiente
    FROM envios 
    WHERE cliente_id = ? AND costo_calculado > 0
", [$clienteId]);

$statsProductos = queryOne("
    SELECT COUNT(*) as total_productos
    FROM productos p
    INNER JOIN envios e ON p.envio_id = e.id
    WHERE e.cliente_id = ?
", [$clienteId]);

// Obtener envíos recientes
$enviosRecientes = queryAll("
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_productos
    FROM envios e
    WHERE e.cliente_id = ?
    ORDER BY e.fecha_registro DESC
    LIMIT 5
", [$clienteId]);

// Calcular tiempo promedio de entrega (últimos 3 meses)
$tiempoPromedio = queryOne("
    SELECT 
        AVG(DATEDIFF(fecha_entrega_final, fecha_registro)) as dias_promedio
    FROM envios
    WHERE cliente_id = ? 
    AND estado = 'Entregado' 
    AND fecha_entrega_final IS NOT NULL
    AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
", [$clienteId]);

$diasPromedio = $tiempoPromedio['dias_promedio'] ? round($tiempoPromedio['dias_promedio'], 1) : 'N/A';

// Incluir header
include '../includes/header.php';
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <!-- Envíos Activos -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Envíos Activos</span>
            <span class="stat-icon">📦</span>
        </div>
        <div class="stat-value"><?php echo $statsEnvios['envios_activos'] ?? 0; ?></div>
        <div class="stat-change">
            En tránsito o pendientes
        </div>
    </div>
    
    <!-- Total Envíos -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Envíos</span>
            <span class="stat-icon">📊</span>
        </div>
        <div class="stat-value"><?php echo $statsEnvios['total_envios'] ?? 0; ?></div>
        <div class="stat-change positive">
            ✓ <?php echo $statsEnvios['envios_entregados'] ?? 0; ?> entregados
        </div>
    </div>
    
    <!-- Saldo Pendiente -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Saldo Pendiente</span>
            <span class="stat-icon">💰</span>
        </div>
        <div class="stat-value">
            <?php echo formatMoney($statsFinanzas['total_pendiente'] ?? 0); ?>
        </div>
        <div class="stat-change <?php echo $statsFinanzas['total_pendiente'] > 0 ? 'negative' : 'positive'; ?>">
            <?php if ($statsFinanzas['total_pendiente'] > 0): ?>
                ⚠ Pago pendiente
            <?php else: ?>
                ✓ Al día
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tiempo Promedio -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Tiempo Promedio</span>
            <span class="stat-icon">⏱️</span>
        </div>
        <div class="stat-value"><?php echo $diasPromedio; ?></div>
        <div class="stat-change">
            días de entrega
        </div>
    </div>
</div>

<!-- Botones de Acción Rápida -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Acciones Rápidas</h2>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <a href="/cliente/nuevo-envio.php" class="btn btn-primary" style="text-align: center;">
            ➕ Registrar Nuevo Envío
        </a>
        <a href="/cliente/mis-envios.php" class="btn btn-secondary" style="text-align: center;">
            📦 Ver Mis Envíos
        </a>
        <a href="/cliente/mis-pagos.php" class="btn btn-secondary" style="text-align: center;">
            💰 Registrar Pago
        </a>
    </div>
</div>

<!-- Envíos Recientes -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Envíos Recientes</h2>
        <a href="/cliente/mis-envios.php" class="btn btn-secondary">Ver Todos</a>
    </div>
    
    <?php if (empty($enviosRecientes)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No tienes envíos registrados</h3>
            <p>Comienza registrando tu primer envío desde Miami</p>
            <a href="/cliente/nuevo-envio.php" class="btn btn-primary" style="margin-top: 1rem;">
                ➕ Registrar Primer Envío
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tracking</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Costo</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enviosRecientes as $envio): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($envio['tracking_interno']); ?></strong>
                                <?php if ($envio['destinatario_nombre']): ?>
                                    <br><small style="color: var(--gris-text);">
                                        Para: <?php echo htmlspecialchars($envio['destinatario_nombre']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($envio['fecha_registro']); ?></td>
                            <td><?php echo $envio['total_productos']; ?> item(s)</td>
                            <td>
                                <?php
                                $badgeClass = 'badge-info';
                                if ($envio['estado'] === 'Entregado') $badgeClass = 'badge-success';
                                elseif ($envio['estado'] === 'En tránsito') $badgeClass = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $envio['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($envio['costo_calculado'] > 0): ?>
                                    <?php echo formatMoney($envio['costo_calculado']); ?>
                                <?php else: ?>
                                    <span style="color: var(--gris-text);">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($envio['saldo_pendiente'] > 0): ?>
                                    <strong style="color: var(--danger);">
                                        <?php echo formatMoney($envio['saldo_pendiente']); ?>
                                    </strong>
                                <?php else: ?>
                                    <span style="color: var(--success);">✓ Pagado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Información de Ayuda -->
<div class="card" style="background: rgba(255, 196, 37, 0.1); border: 2px solid var(--amarillo-primary);">
    <div class="card-header">
        <h2 class="card-title">💡 ¿Necesitas Ayuda?</h2>
    </div>
    <div style="color: var(--gris-text);">
        <p><strong>¿Cómo funciona el proceso?</strong></p>
        <ol style="margin-left: 1.5rem; line-height: 1.8;">
            <li>Registra tu compra con el tracking de la tienda (Amazon, iHerb, etc.)</li>
            <li>Nosotros recibimos tu paquete en Miami</li>
            <li>Consolidamos con otros envíos para optimizar costos</li>
            <li>Te asignamos el costo final y puedes registrar tu pago</li>
            <li>Enviamos a Venezuela y te notificamos en cada etapa</li>
            <li>Retiras en Puerto Ordaz o te lo enviamos a tu ciudad</li>
        </ol>
        <p style="margin-top: 1rem;">
            <strong>Contacto:</strong> 
            <a href="mailto:contacto@expressatech.net" style="color: var(--amarillo-primary);">
                contacto@expressatech.net
            </a>
        </p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>