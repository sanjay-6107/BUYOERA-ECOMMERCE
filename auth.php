<?php
/**
 * BUYOERA Mobile API - Authentication Endpoint
 */

require_once __DIR__ . '/common.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = get_json_input();
    $action = $input['action'] ?? 'login';

    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            send_json(['success' => false, 'message' => 'Please enter both email and password.'], 400);
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password'], $user['reset_token'], $user['reset_expires']);
            send_json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $user
            ]);
        } else {
            send_json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }
    }

    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            send_json(['success' => false, 'message' => 'Name, email, and password are required.'], 400);
        }

        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            send_json(['success' => false, 'message' => 'This email is already registered.'], 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :pass, 'customer')");
        $ins->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'pass' => $hash
        ]);

        $userId = (int)$db->lastInsertId();
        send_json([
            'success' => true,
            'message' => 'Account registered successfully',
            'data' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'customer'
            ]
        ], 201);
    }

    send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}

if ($method === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($userId > 0) {
        $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            send_json(['success' => true, 'data' => $user]);
        }
    }
    send_json(['success' => false, 'message' => 'User not found.'], 404);
}
