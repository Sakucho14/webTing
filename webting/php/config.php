<?php
/**
 * Webting v3.0 - Configuración Backend y Conexión PDO
 * Gestión de sesiones, seguridad y respuestas JSON.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Cargar variables de entorno desde .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Configuración de Base de Datos MySQL
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'webting_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Datos del Administrador Único
define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? '//admin//');
define('ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? 'loger2010');

/**
 * Obtener conexión PDO a MySQL
 * @return PDO|null
 */
function getDB() {
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $db;
    } catch (PDOException $e) {
        // Devuelve null si no hay conexión a base de datos
        return null;
    }
}

/**
 * Verificar si el usuario actual es Administrador
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Obtener usuario en sesión
 * @return array|null
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Enviar respuesta JSON estructurada
 * @param bool $success
 * @param mixed $data
 * @param string $message
 * @param int $statusCode
 */
function jsonResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => time()
    ]);
    exit;
}
