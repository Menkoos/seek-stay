-- Migration : gestion de compte (photo, bio, rôle)
-- À exécuter dans phpMyAdmin sur la base bddtest
USE bddtest;

ALTER TABLE `utilisateur_`
    ADD COLUMN IF NOT EXISTS `photo_profil`  VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `bio`           TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `role_type`     ENUM('etudiant','proprietaire') DEFAULT 'etudiant',
    ADD COLUMN IF NOT EXISTS `remember_token`   VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `remember_expires` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_token`      VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_expires`    DATETIME     DEFAULT NULL;
