# M1INRA1 — Programmation Web

Boutique e-commerce développée en **PHP 8.3 sans framework**, avec MySQL 8.4, Docker et Xdebug.

Le projet couvre le fil rouge du cours : authentification, CRUD utilisateurs/produits/catégories, backoffice admin, catalogue et panier.

---

## 1. Lancer le projet

### Prérequis

- Docker Desktop (ou Docker Engine + Compose)
- PHP 8.3+ et Composer pour les scripts et tests (extensions `curl`, `json`, `zip`)

### Installation

```bash
# 1. Copier la configuration locale
cp .env.example .env

# 2. Adapter les mots de passe dans .env si besoin
#    (MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD)

# 3. Construire et démarrer les conteneurs
docker compose up -d --build
```

L'application est disponible sur : **http://localhost:8080**

La base de données est initialisée automatiquement par `init.sql` au premier démarrage.

### Jeu de données de démonstration (Faker)

Le script `scripts/seed_produits.php` génère des produits avec Faker : désignations, catégories, prix HT, stocks, promotions et dates d'entrée.

```bash
# Installer Faker
composer install

# Générer 20 produits (valeur par défaut)
php scripts/seed_produits.php

# Générer 50 produits
php scripts/seed_produits.php 50
```

Le script est prévu pour être lancé depuis l'hôte. Il lit les identifiants MySQL dans `.env`, se connecte à `127.0.0.1:3306` par défaut et ajoute les produits sans supprimer les produits existants.

### Tests E2E avec Selenium

Les tests se trouvent dans `tests/E2E`. Selenium lance un vrai navigateur Chrome dans un conteneur Docker, tandis que PHPUnit et php-webdriver pilotent le navigateur depuis l'hôte.

```bash
# 1. Démarrer l'application, la base et Selenium
docker compose up -d

# 2. Installer les dépendances PHP
composer install

# 3. Lancer les tests E2E
composer test:e2e
```

Le navigateur tourne dans le conteneur `selenium` et accède à l'application via `http://web`. Variables utilisées :

- `APP_URL` : URL vue par le navigateur, `http://web` par défaut ;
- `SELENIUM_URL` : `http://localhost:4444` par défaut ;
- `TEST_ADMIN_LOGIN` / `TEST_ADMIN_PASSWORD` : à définir si le mot de passe admin a été changé.

Les tests couvrent la connexion admin, le refus du backoffice sans connexion, le filtre du catalogue, l'ajout d'un produit et le recalcul dynamique du total du panier. Les produits de test sont créés puis supprimés automatiquement.

> php-webdriver requiert les extensions PHP `curl`, `json` et `zip`. Si Composer signale `ext-zip` manquant, activez `extension=zip` dans le `php.ini` utilisé par la CLI.

### Base déjà existante

Si le volume MySQL existe déjà, exécuter la migration :

```bash
docker compose exec -T db mysql --default-character-set=utf8mb4 \
  -uroot -p"$MYSQL_ROOT_PASSWORD" e_commerce < migration_v2.sql
```

---

## 2. Compte administrateur

| Identifiant | Mot de passe |
|---|---|
| `admin` | `Admin1234!2026` |

> ⚠️ Changez ce mot de passe après la première connexion. Le hash Argon2id est stocké dans `init.sql` et `migration_v2.sql`.

Les nouveaux comptes créés via l'inscription reçoivent automatiquement une ligne `compte` et le rôle `client`.

---

## 3. Structure

