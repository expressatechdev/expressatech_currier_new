<?php
/**
 * EXPRESSATECH CARGO - Detalle de Envío (Admin)
 * Vista completa con historial de pagos
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$envioId = $_GET['id'] ?? 0;

// Obtener datos del envío
$envio = queryOne("
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        u.email as cliente_email,
        u.telefono as cliente_telefono,
        c.numero_consolidado,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    LEFT JOIN consolidados c ON e.consolidado_id = c.id
    WHERE e.id = ?
", [$envioId]);

if (!$envio) {
    redirect('/admin/envios.php');
}

// Obtener productos
$productos = getProductosEnvio($envioId);

// Obtener pagos del envío
$pagos = queryAll("
    SELECT p.*, 
           u.nombre as verificador_nombre,
           u.apellido as verificador_apellido
    FROM pagos p
    LEFT JOIN usuarios u ON p.verificado_por = u.id
    WHERE p.envio_id = ?
    ORDER BY p.fecha_registro DESC
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

.pago-item {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border-left: 4px solid var(--amarillo-primary);
}

.pago-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.pago-monto {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}
</style>

<!-- Botón Volver -->
<div style="margin-bottom: 1rem;">
    <a href="/admin/envios.php" class="btn btn-secondary">
        ← Volver a Envíos
    </a>
</div>

<!-- Info Principal -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 <?php echo htmlspecialchars($envio['tracking_interno']); ?></h2>
        <?php
        $badgeClass = 'badge-info';
        if ($envio['estado'] === 'Entregado') $badgeClass = 'badge-success';
        elseif ($envio['estado'] === 'En tránsito') $badgeClass = 'badge-warning';
        ?>
        <span class="badge <?php echo $badgeClass; ?>"><?php echo $envio['estado']; ?></span>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        <!-- Cliente -->
        <div>
            <h4 style="color: var(--amarillo-primary); margin-bottom: 1rem;">👤 Cliente</h4>
            <?php if ($envio['cliente_nombre']): ?>
                <div style="color: var(--blanco); font-weight: 600; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($envio['cliente_nombre'] . ' ' . $envio['cliente_apellido']); ?>
                </div>
                <div style="color: var(--gris-text); font-size: 0.9rem;">
                    📧 <?php echo htmlspecialchars($envio['cliente_email']); ?>
                    <?php if ($envio['cliente_telefono']): ?>
                        <br>📱 <?php echo htmlspecialchars($envio['cliente_telefono']); ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <span class="badge badge-warning">Admin</span>
            <?php endif; ?>
        </div>
        
        <!-- Compra -->
        <div>
            <h4 style="color: var(--amarillo-primary); margin-bottom: 1rem;">🛒 Compra</h4>
            <div style="color: var(--blanco);">
                <strong><?php echo htmlspecialchars($envio['empresa_compra']); ?></strong>
            </div>
            <div style="color: var(--gris-text); font-size: 0.9rem; margin-top: 0.5rem;">
                📅 <?php echo formatDate($envio['fecha_compra'], 'd/m/Y'); ?>
                <?php if ($envio['tracking_original']): ?>
                    <br>🔖 <?php echo htmlspecialchars($envio['tracking_original']); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Productos -->
        <div>
            <h4 style="color: var(--amarillo-primary); margin-bottom: 1rem;">📦 Productos</h4>
            <div style="color: var(--blanco); font-size: 1.5rem; font-weight: 700;">
                <?php echo $envio['suma_productos']; ?> items
            </div>
            <?php if ($envio['numero_consolidado']): ?>
                <div style="color: var(--gris-text); font-size: 0.9rem; margin-top: 0.5rem;">
                    🗂️ <?php echo htmlspecialchars($envio['numero_consolidado']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Financiero -->
        <div>
            <h4 style="color: var(--amarillo-primary); margin-bottom: 1rem;">💰 Financiero</h4>
            <div style="color: var(--blanco);">
                <strong>Costo:</strong> <?php echo formatMoney($envio['costo_calculado']); ?>
            </div>
            <div style="color: <?php echo $envio['saldo_pendiente'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 0.5rem;">
                <strong>Saldo:</strong> <?php echo formatMoney($envio['saldo_pendiente']); ?>
            </div>
        </div>
    </div>
</div>

<!-- Productos Detallados -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏷️ Productos</h2>
    </div>
    
    <?php if (empty($productos)): ?>
        <p style="color: var(--gris-text); text-align: center; padding: 2rem;">
            No hay productos registrados
        </p>
    <?php else: ?>
        <div style="display: grid; gap: 0.75rem;">
            <?php foreach ($productos as $prod): ?>
                <div style="background: var(--negro-light); padding: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--blanco); font-weight: 600;">
                            <?php echo htmlspecialchars($prod['nombre_producto']); ?>
                        </div>
                        <?php if ($prod['detalle']): ?>
                            <div style="color: var(--gris-text); font-size: 0.9rem; margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($prod['detalle']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="background: var(--amarillo-primary); color: var(--negro-primary); padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 700;">
                        <?php echo $prod['cantidad']; ?>x
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Historial de Pagos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💳 Historial de Pagos</h2>
        <?php
        $totalPagado = 0;
        foreach ($pagos as $p) {
            if ($p['estado'] === 'Verificado') {
                $totalPagado += $p['monto'];
            }
        }
        ?>
        <span class="badge badge-success">Total pagado: <?php echo formatMoney($totalPagado); ?></span>
    </div>
    
    <?php if (empty($pagos)): ?>
        <p style="color: var(--gris-text); text-align: center; padding: 2rem;">
            No hay pagos registrados para este envío
        </p>
    <?php else: ?>
        <div>
            <?php foreach ($pagos as $pago): 
                $badgeClass = 'badge-warning';
                if ($pago['estado'] === 'Verificado') $badgeClass = 'badge-success';
                elseif ($pago['estado'] === 'Rechazado') $badgeClass = 'badge-danger';
            ?>
                <div class="pago-item">
                    <div class="pago-header">
                        <div>
                            <div style="color: var(--gris-text); font-size: 0.85rem;">
                                <?php echo formatDate($pago['fecha_registro'], 'd/m/Y H:i'); ?>
                            </div>
                            <div style="color: var(--blanco); font-weight: 600; margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($pago['metodo']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="pago-monto"><?php echo formatMoney($pago['monto']); ?></div>
                            <span class="badge <?php echo $badgeClass; ?>" style="margin-top: 0.5rem;">
                                <?php echo $pago['estado']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($pago['referencia']): ?>
                        <div style="color: var(--gris-text); font-size: 0.9rem; margin-top: 0.5rem;">
                            📝 Ref: <?php echo htmlspecialchars($pago['referencia']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($pago['estado'] === 'Verificado' && $pago['verificador_nombre']): ?>
                        <div style="color: var(--success); font-size: 0.85rem; margin-top: 0.5rem;">
                            ✓ Verificado por <?php echo htmlspecialchars($pago['verificador_nombre']); ?>
                            el <?php echo formatDate($pago['fecha_verificacion'], 'd/m/Y H:i'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($pago['comprobante_url']): ?>
                        <div style="margin-top: 0.5rem;">
                            <a href="<?php echo htmlspecialchars($pago['comprobante_url']); ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem;">
                                📄 Ver Comprobante
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Timeline -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🛣️ Seguimiento</h2>
    </div>
    
    <div class="timeline">
        <?php
        $timelineItems = [];
        
        if ($envio['fecha_registro']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_registro'],
                'titulo' => 'Envío Registrado',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_miami']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_miami'],
                'titulo' => 'Recibido en Miami',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_consolidacion']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_consolidacion'],
                'titulo' => 'Consolidado',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_salida_miami']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_salida_miami'],
                'titulo' => 'Salió de Miami',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_aduana']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_aduana'],
                'titulo' => 'Llegada a Aduana',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_llegada_puerto_ordaz']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_llegada_puerto_ordaz'],
                'titulo' => 'Llegada a Puerto Ordaz',
                'activo' => true
            ];
        }
        
        if ($envio['fecha_entrega_final']) {
            $timelineItems[] = [
                'fecha' => $envio['fecha_entrega_final'],
                'titulo' => 'Entregado',
                'activo' => true
            ];
        }
        
        foreach ($timelineItems as $index => $item):
        ?>
            <div class="timeline-item">
                <div class="timeline-dot active"></div>
                <?php if ($index < count($timelineItems) - 1): ?>
                    <div class="timeline-line"></div>
                <?php endif; ?>
                <div class="timeline-content">
                    <div style="color: var(--amarillo-primary); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        <?php echo formatDate($item['fecha'], 'd/m/Y H:i'); ?>
                    </div>
                    <div style="color: var(--blanco); font-weight: 600;">
                        <?php echo $item['titulo']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>