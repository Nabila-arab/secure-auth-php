# Secure Auth PHP — Formulaire d’authentification sécurisé (XAMPP)

## 1) Présentation
Ce projet est une mini-application web **PHP** qui implémente un **formulaire de connexion sécurisé** avec :
- un **logo**
- un champ **Identifiant**
- un champ **Mot de passe**
- 3 actions : **Reset**, **Valider**, **Ajout compte**
- affichage d’un message :
  - succès : **« Vous êtes connecté »**
  - erreur : **« Erreur. Recommence. »**

Le projet met aussi en place des mesures de sécurité (hash des mots de passe, CSRF, anti brute-force, sessions, headers HTTP…).

---

## 2) Pré-requis
### 2.1 Outils nécessaires
- **Windows**
- **XAMPP** (Apache + PHP)  
  https://www.apachefriends.org/fr/index.html
- (Recommandé) **Visual Studio Code**  
  https://code.visualstudio.com/
- (Recommandé) **Git** (pour publier sur GitHub/GitLab)  
  https://git-scm.com/downloads

### 2.2 Vérifier la version / environnement
Le projet fonctionne avec Apache/PHP fournis par XAMPP (ex : Apache 2.4 + PHP 8+).

---

## 3) Installation (pas à pas) sur XAMPP
### 3.1 Placer le projet dans `htdocs`
XAMPP sert les projets web depuis :
- `C:\xampp\htdocs\`

Copier/cloner le dossier du projet dans :
- `C:\xampp\htdocs\secure-auth-php\`

### 3.2 Démarrer Apache
1. Ouvrir **XAMPP Control Panel**
2. Cliquer **Start** sur **Apache**
3. Vérifier que le statut devient **Running** (vert)

### 3.3 Ouvrir l’application
Dans le navigateur :
- http://localhost/secure-auth-php/index.php

> Si votre Apache n’est pas sur le port 80 (ex : 8080), adaptez :
> http://localhost:8080/secure-auth-php/index.php

---

## 4) Structure du projet
Arborescence (exemple) :
```
secure-auth-php/
  .htaccess
  index.php
  add_account.php
  logout.php
  assets/
    style.css
    img/
      Logo.png
  lib/
    security.php
    storage.php
  data/
    users.json
    attempts.json
    .htaccess
```

### Rôle des fichiers
- `index.php` : page de connexion + traitement du login
- `add_account.php` : création d’un nouvel utilisateur
- `logout.php` : déconnexion
- `assets/style.css` : styles de l’interface
- `assets/img/Logo.png` : logo affiché dans l’interface
- `lib/security.php` : sessions, CSRF, rate limiting
- `lib/storage.php` : lecture/écriture des utilisateurs dans `data/users.json`
- `data/users.json` : stockage des utilisateurs (hash des mots de passe)
- `data/attempts.json` : stockage des tentatives (anti brute-force)
- `data/.htaccess` : bloque l’accès web direct au dossier `data/`

---

## 5) Utilisation
### 5.1 Connexion
1. Ouvrir : `index.php`
2. Entrer identifiant + mot de passe
3. Cliquer **Valider**

Résultat :
- si correct : message **« Vous êtes connecté »**
- sinon : **« Erreur. Recommence. »**

### 5.2 Reset
- Le bouton **Reset** efface les champs du formulaire.

### 5.3 Ajout compte
1. Cliquer **Ajout compte**
2. Entrer un nouvel identifiant + mot de passe
3. Valider la création

---

## 6) Stockage des comptes (sans base de données)
### 6.1 Pourquoi pas de base de données ?
L’énoncé autorise : **SQL / NoSQL / aucune**.  
Pour rester simple et portable, le projet utilise un stockage **JSON**.

### 6.2 Fichier `users.json`
Les comptes sont enregistrés dans `data/users.json` sous forme :
- clé : `username`
- valeur : `passwordHash` + `createdAt`

> Les mots de passe ne sont jamais stockés en clair.

---

## 7) Mesures de sécurité implémentées (partie notée)
### 7.1 Hash des mots de passe (bcrypt)
- Création : `password_hash(..., PASSWORD_DEFAULT)`
- Vérification : `password_verify(...)`

Objectif : empêcher la récupération du mot de passe même si le fichier `users.json` fuit.

### 7.2 CSRF (protection des formulaires)
- Un token CSRF est généré et stocké en session.
- Chaque formulaire envoie `_csrf`.
- Le serveur refuse la requête si le token est absent ou invalide (403).

Objectif : bloquer les attaques CSRF (requêtes forcées depuis un autre site).

### 7.3 Anti brute-force (rate limiting)
- Limitation : **10 tentatives / 5 minutes / IP**
- Stockage des tentatives dans `data/attempts.json`
- En cas d’excès : réponse 429

Objectif : réduire les attaques par essais multiples de mots de passe.

### 7.4 Sessions sécurisées
- Cookies de session configurés (HttpOnly, SameSite)
- `session_regenerate_id(true)` après authentification (anti session fixation)

Objectif : réduire le vol/fixation de session.

### 7.5 Protection des fichiers sensibles (`data/`)
Accès direct bloqué par Apache avec :
- `data/.htaccess` :
  - `Require all denied`

Test :
- http://localhost/secure-auth-php/data/users.json  
=> doit afficher **403 Forbidden**

Objectif : empêcher de télécharger `users.json` ou `attempts.json` via le navigateur.

### 7.6 Headers HTTP de sécurité
Au niveau du projet (dans `.htaccess` racine) :
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: no-referrer`
- `Permissions-Policy: ...`

