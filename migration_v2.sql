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
