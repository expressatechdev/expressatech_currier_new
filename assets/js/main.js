/**
 * EXPRESSATECH CARGO - JavaScript Principal
 * Sistema de Gestión Logística Miami - Venezuela
 */

// =====================================================
// INICIALIZACIÓN
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Expressatech Cargo - Sistema inicializado');
    
    // Inicializar módulos
    initSidebar();
    initForms();
    initNotifications();
    initAnimations();
    initPlaneAnimation();
});

// =====================================================
// GESTIÓN DEL SIDEBAR (MOBILE)
// =====================================================
function initSidebar() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            
            // Cerrar sidebar al hacer click fuera (mobile)
            if (sidebar.classList.contains('active')) {
                document.addEventListener('click', closeSidebarOnClickOutside);
            }
        });
    }
    
    function closeSidebarOnClickOutside(e) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
            document.removeEventListener('click', closeSidebarOnClickOutside);
        }
    }
    
    // Marcar item activo en navegación
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        if (item.getAttribute('href') === currentPath) {
            item.classList.add('active');
        }
    });
}

// =====================================================
// VALIDACIÓN Y GESTIÓN DE FORMULARIOS
// =====================================================
function initForms() {
    // Validación en tiempo real
    const formInputs = document.querySelectorAll('.form-input');
    
    formInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
    
    // Prevenir envío de formulario si hay errores
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('.form-input[required]');
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Por favor, corrige los errores en el formulario', 'danger');
            } else {
                // Mostrar loading en botón
                const submitBtn = this.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading"></span> Procesando...';
                }
            }
        });
    });
}

// Validar campo individual
function validateField(field) {
    const formGroup = field.closest('.form-group');
    const errorElement = formGroup.querySelector('.form-error');
    let isValid = true;
    let errorMessage = '';
    
    // Limpiar error previo
    formGroup.classList.remove('error');
    if (errorElement) {
        errorElement.textContent = '';
    }
    
    // Validar campo requerido
    if (field.hasAttribute('required') && !field.value.trim()) {
        isValid = false;
        errorMessage = 'Este campo es obligatorio';
    }
    
    // Validar email
    if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
            isValid = false;
            errorMessage = 'Email inválido';
        }
    }
    
    // Validar teléfono
    if (field.type === 'tel' && field.value) {
        const phoneRegex = /^[0-9+\-\s()]+$/;
        if (!phoneRegex.test(field.value)) {
            isValid = false;
            errorMessage = 'Teléfono inválido';
        }
    }
    
    // Validar contraseña
    if (field.type === 'password' && field.value && field.hasAttribute('data-min-length')) {
        const minLength = parseInt(field.getAttribute('data-min-length'));
        if (field.value.length < minLength) {
            isValid = false;
            errorMessage = `Mínimo ${minLength} caracteres`;
        }
    }
    
    // Validar confirmación de contraseña
    if (field.name === 'password_confirm') {
        const passwordField = document.querySelector('[name="password"]');
        if (passwordField && field.value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Las contraseñas no coinciden';
        }
    }
    
    // Mostrar error si no es válido
    if (!isValid) {
        formGroup.classList.add('error');
        if (errorElement) {
            errorElement.textContent = errorMessage;
        }
    }
    
    return isValid;
}

// =====================================================
// SISTEMA DE NOTIFICACIONES Y ALERTAS
// =====================================================
function initNotifications() {
    // Procesar alertas del servidor (PHP)
    const urlParams = new URLSearchParams(window.location.search);
    const alertType = urlParams.get('alert');
    const alertMessage = urlParams.get('message');
    
    if (alertType && alertMessage) {
        showAlert(decodeURIComponent(alertMessage), alertType);
        
        // Limpiar URL
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    // Auto-cerrar alertas después de 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            fadeOut(alert);
        });
    }, 5000);
}

