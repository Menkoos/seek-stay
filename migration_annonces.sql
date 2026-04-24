USE bddtest;

ALTER TABLE `annonces`
    ADD COLUMN IF NOT EXISTS `date_publication` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `nb_vues`          INT(11)      DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `statut`           ENUM('actif','inactif') DEFAULT 'actif',
    ADD COLUMN IF NOT EXISTS `liste_images`     TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `image_principale` TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `utilisateur_id`   VARCHAR(36)  DEFAULT NULL;
