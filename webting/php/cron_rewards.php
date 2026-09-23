<?php
/**
 * Webting V4 - Cron Job para Asignación de Recompensas
 * Este script debe ser ejecutado por cron (ej: 0 0 * * * php /ruta/php/cron_rewards.php)
 */
require_once __DIR__ . '/config.php';

// Verificación de seguridad: Solo permitir CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ser ejecutado por consola/cron.\n");
}

$db = getDB();
if (!$db) {
    die("Error de conexión a la base de datos.\n");
}

echo "Iniciando asignación automática de recompensas...\n";

// Regla de ejemplo: +50 puntos a todos los jugadores activos (con sesión iniciada en los últimos 7 días)
// Dado que en este schema no guardamos last_login, daremos +50 a todos los que tengan role='player' para simular.

try {
    $db->beginTransaction();
    
    $stmt = $db->query("SELECT id, points FROM users WHERE role = 'player'");
    $users = $stmt->fetchAll();
    
    $pointsToGive = 50;
    $count = 0;
    
    foreach ($users as $u) {
        $newPoints = $u['points'] + $pointsToGive;
        
        // Calcular nuevo tier
        $tier = 'Bronce';
        if ($newPoints >= 10000) $tier = 'Platino';
        else if ($newPoints >= 5000) $tier = 'Oro';
        else if ($newPoints >= 1000) $tier = 'Plata';
        
        $db->prepare("UPDATE users SET points = ?, tier = ? WHERE id = ?")
           ->execute([$newPoints, $tier, $u['id']]);
           
        $db->prepare("INSERT INTO reward_transactions (user_id, type, amount, description) VALUES (?, 'earn', ?, 'Bono diario por actividad')")
           ->execute([$u['id'], $pointsToGive]);
           
        $count++;
    }
    
    $db->commit();
    echo "Cron completado. Se asignaron $pointsToGive puntos a $count usuarios.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error ejecutando cron: " . $e->getMessage() . "\n";
}
