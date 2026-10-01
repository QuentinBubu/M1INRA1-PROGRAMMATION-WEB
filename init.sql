-- Force l'utilisation du plugin natif pour cet utilisateur
ALTER USER 'web_user'@'%' IDENTIFIED WITH mysql_native_password BY 'masterApp2809';
GRANT ALL PRIVILEGES ON e_commerce.* TO 'web_user'@'%';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS `user` (
	`user_id` INT NOT NULL AUTO_INCREMENT,
	`user_login` TEXT NOT NULL,
	`user_password` LONGTEXT NOT NULL,
	`user_compte_id` INT NOT NULL,
	`user_mail` TEXT NOT NULL,
	`user_date_new` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`user_date_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`user_id`),
	UNIQUE KEY `cle_etrangere` (`user_compte_id`)
) ENGINE=InnoDB;
