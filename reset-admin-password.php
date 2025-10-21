<?php
/**
 * EXPRESSATECH CARGO - Reset Password Admin
 * Este script actualiza la contraseña del administrador
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('EXPRESSATECH_ACCESS', true);
require_once __DIR__ . '/includes/config.php';

echo "<h1>🔐 Reset Contraseña Admin - Expressatech Cargo</h1>";

// Nueva contraseña
$newPassword = 'Admin123!';
$email = 'contacto@expressatech.net';

echo "<h2>Generando nuevo hash...</h2>";

// Generar hash
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

echo "<p style='color:blue;'>📝 Contraseña: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
echo "<p style='color:gray;'>🔒 Hash generado: <code>" . $hashedPassword . "</code></p>";

// Actualizar en base de datos
try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashedPassword, $email]);
    
    if ($result) {
        echo "<div style='background:#4CAF50;color:white;padding:20px;border-radius:8px;margin:20px 0;'>";
        echo "<h2>✅ CONTRASEÑA ACTUALIZADA EXITOSAMENTE</h2>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Nueva Contraseña:</strong> $newPassword</p>";
        echo "</div>";
        
        // Verificar que funcionó
        echo "<h2>Verificando actualización...</h2>";
        $user = $db->query("SELECT * FROM usuarios WHERE email = '$email'")->fetch();
        
        if ($user) {
            $verify = password_verify($newPassword, $user['password']);
            if ($verify) {
                echo "<p style='color:green;font-size:18px;'>✅ Verificación exitosa - El hash coincide con la contraseña</p>";
                echo "<div style='background:#FFD700;color:#000;padding:15px;border-radius:8px;margin-top:20px;'>";
                echo "<h3>🚀 Ya puedes iniciar sesión</h3>";
                echo "<p><strong>Email:</strong> contacto@expressatech.net</p>";
                echo "<p><strong>Contraseña:</strong> Admin123!</p>";
                echo "<p><a href='/login.php' style='display:inline-block;background:#000;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:10px;'>Ir al Login</a></p>";
                echo "</div>";
            } else {
                echo "<p style='color:red;'>❌ Error: El hash no coincide (problema con password_verify)</p>";
            }
        }
        
    } else {
        echo "<p style='color:red;'>❌ Error al actualizar la contraseña</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background:#f44336;color:white;padding:20px;border-radius:8px;'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>ℹ️ Información Adicional</h2>";
echo "<p>Si necesitas cambiar la contraseña a otra diferente, edita la variable <code>\$newPassword</code> en este archivo.</p>";
echo "<p style='color:red;'><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.</p>";
?>