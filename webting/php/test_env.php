<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

echo "--- WEBTING V4 ENV TEST ---\n";

// 1. Check DB Connection
$db = getDB();
if ($db) {
    echo "[OK] Conexión a Base de Datos exitosa.\n";
} else {
    echo "[ERROR] Falló conexión a Base de Datos.\n";
}

// 2. Check Uploads Directory
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (is_writable($uploadDir)) {
    echo "[OK] Directorio de uploads es escribible.\n";
} else {
    echo "[ERROR] Directorio de uploads NO es escribible.\n";
}

// 3. Check .env protection (if running via web)
if (php_sapi_name() !== 'cli') {
    $envUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/webting/.env";
    echo "\nPara verificar la protección del .env, intente acceder a:\n$envUrl\nDebería devolver un error 403 (Forbidden) gracias al .htaccess.\n";
}
