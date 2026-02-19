<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
require __DIR__ . '/lib/db.php';

start_secure_session();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);

    $ip = client_ip();
    rate_limit_ip($ip, 10, 300, __DIR__ . '/data/attempts.json'); // 10 / 5 min

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Validation stricte (A1 : réduire surface injection)
    $usernameOk = (bool)preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username);
    if (!$usernameOk || $password === '' || strlen($password) > 200) {
        set_flash('error', "Erreur. Recommence.");
        security_log(date('c') . " FAIL invalid_input ip=$ip user=" . $username);
        header('Location: /secure-auth-php/index.php');
        exit;
    }

    // Lockout par compte (A2)
    if (lock_is_blocked($username)) {
        set_flash('error', "Trop d'essais. Compte temporairement bloqué.");
        security_log(date('c') . " BLOCKED ip=$ip user=$username");
        header('Location: /secure-auth-php/index.php');
        exit;
    }

    // Recherche via requête préparée (A1)
    $user = db_find_user($username);

    // Message générique (A2/A7)
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        lock_register_failure($username, 5, 600); // 5 échecs -> blocage 10 min
        set_flash('error', "Erreur. Recommence.");
        security_log(date('c') . " FAIL ip=$ip user=$username");
        header('Location: /secure-auth-php/index.php');
        exit;
    }

    // Succès auth (A2)
    lock_clear($username);
    session_regenerate_id(true); // anti session fixation
    $_SESSION['user'] = ['username' => $username, 'loginAt' => date('c')];

    set_flash('success', "Vous êtes connecté");
    security_log(date('c') . " OK ip=$ip user=$username");
    header('Location: /secure-auth-php/index.php');
    exit;
}

$flash = get_flash();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Authentification sécurisée</title>
  <link rel="stylesheet" href="/secure-auth-php/assets/style.css">
</head>
<body>
  <main class="card">
  <div class="logo" aria-label="logo">
    <img src="/secure-auth-php/assets/img/Logo.png" alt="Logo" class="logo-img">
  </div>    
  <h1>Connexion</h1>

    <?php if ($flash): ?>
      <div class="msg <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/secure-auth-php/index.php" autocomplete="off" novalidate>
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <label for="username">Identifiant</label>
      <input id="username" name="username" type="text" maxlength="30" required>

      <label for="password">Mot de passe</label>

      <div class="password-wrap">
      <input id="password" name="password" type="password" maxlength="200" required>
      <button type="button" class="toggle-eye" aria-label="Afficher/masquer le mot de passe" aria-pressed="false">
        👁
      </button>
    </div>

      <div class="actions">
        <button type="reset" class="secondary">Reset</button>
        <button type="submit" class="primary">Valider</button>
        <a class="link-btn" href="/secure-auth-php/add_account.php">Ajout compte</a>
      </div>
    </form>

    <?php if (!empty($_SESSION['user'])): ?>
      <p class="small">Connecté : <strong><?= e((string)$_SESSION['user']['username']) ?></strong></p>
      <form method="POST" action="/secure-auth-php/logout.php">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit" class="ghost">Se déconnecter</button>
      </form>
    <?php endif; ?>
  </main>
<script src="/secure-auth-php/assets/app.js"></script>
</body>
</html>