<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
require __DIR__ . '/lib/storage.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $usernameOk = (bool)preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username);
    $strongPwd = (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,64}$/', $password);

    if (!$usernameOk) {
        set_flash('error', "Identifiant invalide (3-30, lettres/chiffres/._-).");
        header('Location: /secure-auth-php/add_account.php');
        exit;
    }
    if (!$strongPwd) {
        set_flash('error', "Mot de passe faible (8-64, maj/min/chiffre/symbole).");
        header('Location: /secure-auth-php/add_account.php');
        exit;
    }

    $users = load_users();
    if (!empty($users[$username])) {
        set_flash('error', "Compte déjà existant.");
        header('Location: /secure-auth-php/add_account.php');
        exit;
    }

    $users[$username] = [
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
        'createdAt' => date('c'),
    ];
    save_users($users);

    set_flash('success', "Compte ajouté.");
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
  <title>Ajout compte</title>
  <link rel="stylesheet" href="/secure-auth-php/assets/style.css">
</head>
<script src="/secure-auth-php/assets/app.js"></script>
<body>
  <main class="card">
    <div class="logo" aria-label="logo">
    <img src="/secure-auth-php/assets/img/logo.png" alt="Logo" /></div>
    <h1>Ajout d’un compte</h1>

    <?php if ($flash): ?>
      <div class="msg <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/secure-auth-php/add_account.php" autocomplete="off" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <label for="username">Nouvel identifiant</label>
      <input id="username" name="username" type="text" required>

      <label for="password">Mot de passe</label>
      <input id="password" name="password" type="password" required>

      <div class="actions">
        <button type="reset" class="secondary">Reset</button>
        <button type="submit" class="primary">Créer</button>
        <a class="link-btn" href="/secure-auth-php/index.php">Retour</a>
      </div>
    </form>

    <div class="rules">
  <p class="rules-title">Règles de création</p>
  <ul class="rules-list">
    <li><strong>Identifiant</strong> : 3–30 caractères (a-zA-Z0-9 . _ -)</li>
    <li><strong>Mot de passe</strong> : 8–64 avec majuscule, minuscule, chiffre et symbole</li>
  </ul>
</div>
  </main>
</body>
</html>