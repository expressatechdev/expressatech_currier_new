<?php
/**
 * EXPRESSATECH CARGO - Mis Envíos (Cliente)
 * Lista de todos los envíos del cliente
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$pageTitle = 'Mis Envíos';
$pageSubtitle = 'Historial completo de tus envíos';

$currentUser = getCurrentUser();
$clienteId = $currentUser['id'];

// Filtros
$filtroEstado = $_GET['estado'] ?? '';

// Obtener envíos
$sql = "
    SELECT 
        e.*,
        (SELECT COUNT(*) FROM productos WHERE envio_id = e.id) as total_productos,
        (SELECT SUM(cantidad) FROM productos WHERE envio_id = e.id) as suma_productos
    FROM envios e
    WHERE e.cliente_id = ?
";

$params = [$clienteId];

if ($filtroEstado) {
    $sql .= " AND e.estado = ?";
    $params[] = $filtroEstado;
}

$sql .= " ORDER BY e.fecha_registro DESC";

$envios = queryAll($sql, $params);

// Contar por estado
$estadisticas = queryOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado NOT IN ('Entregado') THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as entregados,
        SUM(CASE WHEN saldo_pendiente > 0 THEN 1 ELSE 0 END) as pendientes_pago
    FROM envios
    WHERE cliente_id = ?
", [$clienteId]);

include '../includes/header.php';
?>

<style>
.filter-bar {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-item {
    flex: 1;
    min-width: 200px;
}

.envio-card {
    background: var(--negro-card);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 2px solid transparent;
    transition: all 0.3s;
    cursor: pointer;
}

.envio-card:hover {
    border-color: var(--amarillo-primary);
    transform: translateY(-3px);
    box-shadow: var(--shadow-yellow);
}

.envio-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.tracking-info {
    flex: 1;
}

.tracking-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--amarillo-primary);
    margin-bottom: 0.5rem;
}

.tracking-meta {
    color: var(--gris-text);
    font-size: 0.9rem;
}

.envio-body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #444;
}

.info-item {
    text-align: center;
}

.info-label {
    color: var(--gris-text);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-value {
    color: var(--blanco);
    font-weight: 600;
    font-size: 1.1rem;
}

.timeline-progress {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #444;
}

.progress-bar {
    height: 6px;
    background: #333;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--amarillo-dark), var(--amarillo-primary));
    transition: width 0.5s ease;
}

.status-pills {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.status-pill {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.stats-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-mini {
    background: var(--negro-card);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    border-left: 3px solid var(--amarillo-primary);
}

.stat-mini-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--amarillo-primary);
}

.stat-mini-label {
    color: var(--gris-text);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .envio-header {
        flex-direction: column;
    }
    
    .envio-body {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Estadísticas Mini -->
<div class="stats-mini-grid">
    <div class="stat-mini">
        <div class="stat-mini-value"><?php echo $estadisticas['total'] ?? 0; ?></div>
        <div class="stat-mini-label">Total Envíos</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-value"><?php echo $estadisticas['activos'] ?? 0; ?></div>
        <div class="stat-mini-label">En Tránsito</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-value"><?php echo $estadisticas['entregados'] ?? 0; ?></div>
        <div class="stat-mini-label">Entregados</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-value"><?php echo $estadisticas['pendientes_pago'] ?? 0; ?></div>
        <div class="stat-mini-label">Pendientes Pago</div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-bar">
    <div class="filter-item">
        <select 
            id="filtro-estado" 
            class="form-input"
            onchange="window.location.href='?estado=' + this.value"
        >
            <option value="">Todos los estados</option>
            <option value="En tránsito" <?php echo $filtroEstado === 'En tránsito' ? 'selected' : ''; ?>>En tránsito</option>
            <option value="Recibido en Miami" <?php echo $filtroEstado === 'Recibido en Miami' ? 'selected' : ''; ?>>Recibido en Miami</option>
            <option value="Consolidado" <?php echo $filtroEstado === 'Consolidado' ? 'selected' : ''; ?>>Consolidado</option>
            <option value="En camino a Venezuela" <?php echo $filtroEstado === 'En camino a Venezuela' ? 'selected' : ''; ?>>En camino a Venezuela</option>
            <option value="Llegada a Aduana" <?php echo $filtroEstado === 'Llegada a Aduana' ? 'selected' : ''; ?>>En Aduana</option>
            <option value="Llegada a Puerto Ordaz" <?php echo $filtroEstado === 'Llegada a Puerto Ordaz' ? 'selected' : ''; ?>>En Puerto Ordaz</option>
            <option value="Entregado" <?php echo $filtroEstado === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
        </select>
    </div>
    
    <a href="/cliente/nuevo-envio.php" class="btn btn-primary" style="white-space: nowrap;">
        ➕ Nuevo Envío
    </a>
</div>

<!-- Lista de Envíos -->
<?php if (empty($envios)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No tienes envíos <?php echo $filtroEstado ? 'con este estado' : 'registrados'; ?></h3>
            <p>
                <?php if ($filtroEstado): ?>
                    <a href="?">Ver todos los envíos</a>
                <?php else: ?>
                    Comienza registrando tu primer envío desde Miami
                <?php endif; ?>
            </p>
            <?php if (!$filtroEstado): ?>
                <a href="/cliente/nuevo-envio.php" class="btn btn-primary" style="margin-top: 1rem;">
                    ➕ Registrar Primer Envío
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($envios as $envio): 
        // Calcular progreso según estado
        $estados = [
            'En tránsito' => 10,
            'Recibido en Miami' => 25,
            'Consolidado' => 40,
            'En camino a Venezuela' => 60,
            'Llegada a Aduana' => 75,
            'Llegada a Puerto Ordaz' => 90,
            'Entregado' => 100
        ];
        $progreso = $estados[$envio['estado']] ?? 0;
        
        // Badge class según estado
        $badgeClass = 'badge-info';
        if ($envio['estado'] === 'Entregado') $badgeClass = 'badge-success';
        elseif ($envio['estado'] === 'En tránsito') $badgeClass = 'badge-warning';
    ?>
        <div class="envio-card" onclick="window.location.href='/cliente/detalle-envio.php?id=<?php echo $envio['id']; ?>'">
            <!-- Header del Envío -->
            <div class="envio-header">
                <div class="tracking-info">
                    <div class="tracking-number">
                        <?php echo htmlspecialchars($envio['tracking_interno']); ?>
                    </div>
                    <div class="tracking-meta">
                        📅 Registrado: <?php echo formatDate($envio['fecha_registro'], 'd/m/Y'); ?>
                        <?php if ($envio['destinatario_nombre']): ?>
                            <br>👤 Para: <?php echo htmlspecialchars($envio['destinatario_nombre']); ?>
                        <?php endif; ?>
                        <br>🏪 <?php echo htmlspecialchars($envio['empresa_compra']); ?>
                    </div>
                </div>
                
                <div>
                    <span class="badge <?php echo $badgeClass; ?>" style="font-size: 1rem;">
                        <?php echo $envio['estado']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Barra de Progreso -->
            <div class="timeline-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progreso; ?>%"></div>
                </div>
                <div style="color: var(--gris-text); font-size: 0.85rem; text-align: center;">
                    <?php echo $progreso; ?>% completado
                </div>
            </div>
            
            <!-- Info Grid -->
            <div class="envio-body">
                <div class="info-item">
                    <div class="info-label">Productos</div>
                    <div class="info-value"><?php echo $envio['suma_productos'] ?? 0; ?> items</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Costo</div>
                    <div class="info-value">
                        <?php if ($envio['costo_calculado'] > 0): ?>
                            <?php echo formatMoney($envio['costo_calculado']); ?>
                        <?php else: ?>
                            <span style="color: var(--gris-text);">Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Saldo</div>
                    <div class="info-value">
                        <?php if ($envio['saldo_pendiente'] > 0): ?>
                            <span style="color: var(--danger);">
                                <?php echo formatMoney($envio['saldo_pendiente']); ?>
                            </span>
                        <?php elseif ($envio['costo_calculado'] > 0): ?>
                            <span style="color: var(--success);">✓ Pagado</span>
                        <?php else: ?>
                            <span style="color: var(--gris-text);">-</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($envio['fecha_llegada_puerto_ordaz']): ?>
                    <div class="info-item">
                        <div class="info-label">Llegó Puerto Ordaz</div>
                        <div class="info-value" style="font-size: 0.9rem;">
                            <?php echo formatDate($envio['fecha_llegada_puerto_ordaz'], 'd/m/Y'); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pills de Acción Rápida -->
            <div class="status-pills">
                <?php if ($envio['saldo_pendiente'] > 0): ?>
                    <span class="status-pill" style="background: var(--danger); color: white;">
                        ⚠ Pago Pendiente
                    </span>
                <?php endif; ?>
                
                <?php if ($envio['estado'] === 'Llegada a Puerto Ordaz'): ?>
                    <span class="status-pill" style="background: var(--success); color: white;">
                        ✓ Listo para Retiro
                    </span>
                <?php endif; ?>
                
                <?php if ($envio['es_costo_cero']): ?>
                    <span class="status-pill" style="background: var(--info); color: white;">
                        🎁 Sin Costo
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>