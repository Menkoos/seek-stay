-- Migration : ajout des colonnes pour "Se souvenir de moi" et réinitialisation de mot de passe
USE bddtest;

ALTER TABLE `utilisateur_`
    ADD COLUMN IF NOT EXISTS `remember_token`   VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `remember_expires` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_token`      VARCHAR(64)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `reset_expires`    DATETIME     DEFAULT NULL;
