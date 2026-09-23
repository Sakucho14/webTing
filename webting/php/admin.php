<?php
/**
 * Webting v3.0 - API del Panel Administrativo
 * Estadísticas globales, gestión de usuarios y personalización visual.
 */

require_once __DIR__ . '/config.php';

// Todos los endpoints de este archivo requieren permisos de Administrador
if (!isAdmin()) {
    jsonResponse(false, null, 'Acceso denegado. Se requiere sesión de Administrador.', 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

switch ($action) {
    case 'stats':
        handleGetStats();
        break;
    case 'users':
        handleGetUsers();
        break;
    case 'delete_user':
        handleDeleteUser();
        break;
    case 'get_settings':
        handleGetSettings();
        break;
    case 'save_settings':
        handleSaveSettings();
        break;
    default:
        jsonResponse(false, null, 'Acción administrativa no válida', 400);
}

function handleGetStats() {
    $db = getDB();
    if ($db) {
        $totalGames = (int)$db->query("SELECT COUNT(*) FROM games")->fetchColumn();
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalScores = (int)$db->query("SELECT COUNT(*) FROM scores")->fetchColumn();
        
        $mostPlayed = $db->query("SELECT title, play_count FROM games ORDER BY play_count DESC LIMIT 1")->fetch();
        $highestScore = $db->query("SELECT player_name, score FROM scores ORDER BY score DESC LIMIT 1")->fetch();
        $recentUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

        jsonResponse(true, [
            'totalGames' => $totalGames,
            'totalUsers' => $totalUsers,
            'totalScores' => $totalScores,
            'mostPlayedGame' => $mostPlayed ? $mostPlayed['title'] . ' (' . $mostPlayed['play_count'] . ' jugadas)' : 'Ninguno',
            'highestGlobalScore' => $highestScore ? $highestScore['player_name'] . ' (' . $highestScore['score'] . ' pts)' : 'Sin registros',
            'recentUsers' => $recentUsers
        ], 'Estadísticas del sistema obtenidas');
    } else {
        jsonResponse(true, [
            'totalGames' => 0,
            'totalUsers' => 1,
            'totalScores' => 0,
            'mostPlayedGame' => 'Ninguno',
            'highestGlobalScore' => 'Ninguno',
            'recentUsers' => 1
        ], 'Estadísticas en modo local');
    }
}

function handleGetUsers() {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT id, username, email, role, email_verified, DATE_FORMAT(created_at, '%d/%m/%Y') as created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        jsonResponse(true, $users, 'Lista de usuarios registrada');
    } else {
        jsonResponse(true, [
            ['id' => 1, 'username' => ADMIN_USERNAME, 'email' => 'admin@webting.com', 'role' => 'admin', 'email_verified' => 1, 'created_at' => date('d/m/Y')]
        ], 'Lista de usuarios (Modo Local)');
    }
}

function handleDeleteUser() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $userId = (int)($input['userId'] ?? 0);

    if ($userId <= 0) {
        jsonResponse(false, null, 'ID de usuario no válido', 400);
    }

    $db = getDB();
    if ($db) {
        // Evitar eliminar al administrador principal
        $stmtChk = $db->prepare("SELECT username FROM users WHERE id = :id");
        $stmtChk->execute([':id' => $userId]);
        $user = $stmtChk->fetch();

        if ($user && $user['username'] === ADMIN_USERNAME) {
            jsonResponse(false, null, 'No es posible eliminar la cuenta principal del sistema.', 403);
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        jsonResponse(true, null, 'Usuario eliminado correctamente');
    } else {
        jsonResponse(true, null, 'Usuario procesado localmente');
    }
}

function handleGetSettings() {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        jsonResponse(true, $settings, 'Ajustes del sitio cargados');
    } else {
        jsonResponse(true, [
            'banner_title' => 'Aprende Jugando en Vivo',
            'banner_subtitle' => 'Plataforma de juegos educativos interactivos con emulacion en tiempo real.',
            'primary_color' => '#FF5500',
            'secondary_color' => '#00C2FF',
            'hero_image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&q=80'
        ], 'Ajustes predeterminados');
    }
}

function handleSaveSettings() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v");
        foreach ($input as $key => $val) {
            $stmt->execute([':k' => $key, ':v' => $val]);
        }
        jsonResponse(true, null, 'Ajustes de personalización guardados exitosamente');
    } else {
        jsonResponse(true, null, 'Ajustes guardados localmente');
    }
}
