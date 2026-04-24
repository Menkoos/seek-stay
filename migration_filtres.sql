USE bddtest;

ALTER TABLE `annonces`
    ADD COLUMN IF NOT EXISTS `equipements`   TEXT                          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `type_proprio`  ENUM('particulier','agence')  DEFAULT 'particulier',
    ADD COLUMN IF NOT EXISTS `apl_accepte`   TINYINT(1)                    DEFAULT 0;
