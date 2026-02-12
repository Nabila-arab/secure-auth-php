<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

csrf_verify($_POST['_csrf'] ?? null);

$_SESSION = [];
session_destroy();

header('Location: /secure-auth-php/index.php');
exit;