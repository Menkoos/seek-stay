USE bddtest;

ALTER TABLE `messagerie`
    ADD INDEX IF NOT EXISTS `idx_emetteur`  (`emetteur_id`),
    ADD INDEX IF NOT EXISTS `idx_recepteur` (`recepteur_id`),
    ADD INDEX IF NOT EXISTS `idx_date`      (`date_emission`),
    ADD INDEX IF NOT EXISTS `idx_lu`        (`lu`);
