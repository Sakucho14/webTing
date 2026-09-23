<?php
/**
 * Webting V4 - API de Gestión de Documentos
 */
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!getCurrentUser()) {
    jsonResponse(false, null, 'No autorizado', 401);
}

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'list':
        handleList();
        break;
    case 'download':
        handleDownload();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleUpload() {
    if (!isAdmin()) {
        jsonResponse(false, null, 'Solo administradores pueden subir documentos', 403);
    }
    
    if (!isset($_FILES['document'])) {
        jsonResponse(false, null, 'No se proporcionó ningún archivo', 400);
    }

    $file = $_FILES['document'];
    $title = $_POST['title'] ?? 'Sin Título';
    $category = $_POST['category'] ?? 'General';
    
    $allowedMimeTypes = [
        'application/pdf', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // xlsx
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMimeTypes)) {
        jsonResponse(false, null, 'Tipo de archivo no permitido. Solo PDF, DOCX, XLSX', 400);
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $db = getDB();
        $user = getCurrentUser();
        
        if ($db) {
            // Check if document with same title exists for versioning
            $stmt = $db->prepare("SELECT id, file_path, version FROM document_files WHERE title = ?");
            $stmt->execute([$title]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Versioning
                $db->prepare("INSERT INTO document_versions (document_id, version_num, file_path) VALUES (?, ?, ?)")
                   ->execute([$existing['id'], $existing['version'], $existing['file_path']]);
                
                $db->prepare("UPDATE document_files SET file_path = ?, file_type = ?, category = ?, version = version + 1 WHERE id = ?")
                   ->execute([$filename, $mime, $category, $existing['id']]);
                   
                jsonResponse(true, ['version' => $existing['version'] + 1], 'Nueva versión subida correctamente');
            } else {
                $db->prepare("INSERT INTO document_files (title, file_path, file_type, category, uploaded_by) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$title, $filename, $mime, $category, $user['id']]);
                   
                jsonResponse(true, null, 'Documento subido correctamente');
            }
        } else {
            // JSON fallback logic
            $jsonPath = __DIR__ . '/../data/document_files.json';
            $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
            
            $existingIndex = -1;
            foreach ($data as $i => $d) {
                if ($d['title'] === $title) {
                    $existingIndex = $i;
                    break;
                }
            }
            
            if ($existingIndex >= 0) {
                $data[$existingIndex]['version'] = ($data[$existingIndex]['version'] ?? 1) + 1;
                $data[$existingIndex]['file_path'] = $filename;
                $data[$existingIndex]['file_type'] = $mime;
                $data[$existingIndex]['category'] = $category;
                file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
                jsonResponse(true, ['version' => $data[$existingIndex]['version']], 'Nueva versión subida correctamente (Local)');
            } else {
                $id = time();
                $data[] = [
                    'id' => $id,
                    'title' => $title,
                    'file_path' => $filename,
                    'file_type' => $mime,
                    'category' => $category,
                    'uploaded_by' => $user['id'] ?? 1,
                    'uploader' => $user['username'] ?? 'admin',
                    'version' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
                jsonResponse(true, null, 'Documento subido correctamente (Local)');
            }
        }
    } else {
        jsonResponse(false, null, 'Error al guardar el archivo', 500);
    }
}

function handleList() {
    $db = getDB();
    $category = $_GET['category'] ?? null;
    
    if ($db) {
        $query = "SELECT d.id, d.title, d.file_type, d.category, d.version, d.created_at, u.username as uploader FROM document_files d LEFT JOIN users u ON d.uploaded_by = u.id";
        $params = [];
        
        if ($category && $category !== 'All') {
            $query .= " WHERE d.category = ?";
            $params[] = $category;
        }
        
        $query .= " ORDER BY d.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $documents = $stmt->fetchAll();
        
        jsonResponse(true, $documents);
    } else {
        $jsonPath = __DIR__ . '/../data/document_files.json';
        $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        
        if ($category && $category !== 'All') {
            $data = array_filter($data, function($d) use ($category) {
                return $d['category'] === $category;
            });
        }
        
        usort($data, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        jsonResponse(true, array_values($data));
    }
}

function handleDownload() {
    $id = $_GET['id'] ?? null;
    if (!$id) jsonResponse(false, null, 'ID requerido', 400);
    
    $db = getDB();
    $doc = null;
    
    if ($db) {
        $stmt = $db->prepare("SELECT file_path, title, file_type FROM document_files WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
    } else {
        $jsonPath = __DIR__ . '/../data/document_files.json';
        $data = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        foreach ($data as $d) {
            if ($d['id'] == $id) {
                $doc = $d;
                break;
            }
        }
    }
    
    if ($doc) {
        $file = __DIR__ . '/../uploads/' . $doc['file_path'];
        if (file_exists($file)) {
            header('Content-Type: ' . $doc['file_type']);
            header('Content-Disposition: attachment; filename="' . basename($doc['file_path']) . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
    jsonResponse(false, null, 'Archivo no encontrado', 404);
}
