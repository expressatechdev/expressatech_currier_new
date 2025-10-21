<?php
/**
 * EXPRESSATECH CARGO - Gestión de Envíos (Admin)
 * Vista completa de todos los envíos del sistema
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Gestión de Envíos';
$pageSubtitle = 'Control total de todos los envíos';

// Filtros
$filtroEstado = $_GET['estado'] ?? '';
$filtroCliente = $_GET['cliente'] ?? '';
$busqueda = $_GET['search'] ?? '';

// Obtener todos los envíos
$sql = "
    SELECT 
        e.*,
        u.nombre as cliente_nombre,
        u.apellido as cliente_apellido,
        u.email as cliente_email,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_productos,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    LEFT JOIN usuarios u ON e.cliente_id = u.id
    WHERE 1=1
";

$params = [];

if ($filtroEstado) {
    $sql .= " AND e.estado = ?";
    $params[] = $filtroEstado;
}

if ($filtroCliente) {
    $sql .= " AND e.cliente_id = ?";
    $params[] = $filtroCliente;
}

if ($busqueda) {
    $sql .= " AND (e.tracking_interno LIKE ? OR e.tracking_original LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ?)";
    $searchTerm = "%$busqueda%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY e.fecha_registro DESC LIMIT 50";

$envios = queryAll($sql, $params);

// Obtener lista de clientes para filtro
$clientes = getAllClientes();

// Estadísticas rápidas
$stats = queryOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado NOT IN ('Entregado') THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN estado = 'Recibido en Miami' THEN 1 ELSE 0 END) as en_miami,
        SUM(CASE WHEN estado = 'Llegada a Puerto Ordaz' THEN 1 ELSE 0 END) as en_puerto_ordaz
    FROM envios
");

include '../includes/header.php';
?>

<style>
.quick-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-bar {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.search-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.envio-row {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border-left: 4px solid transparent;
    transition: all 0.3s;
    cursor: pointer;
}

.envio-row:hover {
    border-left-color: var(--amarillo-primary);
    transform: translateX(5px);
}

.envio-row.sin-costo {
    border-left-color: var(--info);
}

.envio-row.pendiente-pago {
    border-left-color: var(--danger);
}

.envio-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: center;
}

.tracking-col {
    font-weight: 700;
    color: var(--amarillo-primary);
}

.cliente-col {
    color: var(--blanco);
}

.cliente-col small {
    display: block;
    color: var(--gris-text);
    font-size: 0.85rem;
}

.estado-selector {
    padding: 0.5rem;
    background: var(--negro-light);
    border: 1px solid #444;
    border-radius: 6px;
    color: var(--blanco);
    font-size: 0.9rem;
    cursor: pointer;
}

.estado-selector:focus {
    border-color: var(--amarillo-primary);
    outline: none;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.5rem;
    background: var(--negro-light);
    border: none;
    border-radius: 6px;
    color: var(--gris-text);
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1.2rem;
}

.btn-icon:hover {
    background: var(--amarillo-primary);
    color: var(--negro-primary);
    transform: scale(1.1);
}

@media (max-width: 1024px) {
    .search-form {
        grid-template-columns: 1fr;
    }
    
    .envio-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
}
</style>

<!-- Estadísticas Rápidas -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Envíos</span>
            <span class="stat-icon">📦</span>
        </div>
        <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">En Proceso</span>
            <span class="stat-icon">🚚</span>
        </div>
        <div class="stat-value"><?php echo $stats['en_proceso'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">En Miami</span>
            <span class="stat-icon">🇺🇸</span>
        </div>
        <div class="stat-value"><?php echo $stats['en_miami'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Puerto Ordaz</span>
            <span class="stat-icon">🇻🇪</span>
        </div>
        <div class="stat-value"><?php echo $stats['en_puerto_ordaz'] ?? 0; ?></div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="quick-actions">
    <a href="?action=nuevo" class="btn btn-primary">
        ➕ Registrar Envío (como Admin)
    </a>
    <a href="/admin/consolidados.php" class="btn btn-secondary">
        📊 Gestionar Consolidados
    </a>
    <button onclick="window.print()" class="btn btn-secondary">
        🖨️ Imprimir Lista
    </button>
</div>

<!-- Barra de Búsqueda y Filtros -->
<div class="search-bar">
    <form method="GET" action="" class="search-form">
        <div class="form-group" style="margin: 0;">
            <input 
                type="text" 
                name="search" 
                class="form-input"
                placeholder="🔍 Buscar por tracking, cliente..."
                value="<?php echo htmlspecialchars($busqueda); ?>"
            >
        </div>
        
        <div class="form-group" style="margin: 0;">
            <select name="estado" class="form-input">
                <option value="">Todos los estados</option>
                <option value="En tránsito" <?php echo $filtroEstado === 'En tránsito' ? 'selected' : ''; ?>>En tránsito</option>
                <option value="Recibido en Miami" <?php echo $filtroEstado === 'Recibido en Miami' ? 'selected' : ''; ?>>Recibido en Miami</option>
                <option value="Consolidado" <?php echo $filtroEstado === 'Consolidado' ? 'selected' : ''; ?>>Consolidado</option>
                <option value="En camino a Venezuela" <?php echo $filtroEstado === 'En camino a Venezuela' ? 'selected' : ''; ?>>En camino a Venezuela</option>
                <option value="Llegada a Aduana" <?php echo $filtroEstado === 'Llegada a Aduana' ? 'selected' : ''; ?>>En Aduana</option>
                <option value="Llegada a Puerto Ordaz" <?php echo $filtroEstado === 'Llegada a Puerto Ordaz' ? 'selected' : ''; ?>>Puerto Ordaz</option>
                <option value="Entregado" <?php echo $filtroEstado === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
            </select>
        </div>
        
        <div class="form-group" style="margin: 0;">
            <select name="cliente" class="form-input">
                <option value="">Todos los clientes</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>" <?php echo $filtroCliente == $cliente['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<!-- Lista de Envíos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Envíos Registrados</h2>
        <span class="badge badge-info"><?php echo count($envios); ?> resultados</span>
    </div>
    
    <?php if (empty($envios)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No se encontraron envíos</h3>
            <p>
                <?php if ($busqueda || $filtroEstado || $filtroCliente): ?>
                    <a href="?">Limpiar filtros</a>
                <?php else: ?>
                    No hay envíos registrados en el sistema
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <!-- Header de la tabla -->
        <div class="envio-grid" style="padding: 1rem; background: rgba(255,196,37,0.1); font-weight: 600; color: var(--amarillo-primary); font-size: 0.9rem;">
            <div>TRACKING</div>
            <div>CLIENTE</div>
            <div>PRODUCTOS</div>
            <div>ESTADO</div>
            <div>COSTO</div>
            <div>ACCIONES</div>
        </div>
        
        <!-- Filas de envíos -->
        <?php foreach ($envios as $envio): 
            $rowClass = '';
            if ($envio['es_costo_cero']) $rowClass = 'sin-costo';
            elseif ($envio['saldo_pendiente'] > 0) $rowClass = 'pendiente-pago';
        ?>
            <div class="envio-row <?php echo $rowClass; ?>" 
                 onclick="window.location.href='/admin/detalle-envio.php?id=<?php echo $envio['id']; ?>'">
                <div class="envio-grid">
                    <!-- Tracking -->
                    <div class="tracking-col">
                        <?php echo htmlspecialchars($envio['tracking_interno']); ?>
                        <div style="font-size: 0.85rem; color: var(--gris-text); font-weight: normal;">
                            📅 <?php echo formatDate($envio['fecha_registro'], 'd/m/Y'); ?>
                            <?php if ($envio['destinatario_nombre']): ?>
                                <br>👤 Para: <?php echo htmlspecialchars($envio['destinatario_nombre']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="cliente-col">
                        <?php if ($envio['cliente_nombre']): ?>
                            <?php echo htmlspecialchars($envio['cliente_nombre'] . ' ' . $envio['cliente_apellido']); ?>
                            <small><?php echo htmlspecialchars($envio['cliente_email']); ?></small>
                        <?php else: ?>
                            <span class="badge badge-warning">Admin</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Productos -->
                    <div>
                        <strong><?php echo $envio['suma_productos'] ?? 0; ?></strong> items
                        <div style="font-size: 0.85rem; color: var(--gris-text);">
                            <?php echo htmlspecialchars($envio['empresa_compra']); ?>
                        </div>
                    </div>
                    
                    <!-- Estado (con selector) -->
                    <div onclick="event.stopPropagation()">
                        <select 
                            class="estado-selector"
                            onchange="cambiarEstado(<?php echo $envio['id']; ?>, this.value)"
                        >
                            <option value="En tránsito" <?php echo $envio['estado'] === 'En tránsito' ? 'selected' : ''; ?>>En tránsito</option>
                            <option value="Recibido en Miami" <?php echo $envio['estado'] === 'Recibido en Miami' ? 'selected' : ''; ?>>Recibido en Miami</option>
                            <option value="Consolidado" <?php echo $envio['estado'] === 'Consolidado' ? 'selected' : ''; ?>>Consolidado</option>
                            <option value="En camino a Venezuela" <?php echo $envio['estado'] === 'En camino a Venezuela' ? 'selected' : ''; ?>>En camino a Venezuela</option>
                            <option value="Llegada a Aduana" <?php echo $envio['estado'] === 'Llegada a Aduana' ? 'selected' : ''; ?>>Llegada a Aduana</option>
                            <option value="Llegada a Puerto Ordaz" <?php echo $envio['estado'] === 'Llegada a Puerto Ordaz' ? 'selected' : ''; ?>>Llegada a Puerto Ordaz</option>
                            <option value="Pendiente por retiro" <?php echo $envio['estado'] === 'Pendiente por retiro' ? 'selected' : ''; ?>>Pendiente por retiro</option>
                            <option value="Entregado" <?php echo $envio['estado'] === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                        </select>
                    </div>
                    
                    <!-- Costo -->
                    <div>
                        <?php if ($envio['costo_calculado'] > 0): ?>
                            <strong style="color: var(--amarillo-primary);">
                                <?php echo formatMoney($envio['costo_calculado']); ?>
                            </strong>
                            <?php if ($envio['saldo_pendiente'] > 0): ?>
                                <div style="font-size: 0.85rem; color: var(--danger);">
                                    Pendiente: <?php echo formatMoney($envio['saldo_pendiente']); ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.85rem; color: var(--success);">✓ Pagado</div>
                            <?php endif; ?>
                        <?php elseif ($envio['es_costo_cero']): ?>
                            <span class="badge badge-info">Sin Costo</span>
                        <?php else: ?>
                            <button 
                                class="btn btn-secondary" 
                                style="font-size: 0.85rem; padding: 0.25rem 0.75rem;"
                                onclick="event.stopPropagation(); asignarCosto(<?php echo $envio['id']; ?>)"
                            >
                                💰 Asignar
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="action-buttons" onclick="event.stopPropagation()">
                        <button 
                            class="btn-icon" 
                            title="Ver detalle"
                            onclick="window.location.href='/admin/detalle-envio.php?id=<?php echo $envio['id']; ?>'"
                        >
                            👁️
                        </button>
                        <button 
                            class="btn-icon" 
                            title="Asignar costo"
                            onclick="asignarCosto(<?php echo $envio['id']; ?>)"
                        >
                            💰
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal para Asignar Costo -->
<div id="modal-costo" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--negro-card); padding:2rem; border-radius:12px; max-width:500px; width:90%;">
        <h2 style="color:var(--amarillo-primary); margin-bottom:1.5rem;">💰 Asignar Costo al Envío</h2>
        
        <form id="form-asignar-costo" onsubmit="return submitCosto(event)">
            <input type="hidden" id="envio_id_costo" name="envio_id">
            
            <div class="form-group">
                <label class="form-label">Costo Total (USD) <span class="required">*</span></label>
                <input 
                    type="number" 
                    id="costo_monto" 
                    name="costo" 
                    class="form-input" 
                    step="0.01" 
                    min="0"
                    placeholder="0.00"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Notas (opcional)</label>
                <textarea 
                    name="notas" 
                    class="form-input" 
                    rows="3"
                    placeholder="Detalles adicionales sobre el costo..."
                ></textarea>
            </div>
            
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCosto()">Cancelar</button>
                <button type="submit" class="btn btn-primary">✓ Asignar Costo</button>
            </div>
        </form>
    </div>
</div>

<script>
// Cambiar estado de envío
async function cambiarEstado(envioId, nuevoEstado) {
    if (!confirm(`¿Cambiar estado a: ${nuevoEstado}?`)) {
        location.reload();
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('envio_id', envioId);
        formData.append('nuevo_estado', nuevoEstado);
        
        const response = await fetch('/api/actualizar-estado.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert('Estado actualizado correctamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        window.ExpressatechCargo.showAlert(error.message, 'danger');
        location.reload();
    }
}

// Abrir modal de asignar costo
function asignarCosto(envioId) {
    document.getElementById('envio_id_costo').value = envioId;
    document.getElementById('modal-costo').style.display = 'flex';
}

// Cerrar modal
function cerrarModalCosto() {
    document.getElementById('modal-costo').style.display = 'none';
    document.getElementById('form-asignar-costo').reset();
}

// Enviar formulario de costo
async function submitCosto(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/api/asignar-costo.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.ExpressatechCargo.showAlert('Costo asignado exitosamente', 'success');
            cerrarModalCosto();
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        window.ExpressatechCargo.showAlert(error.message, 'danger');
    }
    
    return false;
}

// Cerrar modal al hacer click fuera
document.getElementById('modal-costo')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalCosto();
    }
});
</script>

<?php include '../includes/footer.php'; ?>