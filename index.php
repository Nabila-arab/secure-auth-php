<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
require __DIR__ . '/lib/storage.php';

start_secure_session();

$users = load_users();
if (empty($users['admin'])) {
    $users['admin'] = [
        'passwordHash' => password_hash('Admin123!', PASSWORD_DEFAULT),
        'createdAt' => date('c'),
    ];
    save_users($users);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    rate_limit_check($ip, 10, 300, __DIR__ . '/data/attempts.json');

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '' || strlen($username) > 50 || strlen($password) > 200) {
        set_flash('error', "Erreur : champs invalides.");
        header('Location: /secure-auth-php/index.php');
        exit;
    }

    $users = load_users();
    $user = find_user($users, $username);

    if (!$user || empty($user['passwordHash']) || !password_verify($password, $user['passwordHash'])) {
        set_flash('error', "Erreur. Recommence.");
        header('Location: /secure-auth-php/index.php');
        exit;
    }

    $_SESSION['user'] = ['username' => $username, 'loginAt' => date('c')];
    set_flash('success', "Vous êtes connecté");
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
<script src="/secure-auth-php/assets/app.js"></script>
<body>
  <main class="card">
    <div class="logo" aria-label="logo">
    <img src="/secure-auth-php/assets/img/logo.png" alt="Logo" /></div>
    <h1>Connexion</h1>

    <?php if ($flash): ?>
      <div class="msg <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/secure-auth-php/index.php" autocomplete="off" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label for="username">Identifiant</label>
      <input id="username" name="username" type="text" maxlength="50" required>

      <label for="password">Mot de passe</label>
      <input id="password" name="password" type="password" maxlength="200" required>

      <div class="actions">
        <button type="reset" class="secondary">Reset</button>
        <button type="submit" class="primary">Valider</button>
        <a class="link-btn" href="/secure-auth-php/add_account.php">Ajout compte</a>
      </div>
    </form>

    <?php if (!empty($_SESSION['user'])): ?>
      <p class="small">Connecté : <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></p>
      <form method="POST" action="/secure-auth-php/logout.php">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button type="submit" class="ghost">Se déconnecter</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>