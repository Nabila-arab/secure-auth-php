<?php
declare(strict_types=1);

require __DIR__ . '/lib/security.php';
require __DIR__ . '/lib/db.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['_csrf'] ?? null);

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Validation stricte (A1)
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

    if (db_user_exists($username)) {
        set_flash('error', "Compte déjà existant.");
        header('Location: /secure-auth-php/add_account.php');
        exit;
    }

    $ok = db_create_user($username, password_hash($password, PASSWORD_DEFAULT));
    if (!$ok) {
        set_flash('error', "Impossible de créer le compte.");
        header('Location: /secure-auth-php/add_account.php');
        exit;
    }

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
<body>
  <main class="card">
  <div class="logo" aria-label="logo">
    <img src="/secure-auth-php/assets/img/Logo.png" alt="Logo" class="logo-img">
  </div>      
  <h1>Ajout d’un compte</h1>

    <?php if ($flash): ?>
      <div class="msg <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/secure-auth-php/add_account.php" autocomplete="off" novalidate>
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <label for="username">Nouvel identifiant</label>
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
        <button type="submit" class="primary">Créer</button>
        <a class="link-btn" href="/secure-auth-php/index.php">Retour</a>
      </div>
    </form>

    <p class="small">
      Règles : identifiant 3-30 (a-zA-Z0-9._-). 
      MDP : 8-64 avec maj/min/chiffre/symbole.
    </p>
  </main>
<script src="/secure-auth-php/assets/app.js"></script>
</body>
</html>