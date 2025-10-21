<?php
define('EXPRESSATECH_ACCESS', true);
require_once 'includes/config.php';

echo "<h1>Test de Conexión - Expressatech Cargo</h1>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Conexión exitosa a la base de datos</p>";
    
    // Probar que las tablas existen
    $tables = queryAll("SHOW TABLES");
    echo "<h3>Tablas creadas:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>" . array_values($table)[0] . "</li>";
    }
    echo "</ul>";
    
    // Verificar productos 4Life
    $count = queryOne("SELECT COUNT(*) as total FROM catalogo_4life");
    echo "<p>✅ Productos 4Life cargados: " . $count['total'] . "</p>";
    
    // Verificar usuario admin
    $admin = queryOne("SELECT nombre, email FROM usuarios WHERE tipo = 'admin'");
    echo "<p>✅ Usuario admin: " . $admin['nombre'] . " (" . $admin['email'] . ")</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>