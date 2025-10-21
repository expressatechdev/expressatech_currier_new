<?php
/**
 * EXPRESSATECH CARGO - Gestión de Pagos (Admin)
 * Dashboard para verificar pagos de clientes
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestión de Pagos';
$pageSubtitle = 'Verificar pagos de clientes';

// Filtros
$filtroEstado = $_GET['estado'] ?? 'Pendiente';

// Obtener pagos
$sql = "
    SELECT 
        p.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        u.email as cliente_email,
        e.tracking_interno,
        e.costo_calculado,
        e.saldo_pendiente
    FROM pagos p
    INNER JOIN usuarios u ON p.cliente_id = u.id
    INNER JOIN envios e ON p.envio_id = e.id
    WHERE 1=1
";

$params = [];

if ($filtroEstado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtroEstado;
}

$sql .= " ORDER BY p.fecha_registro DESC LIMIT 100";

$pagos = queryAll($sql, $params);

// Estadísticas
$stats = queryOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'Verificado' THEN 1 ELSE 0 END) as verificados,
        SUM(CASE WHEN estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazados,
        SUM(CASE WHEN estado = 'Pendiente' THEN monto ELSE 0 END) as monto_pendiente,
        SUM(CASE WHEN estado = 'Verificado' THEN monto ELSE 0 END) as monto_verificado
    FROM pagos
    WHERE DATE(fecha_registro) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

include '../includes/header.php';
?>

<style>
.pago-card {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid var(--amarillo-primary);
}

.pago-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.pago-info {
    flex: 1;
}

.pago-cliente {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--blanco);
    margin-bottom: 0.5rem;
}

.pago-meta {
    color: var(--gris-text);
    font-size: 0.9rem;
}

.pago-monto {
    text-align: right;
}

.monto-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}

.metodo-badge {
    background: var(--negro-light);
    color: var(--amarillo-primary);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-block;
    margin-top: 0.5rem;
}

.pago-body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #444;
}

.pago-detail {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    color: var(--gris-text);
    font-size: 0.85rem;
}

.detail-value {
    color: var(--blanco);
    font-weight: 600;
}

.pago-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #444;
    flex-wrap: wrap;
}

.comprobante-preview {
    max-width: 100%;
    border-radius: 8px;
    margin-top: 1rem;
    cursor: pointer;
    transition: transform 0.3s;
}

.comprobante-preview:hover {
    transform: scale(1.02);
}

.modal-verificar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-verificar.active {
    display: flex;
}

.modal-content-verificar {
    background: var(--negro-card);
    padding: 2rem;
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
}
</style>

<!-- Estadísticas -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Pagos Pendientes</span>
            <span class="stat-icon">⏳</span>
        </div>
        <div class="stat-value"><?php echo $stats['pendientes'] ?? 0; ?></div>
        <div class="stat-change" style="color: var(--amarillo-primary);">
            <?php echo formatMoney($stats['monto_pendiente'] ?? 0); ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Verificados</span>
            <span class="stat-icon">✓</span>
        </div>
        <div class="stat-value"><?php echo $stats['verificados'] ?? 0; ?></div>
        <div class="stat-change" style="color: var(--success);">
            <?php echo formatMoney($stats['monto_verificado'] ?? 0); ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Rechazados</span>
            <span class="stat-icon">✕</span>
        </div>
        <div class="stat-value"><?php echo $stats['rechazados'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total (30 días)</span>
            <span class="stat-icon">📊</span>
        </div>
        <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="padding: 1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <label style="color: var(--gris-text);">Filtrar por estado:</label>
        <select 
            class="form-input" 
            style="max-width: 200px;"
            onchange="window.location.href='?estado=' + this.value"
        >
            <option value="">Todos</option>
            <option value="Pendiente" <?php echo $filtroEstado === 'Pendiente' ? 'selected' : ''; ?>>Pendientes</option>
            <option value="Verificado" <?php echo $filtroEstado === 'Verificado' ? 'selected' : ''; ?>>Verificados</option>
            <option value="Rechazado" <?php echo $filtroEstado === 'Rechazado' ? 'selected' : ''; ?>>Rechazados</option>
        </select>
    </div>
</div>

<!-- Lista de Pagos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💳 Pagos Registrados</h2>
        <span class="badge badge-info"><?php echo count($pagos); ?> pagos</span>
    </div>
    
    <?php if (empty($pagos)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <h3>No hay pagos <?php echo $filtroEstado ? 'con este estado' : 'registrados'; ?></h3>
            <?php if ($filtroEstado): ?>
                <a href="?">Ver todos los pagos</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($pagos as $pago): 
            $badgeClass = 'badge-warning';
            if ($pago['estado'] === 'Verificado') $badgeClass = 'badge-success';
            elseif ($pago['estado'] === 'Rechazado') $badgeClass = 'badge-danger';
        ?>
            <div class="pago-card">
                <div class="pago-header">
                    <div class="pago-info">
                        <div class="pago-cliente">
                            <?php echo htmlspecialchars($pago['cliente_nombre'] . ' ' . $pago['cliente_apellido']); ?>
                        </div>
                        <div class="pago-meta">
                            📦 Envío: <strong><?php echo htmlspecialchars($pago['tracking_interno']); ?></strong>
                            <br>
                            📅 <?php echo formatDate($pago['fecha_registro'], 'd/m/Y H:i'); ?>
                            <br>
                            📧 <?php echo htmlspecialchars($pago['cliente_email']); ?>
                        </div>
                        <div class="metodo-badge">
                            <?php echo htmlspecialchars($pago['metodo']); ?>
                        </div>
                    </div>
                    
                    <div class="pago-monto">
                        <div class="monto-value"><?php echo formatMoney($pago['monto']); ?></div>
                        <span class="badge <?php echo $badgeClass; ?>" style="margin-top: 0.5rem;">
                            <?php echo $pago['estado']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="pago-body">
                    <div class="pago-detail">
                        <span class="detail-label">Costo Total Envío</span>
                        <span class="detail-value"><?php echo formatMoney($pago['costo_calculado']); ?></span>
                    </div>
                    
                    <div class="pago-detail">
                        <span class="detail-label">Saldo Pendiente</span>
                        <span class="detail-value" style="color: <?php echo $pago['saldo_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>">
                            <?php echo formatMoney($pago['saldo_pendiente']); ?>
                        </span>
                    </div>
                    
                    <?php if ($pago['referencia']): ?>
                    <div class="pago-detail">
                        <span class="detail-label">Referencia</span>
                        <span class="detail-value"><?php echo htmlspecialchars($pago['referencia']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pago['tasa_binance']): ?>
                    <div class="pago-detail">
                        <span class="detail-label">Tasa Binance</span>
                        <span class="detail-value">Bs <?php echo number_format($pago['tasa_binance'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($pago['notas_verificacion']): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--negro-light); border-radius: 6px;">
                    <div style="color: var(--gris-text); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        Comentarios del cliente:
                    </div>
                    <div style="color: var(--blanco);">
                        <?php echo nl2br(htmlspecialchars($pago['notas_verificacion'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Comprobante -->
                <?php if ($pago['comprobante_url']): ?>
                <div style="margin-top: 1rem;">
                    <div style="color: var(--gris-text); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        Comprobante:
                    </div>
                    <a href="<?php echo htmlspecialchars($pago['comprobante_url']); ?>" target="_blank">
                        <img 
                            src="<?php echo htmlspecialchars($pago['comprobante_url']); ?>" 
                            class="comprobante-preview"
                            style="max-height: 300px;"
                            alt="Comprobante"
                        >
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Acciones -->
                <?php if ($pago['estado'] === 'Pendiente'): ?>
                <div class="pago-actions">
                    <button 
                        onclick="verificarPago(<?php echo $pago['id']; ?>, 'Verificado')" 
                        class="btn btn-primary"
                    >
                        ✓ Verificar Pago
                    </button>
                    <button 
                        onclick="verificarPago(<?php echo $pago['id']; ?>, 'Rechazado')" 
                        class="btn btn-secondary"
                        style="background: var(--danger);"
                    >
                        ✕ Rechazar Pago
                    </button>
                    <a href="/admin/detalle-envio.php?id=<?php echo $pago['envio_id']; ?>" class="btn btn-secondary">
                        Ver Envío
                    </a>
                </div>
                <?php elseif ($pago['estado'] === 'Verificado'): ?>
                <div class="pago-actions">
                    <div style="color: var(--success); font-weight: 600;">
                        ✓ Verificado por: Admin
                        <?php if ($pago['fecha_verificacion']): ?>
                            el <?php echo formatDate($pago['fecha_verificacion'], 'd/m/Y H:i'); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($pago['estado'] === 'Rechazado'): ?>
                <div class="pago-actions">
                    <div style="color: var(--danger); font-weight: 600;">
                        ✕ Rechazado
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
async function verificarPago(pagoId, nuevoEstado) {
    const accion = nuevoEstado === 'Verificado' ? 'verificar' : 'rechazar';
    const mensaje = nuevoEstado === 'Verificado' 
        ? '¿Verificar este pago? Se actualizará el saldo del envío.' 
        : '¿Rechazar este pago?';
    
    if (!confirm(mensaje)) {
        return;
    }
    
    try {
        const response = await fetch('/api/verificar-pago.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                pago_id: pagoId,
                nuevo_estado: nuevoEstado
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert(
                nuevoEstado === 'Verificado' ? 'Pago verificado exitosamente' : 'Pago rechazado', 
                'success'
            );
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        window.ExpressatechCargo.showAlert(error.message, 'danger');
    }
}
</script>

<?php include '../includes/footer.php'; ?>