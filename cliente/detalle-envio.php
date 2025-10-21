<?php
/**
 * EXPRESSATECH CARGO - Detalle de Envío (Cliente)
 * Vista completa de un envío específico
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$currentUser = getCurrentUser();
$envioId = $_GET['id'] ?? 0;

// Obtener datos del envío
$envio = queryOne("
    SELECT e.* 
    FROM envios e
    WHERE e.id = ? AND e.cliente_id = ?
", [$envioId, $currentUser['id']]);

if (!$envio) {
    redirect('/cliente/mis-envios.php');
}

// Obtener productos del envío
$productos = getProductosEnvio($envioId);

// Obtener pagos del envío
$pagos = queryAll("
    SELECT * FROM pagos 
    WHERE envio_id = ? 
    ORDER BY fecha_registro DESC
", [$envioId]);

$pageTitle = 'Detalle de Envío';
$pageSubtitle = $envio['tracking_interno'];

include '../includes/header.php';
?>

<style>
.timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    position: relative;
}

.timeline-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #444;
    border: 3px solid var(--negro-light);
    flex-shrink: 0;
    margin-top: 4px;
    z-index: 2;
}

.timeline-dot.active {
    background: var(--amarillo-primary);
    box-shadow: 0 0 10px var(--amarillo-primary);
}

.timeline-line {
    position: absolute;
    left: 7px;
    top: 20px;
    bottom: -20px;
    width: 2px;
    background: #444;
}

.timeline-content {
    flex: 1;
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
}

.timeline-date {
    color: var(--amarillo-primary);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.timeline-title {
    color: var(--blanco);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.timeline-description {
    color: var(--gris-text);
    font-size: 0.9rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-card {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 12px;
    border-left: 4px solid var(--amarillo-primary);
}

.detail-title {
    color: var(--gris-text);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
}

.detail-value {
    color: var(--blanco);
    font-size: 1.1rem;
    font-weight: 600;
}

.productos-list {
    list-style: none;
}

.producto-item {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.producto-name {
    color: var(--blanco);
    font-weight: 600;
}

.producto-detalle {
    color: var(--gris-text);
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.producto-cantidad {
    background: var(--amarillo-primary);
    color: var(--negro-primary);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 700;
}
</style>

<!-- Botón Volver -->
<div style="margin-bottom: 1rem;">
    <a href="/cliente/mis-envios.php" class="btn btn-secondary">
        ← Volver a Mis Envíos
    </a>
</div>

<!-- Grid de Detalles -->
<div class="detail-grid">
    <!-- Tracking -->
    <div class="detail-card">
        <div class="detail-title">📦 Tracking Interno</div>
        <div class="detail-value"><?php echo htmlspecialchars($envio['tracking_interno']); ?></div>
    </div>
    
    <!-- Estado -->
    <div class="detail-card">
        <div class="detail-title">📍 Estado Actual</div>
        <div class="detail-value">
            <?php
            $badgeClass = 'badge-info';
            if ($envio['estado'] === 'Entregado') $badgeClass = 'badge-success';
            elseif ($envio['estado'] === 'En tránsito') $badgeClass = 'badge-warning';
            ?>
            <span class="badge <?php echo $badgeClass; ?>"><?php echo $envio['estado']; ?></span>
        </div>
    </div>
    
    <!-- Empresa -->
    <div class="detail-card">
        <div class="detail-title">🏪 Tienda de Compra</div>
        <div class="detail-value"><?php echo htmlspecialchars($envio['empresa_compra']); ?></div>
    </div>
    
    <!-- Fecha Registro -->
    <div class="detail-card">
        <div class="detail-title">📅 Fecha de Registro</div>
        <div class="detail-value"><?php echo formatDate($envio['fecha_registro'], 'd/m/Y H:i'); ?></div>
    </div>
</div>

<!-- Información Financiera -->
<?php if ($envio['costo_calculado'] > 0): ?>
<div class="card" style="background: rgba(255, 196, 37, 0.1); border: 2px solid var(--amarillo-primary);">
    <div class="card-header">
        <h2 class="card-title">💰 Información de Pago</h2>
    </div>
    
    <div class="detail-grid">
        <div class="detail-card" style="border-left-color: var(--success);">
            <div class="detail-title">Costo Total</div>
            <div class="detail-value" style="color: var(--amarillo-primary); font-size: 1.5rem;">
                <?php echo formatMoney($envio['costo_calculado']); ?>
            </div>
        </div>
        
        <div class="detail-card" style="border-left-color: <?php echo $envio['saldo_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">
            <div class="detail-title">Saldo Pendiente</div>
            <div class="detail-value" style="color: <?php echo $envio['saldo_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>; font-size: 1.5rem;">
                <?php if ($envio['saldo_pendiente'] > 0): ?>
                    <?php echo formatMoney($envio['saldo_pendiente']); ?>
                <?php else: ?>
                    ✓ Pagado
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($envio['saldo_pendiente'] > 0): ?>
        <div style="margin-top: 1rem;">
            <a href="/cliente/registrar-pago.php?envio=<?php echo $envio['id']; ?>" class="btn btn-primary">
                💳 Registrar Pago
            </a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Productos del Envío -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏷️ Productos en este Envío</h2>
        <span class="badge badge-info"><?php echo count($productos); ?> items</span>
    </div>
    
    <?php if (empty($productos)): ?>
        <p style="color: var(--gris-text); text-align: center; padding: 2rem;">
            No hay productos registrados
        </p>
    <?php else: ?>
        <ul class="productos-list">
            <?php foreach ($productos as $prod): ?>
                <li class="producto-item">
                    <div>
                        <div class="producto-name">
                            <?php echo htmlspecialchars($prod['nombre_producto']); ?>
                        </div>
                        <?php if ($prod['detalle']): ?>
                            <div class="producto-detalle">
                                <?php echo htmlspecialchars($prod['detalle']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="producto-cantidad">
                        <?php echo $prod['cantidad']; ?>x
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Timeline del Envío -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🛣️ Historial de Seguimiento</h2>
    </div>
    
    <div class="timeline">
        <?php
        $timelineItems = [];
        
        if ($envio['fecha_registro']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_registro'],
                'titulo' => 'Envío Registrado',
                'descripcion' => 'Tu envío fue registrado en nuestro sistema',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_miami']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_miami'],
                'titulo' => 'Recibido en Miami',
                'descripcion' => 'Tu paquete llegó a nuestras instalaciones en Miami',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_consolidacion']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_consolidacion'],
                'titulo' => 'Consolidado',
                'descripcion' => 'Tu envío fue consolidado y el costo fue calculado',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_salida_miami']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_salida_miami'],
                'titulo' => 'Salió de Miami',
                'descripcion' => 'Tu paquete está en camino a Venezuela',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_aduana']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_aduana'],
                'titulo' => 'Llegada a Aduana',
                'descripcion' => 'Tu paquete llegó a la aduana en Venezuela',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_puerto_ordaz']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_puerto_ordaz'],
                'titulo' => 'Llegada a Puerto Ordaz',
                'descripcion' => 'Tu paquete está listo para retiro o envío final',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_entrega_final']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_entrega_final'],
                'titulo' => 'Entregado',
                'descripcion' => 'Tu paquete fue entregado exitosamente',
                'activo' => true
            ];
        }
        
        if (empty($timelineItems)) {
            echo '<p style="color: var(--gris-text); text-align: center;">Aún no hay actualizaciones de seguimiento</p>';
        } else {
            foreach ($timelineItems as $index => $item):
        ?>
            <div class="timeline-item">
                <div class="timeline-dot <?php echo $item['activo'] ? 'active' : ''; ?>"></div>
                <?php if ($index < count($timelineItems) - 1): ?>
                    <div class="timeline-line"></div>
                <?php endif; ?>
                <div class="timeline-content">
                    <div class="timeline-date">
                        <?php echo formatDate($item['fecha'], 'd/m/Y H:i'); ?>
                    </div>
                    <div class="timeline-title"><?php echo $item['titulo']; ?></div>
                    <div class="timeline-description"><?php echo $item['descripcion']; ?></div>
                </div>
            </div>
        <?php 
            endforeach;
        }
        ?>
    </div>
</div>

<!-- Destinatario y Entrega -->
<?php if ($envio['destinatario_nombre'] || $envio['direccion_final']): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📍 Información de Entrega</h2>
    </div>
    
    <div class="detail-grid">
        <?php if ($envio['destinatario_nombre']): ?>
            <div class="detail-card">
                <div class="detail-title">Destinatario</div>
                <div class="detail-value"><?php echo htmlspecialchars($envio['destinatario_nombre']); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($envio['persona_retira']): ?>
            <div class="detail-card">
                <div class="detail-title">Persona que Retira</div>
                <div class="detail-value"><?php echo htmlspecialchars($envio['persona_retira']); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($envio['empresa_reenvio']): ?>
            <div class="detail-card">
                <div class="detail-title">Empresa de Reenvío</div>
                <div class="detail-value"><?php echo htmlspecialchars($envio['empresa_reenvio']); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($envio['ciudad_destino']): ?>
            <div class="detail-card">
                <div class="detail-title">Ciudad Destino</div>
                <div class="detail-value"><?php echo htmlspecialchars($envio['ciudad_destino']); ?></div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($envio['direccion_final']): ?>
        <div style="margin-top: 1rem;">
            <div class="detail-title">Dirección Completa</div>
            <div style="color: var(--blanco); margin-top: 0.5rem;">
                <?php echo nl2br(htmlspecialchars($envio['direccion_final'])); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Factura -->
<?php if ($envio['factura_url']): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📄 Comprobante de Compra</h2>
    </div>
    <div style="text-align: center; padding: 1rem;">
        <a href="<?php echo htmlspecialchars($envio['factura_url']); ?>" 
           target="_blank" 
           class="btn btn-secondary">
            📥 Ver Factura
        </a>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>