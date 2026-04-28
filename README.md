<div align="center">

# Seek & Stay

### Plateforme de location de logements étudiants

*Site web destiné aux étudiants de l'ISEP Paris à la recherche d'un logement, permettant de publier, parcourir et candidater à des annonces de location entre étudiants.*

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](./LICENSE)

</div>

---

## Aperçu

**Seek & Stay** est une plateforme web full-stack conçue dans le cadre d'un projet d'école à l'ISEP Paris. Elle permet aux étudiants de publier des annonces de logement, de rechercher des locations adaptées à leurs critères, de gérer leurs candidatures et d'échanger directement avec d'autres utilisateurs via une messagerie intégrée.

Le projet a été développé en collaboration entre **Sofiane Lebdaoui** ([@Menkoos](https://github.com/Menkoos)) et **Kylian Enyegue** ([@kylian-enyegue](https://github.com/kylian-enyegue)). Le code source original est disponible sur le [repository d'origine](https://github.com/kylian-enyegue/Seek-Stay-website).

---

## Fonctionnalités principales

### Côté utilisateur
| Fonctionnalité | Description |
|---|---|
| **Authentification sécurisée** | Inscription, connexion, mot de passe oublié, réinitialisation, modification, suppression de compte |
| **Publication d'annonces** | Création, modification et suppression d'annonces avec photos, prix, description et adresse |
| **Recherche & filtres** | Parcours des annonces avec filtres (prix, disponibilité, localisation) |
| **Favoris** | Système de mise en favori des annonces préférées |
| **Candidatures** | Postuler à des logements et suivre l'état de ses candidatures |
| **Messagerie interne** | Échange de messages en temps réel entre utilisateurs |
| **Profil utilisateur** | Page profil personnalisable avec photo et informations |
| **Signalement** | Signaler des annonces ou utilisateurs abusifs |
| **Sécurité du compte** | Option "se souvenir de moi", historique de sécurité |

### Panneau d'administration
| Fonctionnalité | Description |
|---|---|
| **Dashboard** | Vue d'ensemble (utilisateurs, annonces, signalements) |
| **Gestion des utilisateurs** | Liste, modification, suspension, suppression |
| **Modération des annonces** | Validation, masquage, suppression |
| **Traitement des signalements** | Gestion des plaintes utilisateurs |
| **Gestion des contenus** | Édition des mentions légales et pages statiques |
| **Système de rôles** | RBAC (utilisateur, modérateur, admin) |

---

## Stack technique

### Backend
- **PHP 8.2** (procédural)
- **PDO** avec **requêtes préparées** (protection contre les injections SQL)
- **Sessions PHP** natives pour la gestion d'authentification
- **API REST** légère pour les fonctionnalités dynamiques (favoris, messagerie)

### Base de données
- **MySQL / MariaDB 10.4**
- 11+ tables relationnelles (utilisateurs, annonces, candidatures, favoris, messagerie, avis, notifications, signalements, rôles, permissions)
- Identifiants en **UUID** (`varchar(36)`)
- Encodage **utf8mb4**
- Migrations SQL versionnées

### Frontend
- **HTML5 / CSS3** (custom, sans framework)
- **JavaScript Vanilla** (pas de jQuery, pas de framework)
- **Font Awesome 6** + **Material Symbols** (icônes)
- Design responsive

### Environnement
- **XAMPP** (Apache + MySQL + PHP)
- **phpMyAdmin** pour l'administration de la base

---

## Architecture du projet

```
seek-stay/
├── admin/                    # Panneau d'administration
│   ├── _auth.php            # Vérification des droits admin
│   ├── _layout.php          # Layout commun admin
│   ├── dashboard.php        # Tableau de bord
│   ├── users.php            # Gestion utilisateurs
│   ├── annonces.php         # Modération des annonces
│   └── signalements.php     # Traitement des signalements
├── api/                      # Endpoints API internes
│   ├── favoris.php
│   └── messages.php
├── img/                      # Assets images
├── js/                       # Scripts JavaScript
├── styles/                   # Feuilles de style CSS
├── uploads/                  # Photos d'annonces uploadées
├── php/                      # Logique métier PHP
├── *.sql                     # Migrations base de données
│   ├── bddtest.sql
│   ├── migration_annonces.sql
│   ├── migration_messagerie.sql
│   └── ...
├── config.php                # Configuration PDO / MySQL
├── session.php               # Gestion de session
├── security.php              # Helpers de sécurité
└── *.php / *.html            # Pages publiques et utilisateur
```

---

## Sécurité

- **Requêtes préparées** PDO sur toutes les interactions base de données (anti-injection SQL)
- **Hashage des mots de passe** via `password_hash()` (bcrypt)
- **Sessions sécurisées** avec régénération d'ID et validation
- **Système de tokens** pour la réinitialisation de mot de passe
- **Validation côté serveur** des entrées utilisateur
- **Protection CSRF** sur les formulaires sensibles
- **RBAC** (Role-Based Access Control) pour l'accès admin
- **Logs d'erreur** côté serveur (les détails ne fuitent pas vers l'utilisateur)

---

## Installation locale

### Prérequis
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.2+)
- ou équivalent : WAMP, MAMP, LAMP

### Étapes

1. **Cloner le repository** dans le dossier web de XAMPP :
   ```bash
   cd C:/xampp/htdocs/
   git clone https://github.com/Menkoos/seek-stay.git
   ```

2. **Lancer XAMPP** : démarrer Apache et MySQL via le panneau de contrôle.

3. **Créer la base de données** :
   - Ouvrir [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Créer une base nommée `bddtest`
   - Importer le fichier `bddtest.sql`
   - Appliquer les migrations dans l'ordre :
     ```
     create_user.sql
     migration_annonces.sql
     migration_compte.sql
     migration_disponibilite.sql
     migration_filtres.sql
     migration_messagerie.sql
     migration_remember.sql
     migration_role.sql
     ```

4. **Configurer les identifiants** : par défaut le projet utilise `root` sans mot de passe (XAMPP par défaut). Si tu as un mot de passe MySQL, modifier `config.php` ou créer un utilisateur dédié via `create_user.sql`.

5. **Accéder au site** : [http://localhost/seek-stay/](http://localhost/seek-stay/)

---

## Pages principales

| Page | Rôle |
|---|---|
| `Accueil.php` | Page d'accueil publique |
| `Authentification.html` | Connexion / inscription |
| `Annonces.php` | Liste et recherche des annonces |
| `annonce.php` | Détail d'une annonce |
| `Publier.php` | Création d'une annonce |
| `Favoris.php` | Annonces sauvegardées |
| `messagerie.php` | Messagerie entre utilisateurs |
| `mon-compte.php` | Espace personnel |
| `profil.php` | Profil utilisateur public |
| `admin/dashboard.php` | Tableau de bord administrateur |

---

## Auteurs

- **Sofiane Lebdaoui** — [@Menkoos](https://github.com/Menkoos)
  Étudiant ingénieur à l'ISEP Paris (cycle ingénieur — Informatique & Réseaux)
  [LinkedIn](https://www.linkedin.com/in/sofiane-lebdaoui-a5b051369/)

- **Kylian Enyegue** — [@kylian-enyegue](https://github.com/kylian-enyegue)
  Étudiant ingénieur à l'ISEP Paris

Projet réalisé dans le cadre d'un projet d'école à l'ISEP Paris.

---

## Licence

Distribué sous licence **MIT**. Voir [`LICENSE`](./LICENSE) pour plus de détails.
