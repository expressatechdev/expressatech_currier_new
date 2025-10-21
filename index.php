<?php
/**
 * EXPRESSATECH CARGO - Landing Page (VERSIÓN CORREGIDA)
 * Página de inicio con información del servicio
 */

define('EXPRESSATECH_ACCESS', true);
require_once 'includes/config.php';

// Si el usuario ya está logueado, redirigir al dashboard correspondiente
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/cliente/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Expressatech Cargo - Sistema de gestión logística para envíos de Miami a Venezuela">
    <title>Expressatech Cargo - Envíos Miami a Venezuela</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Favicon (opcional) -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
</head>
<body class="landing-page">

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Logo Principal (TAMAÑO AJUSTADO) -->
        <img src="/assets/images/logo-expressatech.png" alt="Expressatech Cargo" class="landing-logo" style="max-width: 90px;">
        
        <!-- Título Principal -->
        <h1 class="hero-title">
            Tus envíos de <span class="highlight">Miami a Venezuela</span><br>
            de forma rápida y segura
        </h1>
        
        <!-- Subtítulo -->
        <p class="hero-subtitle">
            Sistema inteligente de gestión logística con seguimiento en tiempo real, 
            consolidación de paquetes y entrega garantizada en Puerto Ordaz.
        </p>
        
        <!-- Features Grid -->
        <div class="hero-features">
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <h3 class="feature-title">Consolidación</h3>
                <p class="feature-text">
                    Agrupamos tus compras para optimizar costos de envío
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🛫</div>
                <h3 class="feature-title">Seguimiento Real</h3>
                <p class="feature-text">
                    Monitorea tu paquete desde Miami hasta Puerto Ordaz
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Entrega Rápida</h3>
                <p class="feature-text">
                    Tiempos de entrega optimizados y transparentes
                </p>
            </div>
        </div>
        
        <!-- Call to Action Buttons -->
        <div class="hero-cta">
            <a href="/login.php" class="btn btn-primary">
                ✈️ Acceder al Sistema
            </a>
            <a href="/register.php" class="btn btn-secondary">
                📝 Crear Cuenta
            </a>
        </div>
        
        <!-- Información de Contacto -->
        <p style="margin-top: 2rem; color: var(--gris-text); font-size: 0.9rem;">
            ¿Necesitas ayuda? <a href="mailto:contacto@expressatech.net" style="color: var(--amarillo-primary);">contacto@expressatech.net</a>
        </p>
    </section>

    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
</body>
</html>