# Formulaire d’authentification sécurisé (PHP + XAMPP)

## 1) Objectif (selon l’énoncé)
Réaliser un formulaire d’identification sécurisé comprenant :
- 1 **logo**
- 1 champ **Identifiant**
- 1 champ **Mot de passe**
- **3 boutons** :
  - **Reset** : remise à zéro des champs
  - **Valider (OK)** : affiche un message de succès ou d’erreur
  - **Ajout compte** : permet d’ajouter un identifiant supplémentaire
- Aucune contrainte de langage/framework/BD
- Le projet doit être publié sur une plateforme (GitHub / GitLab / Framagit)
- Le fichier `README.md` doit expliquer :
  - les informations techniques
  - comment utiliser
  - identifiant/mot de passe si nécessaire

---

## 2) Choix techniques
Ce projet est réalisé en **PHP** (solution simple et rapide avec XAMPP) et respecte des principes de sécurité de base :

- **PHP + Apache** via XAMPP
- Interface : HTML/CSS
- Stockage des comptes : **fichier JSON** (`data/users.json`)  
  (persistance simple sans base SQL/NoSQL)
- Sécurité :
  - mots de passe **hachés** (`password_hash`) + vérification (`password_verify`)
  - protection **CSRF** (token en session)
  - **rate limiting** basique (anti brute-force) via `data/attempts.json`
  - session PHP + cookies durcis (HttpOnly, SameSite)

---

## 3) Prérequis (outils à installer)
### 3.1 XAMPP (Apache + PHP)
Télécharger : https://www.apachefriends.org/fr/index.html

Après installation, XAMPP est généralement dans :
- `C:\xampp\`

### 3.2 (Recommandé) Éditeur de code
Visual Studio Code : https://code.visualstudio.com/

### 3.3 Git pour publier sur GitHub
Git : https://git-scm.com/downloads  
GitHub Desktop : https://desktop.github.com/

---

## 4) Installation / Création du projet (pas à pas)
### 4.1 Démarrer Apache
1. Ouvrir **XAMPP Control Panel**
2. Cliquer sur **Start** pour **Apache**
3. Vérifier dans un navigateur :
   - http://localhost/

Si Apache ne démarre pas, le problème le plus fréquent est un port déjà utilisé (80/443).  
Dans ce cas, changer le port dans la configuration Apache (ex. 8080) puis redémarrer.

### 4.2 Créer le dossier du projet
Créer le dossier dans `htdocs` (racine web d’Apache) :

- `C:\xampp\htdocs\secure-auth-php\`

### 4.3 Arborescence du projet
Le projet contient :

```text
secure-auth-php/
  index.php            # Login (formulaire + traitement)
  add_account.php      # Ajout compte (formulaire + traitement)
  logout.php           # Déconnexion
  lib/
    security.php       # Session, CSRF, rate limit
    storage.php        # Lecture/écriture JSON
  data/
    users.json         # Comptes (hash)
    attempts.json      # Tentatives (anti brute-force)
  assets/
    style.css          # Style
  README.md
```

---

## 5) Lancer le projet
### 5.1 URL du projet
Si Apache écoute sur le port 80 (par défaut) :
- http://localhost/secure-auth-php/index.php

Si Apache a été déplacé en 8080 :
- http://localhost:8080/secure-auth-php/index.php

---

## 6) Identifiants de test
Pour tester la connexion :

- **Identifiant :** `Nabila`  
- **Mot de passe :** `Nabila2026!`

---

## 7) Utilisation (fonctionnel)
### 7.1 Connexion
Sur la page `index.php` :
- saisir identifiant + mot de passe
- cliquer **Valider**
- résultat :
  - succès : message **"Vous êtes connecté"**
  - échec : message **"Erreur. Recommence."**

### 7.2 Reset
Le bouton **Reset** vide les champs (fonction navigateur).

### 7.3 Ajout de compte
Cliquer **Ajout compte** :
- redirige vers `add_account.php`
- permet de créer un nouvel utilisateur
- après création : retour vers la page de connexion

### 7.4 Déconnexion
Quand l’utilisateur est connecté :
- bouton **Se déconnecter**
- destruction de la session

---

## 8) Explications techniques et sécurité
### 8.1 Stockage des comptes (JSON)
- Les utilisateurs sont stockés dans `data/users.json`
- Les mots de passe ne sont **jamais** stockés en clair : uniquement un **hash**

### 8.2 Hash de mot de passe
- Création : `password_hash($password, PASSWORD_DEFAULT)`
- Vérification : `password_verify($password, $hash)`

Bénéfices :
- hash + sel automatiques
- difficulté accrue pour brute-force offline

### 8.3 Protection CSRF
- Un token CSRF est généré et stocké en session
- Chaque formulaire POST inclut un champ caché `_csrf`
- Si le token ne correspond pas : **403 (Requête invalide)**

Objectif : empêcher les requêtes “forgées” depuis un autre site.

### 8.4 Rate limiting (anti brute-force)
- Les tentatives de login sont enregistrées par IP dans `data/attempts.json`
- Règle : **10 tentatives / 5 minutes / IP**
- Si dépassement : **429** “Trop de tentatives…”

Objectif : ralentir les attaques par mot de passe.

### 8.5 Sessions + cookies
La session PHP stocke l’utilisateur connecté.  
Les cookies de session sont durcis via :
- `HttpOnly` (réduit le vol de cookie via JS en cas d’XSS)
- `SameSite=Lax` (réduit les risques CSRF)
- `Secure` activable si HTTPS (en production)

### 8.6 Messages d’erreur génériques
Le même message est renvoyé si :
- utilisateur inexistant
- mot de passe incorrect

Objectif : éviter l’énumération d’utilisateurs (*user enumeration*).

---

## 9) Dépannage
### 9.1 Page blanche / erreur
- vérifier qu’Apache est “Running”
- vérifier l’URL correcte
- vérifier que les fichiers sont bien dans `C:\xampp\htdocs\secure-auth-php\`

### 9.2 Port 80 occupé
Si Apache ne démarre pas :
- un autre logiciel peut utiliser le port 80/443
- changer Apache en 8080 et retester

---

## 10) Publication sur GitHub (rendu)
1. Créer un dépôt GitHub
2. Ajouter les fichiers du projet
3. Pousser (push)
  

---

## 11) Améliorations possibles
- Stockage persistant en **SQLite**
- `session_regenerate_id(true)` après login (anti session fixation)
- headers de sécurité via `.htaccess`
- logs sécurité (audit des tentatives)
- 2FA (TOTP) en extension
