<?php
/**
 * EXPRESSATECH CARGO - Nuevo Envío (OPCIÓN A - SIMPLE)
 * 4Life: Dropdown | Otras: Texto Libre
 */

define('EXPRESSATECH_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$pageTitle = 'Registrar Nuevo Envío';
$pageSubtitle = 'Completa los datos de tu compra';

$currentUser = getCurrentUser();

// Lista de empresas
$empresas = [
    '4Life',
    'Amazon',
    'iHerb',
    'Walmart',
    'eBay',
    'Target',
    'Costco',
    'GNC',
    'Vitacost',
    'Otra'
];

include '../includes/header.php';
?>

<style>
.producto-row {
    background: rgba(255, 196, 37, 0.05);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 3px solid var(--amarillo-primary);
    position: relative;
}

.producto-row-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.producto-numero {
    color: var(--amarillo-primary);
    font-weight: 700;
    font-size: 1.1rem;
}

.btn-remove-producto {
    background: var(--danger);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.3s;
}

.btn-remove-producto:hover {
    background: #d32f2f;
    transform: scale(1.05);
}

.btn-add-producto {
    background: transparent;
    border: 2px dashed var(--amarillo-primary);
    color: var(--amarillo-primary);
    padding: 1rem;
    width: 100%;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s;
    margin-top: 1rem;
}

.btn-add-producto:hover {
    background: rgba(255, 196, 37, 0.1);
    border-style: solid;
}

.file-upload-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
}

.file-upload-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-upload-label {
    display: block;
    padding: 1rem;
    background: var(--negro-card);
    border: 2px dashed #666;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.file-upload-label:hover {
    border-color: var(--amarillo-primary);
    background: rgba(255, 196, 37, 0.05);
}

