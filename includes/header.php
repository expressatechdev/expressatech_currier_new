<?php
/**
 * EXPRESSATECH CARGO - Header Reutilizable
 * Incluye el header común para dashboards
 */

if (!defined('EXPRESSATECH_ACCESS')) {
    die('Acceso directo no permitido');
}

// Obtener datos del usuario actual
$currentUser = getCurrentUser();

if (!$currentUser) {
    redirect('/login.php');
}

$userInitial = strtoupper(substr($currentUser['nombre'], 0, 1));
$userName = $currentUser['nombre'] . ' ' . $currentUser['apellido'];
$userEmail = $currentUser['email'];
$userType = $currentUser['tipo'];
$isAdmin = ($userType === 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Expressatech Cargo</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="logged-in">

    <div class="dashboard-layout">
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Header del Sidebar -->
            <div class="sidebar-header">
                <img src="/assets/images/logo-expressatech.png" alt="Expressatech Cargo" class="sidebar-logo">
            </div>
            
            <!-- Navegación -->
            <nav class="sidebar-nav">
                <?php if ($isAdmin): ?>
                    <!-- Menú Admin -->
                    <a href="/admin/dashboard.php" class="nav-item">
                        <span class="nav-icon">🏠</span>
                        <span>Inicio</span>
                    </a>
                    <a href="/admin/envios.php" class="nav-item">
                        <span class="nav-icon">📦</span>
                        <span>Gestionar Envíos</span>
                    </a>
                    <a href="/admin/consolidados.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Consolidados</span>
                    </a>
                    <a href="/admin/verificar-pagos.php" class="nav-item">
                        <span class="nav-icon">💰</span>
                        <span>Verificar Pagos</span>
                    </a>
                    <a href="/admin/pagos.php" class="nav-item">
                        <span class="nav-icon">👥</span>
                        <span>Clientes</span>
                    </a>
                    <a href="/admin/reportes.php" class="nav-item">
                        <span class="nav-icon">📈</span>
                        <span>Reportes</span>
                    </a>
                <?php else: ?>
                    <!-- Menú Cliente -->
                    <a href="/cliente/dashboard.php" class="nav-item">
                        <span class="nav-icon">🏠</span>
                        <span>Inicio</span>
                    </a>
                    <a href="/cliente/mis-envios.php" class="nav-item">
                        <span class="nav-icon">📦</span>
                        <span>Mis Envíos</span>
                    </a>
                    <a href="/cliente/nuevo-envio.php" class="nav-item">
                        <span class="nav-icon">➕</span>
                        <span>Nuevo Envío</span>
                    </a>
                    <a href="/cliente/mis-pagos.php" class="nav-item">
                        <span class="nav-icon">💰</span>
                        <span>Mis Pagos</span>
                    </a>
                    <a href="/cliente/mi-cuenta.php" class="nav-item">
                        <span class="nav-icon">👤</span>
                        <span>Mi Cuenta</span>
                    </a>
                <?php endif; ?>
            </nav>
            
            <!-- Footer del Sidebar -->
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo $userInitial; ?></div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($userName); ?></h4>
                        <p><?php echo $isAdmin ? 'Administrador' : 'Cliente'; ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="btn btn-secondary btn-block">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="menu-toggle">☰</button>
                    <div>
                        <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                        <p><?php echo $pageSubtitle ?? ''; ?></p>
                    </div>
                </div>
                <div class="top-bar-right">
                    <div class="notification-bell">
                        🔔
                        <span class="notification-badge">3</span>
                    </div>
                    <span style="color: var(--gris-text);">
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Área de Contenido -->
            <div class="content-area"></div>