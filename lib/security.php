<?php
declare(strict_types=1);

function start_secure_session(): void {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,      // mets true si HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
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

function rate_limit_check(string $ip, int $maxAttempts, int $windowSeconds, string $file): void {
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