Objectif :
- anti clickjacking (X-Frame-Options)
- anti MIME sniffing
- réduction fuite d’informations via referrer
- réduction des permissions navigateur inutiles

---

## 8) Tests rapides (pour la démonstration)
### 8.1 Test accès aux fichiers `data`
- Ouvrir : http://localhost/secure-auth-php/data/users.json
- Résultat attendu : **403 Forbidden**

### 8.2 Test login (mauvais mot de passe)
- Entrer un mot de passe incorrect
- Résultat : **Erreur. Recommence.**

### 8.3 Test login (bon mot de passe)
- Entrer un compte valide
- Résultat : **Vous êtes connecté**

### 8.4 Test brute-force
- Faire plus de 10 tentatives rapidement
- Résultat : blocage temporaire (429)

---

## 9) Dépannage
### 9.1 Erreur “Not Found”
- Vérifier que le dossier s’appelle exactement `secure-auth-php`
- Vérifier l’emplacement : `C:\xampp\htdocs\secure-auth-php`
- Vérifier que `index.php` existe bien

### 9.2 Apache ne démarre pas
Cause possible : port 80 utilisé.
Solution :
- Changer le port Apache (ex: 8080) dans `httpd.conf`
- Redémarrer Apache
- Ouvrir : http://localhost:8080/

### 9.3 `.htaccess` ignoré
- Vérifier que le fichier s’appelle bien `.htaccess` (pas `.htaccess.txt`)
- Vérifier dans `httpd.conf` que `AllowOverride All` est activé pour `C:/xampp/htdocs`

---

## 10) Publication sur GitHub/GitLab
### 10.1 Avec Git (ligne de commande)
Dans le dossier du projet :
```bash
git init
git add .
git commit -m "Initial commit - secure auth php"
git branch -M main
git remote add origin <URL_DU_DEPOT>
git push -u origin main
```

### 10.2 Avec GitHub Desktop
1. Ouvrir GitHub Desktop
2. Add local repository → sélectionner le dossier du projet
3. Commit
4. Publish repository
5. Copier le lien et le fournir

---

## 11) Remarques / limites
- Projet prévu pour un usage pédagogique (local).
- En production, il faut utiliser HTTPS, un stockage plus robuste (ex : DB), et renforcer davantage (CSP, 2FA, etc.).

---
**Auteur :** (ton nom)  
**Date :** 2026-02-11