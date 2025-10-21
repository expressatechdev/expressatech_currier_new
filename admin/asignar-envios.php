<?php
/**
 * EXPRESSATECH CARGO - Asignar Envíos a Consolidado
 * Seleccionar qué envíos van en el consolidado
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$consolidadoId = $_GET['consolidado'] ?? 0;

// Obtener datos del consolidado
$consolidado = queryOne("SELECT * FROM consolidados WHERE id = ?", [$consolidadoId]);

if (!$consolidado) {
    redirect('/admin/consolidados.php');
}

$pageTitle = 'Asignar Envíos al Consolidado';
$pageSubtitle = $consolidado['numero_consolidado'];

// Obtener envíos disponibles (en Miami y sin consolidar)
$enviosDisponibles = queryAll("
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        u.email as cliente_email,
        u.margen_personalizado,
        u.tipo_cliente,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_items,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE e.estado = 'Recibido en Miami' 
    AND e.consolidado_id IS NULL
    ORDER BY e.fecha_llegada_miami DESC
");

// Obtener envíos ya asignados a este consolidado
$enviosAsignados = queryAll("
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE e.consolidado_id = ?
    ORDER BY e.fecha_registro
", [$consolidadoId]);

include '../includes/header.php';
?>

<style>
.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3rem;
    position: relative;
}

.progress-steps:before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #444;
    z-index: 0;
}

.step {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    position: relative;
    z-index: 1;
    border: 3px solid #444;
}

.step.active {
    background: var(--amarillo-primary);
    color: var(--negro-primary);
    border-color: var(--amarillo-primary);
}

.step.completed {
    background: var(--success);
    border-color: var(--success);
    color: white;
}

.consolidado-info {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--amarillo-primary);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.info-item {
    text-align: center;
}

.info-label {
    color: var(--gris-text);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.info-value {
    color: var(--amarillo-primary);
    font-size: 1.3rem;
    font-weight: 700;
}

.envios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.envio-card {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.envio-card:hover {
    border-color: var(--amarillo-primary);
    transform: translateY(-3px);
}

.envio-card.selected {
    border-color: var(--amarillo-primary);
    background: rgba(255, 196, 37, 0.1);
}

.envio-card.selected:before {
    content: '✓';
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--amarillo-primary);
    color: var(--negro-primary);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.envio-tracking {
    font-weight: 700;
    color: var(--amarillo-primary);
    margin-bottom: 0.5rem;
}

.envio-cliente {
    color: var(--blanco);
    margin-bottom: 0.5rem;
}

.envio-productos {
    color: var(--gris-text);
    font-size: 0.9rem;
}

.vip-badge {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-block;
    margin-top: 0.5rem;
}

.selection-summary {
    position: sticky;
    bottom: 20px;
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid var(--amarillo-primary);
    margin-top: 2rem;
    box-shadow: var(--shadow-lg);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
</style>

<!-- Steps Progress -->
<div class="progress-steps">
    <div class="step completed">1</div>
    <div class="step active">2</div>
    <div class="step">3</div>
</div>

<!-- Info del Consolidado -->
<div class="consolidado-info">
    <h3 style="color: var(--amarillo-primary); margin-bottom: 1rem;">
        📦 <?php echo htmlspecialchars($consolidado['numero_consolidado']); ?>
    </h3>
    
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Costo Courier</div>
            <div class="info-value"><?php echo formatMoney($consolidado['costo_courier']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Costos Adicionales</div>
            <div class="info-value">
                <?php echo formatMoney($consolidado['costo_recoleccion'] + $consolidado['costo_logistica'] + $consolidado['costo_manejo']); ?>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">Costo Total</div>
            <div class="info-value"><?php echo formatMoney($consolidado['costo_total']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Margen Base</div>
            <div class="info-value"><?php echo $consolidado['margen_ganancia']; ?>%</div>
        </div>
    </div>
</div>

<!-- Envíos Ya Asignados -->
<?php if (!empty($enviosAsignados)): ?>
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h2 class="card-title">✓ Envíos Ya Asignados</h2>
        <span class="badge badge-success"><?php echo count($enviosAsignados); ?> envíos</span>
    </div>
    <div class="envios-grid">
        <?php foreach ($enviosAsignados as $envio): ?>
            <div class="envio-card" style="border-color: var(--success); opacity: 0.7;">
                <div class="envio-tracking"><?php echo htmlspecialchars($envio['tracking_interno']); ?></div>
                <div class="envio-cliente">
                    <?php if ($envio['cliente_nombre']): ?>
                        <?php echo htmlspecialchars($envio['cliente_nombre'] . ' ' . $envio['cliente_apellido']); ?>
                    <?php else: ?>
                        <span class="badge badge-warning">Admin</span>
                    <?php endif; ?>
                </div>
                <div class="envio-productos">
                    📦 <?php echo $envio['suma_productos']; ?> productos
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Seleccionar Envíos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Seleccionar Envíos para Consolidar</h2>
        <span class="badge badge-info"><?php echo count($enviosDisponibles); ?> disponibles</span>
    </div>
    
    <?php if (empty($enviosDisponibles)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No hay envíos disponibles</h3>
            <p>No hay envíos en estado "Recibido en Miami" sin consolidar</p>
            <a href="/admin/envios.php" class="btn btn-primary" style="margin-top: 1rem;">
                Ver Todos los Envíos
            </a>
        </div>
    <?php else: ?>
        <div id="envios-container" class="envios-grid">
            <?php foreach ($enviosDisponibles as $envio): ?>
                <div class="envio-card" 
                     data-envio-id="<?php echo $envio['id']; ?>"
                     data-productos="<?php echo $envio['suma_productos']; ?>"
                     onclick="toggleEnvio(this)">
                    <div class="envio-tracking"><?php echo htmlspecialchars($envio['tracking_interno']); ?></div>
                    <div class="envio-cliente">
                        <?php if ($envio['cliente_nombre']): ?>
                            <?php echo htmlspecialchars($envio['cliente_nombre'] . ' ' . $envio['cliente_apellido']); ?>
                            <?php if ($envio['margen_personalizado']): ?>
                                <span class="vip-badge">VIP <?php echo $envio['margen_personalizado']; ?>%</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Admin</span>
                        <?php endif; ?>
                    </div>
                    <div class="envio-productos">
                        📦 <?php echo $envio['suma_productos']; ?> productos
                    </div>
                    <div style="font-size: 0.85rem; color: var(--gris-text); margin-top: 0.5rem;">
                        Llegó: <?php echo formatDate($envio['fecha_llegada_miami'], 'd/m/Y'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Resumen y Botones -->
<div class="selection-summary">
    <div class="summary-grid">
        <div class="info-item">
            <div class="info-label">Envíos Seleccionados</div>
            <div class="info-value" id="count-envios">0</div>
        </div>
        <div class="info-item">
            <div class="info-label">Total Productos</div>
            <div class="info-value" id="count-productos">0</div>
        </div>
        <div class="info-item">
            <div class="info-label">Costo/Producto</div>
            <div class="info-value" id="costo-producto">$0.00</div>
        </div>
        <div class="info-item">
            <div class="info-label">Precio Venta</div>
            <div class="info-value" id="precio-venta">$0.00</div>
        </div>
    </div>
    
    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
        <a href="/admin/consolidados.php" class="btn btn-secondary">
            ← Volver
        </a>
        <button onclick="asignarEnvios()" class="btn btn-primary" id="btn-asignar" disabled>
            Consolidar Envíos Seleccionados →
        </button>
    </div>
</div>

<script>
const consolidadoId = <?php echo $consolidadoId; ?>;
const costoTotal = <?php echo $consolidado['costo_total']; ?>;
const margenBase = <?php echo $consolidado['margen_ganancia']; ?>;
let enviosSeleccionados = [];

function toggleEnvio(card) {
    const envioId = parseInt(card.dataset.envioId);
    
    if (card.classList.contains('selected')) {
        card.classList.remove('selected');
        enviosSeleccionados = enviosSeleccionados.filter(id => id !== envioId);
    } else {
        card.classList.add('selected');
        enviosSeleccionados.push(envioId);
    }
    
    actualizarResumen();
}

function actualizarResumen() {
    const countEnvios = enviosSeleccionados.length;
    
    let totalProductos = 0;
    document.querySelectorAll('.envio-card.selected').forEach(card => {
        totalProductos += parseInt(card.dataset.productos);
    });
    
    const costoPorProducto = totalProductos > 0 ? (costoTotal / totalProductos) : 0;
    const precioVenta = costoPorProducto * (1 + (margenBase / 100));
    
    document.getElementById('count-envios').textContent = countEnvios;
    document.getElementById('count-productos').textContent = totalProductos;
    document.getElementById('costo-producto').textContent = '$' + costoPorProducto.toFixed(4);
    document.getElementById('precio-venta').textContent = '$' + precioVenta.toFixed(4);
    
    document.getElementById('btn-asignar').disabled = countEnvios === 0;
}

async function asignarEnvios() {
    if (enviosSeleccionados.length === 0) {
        window.ExpressatechCargo.showAlert('Selecciona al menos un envío', 'warning');
        return;
    }
    
    const btn = document.getElementById('btn-asignar');
    const btnText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Procesando...';
    
    try {
        // Crear FormData en lugar de JSON
        const formData = new FormData();
        formData.append('consolidado_id', consolidadoId);
        formData.append('envios', JSON.stringify(enviosSeleccionados));
        
        console.log('Enviando:', {
            consolidado_id: consolidadoId,
            envios: enviosSeleccionados
        });
        
        const response = await fetch('/api/asignar-envios-consolidado.php', {
            method: 'POST',
            body: formData // Enviar como FormData, NO como JSON
        });
        
        console.log('Response status:', response.status);
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Error parsing JSON:', responseText);
            throw new Error('Respuesta inválida del servidor');
        }
        
        if (data.success) {
            window.ExpressatechCargo.showAlert(
                `✓ ${enviosSeleccionados.length} envíos consolidados exitosamente. Total productos: ${data.total_productos || 0}`,
                'success'
            );
            setTimeout(() => {
                window.location.href = `/admin/detalle-consolidado.php?id=${consolidadoId}`;
            }, 1500);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error completo:', error);
        window.ExpressatechCargo.showAlert(error.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = btnText;
    }
}
</script>

<?php include '../includes/footer.php'; ?>