// Mostrar alerta dinámica
function showAlert(message, type = 'info') {
    const alertContainer = document.querySelector('.alert-container') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <strong>${getAlertIcon(type)}</strong> ${message}
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        fadeOut(alert);
    }, 5000);
    
    // Reproducir sonido de notificación
    playNotificationSound(type);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.className = 'alert-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

function getAlertIcon(type) {
    const icons = {
        success: '✓',
        danger: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

// Reproducir sonido de notificación
function playNotificationSound(type) {
    // Solo reproducir para alertas importantes
    if (type === 'success' || type === 'danger') {
        const audio = new Audio('/assets/sounds/notification.mp3');
        audio.volume = 0.3;
        audio.play().catch(err => {
            console.log('No se pudo reproducir sonido:', err);
        });
    }
}

// =====================================================
// ANIMACIONES
// =====================================================
function initAnimations() {
    // Fade in elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const animatedElements = document.querySelectorAll('.stat-card, .card, .feature-card');
    animatedElements.forEach(el => observer.observe(el));
}

// Animación del avión en el hero
function initPlaneAnimation() {
    const heroSection = document.querySelector('.hero-section');
    
    if (heroSection) {
        // Crear elemento del avión
        const plane = document.createElement('div');
        plane.className = 'hero-plane';
        plane.innerHTML = '✈️';
        heroSection.appendChild(plane);
        
        // El avión cruza automáticamente cada 10 segundos (controlado por CSS)
        // Aquí podemos agregar lógica adicional si se necesita
    }
}

// =====================================================
// UTILIDADES
// =====================================================

// Fade out animation
function fadeOut(element, duration = 300) {
    element.style.transition = `opacity ${duration}ms`;
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.remove();
    }, duration);
}

// Formatear moneda
function formatMoney(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Formatear fecha
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copiado al portapapeles', 'success');
    }).catch(err => {
        console.error('Error al copiar:', err);
    });
}

// Confirmar acción
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// =====================================================
// AJAX HELPER
// =====================================================
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error en petición:', error);
        showAlert('Error de conexión. Intenta nuevamente.', 'danger');
        throw error;
    }
}

// =====================================================
// GESTIÓN DE SESIÓN (TIMEOUT)
// =====================================================
let sessionTimeout;
const SESSION_WARNING_TIME = 30 * 60 * 1000; // 30 minutos

function resetSessionTimeout() {
    clearTimeout(sessionTimeout);
    
    sessionTimeout = setTimeout(() => {
        if (confirm('Tu sesión está por expirar. ¿Deseas continuar?')) {
            // Renovar sesión
            fetch('/api/renew-session.php')
                .then(() => resetSessionTimeout())
                .catch(() => {
                    showAlert('Sesión expirada. Redirigiendo al login...', 'warning');
                    setTimeout(() => {
                        window.location.href = '/logout.php';
                    }, 2000);
                });
        } else {
            window.location.href = '/logout.php';
        }
    }, SESSION_WARNING_TIME);
}

// Iniciar control de sesión si usuario está logueado
if (document.body.classList.contains('logged-in')) {
    resetSessionTimeout();
    
    // Resetear timeout en cualquier actividad
    ['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetSessionTimeout);
    });
}

// =====================================================
// PRODUCTOS DINÁMICOS (para formulario de envío)
// =====================================================
function initProductosDinamicos() {
    const addProductBtn = document.getElementById('add-product');
    const productosContainer = document.getElementById('productos-container');
    
    if (addProductBtn && productosContainer) {
        addProductBtn.addEventListener('click', function() {
            addProductoRow();
        });
        
        // Permitir eliminar productos
        productosContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-product')) {
                e.target.closest('.producto-row').remove();
                updateProductNumbers();
            }
        });
    }
}