```text
.
├── docker-compose.yml        # Services web (PHP/Apache) + db (MySQL) + selenium
├── Dockerfile                # Image PHP 8.3 + Xdebug + pdo_mysql
├── init.sql                  # Schéma + données initiales + admin
├── migration_v2.sql          # Migration pour une base existante
├── composer.json             # Dépendances de développement (Faker, PHPUnit, Selenium)
├── composer.lock             # Versions verrouillées des dépendances
├── phpunit.xml               # Configuration PHPUnit
├── scripts/
│   └── seed_produits.php     # Génération de produits avec Faker
├── tests/                    # Tests E2E Selenium
│   ├── E2E/                  # Scénarios de navigation
│   └── Support/              # Base Selenium + fixtures BDD
├── projet/                   # Racine web (montée dans /var/www/html)
│   ├── config.php            # Config, sécurité, session, PDO
│   ├── functions.php         # Auth, panier, CSRF, upload, validation
│   ├── Entities/             # ProductEntity, PrixEntity, CategorieEntity
│   ├── templates/            # header.php / footer.php
│   ├── produits/             # Catalogue + backoffice CRUD
│   ├── utilisateurs/         # Backoffice CRUD utilisateurs + rôles
│   ├── categories/           # Ajout, modification de TVA, suppression
│   └── assets/               # CSS, JS, image par défaut
└── .env.example              # Modèle de configuration
```

---

## 4. Fonctionnalités

### Authentification
- Inscription avec prénom, nom, identifiant, e-mail et mot de passe.
- Mot de passe : 12 caractères minimum, une majuscule, une minuscule, un chiffre.
- Hash Argon2id, `password_verify`, rehash automatique.
- CSRF sur tous les formulaires sensibles.
- Session sécurisée : cookie `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, expiration après 30 minutes.
- Déconnexion uniquement en POST + CSRF.

### Catalogue
- Liste des produits actifs (`is_deleted = 0`).
- Tri/filtre par type de produit, avec conservation du filtre après ajout au panier.
- Prix HT / TTC avec TVA par catégorie.
- Promotion en pourcentage saisissable dans le backoffice.
- Image par défaut si aucune image n'est renseignée.
- Jeu de données de démonstration généré avec Faker (produits, prix, stocks, promotions, dates).

### Backoffice (admin)
- Liste des produits, ajout, modification, suppression logique.
- Gestion des utilisateurs : liste, ajout, modification des informations et du rôle, suppression.
- Gestion des catégories : ajout, modification de la TVA, suppression (refusée si des produits y sont associés).
- Accès protégé par `exiger_admin()`.
- CSRF et validation serveur sur chaque formulaire.
- Échappement systématique des sorties avec `h()`.

### Panier
- Ajout avec contrôle du stock.
- Modification des quantités et suppression (quantité 0).
- Calcul de la TVA et du total TTC en session.
- Mise à jour dynamique des sous-totaux et du total TTC lors du changement de quantité.

### Tests
- Tests E2E Selenium + PHPUnit : connexion admin, contrôle d'accès, filtre catalogue, ajout produit, panier dynamique.
- Fixtures produits créées et nettoyées automatiquement.

---

## 5. Sécurité

- Requêtes préparées PDO (`ATTR_EMULATE_PREPARES = false`).
- Échappement XSS via `h()` / `htmlspecialchars`.
- Jetons CSRF sur les formulaires POST.
- Contrôle d'accès admin centralisé.
- Validation serveur des entrées (produits, catégories, utilisateurs, inscription).
- Impossible de supprimer son propre compte ou de retirer le rôle du dernier administrateur.
- En-têtes HTTP : `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP.
- Secrets dans `.env`, non versionné.

---

## 6. Reste à faire (roadmap)

- Frais de port.
- Tunnel de commande (`commande` / `ligne_commande`).
- API REST native + JSON Schema.
- Crawler.
- TD Laravel et TD API Platform.
- TP noté : jeu JavaScript + comptes et scores en PHP.
- SEO avancé / audit Lighthouse, axe et W3C.

---

## 7. Commandes utiles

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Voir les logs
docker compose logs -f web

# Shell dans le conteneur web
docker compose exec web bash

# Vérifier la syntaxe d'un fichier PHP
docker compose exec web php -l /var/www/html/produits/liste.php

# Générer des produits de démonstration
php scripts/seed_produits.php 20

# Démarrer Selenium
docker compose up -d selenium

# Lancer les tests E2E
composer test:e2e

# Accéder à MySQL
docker compose exec db mysql -uweb_user -p e_commerce
```
