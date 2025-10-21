<?php
/**
 * EXPRESSATECH CARGO - Gestión de Consolidados (Admin)
 * Crear y gestionar consolidados de envíos
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestión de Consolidados';
$pageSubtitle = 'Crear y administrar consolidados';

// Obtener consolidados existentes
$consolidados = getConsolidados();

// Obtener envíos disponibles para consolidar (en Miami y sin consolidar)
$enviosDisponibles = queryAll("
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_items,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE e.estado = 'Recibido en Miami' 
    AND e.consolidado_id IS NULL
    ORDER BY e.fecha_llegada_miami DESC
");

include '../includes/header.php';
?>

<style>
.consolidado-card {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border-left: 4px solid var(--amarillo-primary);
    cursor: pointer;
    transition: all 0.3s;
}

.consolidado-card:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-yellow);
}

.consolidado-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.consolidado-numero {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}

.consolidado-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #444;
}

.stat-item {
    text-align: center;
}

.stat-label {
    color: var(--gris-text);
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.stat-value {
    color: var(--blanco);
    font-size: 1.2rem;
    font-weight: 600;
}

.envio-selectable {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
}

.envio-selectable:hover {
    border-color: var(--amarillo-primary);
}

.envio-selectable.selected {
    border-color: var(--amarillo-primary);
    background: rgba(255, 196, 37, 0.1);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 2rem;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--negro-card);
    padding: 2rem;
    border-radius: 12px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--amarillo-primary);
}

.btn-close {
    background: none;
    border: none;
    color: var(--gris-text);
    font-size: 2rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.btn-close:hover {
    color: var(--danger);
}

.form-section {
    background: var(--negro-light);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.section-title {
    color: var(--amarillo-primary);
    font-size: 1.1rem;
    margin-bottom: 1rem;
    font-weight: 600;
}
</style>

<!-- Botón Crear Consolidado -->
<div style="margin-bottom: 2rem;">
    <button onclick="abrirModalNuevoConsolidado()" class="btn btn-primary">
        ➕ Crear Nuevo Consolidado
    </button>
</div>

<!-- Lista de Consolidados -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Consolidados Existentes</h2>
        <span class="badge badge-info"><?php echo count($consolidados); ?> consolidados</span>
    </div>
    
    <?php if (empty($consolidados)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No hay consolidados creados</h3>
            <p>Crea tu primer consolidado para comenzar a gestionar los envíos</p>
        </div>
    <?php else: ?>
        <?php foreach ($consolidados as $cons): ?>
            <div class="consolidado-card" onclick="window.location.href='/admin/detalle-consolidado.php?id=<?php echo $cons['id']; ?>'">
                <div class="consolidado-header">
                    <div>
                        <div class="consolidado-numero"><?php echo htmlspecialchars($cons['numero_consolidado']); ?></div>
                        <div style="color: var(--gris-text); font-size: 0.9rem;">
                            Creado: <?php echo formatDate($cons['fecha_creacion'], 'd/m/Y'); ?>
                        </div>
                    </div>
                    <div>
                        <?php
                        $badgeClass = 'badge-info';
                        if ($cons['estado'] === 'Cerrado') $badgeClass = 'badge-success';
                        elseif ($cons['estado'] === 'En Tránsito') $badgeClass = 'badge-warning';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>" style="font-size: 1rem;">
                            <?php echo $cons['estado']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="consolidado-stats">
                    <div class="stat-item">
                        <div class="stat-label">Costo Total</div>
                        <div class="stat-value"><?php echo formatMoney($cons['costo_total']); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Productos</div>
                        <div class="stat-value"><?php echo $cons['total_productos']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Envíos</div>
                        <div class="stat-value"><?php echo $cons['total_envios'] ?? 0; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Clientes</div>
                        <div class="stat-value"><?php echo $cons['total_clientes'] ?? 0; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Facturado</div>
                        <div class="stat-value" style="color: var(--amarillo-primary);">
                            <?php echo formatMoney($cons['total_facturado']); ?>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Ganancia</div>
                        <div class="stat-value" style="color: var(--success);">
                            <?php echo formatMoney($cons['ganancia_real']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Nuevo Consolidado -->
<div id="modal-nuevo-consolidado" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="color: var(--amarillo-primary); margin: 0;">📦 Crear Nuevo Consolidado</h2>
            <button class="btn-close" onclick="cerrarModalNuevoConsolidado()">×</button>
        </div>
        
        <form id="form-nuevo-consolidado" onsubmit="return crearConsolidado(event)">
            <!-- Sección 1: Datos del Consolidado -->
            <div class="form-section">
                <div class="section-title">📋 Información General</div>
                
                <div class="form-group">
                    <label class="form-label">Número de Consolidado <span class="required">*</span></label>
                    <input 
                        type="text" 
                        name="numero_consolidado" 
                        class="form-input"
                        placeholder="CONS-2025-001"
                        required
                    >
                    <small style="color: var(--gris-text);">Ejemplo: CONS-2025-001</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notas (opcional)</label>
                    <textarea 
                        name="notas" 
                        class="form-input"
                        rows="3"
                        placeholder="Comentarios adicionales sobre este consolidado..."
                    ></textarea>
                </div>
            </div>
            
            <!-- Sección 2: Costos -->
            <div class="form-section">
                <div class="section-title">💰 Costos del Consolidado</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Costo Courier (USD) <span class="required">*</span></label>
                        <input 
                            type="number" 
                            name="costo_courier" 
                            class="form-input"
                            step="0.01"
                            min="0"
                            placeholder="80.00"
                            required
                            oninput="calcularTotalConsolidado()"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Costo Recolección (USD)</label>
                        <input 
                            type="number" 
                            name="costo_recoleccion" 
                            class="form-input"
                            step="0.01"
                            min="0"
                            value="0"
                            placeholder="5.00"
                            oninput="calcularTotalConsolidado()"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Costo Logística (USD)</label>
                        <input 
                            type="number" 
                            name="costo_logistica" 
                            class="form-input"
                            step="0.01"
                            min="0"
                            value="0"
                            placeholder="3.00"
                            oninput="calcularTotalConsolidado()"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Costo Manejo (USD)</label>
                        <input 
                            type="number" 
                            name="costo_manejo" 
                            class="form-input"
                            step="0.01"
                            min="0"
                            value="0"
                            placeholder="2.00"
                            oninput="calcularTotalConsolidado()"
                        >
                    </div>
                </div>
                
                <div style="background: rgba(255,196,37,0.1); padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--blanco); font-weight: 600;">Costo Total:</span>
                        <span id="costo-total-display" style="color: var(--amarillo-primary); font-size: 1.5rem; font-weight: 700;">$0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Sección 3: Margen -->
            <div class="form-section">
                <div class="section-title">📈 Margen de Ganancia</div>
                
                <div class="form-group">
                    <label class="form-label">Margen Base del Consolidado (%) <span class="required">*</span></label>
                    <input 
                        type="number" 
                        name="margen_ganancia" 
                        class="form-input"
                        step="0.1"
                        min="0"
                        max="100"
                        value="30"
                        required
                    >
                    <small style="color: var(--gris-text);">
                        Este margen se aplicará a todos los envíos, excepto clientes VIP con margen personalizado
                    </small>
                </div>
            </div>
            
            <!-- Botones -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalNuevoConsolidado()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    ✓ Crear Consolidado
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevoConsolidado() {
    document.getElementById('modal-nuevo-consolidado').classList.add('active');
    // Generar número de consolidado sugerido
    const fecha = new Date();
    const year = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const sugerencia = `CONS-${year}${mes}-001`;
    document.querySelector('[name="numero_consolidado"]').value = sugerencia;
}

function cerrarModalNuevoConsolidado() {
    document.getElementById('modal-nuevo-consolidado').classList.remove('active');
    document.getElementById('form-nuevo-consolidado').reset();
}

function calcularTotalConsolidado() {
    const courier = parseFloat(document.querySelector('[name="costo_courier"]').value) || 0;
    const recoleccion = parseFloat(document.querySelector('[name="costo_recoleccion"]').value) || 0;
    const logistica = parseFloat(document.querySelector('[name="costo_logistica"]').value) || 0;
    const manejo = parseFloat(document.querySelector('[name="costo_manejo"]').value) || 0;
    
    const total = courier + recoleccion + logistica + manejo;
    
    document.getElementById('costo-total-display').textContent = '$' + total.toFixed(2);
}

async function crearConsolidado(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const btn = e.target.querySelector('[type="submit"]');
    const btnText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Creando...';
    
    try {
        const response = await fetch('/api/crear-consolidado.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert('Consolidado creado exitosamente', 'success');
            setTimeout(() => {
                window.location.href = `/admin/asignar-envios.php?consolidado=${data.consolidado_id}`;
            }, 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        window.ExpressatechCargo.showAlert(error.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = btnText;
    }
    
    return false;
}

// Cerrar modal al hacer click fuera
document.getElementById('modal-nuevo-consolidado')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalNuevoConsolidado();
    }
});
</script>

<?php include '../includes/footer.php'; ?>