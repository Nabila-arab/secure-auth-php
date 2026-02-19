<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

csrf_verify($_POST['_csrf'] ?? null);

// Effacer session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], (bool)$params["secure"], (bool)$params["httponly"]
    );
}
session_destroy();

header('Location: /secure-auth-php/index.php');
exit;