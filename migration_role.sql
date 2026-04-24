USE bddtest;

-- Met à jour le type de la colonne role_type (ancienne valeur : 'etudiant')
ALTER TABLE `utilisateur_`
    MODIFY COLUMN `role_type` ENUM('proprietaire','loueur') DEFAULT 'loueur';

-- Convertit les anciens comptes 'etudiant' en 'loueur'
UPDATE `utilisateur_`
    SET `role_type` = 'loueur'
    WHERE `role_type` IS NULL OR `role_type` = '';
