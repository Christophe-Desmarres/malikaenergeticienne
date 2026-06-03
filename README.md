# Malika Énergéticienne — Site web

Site vitrine pour **Malika Desmarres**, praticienne en soins énergétiques LaHoChi 13ème Octave et réflexologie faciale Dien Chan, à Trédion (Morbihan, 56).

---

## Stack technique

| Couche | Technologie |
|---|---|
| Frontend | HTML5 · CSS3 · JavaScript vanilla |
| Backend | PHP 8.1+ |
| Base de données | MySQL (via WAMP) |
| Éditeur de texte | Quill.js 1.3.7 |
| Icônes | Font Awesome 6 |
| Polices | Google Fonts (Playfair Display · Open Sans) |
| Serveur local | WAMP (Apache + PHP + MySQL) |

---

## Structure du projet

```
malikaenergeticienne.fr/
│
├── index.html                    # Page d'accueil
├── lahochi.html                  # Page soin LaHoChi 13ème Octave
├── reflexologie-dien-chan.html   # Page réflexologie Dien Chan
├── actualites.html               # Liste de toutes les actualités
├── article.html                  # Page d'un article individuel
├── mentions-legales.html
├── politique-confidentialite.html
│
├── .env                          # Variables d'environnement (non commité)
├── .env.example                  # Modèle .env à copier
├── .gitignore
├── .htaccess                     # Règles Apache (limites upload, protection .env)
├── .user.ini                     # Limites PHP (upload_max_filesize, post_max_size)
├── robots.txt
├── sitemap.xml
│
├── admin/                        # Espace administration (protégé par session)
│   ├── config.php                # Helpers session, CSRF, articles JSON
│   ├── db.php                    # Connexion PDO, chargement .env, tbl(), is_dev()
│   ├── login.php                 # Authentification
│   ├── logout.php
│   ├── setup.php                 # Wizard création BDD + premier compte admin
│   ├── index.php                 # Dashboard — liste des actualités
│   ├── form.php                  # Formulaire ajout / modification d'une actualité
│   ├── delete.php                # Suppression d'une actualité
│   ├── toggle.php                # Publier / masquer une actualité
│   ├── users.php                 # Liste des comptes administrateurs
│   ├── user-form.php             # Ajout / modification d'un compte
│   └── user-delete.php           # Suppression d'un compte
│
├── api/
│   ├── articles.php              # API JSON publique (articles publiés)
│   └── send_email.php            # Envoi du formulaire de contact
│
├── assets/
│   ├── images/
│   │   ├── nuages_cosmiques.jpg  # Image hero principale
│   │   └── articles/             # Images uploadées via l'admin (ignoré par git)
│   └── js/
│       └── form.js               # FAQ accordion + formulaire de contact
│
└── data/
    ├── .htaccess                 # Bloque l'accès web direct au JSON
    └── articles.json             # Stockage des actualités
```

---

## Installation locale (WAMP)

### 1. Cloner / copier le projet

```
C:\wamp64\www\malikaenergeticienne.fr\
```

### 2. Configurer l'environnement

```bash
cp .env.example .env
```

Éditer `.env` :

```env
APP_ENV=development        # development | production
DB_HOST=127.0.0.1
DB_NAME=malika_db
DB_USER=root
DB_PASS=                   # Vide par défaut sur WAMP
DB_CHARSET=utf8mb4
DB_PREFIX=malikaenergeticienne_
```

### 3. Initialiser la base de données

Ouvrir dans le navigateur :

```
http://localhost/malikaenergeticienne.fr/admin/setup.php
```

Ce wizard :
- Crée la base `malika_db` si elle n'existe pas
- Crée la table `malikaenergeticienne_admins`
- Permet de créer le premier compte administrateur

### 4. Accéder à l'admin

```
http://localhost/malikaenergeticienne.fr/admin/
```

---

## Fonctionnalités

### Site public

| Page | Description |
|---|---|
| Accueil | Présentation, soins, témoignages, FAQ, contact |
| LaHoChi 13ème Octave | Page SEO dédiée au soin LaHoChi |
| Réflexologie Dien Chan | Page SEO dédiée à la réflexologie |
| Actualités | Grille de tous les articles publiés |
| Article | Page individuelle avec boutons de partage (Facebook, WhatsApp, copier le lien) |

### Espace admin

| Fonctionnalité | Description |
|---|---|
| Authentification | Login/logout avec session PHP sécurisée |
| Actualités | Créer, modifier, publier/masquer, supprimer |
| Éditeur riche | Quill.js (gras, italique, listes, titres, liens) |
| Images | Upload avec compression automatique (canvas JS → max 1200px, JPEG 82%) |
| Utilisateurs | Gérer plusieurs comptes admin (ajout, modification, suppression) |
| Protection CSRF | Token CSRF sur tous les formulaires et actions |

### SEO

- Balises meta, Open Graph, Twitter Card
- Données structurées JSON-LD (LocalBusiness, Service, FAQPage, BreadcrumbList)
- `sitemap.xml` et `robots.txt`
- Pages dédiées par soin (meilleur ciblage de mots-clés locaux)

---

## Environnements

| Variable | `development` | `production` |
|---|---|---|
| `display_errors` | On | Off |
| Panneau debug upload | Visible | Masqué |
| Logs PHP | `E_ALL` | Désactivés |

Basculer dans `.env` :
```env
APP_ENV=production
```

---

## Sécurité

- `.env` protégé par `.htaccess` (accès web bloqué)
- `data/.htaccess` bloque l'accès direct à `articles.json`
- Mots de passe stockés en **bcrypt** (`password_hash` / `password_verify`)
- Sanitisation HTML côté PHP (`strip_tags` avec liste blanche)
- Tokens CSRF sur toutes les mutations (formulaires POST et liens GET destructifs)
- Upload validé par MIME type réel (`finfo`) + extension + taille max 5 Mo
- Protection contre la suppression du dernier administrateur

---

## API interne

### `GET /api/articles.php`

| Paramètre | Description |
|---|---|
| _(aucun)_ | Retourne les 3 derniers articles publiés |
| `?limit=N` | Retourne les N derniers articles publiés |
| `?all=1` | Retourne tous les articles publiés |
| `?id=xxx` | Retourne l'article avec cet identifiant |

Réponse : `application/json`

---

## Mise en production (OVH)

1. Passer `.env` à `APP_ENV=production`
2. Mettre à jour `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` avec les identifiants OVH
3. Uploader les fichiers via FTP (sauf `.env` si déjà présent sur le serveur)
4. Ouvrir `https://malikaenergeticienne.fr/admin/setup.php` une fois pour initialiser la base
5. **Supprimer ou renommer `setup.php`** après la première configuration

---

## Auteur

Site développé pour **Malika Desmarres** — Énergéticienne à Trédion, Morbihan.  
Contact : [contact@malikaenergeticienne.fr](mailto:contact@malikaenergeticienne.fr)
