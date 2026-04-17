-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 27 mars 2026 à 16:34
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bddtest`
--

-- --------------------------------------------------------

--
-- Structure de la table `annonces`
--

CREATE TABLE `annonces` (
                            `id_annonce` varchar(36) NOT NULL,
                            `utilisateur_id` varchar(36) DEFAULT NULL,          -- NULL autorisé : auth pas encore branchée
                            `adresse` text DEFAULT NULL,
                            `ville` text DEFAULT NULL,
                            `code_postal` text DEFAULT NULL,
                            `prix` float DEFAULT NULL,
                            `nb_vues` int(11) DEFAULT 0,
                            `description` text DEFAULT NULL,
                            `superficie` float DEFAULT NULL,
                            `type_immeuble` text DEFAULT NULL,
                            `type_offre` text DEFAULT NULL,
                            `image_principale` text DEFAULT NULL,
                            `liste_images` text DEFAULT NULL,                   -- JSON : tableau des chemins d'images
                            `date_publication` datetime DEFAULT NULL,
                            `statut` enum('actif','inactif') DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
                        `id_avis` varchar(36) NOT NULL,
                        `utilisateur_id` varchar(36) NOT NULL,
                        `annonce_id` varchar(36) NOT NULL,
                        `note` int(11) DEFAULT NULL,
                        `commentaire` text DEFAULT NULL,
                        `date_avis` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `candidatures`
--

CREATE TABLE `candidatures` (
                                `id` varchar(36) NOT NULL,
                                `utilisateur_id` varchar(36) NOT NULL,
                                `annonce_id` varchar(36) NOT NULL,
                                `status` varchar(255) DEFAULT NULL,
                                `message` text DEFAULT NULL,
                                `date_emission` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
                           `id_favoris` varchar(36) NOT NULL,
                           `utilisateur_id` varchar(36) NOT NULL,
                           `annonce_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messagerie`
--

CREATE TABLE `messagerie` (
                              `id` varchar(36) NOT NULL,
                              `emetteur_id` varchar(36) NOT NULL,
                              `recepteur_id` varchar(36) NOT NULL,
                              `contenu` text DEFAULT NULL,
                              `date_emission` datetime DEFAULT NULL,
                              `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
                                 `id_notification` varchar(36) NOT NULL,
                                 `utilisateur_id` varchar(36) NOT NULL,
                                 `type_notification` varchar(255) DEFAULT NULL,
                                 `contenu` text DEFAULT NULL,
                                 `lecture_faite` tinyint(1) DEFAULT 0,
                                 `date_emission` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
                               `id_perm` varchar(36) NOT NULL,
                               `name` varchar(255) NOT NULL,
                               `resource` varchar(255) NOT NULL,
                               `action` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
                         `id_role` varchar(36) NOT NULL,
                         `name` varchar(255) NOT NULL,
                         `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
                                    `id` varchar(36) NOT NULL,
                                    `role_id` varchar(36) NOT NULL,
                                    `permission_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `signalements_annonces`
--

CREATE TABLE `signalements_annonces` (
                                         `id_signalement` varchar(36) NOT NULL,
                                         `utilisateur_id` varchar(36) NOT NULL,
                                         `annonce_id` varchar(36) NOT NULL,
                                         `motif` text DEFAULT NULL,
                                         `date_emission` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_`
--

CREATE TABLE `utilisateur_` (
                                `id_utilisateur` varchar(36) NOT NULL,
                                `nom` varchar(255) DEFAULT NULL,
                                `lastname` varchar(255) DEFAULT NULL,
                                `email` varchar(255) NOT NULL,
                                `mot_de_passe` varchar(255) NOT NULL,
                                `telephone` varchar(30) DEFAULT NULL,
                                `date_inscription` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `annonces`
--
ALTER TABLE `annonces`
    ADD PRIMARY KEY (`id_annonce`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
    ADD PRIMARY KEY (`id_avis`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `annonce_id` (`annonce_id`);

--
-- Index pour la table `candidatures`
--
ALTER TABLE `candidatures`
    ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `annonce_id` (`annonce_id`);

--
-- Index pour la table `favoris`
--
ALTER TABLE `favoris`
    ADD PRIMARY KEY (`id_favoris`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `annonce_id` (`annonce_id`);

--
-- Index pour la table `messagerie`
--
ALTER TABLE `messagerie`
    ADD PRIMARY KEY (`id`),
  ADD KEY `emetteur_id` (`emetteur_id`),
  ADD KEY `recepteur_id` (`recepteur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
    ADD PRIMARY KEY (`id_notification`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
    ADD PRIMARY KEY (`id_perm`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
    ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
    ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Index pour la table `signalements_annonces`
--
ALTER TABLE `signalements_annonces`
    ADD PRIMARY KEY (`id_signalement`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `annonce_id` (`annonce_id`);

--
-- Index pour la table `utilisateur_`
--
ALTER TABLE `utilisateur_`
    ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `annonces`
--
ALTER TABLE `annonces`
    ADD CONSTRAINT `annonces_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`);

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
    ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id_annonce`);

--
-- Contraintes pour la table `candidatures`
--
ALTER TABLE `candidatures`
    ADD CONSTRAINT `candidatures_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`),
  ADD CONSTRAINT `candidatures_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id_annonce`);

--
-- Contraintes pour la table `favoris`
--
ALTER TABLE `favoris`
    ADD CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`),
  ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id_annonce`);

--
-- Contraintes pour la table `messagerie`
--
ALTER TABLE `messagerie`
    ADD CONSTRAINT `messagerie_ibfk_1` FOREIGN KEY (`emetteur_id`) REFERENCES `utilisateur_` (`id_utilisateur`),
  ADD CONSTRAINT `messagerie_ibfk_2` FOREIGN KEY (`recepteur_id`) REFERENCES `utilisateur_` (`id_utilisateur`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
    ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`);

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
    ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`),
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id_perm`);

