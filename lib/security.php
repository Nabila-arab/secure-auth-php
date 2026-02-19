<?php
declare(strict_types=1);

function start_secure_session(): void {
    // durcissements (A2)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,     // true uniquement si HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function e(string $value): string {
    // Output encoding systématique (A7)
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(?string $token): void {
    if (!$token || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit("Requête invalide (CSRF).");
    }
}

function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function security_log(string $line): void {
    $file = __DIR__ . '/../data/security.log';
    file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
}

/**
 * Rate limiting simple par IP (A2)
 */
function rate_limit_ip(string $ip, int $maxAttempts, int $windowSeconds, string $file): void {
    $now = time();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(new stdClass(), JSON_PRETTY_PRINT));
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) $data = [];

    $list = $data[$ip] ?? [];
    $list = array_values(array_filter($list, fn($t) => is_int($t) && ($now - $t) <= $windowSeconds));

    if (count($list) >= $maxAttempts) {
        http_response_code(429);
        exit("Trop de tentatives. Réessaie plus tard.");
    }

    $list[] = $now;
    $data[$ip] = $list;

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Lockout par username (A2): après X échecs -> bloqué Y secondes
 */
function lock_file(): string {
    return __DIR__ . '/../data/locks.json';
}

function lock_is_blocked(string $username): bool {
    $file = lock_file();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(new stdClass(), JSON_PRETTY_PRINT));
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) $data = [];

    $entry = $data[$username] ?? null;
    if (!is_array($entry)) return false;

    $blockedUntil = (int)($entry['blockedUntil'] ?? 0);
    return time() < $blockedUntil;
}

function lock_register_failure(string $username, int $maxFails, int $blockSeconds): void {
    $file = lock_file();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(new stdClass(), JSON_PRETTY_PRINT));
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) $data = [];

    $entry = $data[$username] ?? ['fails' => 0, 'blockedUntil' => 0];
    $fails = (int)($entry['fails'] ?? 0) + 1;

    $blockedUntil = (int)($entry['blockedUntil'] ?? 0);
    if ($fails >= $maxFails) {
        $blockedUntil = time() + $blockSeconds;
        $fails = 0; // reset après blocage
    }

    $data[$username] = ['fails' => $fails, 'blockedUntil' => $blockedUntil];
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function lock_clear(string $username): void {
    $file = lock_file();
    if (!file_exists($file)) return;

    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) $data = [];
    unset($data[$username]);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}