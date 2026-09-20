<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/db.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'invalid_method']);
    exit;
}
$action = $_POST['action'] ?? '';
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'missing_fields']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'email_taken']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, created_at) VALUES (:u, :e, :p, :c)');
    $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash, ':c' => date('Y-m-d H:i:s')]);
    $userId = (int)$pdo->lastInsertId();
    echo json_encode(['success' => true]);
    exit;
}
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'missing_fields']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'invalid_credentials']);
        exit;
    }
    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];
    echo json_encode(['success' => true, 'role' => $row['role']]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'invalid_action']);