--
-- Contraintes pour la table `signalements_annonces`
--
ALTER TABLE `signalements_annonces`
    ADD CONSTRAINT `signalements_annonces_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur_` (`id_utilisateur`),
  ADD CONSTRAINT `signalements_annonces_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id_annonce`);

-- --------------------------------------------------------
--
-- Table `pages_contenu`
-- Stocke les textes modifiables des pages Mentions légales et CGU
-- Chaque ligne = une section d'une page (ex: "editeur_site" de la page "mentions")
--
CREATE TABLE `pages_contenu` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `page` varchar(50) NOT NULL,        -- 'mentions' ou 'cgu'
                                 `section` varchar(100) NOT NULL,    -- identifiant de la section (ex: 'editeur_site')
                                 `titre` varchar(255) NOT NULL,      -- titre affiché (ex: "1. Éditeur du site")
                                 `contenu` text NOT NULL,            -- texte de la section
                                 `date_modification` datetime DEFAULT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Données initiales : sections de la page Mentions légales
--
INSERT INTO `pages_contenu` (`page`, `section`, `titre`, `contenu`, `date_modification`) VALUES
                                                                                             ('mentions', 'editeur_site', '1. Éditeur du site', 'Ce site a été conçu et développé dans le cadre d\'un projet académique par les étudiants du groupe G9A de l\'ISEP (Institut Supérieur d\'Électronique de Paris), promotion 2025/2026.\nAdresse : 28 rue Notre-Dame-des-Champs, 75006 Paris, France', NOW()),
('mentions', 'hebergement', '2. Hébergement', 'Ce site est hébergé dans le cadre du projet pédagogique de l\'ISEP. Pour toute question relative à l\'hébergement, veuillez contacter l\'équipe via le formulaire de contact.', NOW()),
('mentions', 'propriete_intellectuelle', '3. Propriété intellectuelle', 'L\'ensemble des contenus présents sur ce site (textes, images, logos, icônes, structure) sont la propriété exclusive des étudiants du groupe G9A ou sont utilisés avec les autorisations nécessaires.\nToute reproduction, distribution ou modification sans autorisation préalable est interdite.', NOW()),
('mentions', 'donnees_personnelles', '4. Données personnelles', 'Dans le cadre de l\'utilisation de ce site, certaines données personnelles peuvent être collectées (adresse e-mail, nom, préférences de recherche). Ces données sont utilisées uniquement dans le cadre du fonctionnement de la plateforme et ne sont pas transmises à des tiers.', NOW()),
('mentions', 'cookies', '5. Cookies', 'Ce site peut utiliser des cookies à des fins techniques et de mémorisation de vos préférences. Vous pouvez gérer vos préférences à tout moment depuis la page Gestion des cookies.', NOW()),
('mentions', 'responsabilite', '6. Responsabilité', 'Les informations contenues sur ce site sont fournies à titre indicatif. L\'équipe du projet G9A ne saurait être tenue responsable des erreurs, omissions ou résultats qui pourraient être obtenus suite à l\'utilisation de ces informations.\nCe site est un projet étudiant réalisé à des fins pédagogiques. Certaines fonctionnalités peuvent être simulées ou incomplètes.', NOW()),
('mentions', 'droit_applicable', '7. Droit applicable', 'Les présentes mentions légales sont soumises au droit français. En cas de litige, les tribunaux compétents seront ceux du ressort de Paris.', NOW());

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
