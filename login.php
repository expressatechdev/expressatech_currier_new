<?php
/**
 * EXPRESSATECH CARGO - Login (VERSIÓN CORREGIDA)
 */

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/cliente/dashboard.php');
    }
}

$error = '';
$success = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRF($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor, completa todos los campos';
        } else {
            $result = authenticateUser($email, $password);
            
            if ($result['success']) {
                // Redirigir según tipo de usuario
                if ($result['tipo'] === 'admin') {
                    redirect('/admin/dashboard.php');
                } else {
                    redirect('/cliente/dashboard.php');
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Mensajes de URL
if (isset($_GET['message'])) {
    $success = sanitize($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Expressatech Cargo</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="form-page">

    <div class="form-container">
        <!-- Logo -->
        <img src="/assets/images/logo-expressatech.png" alt="Expressatech Cargo" class="form-logo">
        
        <!-- Título -->
        <h1 class="form-title">Iniciar Sesión</h1>
        <p class="form-subtitle">Accede a tu panel de control</p>
        
        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>✕</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✓</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario -->
        <form method="POST" action="" data-validate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
            
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
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                    autofocus
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
                    placeholder="••••••••"
                    required
                >
                <span class="form-error"></span>
            </div>
            
            <!-- Botón Submit -->
            <button type="submit" class="btn btn-primary btn-block">
                ✈️ Iniciar Sesión
            </button>
        </form>
        
        <!-- Links adicionales -->
        <div class="form-link">
            ¿No tienes cuenta? <a href="/register.php">Regístrate aquí</a>
        </div>
        
        <div class="form-link">
            <a href="/index.php">← Volver al inicio</a>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>