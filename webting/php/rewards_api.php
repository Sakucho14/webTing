<?php
/**
 * Webting V4 - API de Recompensas
 */
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = getCurrentUser();

if (!$user) {
    jsonResponse(false, null, 'No autorizado', 401);
}

switch ($action) {
    case 'dashboard':
        handleDashboard($user);
        break;
    case 'catalog':
        handleCatalog();
        break;
    case 'redeem':
        handleRedeem($user);
        break;
    case 'history':
        handleHistory($user);
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleDashboard($user) {
    $db = getDB();
    if (!$db) jsonResponse(false, null, 'Error BD', 500);
    
    $stmt = $db->prepare("SELECT points, tier FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $data = $stmt->fetch();
    
    // Calcular progreso
    $points = (int)$data['points'];
    $tier = $data['tier'];
    $nextTierPoints = 0;
    
    if ($tier == 'Bronce') $nextTierPoints = 1000;
    else if ($tier == 'Plata') $nextTierPoints = 5000;
    else if ($tier == 'Oro') $nextTierPoints = 10000;
    else $nextTierPoints = $points; // Max tier
    
    $progress = $nextTierPoints > 0 && $tier != 'Platino' ? min(100, ($points / $nextTierPoints) * 100) : 100;
    
    jsonResponse(true, [
        'points' => $points,
        'tier' => $tier,
        'progress' => $progress,
        'next_tier_points' => $nextTierPoints
    ]);
}

function handleCatalog() {
    $db = getDB();
    if (!$db) jsonResponse(false, null, 'Error BD', 500);
    
    $stmt = $db->query("SELECT * FROM reward_catalog WHERE is_active = 1");
    $catalog = $stmt->fetchAll();
    
    jsonResponse(true, $catalog);
}

function handleRedeem($user) {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $rewardId = (int)($input['reward_id'] ?? 0);
    
    $db = getDB();
    if (!$db) jsonResponse(false, null, 'Error BD', 500);
    
    try {
        $db->beginTransaction();
        
        // Lock user row
        $stmtUser = $db->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
        $stmtUser->execute([$user['id']]);
        $userData = $stmtUser->fetch();
        
        $stmtReward = $db->prepare("SELECT * FROM reward_catalog WHERE id = ?");
        $stmtReward->execute([$rewardId]);
        $reward = $stmtReward->fetch();
        
        if (!$reward) {
            throw new Exception("Recompensa no encontrada");
        }
        
        if ($userData['points'] < $reward['cost_points']) {
            throw new Exception("Puntos insuficientes");
        }
        
        // Deduct points
        $newPoints = $userData['points'] - $reward['cost_points'];
        $db->prepare("UPDATE users SET points = ? WHERE id = ?")->execute([$newPoints, $user['id']]);
        
        // Insert transaction
        $db->prepare("INSERT INTO reward_transactions (user_id, type, amount, description) VALUES (?, 'redeem', ?, ?)")
           ->execute([$user['id'], $reward['cost_points'], "Canje: " . $reward['title']]);
           
        $db->commit();
        jsonResponse(true, ['new_points' => $newPoints], "Canje exitoso");
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, null, $e->getMessage(), 400);
    }
}

function handleHistory($user) {
    $db = getDB();
    if (!$db) jsonResponse(false, null, 'Error BD', 500);
    
    $stmt = $db->prepare("SELECT type, amount, description, created_at FROM reward_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user['id']]);
    $history = $stmt->fetchAll();
    
    jsonResponse(true, $history);
}
