<?php
/**
 * EXPRESSATECH CARGO - Detalle de Consolidado
 * Vista completa de un consolidado con análisis financiero
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$consolidadoId = $_GET['id'] ?? 0;

// Obtener datos del consolidado
$consolidado = queryOne("SELECT * FROM vista_resumen_consolidados WHERE id = ?", [$consolidadoId]);

if (!$consolidado) {
    redirect('/admin/consolidados.php');
}

$pageTitle = 'Detalle de Consolidado';
$pageSubtitle = $consolidado['numero_consolidado'];

// Obtener envíos del consolidado
$envios = queryAll("
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        u.email as cliente_email,
        u.margen_personalizado,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos,
        (SELECT SUM(monto) FROM pagos WHERE envio_id = e.id AND estado = 'Verificado') as total_pagado
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE e.consolidado_id = ?
    ORDER BY e.fecha_registro
", [$consolidadoId]);

// Calcular estadísticas
$totalProductos = 0;
$totalFacturado = 0;
$totalPagado = 0;
$clientesUnicos = [];

foreach ($envios as $envio) {
    $totalProductos += $envio['suma_productos'];
    $totalFacturado += $envio['costo_calculado'];
    $totalPagado += $envio['total_pagado'] ?? 0;
    if ($envio['cliente_id']) {
        $clientesUnicos[$envio['cliente_id']] = true;
    }
}

$gananciaProyectada = $totalFacturado - $consolidado['costo_total'];
$gananciaReal = $totalPagado - $consolidado['costo_total'];
$porcentajeGanancia = $consolidado['costo_total'] > 0 ? (($gananciaReal / $consolidado['costo_total']) * 100) : 0;

include '../includes/header.php';
?>

<style>
.detalle-header {
    background: linear-gradient(135deg, var(--negro-primary), var(--negro-light));
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--amarillo-primary);
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.consolidado-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.metric-card {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}

.metric-label {
    color: var(--gris-text);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}

.metric-value.success {
    color: var(--success);
}

.metric-value.danger {
    color: var(--danger);
}

.ganancia-indicator {
    background: var(--negro-card);
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 2rem;
}

.ganancia-display {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.ganancia-display.positive {
    color: var(--success);
}

.ganancia-display.negative {
    color: var(--danger);
}

.progress-bar-container {
    background: #333;
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    margin: 1rem 0;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--amarillo-dark), var(--amarillo-primary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--negro-primary);
    font-weight: 700;
    transition: width 0.5s ease;
}

.envio-row {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr;
    gap: 1rem;
    align-items: center;
}

.envio-row:hover {
    background: rgba(255, 196, 37, 0.05);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 1024px) {
    .envio-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
}
</style>

<!-- Header del Consolidado -->
<div class="detalle-header">
    <div class="header-top">
        <div>
            <div class="consolidado-title"><?php echo htmlspecialchars($consolidado['numero_consolidado']); ?></div>
            <div style="color: var(--gris-text); margin-top: 0.5rem;">
                Creado: <?php echo formatDate($consolidado['fecha_creacion'], 'd/m/Y H:i'); ?>
            </div>
        </div>
        <div>
            <?php
            $badgeClass = 'badge-info';
            if ($consolidado['estado'] === 'Cerrado') $badgeClass = 'badge-success';
            elseif ($consolidado['estado'] === 'En Tránsito') $badgeClass = 'badge-warning';
            ?>
            <span class="badge <?php echo $badgeClass; ?>" style="font-size: 1.2rem; padding: 0.5rem 1rem;">
                <?php echo $consolidado['estado']; ?>
            </span>
        </div>
    </div>
    
    <!-- Métricas Principales -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Inversión Total</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_total']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Productos</div>
            <div class="metric-value"><?php echo $totalProductos; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Costo/Producto</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_por_producto']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Precio Venta</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['precio_venta_producto']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Envíos</div>
            <div class="metric-value"><?php echo count($envios); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Clientes</div>
            <div class="metric-value"><?php echo count($clientesUnicos); ?></div>
        </div>
    </div>
</div>

<!-- Indicador de Ganancia -->
<div class="ganancia-indicator">
    <h3 style="color: var(--gris-text); margin-bottom: 1rem;">💰 Análisis de Ganancia</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
        <div>
            <div style="color: var(--gris-text); margin-bottom: 0.5rem;">Facturado Total</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--amarillo-primary);">
                <?php echo formatMoney($totalFacturado); ?>
            </div>
        </div>
        
        <div>
            <div style="color: var(--gris-text); margin-bottom: 0.5rem;">Pagado Real</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--success);">
                <?php echo formatMoney($totalPagado); ?>
            </div>
        </div>
        
        <div>
            <div style="color: var(--gris-text); margin-bottom: 0.5rem;">Ganancia Real</div>
            <div class="ganancia-display <?php echo $gananciaReal >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo formatMoney($gananciaReal); ?>
            </div>
            <div style="color: var(--gris-text); font-size: 1.2rem;">
                <?php echo number_format($porcentajeGanancia, 1); ?>% sobre inversión
            </div>
        </div>
    </div>
    
    <!-- Barra de Progreso de Pagos -->
    <div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--gris-text);">Progreso de Cobros</span>
            <span style="color: var(--amarillo-primary); font-weight: 700;">
                <?php echo $totalFacturado > 0 ? number_format(($totalPagado / $totalFacturado) * 100, 1) : 0; ?>%
            </span>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="width: <?php echo $totalFacturado > 0 ? ($totalPagado / $totalFacturado) * 100 : 0; ?>%">
                <?php echo formatMoney($totalPagado); ?> de <?php echo formatMoney($totalFacturado); ?>
            </div>
        </div>
    </div>
</div>

<!-- Desglose de Costos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💵 Desglose de Costos</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 1rem;">
        <div class="metric-card">
            <div class="metric-label">Courier</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_courier']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Recolección</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_recoleccion']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Logística</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_logistica']); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Manejo</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_manejo']); ?></div>
        </div>
        <div class="metric-card" style="border: 2px solid var(--amarillo-primary);">
            <div class="metric-label">TOTAL</div>
            <div class="metric-value"><?php echo formatMoney($consolidado['costo_total']); ?></div>
        </div>
    </div>
</div>

<!-- Envíos del Consolidado -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Envíos en este Consolidado</h2>
        <span class="badge badge-info"><?php echo count($envios); ?> envíos</span>
    </div>
    
    <?php if (empty($envios)): ?>
        <div class="empty-state">
            <p>No hay envíos asignados a este consolidado</p>
            <a href="/admin/asignar-envios.php?consolidado=<?php echo $consolidadoId; ?>" class="btn btn-primary">
                Asignar Envíos
            </a>
        </div>
    <?php else: ?>
        <!-- Header -->
        <div class="envio-row" style="background: rgba(255,196,37,0.1); font-weight: 600; color: var(--amarillo-primary);">
            <div>TRACKING / CLIENTE</div>
            <div>PRODUCTOS</div>
            <div>MARGEN</div>
            <div>COSTO</div>
            <div>PAGADO</div>
            <div>SALDO</div>
        </div>
        
        <!-- Filas -->
        <?php foreach ($envios as $envio): ?>
            <div class="envio-row">
                <div>
                    <div style="font-weight: 700; color: var(--amarillo-primary);">
                        <?php echo htmlspecialchars($envio['tracking_interno']); ?>
                    </div>
                    <div style="color: var(--blanco); font-size: 0.9rem;">
                        <?php if ($envio['cliente_nombre']): ?>
                            <?php echo htmlspecialchars($envio['cliente_nombre'] . ' ' . $envio['cliente_apellido']); ?>
                            <?php if ($envio['margen_personalizado']): ?>
                                <span class="badge badge-warning" style="font-size: 0.7rem;">VIP</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Admin</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="color: var(--blanco);">
                    <strong><?php echo $envio['suma_productos']; ?></strong> items
                </div>
                
                <div>
                    <?php if ($envio['margen_aplicado']): ?>
                        <span class="badge badge-info"><?php echo $envio['margen_aplicado']; ?>%</span>
                    <?php else: ?>
                        <span style="color: var(--gris-text);">-</span>
                    <?php endif; ?>
                </div>
                
                <div style="color: var(--amarillo-primary); font-weight: 600;">
                    <?php echo formatMoney($envio['costo_calculado']); ?>
                </div>
                
                <div style="color: var(--success); font-weight: 600;">
                    <?php echo formatMoney($envio['total_pagado'] ?? 0); ?>
                </div>
                
                <div>
                    <?php if ($envio['saldo_pendiente'] > 0): ?>
                        <strong style="color: var(--danger);">
                            <?php echo formatMoney($envio['saldo_pendiente']); ?>
                        </strong>
                    <?php else: ?>
                        <span style="color: var(--success);">✓ Pagado</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Acciones del Consolidado -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">⚙️ Acciones</h2>
    </div>
    
    <div class="action-buttons" style="padding: 1rem;">
        <a href="/admin/consolidados.php" class="btn btn-secondary">
            ← Volver a Consolidados
        </a>
        
        <?php if ($consolidado['estado'] === 'Abierto'): ?>
            <a href="/admin/asignar-envios.php?consolidado=<?php echo $consolidadoId; ?>" class="btn btn-primary">
                ➕ Agregar Más Envíos
            </a>
            
            <button onclick="cerrarConsolidado()" class="btn btn-primary">
                ✓ Cerrar Consolidado
            </button>
        <?php endif; ?>
        
        <?php if ($consolidado['estado'] === 'Cerrado'): ?>
            <button onclick="marcarEnTransito()" class="btn btn-primary">
                🚚 Marcar En Tránsito
            </button>
        <?php endif; ?>
        
        <button onclick="window.print()" class="btn btn-secondary">
            🖨️ Imprimir Reporte
        </button>
    </div>
</div>

<script>
async function cerrarConsolidado() {
    if (!confirm('¿Cerrar este consolidado? No se podrán agregar más envíos.')) {
        return;
    }
    
    try {
        const response = await fetch('/api/cambiar-estado-consolidado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                consolidado_id: <?php echo $consolidadoId; ?>,
                nuevo_estado: 'Cerrado'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert('Consolidado cerrado exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        window.ExpressatechCargo.showAlert(error.message, 'danger');
    }
}

async function marcarEnTransito() {
    if (!confirm('¿Marcar consolidado en tránsito a Venezuela?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/cambiar-estado-consolidado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                consolidado_id: <?php echo $consolidadoId; ?>,
                nuevo_estado: 'En Tránsito'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert('Consolidado marcado en tránsito', 'success');
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