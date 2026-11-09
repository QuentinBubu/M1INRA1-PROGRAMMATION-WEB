# M1INRA1 — Programmation Web

Boutique e-commerce développée en **PHP 8.3 sans framework**, avec MySQL 8.4, Docker et Xdebug.

Le projet couvre le fil rouge du cours : authentification, CRUD produits/catégories, backoffice admin, catalogue et panier.

---

## 1. Lancer le projet

### Prérequis

- Docker Desktop (ou Docker Engine + Compose)

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
├── docker-compose.yml        # Services web (PHP/Apache) + db (MySQL)
├── Dockerfile                # Image PHP 8.3 + Xdebug + pdo_mysql
├── init.sql                  # Schéma + données initiales + admin
├── migration_v2.sql          # Migration pour une base existante
├── projet/                   # Racine web (montée dans /var/www/html)
│   ├── config.php            # Config, sécurité, session, PDO
│   ├── functions.php         # Auth, panier, CSRF, upload, validation
│   ├── Entities/             # ProductEntity, PrixEntity, CategorieEntity
│   ├── templates/            # header.php / footer.php
│   ├── produits/             # Catalogue + backoffice CRUD
│   ├── categories/           # Ajout de catégorie
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
- Prix HT / TTC avec TVA par catégorie.
- Promotion en pourcentage (affichage).
- Image par défaut si aucune image n'est renseignée.

### Backoffice (admin)
- Liste des produits, ajout, modification, suppression logique.
- Gestion des catégories (ajout).
- Accès protégé par `exiger_admin()`.
- CSRF et validation serveur sur chaque formulaire.
- Échappement systématique des sorties avec `h()`.

### Panier
- Ajout avec contrôle du stock.
- Modification des quantités et suppression (quantité 0).
- Calcul de la TVA et du total TTC en session.

---

## 5. Sécurité

- Requêtes préparées PDO (`ATTR_EMULATE_PREPARES = false`).
- Échappement XSS via `h()` / `htmlspecialchars`.
- Jetons CSRF sur les formulaires POST.
- Contrôle d'accès admin centralisé.
- Validation serveur des entrées (produits, catégories, inscription).
- En-têtes HTTP : `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP.
- Secrets dans `.env`, non versionné.

---

## 6. Reste à faire (roadmap)

- Filtre/tri du catalogue par type de produit.
- Frais de port et mise à jour dynamique du montant.
- Tunnel de commande (`commande` / `ligne_commande`).
- API REST native + JSON Schema.
- Faker / jeu de données de démonstration.
- Tests Selenium / PHPUnit.
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

# Accéder à MySQL
docker compose exec db mysql -uweb_user -p e_commerce
```
