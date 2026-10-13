CREATE DATABASE IF NOT EXISTS e_commerce
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
 
USE e_commerce;

CREATE USER IF NOT EXISTS 'web_user'@'localhost' IDENTIFIED BY 'masterApp2809';
GRANT SELECT, INSERT, UPDATE, DELETE ON e_commerce.* TO 'web_user'@'localhost';
FLUSH PRIVILEGES;

-- -----------------------------------------------------
-- Catégories
-- -----------------------------------------------------
CREATE TABLE categorie (
  categorie_id  INT AUTO_INCREMENT,
  libelle_c     VARCHAR(100) NOT NULL,
  taxe_c        DECIMAL NOT NULL DEFAULT 0.20,
  PRIMARY KEY (categorie_id),
  UNIQUE KEY uq_categorie_libelle (libelle_c)
);
 
-- -----------------------------------------------------
-- Produits
-- promo_p : pourcentage de promotion, NULL = pas de promo
-- url_image_p : NULL = afficher l'image générique
-- -----------------------------------------------------
CREATE TABLE produit (
  p_id           INT AUTO_INCREMENT,
  designation_p  VARCHAR(255) NOT NULL,
  date_in        DATE NOT NULL,
  times_in       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  stock_p        INT NOT NULL DEFAULT 0,
  url_image_p    TEXT NULL,
  promo_p        TINYINT UNSIGNED NULL,
  categorie_id   INT NOT NULL,
  is_deleted     TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (p_id),
  CONSTRAINT chk_stock CHECK (stock_p >= 0),
  CONSTRAINT chk_promo CHECK (promo_p IS NULL OR promo_p BETWEEN 1 AND 100),
  CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id)
    REFERENCES categorie (categorie_id) ON DELETE RESTRICT
);
 
-- -----------------------------------------------------
-- Historique des prix (le prix courant = ligne la plus récente)
-- -----------------------------------------------------
CREATE TABLE prix_historique (
  prix_id     INT AUTO_INCREMENT,
  p_id        INT NOT NULL,
  prix_ht     DECIMAL(10,2) NOT NULL,
  date_debut  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (prix_id),
  INDEX idx_prix_produit_date (p_id, date_debut),
  CONSTRAINT chk_prix CHECK (prix_ht >= 0),
  CONSTRAINT fk_prix_produit FOREIGN KEY (p_id)
    REFERENCES produit (p_id) ON DELETE CASCADE
);
 
-- -----------------------------------------------------
-- Comptes clients
-- -----------------------------------------------------
CREATE TABLE compte (
  user_id       INT AUTO_INCREMENT,
  prenom_c             VARCHAR(100) NOT NULL,
  nom_c                VARCHAR(100) NOT NULL,
  adresse_livraison_c  TEXT NULL,
  telephone_c          VARCHAR(20) NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_user_id FOREIGN KEY (user_id)
    REFERENCES `user` (user_id) ON DELETE CASCADE
);
 
-- -----------------------------------------------------
-- Rôles
-- -----------------------------------------------------
CREATE TABLE role (
  id_role       INT AUTO_INCREMENT,
  libelle_role  VARCHAR(30) NOT NULL,
  PRIMARY KEY (id_role),
  UNIQUE KEY uq_role_libelle (libelle_role)
);
 
-- -----------------------------------------------------
-- Utilisateurs (authentification)
-- user_password : stocker le résultat de password_hash()
-- -----------------------------------------------------
CREATE TABLE `user` (
  user_id          INT AUTO_INCREMENT,
  user_login       VARCHAR(50)  NOT NULL,
  user_password    VARCHAR(255) NOT NULL,
  user_mail        VARCHAR(255) NOT NULL,
  user_date_new    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_date_login  DATETIME NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_user_login (user_login),
  UNIQUE KEY uq_user_mail (user_mail),
);
 
-- -----------------------------------------------------
-- Droits : lien utilisateur <-> rôle
-- -----------------------------------------------------
CREATE TABLE droit (
  user_id  INT NOT NULL,
  id_role  INT NOT NULL,
  PRIMARY KEY (user_id, id_role),
  CONSTRAINT fk_droit_user FOREIGN KEY (user_id)
    REFERENCES `user` (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_droit_role FOREIGN KEY (id_role)
    REFERENCES role (id_role) ON DELETE CASCADE
);
 
-- -----------------------------------------------------
-- Données initiales
-- -----------------------------------------------------
INSERT INTO role (libelle_role) VALUES ('client'), ('admin');
 
INSERT INTO categorie (categorie_id, libelle_c, taxe_c) VALUES
  (1, 'Smartphone', 0.20),
  (2, 'Ordinateur portable', 0.20),
  (3, 'Casque audio', 0.20),
  (4, 'Montre connectée', 0.20),
  (5, 'Tablette', 0.20),
  (6, 'Caméra', 0.20);
