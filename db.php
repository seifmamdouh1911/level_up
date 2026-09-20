<?php
declare(strict_types=1);
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'levelup';
$DB_USER = 'root';
$DB_PASS = '';
try {
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(128) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Check if role column exists (for existing installation)
try {
    $pdo->query("SELECT role FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash");
}

// Seed Admin User
$adminEmail = 'admin@levelup.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $adminEmail]);
if (!$stmt->fetch()) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES ('Admin', :email, :pass, 'admin', NOW())")
        ->execute([':email' => $adminEmail, ':pass' => $pass]);
}
$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS user_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    level INT NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_user_cat (user_id, category_id),
    INDEX idx_uc_user (user_id),
    INDEX idx_uc_cat (category_id),
    CONSTRAINT fk_uc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_uc_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    INDEX idx_task_user (user_id),
    INDEX idx_task_cat (category_id),
    INDEX idx_task_status (status),
    INDEX idx_task_completed (completed_at),
    CONSTRAINT fk_task_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS task_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    action VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_history_task (task_id),
    CONSTRAINT fk_history_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($count === 0) {
    $categories = ['Health','Study','Religion','Wealth','Social','Fitness'];
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
    foreach ($categories as $c) {
        $stmt->execute([':name' => $c]);
    }
}