function addProductoRow() {
    const container = document.getElementById('productos-container');
    const productCount = container.querySelectorAll('.producto-row').length + 1;
    
    const row = document.createElement('div');
    row.className = 'producto-row';
    row.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>Producto ${productCount}</label>
                <select name="productos[${productCount}][nombre]" class="form-input" required>
                    <option value="">Seleccionar...</option>
                    <!-- Opciones cargadas dinámicamente -->
                </select>
            </div>
            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" name="productos[${productCount}][cantidad]" 
                       class="form-input" min="1" value="1" required>
            </div>
        </div>
        <div class="form-group">
            <label>Detalle (opcional)</label>
            <input type="text" name="productos[${productCount}][detalle]" 
                   class="form-input" placeholder="Ej: Sabor vainilla, talla L...">
        </div>
        <button type="button" class="btn btn-secondary remove-product">
            Eliminar Producto
        </button>
        <hr style="margin: 1rem 0; border-color: #444;">
    `;
    
    container.appendChild(row);
}

function updateProductNumbers() {
    const rows = document.querySelectorAll('.producto-row');
    rows.forEach((row, index) => {
        const label = row.querySelector('label');
        if (label) {
            label.textContent = `Producto ${index + 1}`;
        }
    });
}

// =====================================================
// CARGA CONDICIONAL DE PRODUCTOS 4LIFE
// =====================================================
function init4LifeConditional() {
    const empresaSelect = document.querySelector('[name="empresa_compra"]');
    
    if (empresaSelect) {
        empresaSelect.addEventListener('change', function() {
            const is4Life = this.value.toLowerCase().includes('4life');
            updateProductOptions(is4Life);
        });
        
        // Trigger inicial si ya hay valor
        if (empresaSelect.value) {
            const is4Life = empresaSelect.value.toLowerCase().includes('4life');
            updateProductOptions(is4Life);
        }
    }
}

function updateProductOptions(is4Life) {
    const productSelects = document.querySelectorAll('[name*="productos"][name*="[nombre]"]');
    
    productSelects.forEach(select => {
        // Aquí se cargarían los productos vía AJAX
        // Por ahora dejamos la estructura
        if (is4Life) {
            loadProductos4Life(select);
        } else {
            loadProductosGenerales(select);
        }
    });
}

async function loadProductos4Life(selectElement) {
    try {
        const response = await fetch('/api/obtener-productos.php?tipo=4life');
        const productos = await response.json();
        
        selectElement.innerHTML = '<option value="">Seleccionar producto 4Life...</option>';
        productos.forEach(prod => {
            const option = document.createElement('option');
            option.value = prod.nombre_producto;
            option.textContent = prod.nombre_producto;
            selectElement.appendChild(option);
        });
    } catch (error) {
        console.error('Error cargando productos 4Life:', error);
    }
}

async function loadProductosGenerales(selectElement) {
    // Similar a loadProductos4Life pero con productos generales
    selectElement.innerHTML = '<option value="">Seleccionar producto...</option>';
    // Implementación pendiente de API
}

// =====================================================
// TABLA INTERACTIVA
// =====================================================
function initDataTable() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Agregar funcionalidad de búsqueda
        addTableSearch(table);
        
        // Agregar ordenamiento de columnas
        addTableSort(table);
    });
}

function addTableSearch(table) {
    const searchInput = table.previousElementSibling?.querySelector('.table-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

function addTableSort(table) {
    const headers = table.querySelectorAll('th[data-sortable]');
    
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => sortTable(table, index));
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.dataset.sortOrder === 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (isAscending) {
            return aValue.localeCompare(bValue, undefined, { numeric: true });
        } else {
            return bValue.localeCompare(aValue, undefined, { numeric: true });
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.dataset.sortOrder = isAscending ? 'desc' : 'asc';
}

// =====================================================
// EXPORTAR FUNCIONES GLOBALES
// =====================================================
window.ExpressatechCargo = {
    showAlert,
    formatMoney,
    formatDate,
    copyToClipboard,
    confirmAction,
    fetchAPI
};

console.log('✅ Expressatech Cargo JavaScript cargado correctamente');