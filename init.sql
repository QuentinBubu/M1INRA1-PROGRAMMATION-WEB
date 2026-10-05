ALTER USER 'web_user'@'%' IDENTIFIED WITH mysql_native_password BY 'masterApp2809';

GRANT ALL PRIVILEGES ON e_commerce.* TO 'web_user'@'%';

FLUSH PRIVILEGES;

CREATE TABLE
	produit (
		p_id INT AUTO_INCREMENT,
		designation_p VARCHAR(255) NOT NULL,
		date_in DATE NOT NULL,
		times_in DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		stock_p INT DEFAULT 0,
		url_image_p VARCHAR(255),
		promo_p INT,
		PRIMARY KEY (p_id)
	);

CREATE TABLE
	historique (hist_date DATETIME, PRIMARY KEY (hist_date));

CREATE TABLE
	categorie (type_p VARCHAR(100), PRIMARY KEY (type_p));

CREATE TABLE
	compte (
		user_compte_id INT AUTO_INCREMENT,
		prenom_c VARCHAR(100) NOT NULL,
		nom_c VARCHAR(100) NOT NULL,
		adresse_livraison_c TEXT,
		telephone_c DECIMAL(10, 0),
		PRIMARY KEY (user_compte_id)
	);

CREATE TABLE
	role (id_role INT AUTO_INCREMENT, PRIMARY KEY (id_role));

CREATE TABLE
	`user` (
		user_id INT AUTO_INCREMENT,
		user_login TEXT NOT NULL,
		user_password TEXT NOT NULL,
		user_mail TEXT NOT NULL,
		user_date_new DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		user_date_login DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		user_compte_id INT NOT NULL,
		PRIMARY KEY (user_id),
		FOREIGN KEY (user_compte_id) REFERENCES compte (user_compte_id)
	);

CREATE TABLE
	couter (
		p_id INT,
		hist_date DATETIME,
		prix_ht DECIMAL(10, 2) NOT NULL,
		PRIMARY KEY (p_id, hist_date),
		FOREIGN KEY (p_id) REFERENCES produit (p_id),
		FOREIGN KEY (hist_date) REFERENCES historique (hist_date)
	);

CREATE TABLE
	appartenir (
		p_id INT,
		type_p VARCHAR(100),
		PRIMARY KEY (p_id, type_p),
		FOREIGN KEY (p_id) REFERENCES produit (p_id),
		FOREIGN KEY (type_p) REFERENCES categorie (type_p)
	);

CREATE TABLE
	droit (
		user_compte_id INT,
		id_role INT,
		PRIMARY KEY (user_compte_id, id_role),
		FOREIGN KEY (user_compte_id) REFERENCES compte (user_compte_id),
		FOREIGN KEY (id_role) REFERENCES role (id_role)
	);