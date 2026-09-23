<?php
/**
 * Webting v3.5 - API de Documentos (CMS)
 * Gestión de documentos informativos dinámicos.
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list_public';

switch ($action) {
    case 'list_public':
        handleListPublic();
        break;
    case 'list_admin':
        handleListAdmin();
        break;
    case 'get':
        handleGetDocument();
        break;
    case 'save':
        handleSaveDocument();
        break;
    case 'delete':
        handleDeleteDocument();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleListPublic() {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT id, title FROM documents WHERE published = 1 ORDER BY created_at ASC");
        $docs = $stmt->fetchAll();
        jsonResponse(true, $docs, 'Documentos públicos listados');
    } else {
        // Fallback local
        $jsonPath = __DIR__ . '/../data/documents.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true) ?? [];
            $publicData = array_filter($data, fn($d) => $d['published'] == 1);
            $publicList = array_map(fn($d) => ['id' => $d['id'], 'title' => $d['title']], array_values($publicData));
            jsonResponse(true, $publicList, 'Documentos (JSON local)');
        } else {
            jsonResponse(true, [], 'No se encontraron documentos');
        }
    }
}

function handleListAdmin() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT id, title, published, DATE_FORMAT(created_at, '%d/%m/%Y') as created_at FROM documents ORDER BY created_at DESC");
        $docs = $stmt->fetchAll();
        jsonResponse(true, $docs, 'Documentos listados (Admin)');
    } else {
        $jsonPath = __DIR__ . '/../data/documents.json';
        $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        jsonResponse(true, $data, 'Documentos listados localmente');
    }
}

function handleGetDocument() {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(false, null, 'ID requerido', 400);
    }
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT id, title, content_html, published FROM documents WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $doc = $stmt->fetch();
        if ($doc) {
            jsonResponse(true, $doc, 'Documento obtenido');
        } else {
            jsonResponse(false, null, 'Documento no encontrado', 404);
        }
    } else {
        $jsonPath = __DIR__ . '/../data/documents.json';
        $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        foreach ($data as $d) {
            if ($d['id'] == $id) {
                jsonResponse(true, $d, 'Documento obtenido (Local)');
            }
        }
        jsonResponse(false, null, 'Documento no encontrado localmente', 404);
    }
}

function handleSaveDocument() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $id = trim($input['id'] ?? '');
    $title = trim($input['title'] ?? '');
    $content = $input['content_html'] ?? '';
    $published = isset($input['published']) && $input['published'] ? 1 : 0;

    if (empty($title) || empty($content)) {
        jsonResponse(false, null, 'Título y contenido son obligatorios', 400);
    }

    $db = getDB();
    if ($db) {
        if (empty($id)) {
            $stmt = $db->prepare("INSERT INTO documents (title, content_html, published) VALUES (:t, :c, :p)");
            $stmt->execute([':t' => $title, ':c' => $content, ':p' => $published]);
            $newId = $db->lastInsertId();
        } else {
            $stmt = $db->prepare("UPDATE documents SET title = :t, content_html = :c, published = :p WHERE id = :id");
            $stmt->execute([':t' => $title, ':c' => $content, ':p' => $published, ':id' => $id]);
            $newId = $id;
        }
        jsonResponse(true, ['id' => $newId], 'Documento guardado exitosamente');
    } else {
        // Fallback local
        $jsonPath = __DIR__ . '/../data/documents.json';
        $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        if (empty($id)) {
            $id = time(); // Generar ID local
            $data[] = ['id' => $id, 'title' => $title, 'content_html' => $content, 'published' => $published, 'created_at' => date('d/m/Y')];
        } else {
            foreach ($data as &$d) {
                if ($d['id'] == $id) {
                    $d['title'] = $title;
                    $d['content_html'] = $content;
                    $d['published'] = $published;
                }
            }
        }
        file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
        jsonResponse(true, ['id' => $id], 'Documento guardado localmente');
    }
}

function handleDeleteDocument() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Acceso denegado', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = $input['id'] ?? null;

    if (!$id) {
        jsonResponse(false, null, 'ID requerido', 400);
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM documents WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonResponse(true, null, 'Documento eliminado');
    } else {
        $jsonPath = __DIR__ . '/../data/documents.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true) ?? [];
            $data = array_filter($data, fn($d) => $d['id'] != $id);
            file_put_contents($jsonPath, json_encode(array_values($data), JSON_PRETTY_PRINT));
        }
        jsonResponse(true, null, 'Documento eliminado localmente');
    }
}
