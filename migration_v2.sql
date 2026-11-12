-- Migration pour une base deja existante.
-- A executer une seule fois :
--   docker compose exec -T db mysql --default-character-set=utf8mb4 -uroot -p"$MYSQL_ROOT_PASSWORD" e_commerce < migration_v2.sql

USE e_commerce;

-- 1. Compte administrateur par defaut
-- Identifiant : admin / Mot de passe : Admin1234!2026 (a changer)
INSERT IGNORE INTO `user` (user_login, user_password, user_mail) VALUES
  ('admin',
   '$argon2id$v=19$m=65536,t=4,p=1$a2U1aEdiWVhHVkZCTGFrRw$uDgt/0MMSmb5AeAoXzsdIZksJj0E33ddTQsuPIbZIqM',
   'admin@example.com');

INSERT IGNORE INTO compte (user_id, prenom_c, nom_c)
SELECT user_id, 'Admin', 'Admin' FROM `user` WHERE user_login = 'admin';

INSERT IGNORE INTO droit (user_id, id_role)
SELECT u.user_id, r.id_role
FROM `user` u
INNER JOIN role r ON r.libelle_role = 'admin'
WHERE u.user_login = 'admin';

-- 2. Backfill : donner le role client aux utilisateurs qui n'ont aucun droit
INSERT IGNORE INTO droit (user_id, id_role)
SELECT u.user_id, r.id_role
FROM `user` u
INNER JOIN role r ON r.libelle_role = 'client'
WHERE u.user_login <> 'admin'
  AND NOT EXISTS (SELECT 1 FROM droit d WHERE d.user_id = u.user_id);

-- 3. Backfill : creer un compte client minimal si absent
INSERT IGNORE INTO compte (user_id, prenom_c, nom_c)
SELECT u.user_id, u.user_login, u.user_login
FROM `user` u
WHERE u.user_login <> 'admin'
  AND NOT EXISTS (SELECT 1 FROM compte c WHERE c.user_id = u.user_id);

-- 4. Taux de TVA : precision a 4 decimales (0.0550 = 5,5 %)
ALTER TABLE categorie
  MODIFY taxe_c DECIMAL(5,4) NOT NULL DEFAULT 0.2000;

-- 5. Commandes et lignes de commande
CREATE TABLE IF NOT EXISTS commande (
  commande_id       INT AUTO_INCREMENT,
  numero_commande   VARCHAR(32) NOT NULL,
  user_id           INT NOT NULL,
  statut            ENUM('payee', 'annulee') NOT NULL DEFAULT 'payee',
  adresse_livraison TEXT NOT NULL,
  telephone         VARCHAR(20) NULL,
  total_ht          DECIMAL(10,2) NOT NULL,
  total_tva         DECIMAL(10,2) NOT NULL,
  total_ttc         DECIMAL(10,2) NOT NULL,
  mode_paiement     VARCHAR(30) NOT NULL DEFAULT 'carte',
  carte_fin         CHAR(4) NULL,
  date_commande     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (commande_id),
  UNIQUE KEY uq_commande_numero (numero_commande),
  INDEX idx_commande_user_date (user_id, date_commande),
  CONSTRAINT fk_commande_user FOREIGN KEY (user_id)
    REFERENCES `user` (user_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS ligne_commande (
  ligne_id          INT AUTO_INCREMENT,
  commande_id       INT NOT NULL,
  p_id              INT NULL,
  designation       VARCHAR(255) NOT NULL,
  quantite          INT NOT NULL,
  prix_unitaire_ht  DECIMAL(10,2) NOT NULL,
  taux_tva          DECIMAL(5,4) NOT NULL,
  total_ligne_ttc   DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (ligne_id),
  INDEX idx_ligne_commande (commande_id),
  CONSTRAINT chk_ligne_quantite CHECK (quantite > 0),
  CONSTRAINT fk_ligne_commande FOREIGN KEY (commande_id)
    REFERENCES commande (commande_id) ON DELETE CASCADE,
  CONSTRAINT fk_ligne_produit FOREIGN KEY (p_id)
    REFERENCES produit (p_id) ON DELETE SET NULL
);
