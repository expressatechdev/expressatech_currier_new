<?php
/**
 * EXPRESSATECH CARGO - Logout
 * Cerrar sesión del usuario
 */

define('EXPRESSATECH_ACCESS', true);
require_once 'includes/config.php';

// Destruir sesión
session_unset();
session_destroy();

// Redirigir al login con mensaje
redirect('/login.php?message=' . urlencode('Sesión cerrada exitosamente'));
?>