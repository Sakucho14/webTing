<?php
/**
 * Webting v3.0 - API de Autenticación
 * Registro con verificación de correo Gmail y Login exclusivo //admin//
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'verify_email':
        handleVerifyEmail();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheckAuth();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonResponse(false, null, 'Por favor ingrese usuario y contraseña', 400);
    }

    // Comprobación especial para cuenta de Administrador Principal //admin//
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $userSession = [
            'id' => 1,
            'username' => ADMIN_USERNAME,
            'email' => 'admin@webting.com',
            'role' => 'admin',
            'email_verified' => 1
        ];
        $_SESSION['user'] = $userSession;
        jsonResponse(true, $userSession, 'Inicio de sesión como Administrador exitoso');
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u OR email = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['email_verified']) {
                jsonResponse(false, ['needs_verification' => true, 'email' => $user['email']], 'Su correo electrónico no ha sido verificado.', 403);
            }

            $userSession = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'email_verified' => (int)$user['email_verified']
            ];
            $_SESSION['user'] = $userSession;
            jsonResponse(true, $userSession, 'Inicio de sesión exitoso');
        } else {
            jsonResponse(false, null, 'Usuario o contraseña incorrectos', 401);
        }
    } else {
        // Fallback local sin base de datos
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $userSession = ['id' => 1, 'username' => ADMIN_USERNAME, 'email' => 'admin@webting.com', 'role' => 'admin', 'email_verified' => 1];
            $_SESSION['user'] = $userSession;
            jsonResponse(true, $userSession, 'Inicio de sesión como Administrador exitoso (Modo Local)');
        }
        jsonResponse(false, null, 'Credenciales no válidas en modo local', 401);
    }
}

function handleRegister() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(false, null, 'Todos los campos son obligatorios', 400);
    }

    // Validar formato de correo Gmail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        jsonResponse(false, null, 'Debe proporcionar una dirección de correo Gmail válida (@gmail.com)', 400);
    }

    // Generar código de verificación de 6 dígitos
    $verificationCode = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $db = getDB();
    if ($db) {
        try {
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, email_verified, verification_code) VALUES (:u, :e, :p, 'player', 0, :c)");
            $stmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $passwordHash,
                ':c' => $verificationCode
            ]);
            
            jsonResponse(true, [
                'email' => $email,
                'verification_code' => $verificationCode // Devuelto para simulación en pantalla
            ], 'Cuenta creada. Ingrese el código de verificación enviado a su Gmail.');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                jsonResponse(false, null, 'El nombre de usuario o correo ya se encuentra registrado', 409);
            }
            jsonResponse(false, null, 'Error al registrar el usuario: ' . $e->getMessage(), 500);
        }
    } else {
        // Simulación en frontend
        jsonResponse(true, [
            'email' => $email,
            'verification_code' => $verificationCode
        ], 'Simulación de registro exitosa. Verifique el código enviado.');
    }
}

function handleVerifyEmail() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $code = trim($input['code'] ?? '');

    if (empty($email) || empty($code)) {
        jsonResponse(false, null, 'Se requiere correo y código de verificación', 400);
    }

    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE email = :e AND verification_code = :c LIMIT 1");
        $stmt->execute([':e' => $email, ':c' => $code]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $db->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE id = :id");
            $update->execute([':id' => $user['id']]);

            $userSession = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $email,
                'role' => $user['role'],
                'email_verified' => 1
            ];
            $_SESSION['user'] = $userSession;
            jsonResponse(true, $userSession, 'Correo verificado con éxito. Sesión iniciada.');
        } else {
            jsonResponse(false, null, 'El código de verificación es incorrecto.', 400);
        }
    } else {
        jsonResponse(true, null, 'Verificación completada (Modo Local).');
    }
}

function handleLogout() {
    unset($_SESSION['user']);
    session_destroy();
    jsonResponse(true, null, 'Sesión cerrada correctamente');
}

function handleCheckAuth() {
    $user = getCurrentUser();
    if ($user) {
        jsonResponse(true, $user, 'Usuario autenticado');
    } else {
        jsonResponse(false, null, 'No hay sesión activa', 401);
    }
}
