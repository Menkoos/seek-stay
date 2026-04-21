-- À exécuter UNE SEULE FOIS dans phpMyAdmin (onglet SQL)
-- Cela crée un utilisateur commun pour tout le groupe

CREATE USER IF NOT EXISTS 'seekstay'@'localhost' IDENTIFIED BY 'SeekStay2025!';
GRANT ALL PRIVILEGES ON bddtest.* TO 'seekstay'@'localhost';
FLUSH PRIVILEGES;
