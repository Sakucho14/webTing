<?php
/**
 * Webting v3.0 - API de Juegos
 * CRUD completo de juegos con configuración de iframe y sistema de puntuación.
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleListGames();
        break;
    case 'save':
        handleSaveGame();
        break;
    case 'delete':
        handleDeleteGame();
        break;
    case 'increment_play':
        handleIncrementPlay();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleListGames() {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT * FROM games ORDER BY created_at DESC");
        $games = $stmt->fetchAll();

        foreach ($games as &$game) {
            $stmtCtrl = $db->prepare("SELECT key_name, action_desc FROM game_controls WHERE game_id = :id");
            $stmtCtrl->execute([':id' => $game['id']]);
            $game['controls'] = $stmtCtrl->fetchAll();
            $game['iframe_scroll'] = (bool)$game['iframe_scroll'];
            $game['iframe_fullscreen'] = (bool)$game['iframe_fullscreen'];
            $game['scoring_enabled'] = (bool)$game['scoring_enabled'];
        }

        jsonResponse(true, $games, 'Lista de juegos obtenida');
    } else {
        // Cargar desde games.json
        $jsonPath = __DIR__ . '/../data/games.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true) ?? [];
            jsonResponse(true, $data, 'Lista de juegos (JSON local)');
        } else {
            jsonResponse(true, [], 'No se encontraron juegos');
        }
    }
}

function handleSaveGame() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado. Se requieren permisos de Administrador.', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $id = trim($input['id'] ?? '');
    $title = trim($input['title'] ?? '');
    $subject = trim($input['subject'] ?? 'Matemáticas');
    $difficulty = trim($input['difficulty'] ?? 'Medio');
    $ageRange = trim($input['ageRange'] ?? '9-12');
    $competency = trim($input['competency'] ?? '');
    $logoUrl = trim($input['logoUrl'] ?? '');
    $hoverDescription = trim($input['hoverDescription'] ?? '');
    $iframeUrl = trim($input['iframeUrl'] ?? '');
    
    // Configuraciones avanzadas de Iframe y Puntuación
    $iframeScroll = !empty($input['iframeScroll']) ? 1 : 0;
    $iframeSandbox = trim($input['iframeSandbox'] ?? '');
    $iframeFullscreen = !empty($input['iframeFullscreen']) ? 1 : 0;
    $scoringEnabled = !empty($input['scoringEnabled']) ? 1 : 0;

    $controls = $input['controls'] ?? [];

    if (empty($title) || empty($iframeUrl)) {
        jsonResponse(false, null, 'El título y la URL del juego son obligatorios', 400);
    }

    // Generar ID si es nuevo
    if (empty($id)) {
        $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . rand(100, 999);
    }

    $db = getDB();
    if ($db) {
        try {
            $db->beginTransaction();

            $sql = "INSERT INTO games (id, title, subject, difficulty, age_range, competency, logo_url, hover_description, iframe_url, iframe_scroll, iframe_sandbox, iframe_fullscreen, scoring_enabled)
                    VALUES (:id, :title, :subject, :diff, :age, :comp, :logo, :hover, :iframe, :scroll, :sandbox, :fs, :scoring)
                    ON DUPLICATE KEY UPDATE 
                    title = VALUES(title), subject = VALUES(subject), difficulty = VALUES(difficulty),
                    age_range = VALUES(age_range), competency = VALUES(competency), logo_url = VALUES(logo_url),
                    hover_description = VALUES(hover_description), iframe_url = VALUES(iframe_url),
                    iframe_scroll = VALUES(iframe_scroll), iframe_sandbox = VALUES(iframe_sandbox),
                    iframe_fullscreen = VALUES(iframe_fullscreen), scoring_enabled = VALUES(scoring_enabled)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':subject' => $subject,
                ':diff' => $difficulty,
                ':age' => $ageRange,
                ':comp' => $competency,
                ':logo' => $logoUrl,
                ':hover' => $hoverDescription,
                ':iframe' => $iframeUrl,
                ':scroll' => $iframeScroll,
                ':sandbox' => $iframeSandbox,
                ':fs' => $iframeFullscreen,
                ':scoring' => $scoringEnabled
            ]);

            // Reemplazar controles
            $stmtDel = $db->prepare("DELETE FROM game_controls WHERE game_id = :id");
            $stmtDel->execute([':id' => $id]);

            $stmtIns = $db->prepare("INSERT INTO game_controls (game_id, key_name, action_desc) VALUES (:gid, :k, :a)");
            foreach ($controls as $ctrl) {
                if (!empty($ctrl['key']) && !empty($ctrl['action'])) {
                    $stmtIns->execute([
                        ':gid' => $id,
                        ':k' => trim($ctrl['key']),
                        ':a' => trim($ctrl['action'])
                    ]);
                }
            }

            $db->commit();
            jsonResponse(true, ['id' => $id], 'Juego guardado correctamente en la base de datos');
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, null, 'Error al guardar el juego: ' . $e->getMessage(), 500);
        }
    } else {
        // Guardar en games.json
        $jsonPath = __DIR__ . '/../data/games.json';
        $games = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        
        $newGame = [
            'id' => $id,
            'title' => $title,
            'subject' => $subject,
            'difficulty' => $difficulty,
            'ageRange' => $ageRange,
            'competency' => $competency,
            'logoUrl' => $logoUrl,
            'hoverDescription' => $hoverDescription,
            'iframeUrl' => $iframeUrl,
            'iframeScroll' => (bool)$iframeScroll,
            'iframeSandbox' => $iframeSandbox,
            'iframeFullscreen' => (bool)$iframeFullscreen,
            'scoringEnabled' => (bool)$scoringEnabled,
            'controls' => $controls,
            'playCount' => 0
        ];

        // Reemplazar o añadir
        $found = false;
        foreach ($games as &$g) {
            if ($g['id'] === $id) {
                $g = array_merge($g, $newGame);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $games[] = $newGame;
        }

        file_put_contents($jsonPath, json_encode($games, JSON_PRETTY_PRINT));
        jsonResponse(true, ['id' => $id], 'Juego guardado en archivo JSON local');
    }
}

function handleDeleteGame() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = trim($input['id'] ?? '');

    if (empty($id)) {
        jsonResponse(false, null, 'ID del juego es obligatorio', 400);
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM games WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonResponse(true, null, 'Juego eliminado correctamente');
    } else {
        $jsonPath = __DIR__ . '/../data/games.json';
        if (file_exists($jsonPath)) {
            $games = json_decode(file_get_contents($jsonPath), true) ?? [];
            $games = array_filter($games, fn($g) => $g['id'] !== $id);
            file_put_contents($jsonPath, json_encode(array_values($games), JSON_PRETTY_PRINT));
        }
        jsonResponse(true, null, 'Juego eliminado del archivo JSON local');
    }
}

function handleIncrementPlay() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = trim($input['id'] ?? '');

    if (empty($id)) {
        jsonResponse(false, null, 'ID del juego requerido', 400);
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("UPDATE games SET play_count = play_count + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonResponse(true, null, 'Contador actualizado');
    } else {
        jsonResponse(true, null, 'Contador actualizado localmente');
    }
}
