# TP1 — Formulaire d’authentification sécurisé (PHP / XAMPP) — OWASP A1, A2, A7

## 1) Objectif (énoncé)
Réaliser un formulaire d’identification sécurisé contenant :
- un logo
- un champ **identifiant**
- un champ **mot de passe**
- 3 boutons :
  - **Reset** : remise à zéro des champs
  - **Valider** : affiche un message de succès ou d’erreur
  - **Ajout compte** : permet d’ajouter un identifiant (créer un compte)
- un **README** détaillant :
  - informations techniques
  - comment utiliser
  - identifiant / mot de passe si nécessaire

---

## 2) Choix techniques
- **Langage** : PHP (déploiement simple avec XAMPP)
- **Serveur** : Apache (XAMPP)
- **Stockage** : SQLite (`data/app.db`) via **PDO**
- **Front** : HTML/CSS + JavaScript (icône “œil” pour afficher/masquer le mot de passe)
- **Objectif sécurité (OWASP Top 10)** :
  - **A1 — Injection**
  - **A2 — Broken Authentication**
  - **A7 — XSS**

---

## 3) Prérequis / Outils
### 3.1 Installer XAMPP
- https://www.apachefriends.org/fr/index.html

### 3.2 (Recommandé) Éditeur
- VS Code : https://code.visualstudio.com/

### 3.3 (Optionnel) Git
- Git : https://git-scm.com/downloads

---

## 4) Installation du projet (Windows + XAMPP)
### 4.1 Placement dans `htdocs`
Copier le dossier du projet dans :
- `C:\xampp\htdocs\secure-auth-php\`

Fichiers principaux :
- `index.php` : page de connexion
- `add_account.php` : création de compte
- `logout.php` : déconnexion
- `lib/security.php` : session sécurisée, CSRF, rate limiting, lockout, logs, échappement XSS
- `lib/db.php` : SQLite + requêtes préparées (PDO)
- `assets/style.css` : styles
- `assets/app.js` : bouton “œil”
- `data/attempts.json` et `data/locks.json` : compteurs de sécurité (anti brute-force)

> La base SQLite `data/app.db` est créée automatiquement au premier lancement (table `users`).

### 4.2 Démarrer Apache
1. Ouvrir **XAMPP Control Panel**
2. Cliquer **Start** sur **Apache**
3. Vérifier que `http://localhost/` fonctionne

### 4.3 Lancer l’application
- http://localhost/secure-auth-php/index.php

---

## 5) Utilisation
### 5.1 Connexion
- Saisir identifiant + mot de passe
- Cliquer **Valider**
  - succès : message **« Vous êtes connecté »**
  - échec : message **« Erreur. Recommence. »** (message générique)

### 5.2 Reset
- **Reset** vide les champs (fonction du navigateur).

### 5.3 Ajout d’un compte
- Cliquer **Ajout compte**
- Créer un identifiant conforme (3–30 caractères : lettres/chiffres/._-)
- Choisir un mot de passe fort (8–64, maj/min/chiffre/symbole)
- Le compte est enregistré dans SQLite (`data/app.db`).

### 5.4 Afficher/masquer le mot de passe
- Cliquer sur l’icône “œil” dans le champ mot de passe (JS local `assets/app.js`).

### 5.5 Déconnexion
- Bouton **Se déconnecter** (requête POST + protection CSRF)
- Destruction de session + suppression du cookie de session.

---

## 6) Compte de test (pour correction)
Pour tester rapidement :
1. Aller sur **Ajout compte**
2. Créer l’utilisateur :
   - Identifiant : `Nabila`
   - Mot de passe : `Nabila2026!`
3. Revenir sur la page de connexion et se connecter avec ces identifiants.

---

## 7) Détails sécurité — OWASP Top 10

### A1 — Injection (corrigé)
Objectif : empêcher les injections (ex. SQL Injection).

Mesures :
- SQLite via **PDO**
- **Requêtes préparées** (pas de concaténation SQL) :
  - `SELECT ... WHERE username = :u`
  - `INSERT ... VALUES (:u, :p, :c)`
- Validation stricte côté serveur :
  - identifiant : `^[a-zA-Z0-9._-]{3,30}$`
  - taille max côté serveur (mot de passe)

Test conseillé :
- tenter un identifiant : `nabila' OR 1=1 --` → refus ou message générique, pas de comportement anormal.

### A2 — Broken Authentication (corrigé)
Objectif : éviter les failles d’authentification et de gestion de session.

Mesures :
- Mots de passe stockés en **hash** :
  - `password_hash()` / `password_verify()`
- Protection contre session fixation :
  - `session_regenerate_id(true)` après connexion réussie
- Cookies de session durcis :
  - `HttpOnly`, `SameSite=Lax` (et `Secure` si HTTPS)
- Anti brute-force :
  - rate limiting par IP (10 tentatives / 5 minutes) via `data/attempts.json`
  - lockout par compte après 5 échecs (blocage 10 minutes) via `data/locks.json`
- Message d’erreur générique (réduit l’énumération d’utilisateurs)

### A7 — XSS (corrigé)
Objectif : empêcher l’exécution de code JavaScript injecté.

Mesures :
- Échappement systématique des sorties avec `e()` (wrapper de `htmlspecialchars`)
- Headers de sécurité + CSP via `.htaccess` :
  - `Content-Security-Policy`
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
- Filtrage des identifiants (regex)

Test conseillé :
- tenter username `<script>alert(1)</script>` → refusé ou affiché échappé, jamais exécuté.

**Vérification des headers (preuve A7)** :
1. Ouvrir la page `index.php`
2. F12 → onglet **Network** → cliquer la requête `index.php`
3. Vérifier dans **Response Headers** la présence des headers ci-dessus.

---

> `data/app.db` est un fichier généré localement

---

## 8) Dépannage
- **Apache ne démarre pas** : port 80 occupé → changer le port Apache (ex. 8080) puis utiliser `http://localhost:8080/...`
- **`.htaccess` n’a pas d’effet** : activer `AllowOverride All` dans Apache, puis redémarrer Apache
- **SQLite “driver” manquant** : activer `pdo_sqlite` et `sqlite3` dans `php.ini`, puis redémarrer Apache