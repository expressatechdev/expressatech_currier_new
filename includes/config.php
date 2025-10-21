<?php
/**
 * EXPRESSATECH CARGO - Configuración Principal
 * Archivo de conexión a base de datos y constantes del sistema
 */

// Prevenir acceso directo
if (!defined('EXPRESSATECH_ACCESS')) {
    die('Acceso directo no permitido');
}

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS (HOSTINGER)
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'u367875829_ecargoapp');
define('DB_USER', 'u367875829_ecargoapp');
define('DB_PASS', 'GustaBivi.1');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURACIÓN DEL SISTEMA
// =====================================================
define('SITE_URL', 'https://ecargo.expressatech.net');
define('SITE_NAME', 'Expressatech Cargo');
define('ADMIN_EMAIL', 'contacto@expressatech.net');
define('TIMEZONE', 'America/Caracas');

// =====================================================
// CONFIGURACIÓN DE ARCHIVOS
// =====================================================
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB en bytes
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// =====================================================
// CONFIGURACIÓN DE SEGURIDAD
// =====================================================
define('SESSION_LIFETIME', 7200); // 2 horas en segundos
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);

// =====================================================
// CONFIGURACIÓN DE NOTIFICACIONES
// =====================================================
define('RECORDATORIO_DIAS', 15); // Días para enviar recordatorio de pago
define('SMTP_ENABLED', true); // Activar cuando configures PHPMailer

// =====================================================
// CONEXIÓN PDO A LA BASE DE DATOS
// =====================================================
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error al conectar con la base de datos. Por favor, contacta al administrador.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("No se puede deserializar singleton");
    }
}

// =====================================================
// FUNCIONES HELPER DE CONEXIÓN
// =====================================================

/**
 * Obtener conexión a la base de datos
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Ejecutar query y obtener todos los resultados
 */
function queryAll($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ejecutar query y obtener un solo resultado
 */
function queryOne($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Ejecutar query de INSERT/UPDATE/DELETE
 */
function execute($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Obtener el último ID insertado
 */
function lastInsertId() {
    return getDB()->lastInsertId();
}

// =====================================================
// CONFIGURACIÓN DE ZONA HORARIA
// =====================================================
date_default_timezone_set(TIMEZONE);

// =====================================================
// INICIAR SESIÓN
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

// =====================================================
// FUNCIONES DE SEGURIDAD BÁSICAS
// =====================================================

/**
 * Sanitizar entrada de usuario
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar token CSRF
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirigir
 */
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

/**
 * Verificar si usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_tipo']);
}

/**
 * Verificar si usuario es admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_tipo'] === 'admin';
}

/**
 * Requerir login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

/**
 * Requerir admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/login.php');
    }
}

// =====================================================
// MANEJO DE ERRORES
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

/**
 * Registrar error personalizado
 */
function logError($message, $context = []) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    error_log($logMessage . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
}

/**
 * Respuesta JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

?>