.file-name {
    margin-top: 0.5rem;
    color: var(--amarillo-primary);
    font-size: 0.9rem;
}
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📦 Nuevo Envío desde Miami</h2>
        <p style="color: var(--gris-text); font-size: 0.9rem; margin-top: 0.5rem;">
            Registra tu compra para que podamos rastrearla desde el momento que sale de la tienda
        </p>
    </div>
    
    <form id="form-nuevo-envio" enctype="multipart/form-data">
        <input type="hidden" name="cliente_id" value="<?php echo $currentUser['id']; ?>">
        
        <!-- Información del Envío -->
        <div style="background: var(--negro-light); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3 style="color: var(--amarillo-primary); margin-bottom: 1.5rem;">
                📋 Información del Envío
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_compra" class="form-label">
                        Fecha de Compra <span class="required">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="fecha_compra" 
                        name="fecha_compra" 
                        class="form-input"
                        max="<?php echo date('Y-m-d'); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="tracking_original" class="form-label">
                        Tracking o # de Orden
                    </label>
                    <input 
                        type="text" 
                        id="tracking_original" 
                        name="tracking_original" 
                        class="form-input"
                        placeholder="Ej: 1234567890"
                    >
                    <small style="color: var(--gris-text);">El número de seguimiento de la tienda</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="empresa_compra" class="form-label">
                    Tienda o Empresa de Compra <span class="required">*</span>
                </label>
                <select 
                    id="empresa_compra" 
                    name="empresa_compra" 
                    class="form-input"
                    required
                >
                    <option value="">Selecciona una tienda...</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo htmlspecialchars($empresa); ?>">
                            <?php echo htmlspecialchars($empresa); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="destinatario_nombre" class="form-label">
                    Este envío es para: (opcional)
                </label>
                <input 
                    type="text" 
                    id="destinatario_nombre" 
                    name="destinatario_nombre" 
                    class="form-input"
                    placeholder="Deja vacío si es para ti, o escribe el nombre del destinatario"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    Factura o Comprobante de Compra
                </label>
                <div class="file-upload-wrapper">
                    <input 
                        type="file" 
                        id="factura" 
                        name="factura" 
                        accept=".pdf,.jpg,.jpeg,.png"
                    >
                    <label for="factura" class="file-upload-label">
                        📄 Click para seleccionar archivo
                        <div class="file-name" id="file-name"></div>
                    </label>
                </div>
                <small style="color: var(--gris-text);">
                    Formatos permitidos: PDF, JPG, PNG (máx. 5MB)
                </small>
            </div>
        </div>
        
        <!-- Productos -->
        <div style="background: var(--negro-light); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3 style="color: var(--amarillo-primary); margin-bottom: 1.5rem;">
                🏷️ Productos del Envío
            </h3>
            
            <div id="productos-container">
                <!-- Los productos se agregan aquí dinámicamente -->
            </div>
            
            <button type="button" id="btn-add-producto" class="btn-add-producto">
                ➕ Agregar Otro Producto
            </button>
        </div>
        
        <!-- Botones -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="/cliente/dashboard.php" class="btn btn-secondary">
                Cancelar
            </a>
            <button type="submit" id="btn-submit" class="btn btn-primary">
                ✈️ Registrar Envío
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready - Iniciando script');
    
    const productosContainer = document.getElementById('productos-container');
    const btnAddProducto = document.getElementById('btn-add-producto');
    const empresaSelect = document.getElementById('empresa_compra');
    
    if (!productosContainer || !btnAddProducto || !empresaSelect) {
        console.error('ERROR: Elementos no encontrados');
        return;
    }
    
    let productoCount = 0;
    let productos4Life = [];
    let productosListosCargados = false;
    
    // Cargar productos de 4Life al inicio
    cargarProductos4Life();
    
    // Detectar cambio de empresa
    empresaSelect.addEventListener('change', function() {
        console.log('Empresa cambiada a:', this.value);
        // Limpiar productos y agregar uno nuevo
        productosContainer.innerHTML = '';
        productoCount = 0;
        agregarProducto();
    });
    
    // Botón agregar producto
    btnAddProducto.addEventListener('click', agregarProducto);
    
    // Preview de archivo
    document.getElementById('factura')?.addEventListener('change', function() {
        const fileName = this.files[0]?.name || '';
        document.getElementById('file-name').textContent = fileName ? `✓ ${fileName}` : '';
    });
    
    // Submit del formulario
    document.getElementById('form-nuevo-envio').addEventListener('submit', function(e) {
        e.preventDefault();
        enviarFormulario();
    });
    
    // Cargar productos 4Life
    async function cargarProductos4Life() {
        try {
            const response = await fetch('/api/obtener-productos.php?tipo=4life');
            const data = await response.json();
            
            console.log('Productos 4Life recibidos:', data);
            
            if (data.success && data.productos) {
                productos4Life = data.productos;
                productosListosCargados = true;
                console.log('✓ Total productos 4Life:', productos4Life.length);
                
                // Agregar primer producto después de cargar
                agregarProducto();
            } else {
                console.error('No se pudieron cargar los productos 4Life');
                alert('Error al cargar productos. Por favor recarga la página.');
            }
        } catch (error) {
            console.error('Error cargando productos:', error);
            alert('Error de conexión. Por favor recarga la página.');
        }
    }
    
    // Agregar producto
    function agregarProducto() {
        if (!productosListosCargados) {
            console.log('Esperando carga de productos...');
            return;
        }
        
        productoCount++;
        const empresa = empresaSelect.value.toLowerCase();
        const es4Life = empresa.includes('4life') || empresa.includes('4 life');
        
        console.log(`Agregando producto #${productoCount}, Es4Life: ${es4Life}`);
        
        const productoDiv = document.createElement('div');
        productoDiv.className = 'producto-row';
        productoDiv.dataset.numero = productoCount;
        
        if (es4Life) {
            // DROPDOWN PARA 4LIFE CON OPCIÓN "OTRO"
            let optionsHTML = '<option value="">Selecciona un producto de 4Life...</option>';
            
            // Agregar productos de 4Life
            productos4Life.forEach(producto => {
                optionsHTML += `<option value="${producto.nombre_producto}">${producto.nombre_producto}</option>`;
            });
            
            // Agregar opción "OTRO" al final
            optionsHTML += '<option value="__OTRO__">OTRO (Escribir producto)</option>';
            
            productoDiv.innerHTML = `
                <div class="producto-row-header">
                    <span class="producto-numero">Producto #${productoCount}</span>
                    ${productoCount > 1 ? '<button type="button" class="btn-remove-producto" onclick="eliminarProducto(this)">🗑️ Eliminar</button>' : ''}
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">
                            Nombre del Producto <span class="required">*</span>
                        </label>
                        <select 
                            name="productos[${productoCount}][nombre_select]" 
                            class="form-input producto-select-4life"
                            data-numero="${productoCount}"
                            onchange="toggle4LifeOtro(this, ${productoCount})"
                        >
                            ${optionsHTML}
                        </select>
                        
                        <!-- Campo de texto oculto por defecto (para OTRO) -->
                        <input 
                            type="text" 
                            name="productos[${productoCount}][nombre]" 
                            class="form-input campo-otro-4life"
                            id="campo-otro-4life-${productoCount}"
                            placeholder="Escribe el nombre del producto..."
                            style="display: none; margin-top: 0.5rem;"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Cantidad <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="productos[${productoCount}][cantidad]" 
                            class="form-input"
                            min="1"
                            value="1"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Detalle o Comentario (opcional)
                    </label>
                    <input 
                        type="text" 
                        name="productos[${productoCount}][detalle]" 
                        class="form-input"
                        placeholder="Ej: Sabor chocolate, presentación especial, etc."
                    >
                </div>
            `;
        } else {
            // CAMPOS DE TEXTO LIBRE PARA OTRAS EMPRESAS
            productoDiv.innerHTML = `
                <div class="producto-row-header">
                    <span class="producto-numero">Producto #${productoCount}</span>
                    ${productoCount > 1 ? '<button type="button" class="btn-remove-producto" onclick="eliminarProducto(this)">🗑️ Eliminar</button>' : ''}
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">
                            Nombre del Producto <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="productos[${productoCount}][nombre]" 
                            class="form-input"
                            placeholder="Escribe el nombre del producto..."
                            required
                        >
                        <small style="color: var(--gris-text);">Describe el producto que compraste</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Cantidad <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="productos[${productoCount}][cantidad]" 
                            class="form-input"
                            min="1"
                            value="1"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Detalle o Comentario (opcional)
                    </label>
                    <input 
                        type="text" 
                        name="productos[${productoCount}][detalle]" 
                        class="form-input"
                        placeholder="Ej: Sabor, tamaño, marca, características, etc."
                    >
                </div>
            `;
        }
        
        productosContainer.appendChild(productoDiv);
        console.log(`✓ Producto #${productoCount} agregado`);
    }
    
    // Función para manejar la opción "OTRO" en 4Life
    window.toggle4LifeOtro = function(select, numero) {
        const campoOtro = document.getElementById(`campo-otro-4life-${numero}`);
        const valor = select.value;
        
        console.log('Valor seleccionado:', valor);
        
        if (valor === '__OTRO__') {
            // Mostrar campo de texto
            campoOtro.style.display = 'block';
            campoOtro.required = true;
            select.required = false;
            console.log('Campo OTRO activado');
        } else {
            // Ocultar campo de texto
            campoOtro.style.display = 'none';
            campoOtro.required = false;
            campoOtro.value = '';
            select.required = true;
            
            // Si seleccionó un producto, copiar al campo nombre
            if (valor) {
                campoOtro.value = valor;
            }
        }
    };
    
    // Eliminar producto
    window.eliminarProducto = function(btn) {
        const row = btn.closest('.producto-row');
        if (document.querySelectorAll('.producto-row').length > 1) {
            row.remove();
            renumerarProductos();
        } else {
            alert('Debes mantener al menos un producto');
        }
    };
    
    // Renumerar productos
    function renumerarProductos() {
        document.querySelectorAll('.producto-row').forEach((row, index) => {
            row.querySelector('.producto-numero').textContent = `Producto #${index + 1}`;
        });
    }
    
    // Enviar formulario
    async function enviarFormulario() {
        const btn = document.getElementById('btn-submit');
        const btnText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Registrando...';
        
        try {
            const form = document.getElementById('form-nuevo-envio');
            const formData = new FormData(form);
            
            // Validar y procesar productos de 4Life
            let productoValido = true;
            document.querySelectorAll('.producto-select-4life').forEach((select) => {
                const numero = select.dataset.numero;
                const campoOtro = document.getElementById(`campo-otro-4life-${numero}`);
                
                if (select.value === '__OTRO__') {
                    // Si seleccionó OTRO, verificar que escribió algo
                    if (!campoOtro.value.trim()) {
                        productoValido = false;
                        alert('Por favor escribe el nombre del producto en "OTRO"');
                        return false;
                    }
                    // El valor ya está en el campo nombre
                } else if (select.value) {
                    // Si seleccionó un producto del dropdown, asignar al campo nombre
                    const inputNombre = form.querySelector(`[name="productos[${numero}][nombre]"]`);
                    if (inputNombre) {
                        inputNombre.value = select.value;
                    }
                }
            });
            
            if (!productoValido) {
                btn.disabled = false;
                btn.innerHTML = btnText;
                return;
            }
            
            console.log('Enviando formulario...');
            
            const response = await fetch('/api/procesar-envio.php', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            console.log('Response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Error parsing JSON:', responseText);
                throw new Error('Respuesta inválida del servidor');
            }
            
            if (data.success) {
                window.ExpressatechCargo.showAlert(
                    `¡Envío registrado exitosamente! Tracking: ${data.tracking_interno}`, 
                    'success'
                );
                setTimeout(() => {
                    window.location.href = '/cliente/mis-envios.php';
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
    }
});
</script>

<?php include '../includes/footer.php'; ?>