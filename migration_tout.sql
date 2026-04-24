-- ════════════════════════════════════════════════════════════════
--  Migration complète Seek & Stay
--  À importer dans phpMyAdmin sur la base bddtest
-- ════════════════════════════════════════════════════════════════
USE bddtest;

-- ─── TABLE utilisateur_ ────────────────────────────────────────
ALTER TABLE `utilisateur_`
    ADD COLUMN IF NOT EXISTS `photo_profil`     VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `bio`              TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `role_type`        ENUM('proprietaire','loueur') DEFAULT 'loueur',
    ADD COLUMN IF NOT EXISTS `is_admin`         TINYINT(1)   DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `remember_token`   VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `remember_expires` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_token`      VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_expires`    DATETIME     DEFAULT NULL;

-- Promouvoir un utilisateur en admin (remplace l'email par le tien)
UPDATE `utilisateur_` SET `is_admin` = 1 WHERE `email` = 'snok59000@gmail.com';

-- Si la colonne existait déjà en ENUM('etudiant','proprietaire'), on la convertit
ALTER TABLE `utilisateur_`
    MODIFY COLUMN `role_type` ENUM('proprietaire','loueur') DEFAULT 'loueur';

UPDATE `utilisateur_`
    SET `role_type` = 'loueur'
    WHERE `role_type` IS NULL OR `role_type` = '';

-- ─── TABLE annonces ────────────────────────────────────────────
ALTER TABLE `annonces`
    ADD COLUMN IF NOT EXISTS `utilisateur_id`   VARCHAR(36)                   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `image_principale` VARCHAR(255)                  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `liste_images`     TEXT                          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `date_publication` DATETIME                      DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `nb_vues`          INT                           DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `statut`           ENUM('actif','inactif','archive') DEFAULT 'actif',
    ADD COLUMN IF NOT EXISTS `equipements`      TEXT                          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `type_proprio`     ENUM('particulier','agence')  DEFAULT 'particulier',
    ADD COLUMN IF NOT EXISTS `apl_accepte`      TINYINT(1)                    DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `date_disponible`  DATE                          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `duree_min`        VARCHAR(30)                   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lat`              DECIMAL(10,7)                 DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lng`              DECIMAL(10,7)                 DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `meuble`           TINYINT(1)                    DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `nb_pieces`        INT                           DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `charges_incluses` TINYINT(1)                    DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `animaux_acceptes` TINYINT(1)                    DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `fumeur_autorise`  TINYINT(1)                    DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `accessible_pmr`   TINYINT(1)                    DEFAULT 0;

-- ─── TABLE messagerie (index de performance) ───────────────────
ALTER TABLE `messagerie`
    ADD INDEX IF NOT EXISTS `idx_emetteur`  (`emetteur_id`),
    ADD INDEX IF NOT EXISTS `idx_recepteur` (`recepteur_id`),
    ADD INDEX IF NOT EXISTS `idx_date`      (`date_emission`),
    ADD INDEX IF NOT EXISTS `idx_lu`        (`lu`);

-- ─── TABLE favoris ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favoris` (
    `id_favori`      VARCHAR(36) NOT NULL PRIMARY KEY,
    `utilisateur_id` VARCHAR(36) NOT NULL,
    `annonce_id`     VARCHAR(36) NOT NULL,
    `date_ajout`     DATETIME    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_favori` (`utilisateur_id`, `annonce_id`),
    INDEX `idx_user`    (`utilisateur_id`),
    INDEX `idx_annonce` (`annonce_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLE signalements ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `signalements` (
    `id_signalement`   VARCHAR(36)  NOT NULL PRIMARY KEY,
    `signaleur_id`     VARCHAR(36)  NOT NULL,
    `cible_type`       ENUM('annonce','utilisateur') NOT NULL,
    `cible_id`         VARCHAR(36)  NOT NULL,
    `raison`           VARCHAR(50)  NOT NULL,
    `commentaire`      TEXT         DEFAULT NULL,
    `date_signalement` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `statut`           ENUM('en_attente','traite','rejete') DEFAULT 'en_attente',
    INDEX `idx_signaleur` (`signaleur_id`),
    INDEX `idx_cible`     (`cible_type`, `cible_id`),
    INDEX `idx_statut`    (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
