<?php
/**
 * EXPRESSATECH CARGO - Registrar Pago (Cliente)
 * Permite al cliente registrar un pago para un envío
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$currentUser = getCurrentUser();
$envioId = $_GET['envio'] ?? 0;

// Obtener datos del envío
$envio = queryOne("
    SELECT e.*, 
           (SELECT SUM(monto) FROM pagos WHERE envio_id = e.id AND estado IN ('Verificado', 'Pendiente')) as total_pagado_registrado
    FROM envios e
    WHERE e.id = ? AND e.cliente_id = ?
", [$envioId, $currentUser['id']]);

if (!$envio) {
    redirect('/cliente/mis-envios.php');
}

// Si no tiene costo asignado, no puede pagar
if ($envio['costo_calculado'] <= 0) {
    redirect('/cliente/detalle-envio.php?id=' . $envioId);
}

// Calcular saldo real (considerando pagos pendientes y verificados)
$saldoReal = $envio['costo_calculado'] - ($envio['total_pagado_registrado'] ?? 0);

if ($saldoReal <= 0) {
    redirect('/cliente/detalle-envio.php?id=' . $envioId);
}

$pageTitle = 'Registrar Pago';
$pageSubtitle = 'Envío ' . $envio['tracking_interno'];

// Obtener tasa Binance del día (puedes integrar API real después)
$tasaBinance = 50.00; // Temporal - después integrar API

include '../includes/header.php';
?>

<style>
.payment-summary {
    background: linear-gradient(135deg, rgba(255,196,37,0.2), rgba(255,196,37,0.05));
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 2px solid var(--amarillo-primary);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255,196,37,0.2);
}

.summary-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.summary-label {
    color: var(--gris-text);
    font-size: 0.95rem;
}

.summary-value {
    color: var(--blanco);
    font-weight: 600;
    font-size: 1.1rem;
}

.summary-value.highlight {
    color: var(--amarillo-primary);
    font-size: 1.5rem;
    font-weight: 700;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.method-card {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.method-card:hover {
    border-color: var(--amarillo-primary);
    transform: translateY(-3px);
}

.method-card.selected {
    border-color: var(--amarillo-primary);
    background: rgba(255,196,37,0.1);
}

.method-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.method-name {
    color: var(--blanco);
    font-weight: 600;
}

.bolivares-calculator {
    background: var(--negro-card);
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
    display: none;
}

.bolivares-calculator.active {
    display: block;
}

.calculator-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.file-preview {
    background: var(--negro-light);
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    display: none;
}

.file-preview.active {
    display: block;
}
</style>

<!-- Resumen del Pago -->
<div class="payment-summary">
    <h3 style="color: var(--amarillo-primary); margin-bottom: 1.5rem; text-align: center;">
        💰 Resumen del Pago
    </h3>
    
    <div class="summary-row">
        <span class="summary-label">Envío:</span>
        <span class="summary-value"><?php echo htmlspecialchars($envio['tracking_interno']); ?></span>
    </div>
    
    <div class="summary-row">
        <span class="summary-label">Costo Total:</span>
        <span class="summary-value"><?php echo formatMoney($envio['costo_calculado']); ?></span>
    </div>
    
    <?php if ($envio['total_pagado_registrado'] > 0): ?>
    <div class="summary-row">
        <span class="summary-label">Ya Registrado:</span>
        <span class="summary-value" style="color: var(--success);">
            -<?php echo formatMoney($envio['total_pagado_registrado']); ?>
        </span>
    </div>
    <?php endif; ?>
    
    <div class="summary-row">
        <span class="summary-label">Saldo a Pagar:</span>
        <span class="summary-value highlight"><?php echo formatMoney($saldoReal); ?></span>
    </div>
</div>

<!-- Formulario de Pago -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💳 Registrar Tu Pago</h2>
    </div>
    
    <form id="form-registrar-pago" enctype="multipart/form-data">
        <input type="hidden" name="cliente_id" value="<?php echo $currentUser['id']; ?>">
        <input type="hidden" name="envio_id" value="<?php echo $envioId; ?>">
        <input type="hidden" id="metodo-seleccionado" name="metodo" value="" required>
        
        <!-- Selección de Método de Pago -->
        <div class="form-group">
            <label class="form-label">
                Método de Pago <span class="required">*</span>
            </label>
            <div class="payment-methods">
                <div class="method-card" onclick="seleccionarMetodo('Zelle')">
                    <div class="method-icon">💵</div>
                    <div class="method-name">Zelle</div>
                </div>
                <div class="method-card" onclick="seleccionarMetodo('Zinli')">
                    <div class="method-icon">📱</div>
                    <div class="method-name">Zinli</div>
                </div>
                <div class="method-card" onclick="seleccionarMetodo('Dólares Banesco')">
                    <div class="method-icon">🏦</div>
                    <div class="method-name">Dólares Banesco</div>
                </div>
                <div class="method-card" onclick="seleccionarMetodo('Binance USDT')">
                    <div class="method-icon">₿</div>
                    <div class="method-name">Binance USDT</div>
                </div>
                <div class="method-card" onclick="seleccionarMetodo('Bolívares')">
                    <div class="method-icon">Bs</div>
                    <div class="method-name">Bolívares</div>
                </div>
            </div>
        </div>
        
        <!-- Monto en USD -->
        <div class="form-group">
            <label for="monto" class="form-label">
                Monto a Pagar (USD) <span class="required">*</span>
            </label>
            <input 
                type="number" 
                id="monto" 
                name="monto" 
                class="form-input"
                step="0.01"
                min="0.01"
                max="<?php echo $saldoReal; ?>"
                value="<?php echo $saldoReal; ?>"
                required
                oninput="calcularBolivares()"
            >
            <small style="color: var(--gris-text);">
                Puedes pagar el total o hacer pagos parciales
            </small>
        </div>
        
        <!-- Calculadora de Bolívares (oculta por defecto) -->
        <div id="bolivares-calculator" class="bolivares-calculator">
            <h4 style="color: var(--amarillo-primary); margin-bottom: 1rem;">
                📊 Calculadora de Bolívares
            </h4>
            
            <div class="form-group">
                <label class="form-label">Tasa del Día (Binance)</label>
                <input 
                    type="number" 
                    id="tasa-binance" 
                    name="tasa_binance" 
                    class="form-input"
                    step="0.01"
                    value="<?php echo $tasaBinance; ?>"
                    oninput="calcularBolivares()"
                >
                <small style="color: var(--gris-text);">
                    Tasa actual en Binance P2P
                </small>
            </div>
            
            <div style="background: rgba(255,196,37,0.1); padding: 1rem; border-radius: 6px; text-align: center;">
                <div style="color: var(--gris-text); margin-bottom: 0.5rem;">Total en Bolívares:</div>
                <div id="total-bolivares" style="color: var(--amarillo-primary); font-size: 2rem; font-weight: 700;">
                    Bs 0.00
                </div>
            </div>
        </div>
        
        <!-- Referencia del Pago -->
        <div class="form-group">
            <label for="referencia" class="form-label">
                Número de Referencia
            </label>
            <input 
                type="text" 
                id="referencia" 
                name="referencia" 
                class="form-input"
                placeholder="Ej: 123456789 (opcional)"
            >
            <small style="color: var(--gris-text);">
                Número de confirmación o referencia del pago
            </small>
        </div>
        
        <!-- Comprobante -->
        <div class="form-group">
            <label for="comprobante" class="form-label">
                Comprobante de Pago <span class="required">*</span>
            </label>
            <div class="file-upload-wrapper">
                <input 
                    type="file" 
                    id="comprobante" 
                    name="comprobante" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    required
                    onchange="previewFile(this)"
                >
                <label for="comprobante" class="file-upload-label">
                    📄 Click para seleccionar comprobante
                    <div class="file-name" id="file-name"></div>
                </label>
            </div>
            <small style="color: var(--gris-text);">
                Captura de pantalla o PDF del comprobante (máx. 5MB)
            </small>
            
            <div id="file-preview" class="file-preview">
                <img id="preview-image" style="max-width: 100%; border-radius: 6px; display: none;">
            </div>
        </div>
        
        <!-- Comentarios -->
        <div class="form-group">
            <label for="comentarios" class="form-label">
                Comentarios (opcional)
            </label>
            <textarea 
                id="comentarios" 
                name="comentarios" 
                class="form-input"
                rows="3"
                placeholder="Información adicional sobre el pago..."
            ></textarea>
        </div>
        
        <!-- Botones -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
            <a href="/cliente/detalle-envio.php?id=<?php echo $envioId; ?>" class="btn btn-secondary">
                Cancelar
            </a>
            <button type="submit" id="btn-submit" class="btn btn-primary">
                💰 Registrar Pago
            </button>
        </div>
    </form>
</div>

<script>
const saldoMaximo = <?php echo $saldoReal; ?>;
let metodoActual = '';

function seleccionarMetodo(metodo) {
    // Remover selección anterior
    document.querySelectorAll('.method-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Seleccionar nuevo
    event.currentTarget.classList.add('selected');
    document.getElementById('metodo-seleccionado').value = metodo;
    metodoActual = metodo;
    
    // Mostrar/ocultar calculadora de bolívares
    const calculator = document.getElementById('bolivares-calculator');
    if (metodo === 'Bolívares') {
        calculator.classList.add('active');
        calcularBolivares();
    } else {
        calculator.classList.remove('active');
    }
}

function calcularBolivares() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const tasa = parseFloat(document.getElementById('tasa-binance').value) || 0;
    const totalBs = monto * tasa;
    
    document.getElementById('total-bolivares').textContent = 
        'Bs ' + totalBs.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function previewFile(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('file-name').textContent = fileName ? `✓ ${fileName}` : '';
    
    // Preview de imagen
    const file = input.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('file-preview');
            const img = document.getElementById('preview-image');
            img.src = e.target.result;
            img.style.display = 'block';
            preview.classList.add('active');
        };
        reader.readAsDataURL(file);
    }
}

// Submit del formulario
document.getElementById('form-registrar-pago').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btn-submit');
    const btnText = btn.innerHTML;
    
    // Validar método seleccionado
    if (!metodoActual) {
        window.ExpressatechCargo.showAlert('Por favor selecciona un método de pago', 'warning');
        return;
    }
    
    // Validar monto
    const monto = parseFloat(document.getElementById('monto').value);
    if (monto <= 0 || monto > saldoMaximo) {
        window.ExpressatechCargo.showAlert(
            `El monto debe estar entre $0.01 y $${saldoMaximo.toFixed(2)}`, 
            'warning'
        );
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Registrando...';
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('/api/registrar-pago.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        const data = JSON.parse(responseText);
        
        if (data.success) {
            window.ExpressatechCargo.showAlert(
                '✓ Pago registrado exitosamente. Será verificado por el administrador.', 
                'success'
            );
            setTimeout(() => {
                window.location.href = '/cliente/detalle-envio.php?id=<?php echo $envioId; ?>';
            }, 2000);
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        window.ExpressatechCargo.showAlert(error.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = btnText;
    }
});
</script>

<?php include '../includes/footer.php'; ?>