<?php
declare(strict_types=1);

function db_path(): string {
    return __DIR__ . '/../data/app.db';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $pdo = new PDO('sqlite:' . db_path(), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
    ");

    return $pdo;
}

function db_find_user(string $username): ?array {
    $stmt = db()->prepare("SELECT username, password_hash, created_at FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_create_user(string $username, string $passwordHash): bool {
    $stmt = db()->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (:u, :p, :c)");
    try {
        return $stmt->execute([':u' => $username, ':p' => $passwordHash, ':c' => date('c')]);
    } catch (PDOException $e) {
        return false;
    }
}

function db_user_exists(string $username): bool {
    return db_find_user($username) !== null;
}