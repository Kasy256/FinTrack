<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fintrack_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_connection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
    }
}

function init_db() {
    $pdo = get_connection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100),
        income DECIMAL(15, 2) DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'KES',
        employment_type VARCHAR(50),
        theme VARCHAR(20) DEFAULT 'auto',
        streak_count INT DEFAULT 1,
        last_active_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    // Migration: ensure streak columns exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS streak_count INT DEFAULT 1");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_active_date DATE");

    $pdo->exec("CREATE TABLE IF NOT EXISTS entries (
        id BIGINT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('income', 'expense') NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        category VARCHAR(50),
        source VARCHAR(50),
        note TEXT,
        date DATE NOT NULL,
        currency VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS budgets (
        id BIGINT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(20),
        category VARCHAR(50),
        limit_amount DECIMAL(15, 2) NOT NULL,
        currency VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS goals (
        id BIGINT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        target DECIMAL(15, 2) NOT NULL,
        saved DECIMAL(15, 2) DEFAULT 0,
        type VARCHAR(50),
        currency VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
}
?>
