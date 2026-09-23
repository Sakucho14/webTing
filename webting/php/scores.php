<?php
/**
 * Webting v3.0 - API de Puntuaciones
 * Registro de puntajes reales basados en el sistema de tiempo de juego.
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        handleGetScores();
        break;
    case 'add':
        handleAddScore();
        break;
    case 'delete_score':
        handleDeleteScore();
        break;
    case 'clear_scores':
        handleClearScores();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleGetScores() {
    $gameId = trim($_GET['game_id'] ?? '');

    if (empty($gameId)) {
        jsonResponse(false, null, 'ID del juego requerido', 400);
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT id, player_name, score, DATE_FORMAT(scored_at, '%d/%m/%Y %H:%i') as date FROM scores WHERE game_id = :gid ORDER BY score DESC LIMIT 10");
        $stmt->execute([':gid' => $gameId]);
        $scores = $stmt->fetchAll();
        jsonResponse(true, $scores, 'Top 10 puntajes obtenidos');
    } else {
        jsonResponse(true, [], 'Modo local sin puntajes en BD');
    }
}

function handleAddScore() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $gameId = trim($input['gameId'] ?? '');
    $playerName = trim($input['playerName'] ?? 'Anónimo');
    $score = (int)($input['score'] ?? 0);

    if (empty($gameId) || $score <= 0) {
        jsonResponse(false, null, 'Datos de puntaje inválidos', 400);
    }

    $db = getDB();
    if ($db) {
        // Verificar si el juego tiene habilitada la puntuación
        $stmtChk = $db->prepare("SELECT scoring_enabled FROM games WHERE id = :gid");
        $stmtChk->execute([':gid' => $gameId]);
        $game = $stmtChk->fetch();

        if ($game && !$game['scoring_enabled']) {
            jsonResponse(false, null, 'Este juego no tiene habilitado el sistema de puntuación', 400);
        }

        $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;

        $stmt = $db->prepare("INSERT INTO scores (game_id, user_id, player_name, score) VALUES (:gid, :uid, :pname, :score)");
        $stmt->execute([
            ':gid' => $gameId,
            ':uid' => $userId,
            ':pname' => $playerName,
            ':score' => $score
        ]);

        jsonResponse(true, null, 'Puntaje registrado exitosamente');
    } else {
        jsonResponse(true, null, 'Puntaje registrado localmente');
    }
}

function handleDeleteScore() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $scoreId = (int)($input['scoreId'] ?? 0);

    $db = getDB();
    if ($db && $scoreId > 0) {
        $stmt = $db->prepare("DELETE FROM scores WHERE id = :id");
        $stmt->execute([':id' => $scoreId]);
        jsonResponse(true, null, 'Puntaje eliminado');
    } else {
        jsonResponse(true, null, 'Acción procesada');
    }
}

function handleClearScores() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $gameId = trim($input['gameId'] ?? '');

    $db = getDB();
    if ($db && !empty($gameId)) {
        $stmt = $db->prepare("DELETE FROM scores WHERE game_id = :gid");
        $stmt->execute([':gid' => $gameId]);
        jsonResponse(true, null, 'Todos los puntajes del juego han sido borrados');
    } else {
        jsonResponse(true, null, 'Acción procesada');
    }
}
