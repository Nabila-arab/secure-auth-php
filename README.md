# Secure Auth PHP (XAMPP) — Formulaire d’authentification sécurisé

Mini application PHP d’authentification réalisée pour un TP sécurité.
Interface simple (logo + formulaire) et mesures de sécurité essentielles (hash, CSRF, anti brute-force, sessions, protection des fichiers sensibles).

---

## Sommaire
- [1. Fonctionnalités](#1-fonctionnalités)
- [2. Prérequis](#2-prérequis)
- [3. Installation (Windows + XAMPP)](#3-installation-windows--xampp)
- [4. Lancer et utiliser l’application](#4-lancer-et-utiliser-lapplication)
- [5. Structure du projet](#5-structure-du-projet)
- [6. Sécurité (mesures mises en place)](#6-sécurité-mesures-mises-en-place)
- [7. Tests de démonstration (pour le prof)](#7-tests-de-démonstration-pour-le-prof)
- [8. Dépannage](#8-dépannage)

---

## 1. Fonctionnalités
### Authentification
- Connexion via **identifiant + mot de passe**
- Message :
  - ✅ succès : **« Vous êtes connecté »**
  - ❌ erreur : **« Erreur. Recommence. »**

### Ajout de compte
- Page dédiée : `add_account.php`
- Règles :
  - Identifiant : **3–30** caractères (a-zA-Z0-9 . _ -)
  - Mot de passe : **8–64** + majuscule/minuscule/chiffre/symbole

### UI
- Logo : `assets/img/logo.png`
- Design “cyber” + animations légères (CSS)

---

## 2. Prérequis
- Windows
- XAMPP (Apache + PHP) : https://www.apachefriends.org/fr/index.html

---

## 3. Installation (Windows + XAMPP)
1. Copier/cloner le projet dans :
   - `C:\xampp\htdocs\secure-auth-php\`
2. Démarrer **Apache** dans XAMPP Control Panel
3. Ouvrir dans un navigateur :
   - http://localhost/secure-auth-php/index.php

---

## 4. Lancer et utiliser l’application
### 4.1 Connexion
- Ouvrir : `index.php`
- Renseigner identifiant + mot de passe
- Cliquer **Valider**

### 4.2 Ajout compte
- Cliquer **Ajout compte**
- Créer un nouvel utilisateur

### 4.3 Déconnexion
- Cliquer **Se déconnecter**

---

## 5. Structure du projet
```
secure-auth-php/
  index.php
  add_account.php
  logout.php
  .htaccess
  assets/
    style.css
    app.js
    img/
      logo.png
  lib/
    security.php
    storage.php
  data/
    .htaccess
    users.json           (local)
    attempts.json        (local)
```

> Le dossier `data/` contient des fichiers locaux (créés/écrits par PHP).  
> Il est bloqué en accès web direct via `data/.htaccess`.

---

## 6. Sécurité (mesures mises en place)

| Menace | Mesure | Où |
|---|---|---|
| Vol de mots de passe en clair | Hash `password_hash` / `password_verify` | `index.php`, `add_account.php` |
| CSRF (requêtes forcées) | Token CSRF en session + vérification | `lib/security.php` |
| Brute-force | Limite 10 tentatives / 5 min / IP | `lib/security.php` + `data/attempts.json` |
| Session fixation | `session_regenerate_id(true)` après login | `index.php` |
| Exposition des fichiers sensibles | `data/.htaccess` → `Require all denied` | `data/.htaccess` |
| Clickjacking / sniffing / referrer | Headers HTTP de sécurité | `.htaccess` racine |

### 6.1 Protection du dossier `data/`
Test :
- http://localhost/secure-auth-php/data/users.json  
Résultat attendu : **403 Forbidden**

---

## 7. Tests de démonstration
1. **Protection data/** : ouvrir `.../data/users.json` → **403**
2. **Mauvais login** : message **Erreur. Recommence.**
3. **Bon login** : message **Vous êtes connecté**
4. **Ajout compte** : créer un nouvel utilisateur puis se connecter
5. **Brute-force** : > 10 tentatives → blocage temporaire (HTTP 429)

---

## 8. Dépannage
### 8.1 Page Forbidden partout
Vérifier :
- `.htaccess` racine : headers seulement (ne pas mettre `Require all denied`)
- `data/.htaccess` : `Require all denied`

### 8.2 `.htaccess` ignoré
Dans `httpd.conf` :
- `AllowOverride All` sur `C:/xampp/htdocs`

### 8.3 Apache ne démarre pas
Port déjà utilisé :
- changer le port Apache (ex: 8080) puis ouvrir `http://localhost:8080/`

---

**Auteur :** Nabila ARAB
