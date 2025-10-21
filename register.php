<?php
/**
 * EXPRESSATECH CARGO - Registro (VERSIÓN CORREGIDA)
 */

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('/cliente/dashboard.php');
}

$error = '';
$success = '';
$formData = [];

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRF($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido';
    } else {
        // Capturar datos
        $formData = [
            'nombre' => sanitize($_POST['nombre'] ?? ''),
            'apellido' => sanitize($_POST['apellido'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'telefono' => sanitize($_POST['telefono'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];
        
        // Validaciones
        if (empty($formData['nombre']) || empty($formData['apellido']) || 
            empty($formData['email']) || empty($formData['password'])) {
            $error = 'Todos los campos marcados son obligatorios';
        } elseif (!validEmail($formData['email'])) {
            $error = 'Email inválido';
        } elseif (strlen($formData['password']) < PASSWORD_MIN_LENGTH) {
            $error = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
        } elseif ($formData['password'] !== $formData['password_confirm']) {
            $error = 'Las contraseñas no coinciden';
        } else {
            // Crear usuario
            $result = createUser(
                $formData['nombre'],
                $formData['apellido'],
                $formData['email'],
                $formData['telefono'],
                $formData['password']
            );
            
            if ($result['success']) {
                // Redirigir al login
                redirect('/login.php?message=' . urlencode('¡Registro exitoso! Ya puedes iniciar sesión'));
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Expressatech Cargo</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="form-page">

    <div class="form-container">
        <!-- Logo -->
        <img src="/assets/images/logo-expressatech.png" alt="Expressatech Cargo" class="form-logo">
        
        <!-- Título -->
        <h1 class="form-title">Crear Cuenta</h1>
        <p class="form-subtitle">Únete a Expressatech Cargo</p>
        
        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>✕</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario -->
        <form method="POST" action="" data-validate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
            
            <!-- Nombre y Apellido -->
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre" class="form-label">
                        Nombre <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        class="form-input" 
                        placeholder="Tu nombre"
                        value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>"
                        required
                        autofocus
                    >
                    <span class="form-error"></span>
                </div>
                
                <div class="form-group">
                    <label for="apellido" class="form-label">
                        Apellido <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="apellido" 
                        name="apellido" 
                        class="form-input" 
                        placeholder="Tu apellido"
                        value="<?php echo htmlspecialchars($formData['apellido'] ?? ''); ?>"
                        required
                    >
                    <span class="form-error"></span>
                </div>
            </div>
            
            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">
                    Email <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="tu@email.com"
                    value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                    required
                >
                <span class="form-error"></span>
            </div>
            
            <!-- Teléfono -->
            <div class="form-group">
                <label for="telefono" class="form-label">
                    Teléfono (WhatsApp)
                </label>
                <input 
                    type="tel" 
                    id="telefono" 
                    name="telefono" 
                    class="form-input" 
                    placeholder="+58 414 1234567"
                    value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>"
                >
                <span class="form-error"></span>
            </div>
            
            <!-- Contraseña -->
            <div class="form-group">
                <label for="password" class="form-label">
                    Contraseña <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres"
                    data-min-length="<?php echo PASSWORD_MIN_LENGTH; ?>"
                    required
                >
                <span class="form-error"></span>
            </div>
            
            <!-- Confirmar Contraseña -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">
                    Confirmar Contraseña <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    class="form-input" 
                    placeholder="Repite tu contraseña"
                    required
                >
                <span class="form-error"></span>
            </div>
            
            <!-- Botón Submit -->
            <button type="submit" class="btn btn-primary btn-block">
                📝 Crear Cuenta
            </button>
        </form>
        
        <!-- Links adicionales -->
        <div class="form-link">
            ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión aquí</a>
        </div>
        
        <div class="form-link">
            <a href="/index.php">← Volver al inicio</a>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>