# CNPS – Suivi des Recommandations

Application web de gestion du cycle de vie des recommandations issues des séances d'audit, réunions de direction et comités techniques au sein de la **Caisse Nationale de Prévoyance Sociale (CNPS)**.

## Présentation

Le système implémente un workflow structuré à **11 statuts (S0 → S10)** permettant de suivre chaque recommandation depuis sa création jusqu'à sa clôture, avec une matrice de droits par rôle et une traçabilité complète de toutes les transitions.

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Framework | Symfony 7.1 |
| ORM | Doctrine ORM 3.6 |
| PHP | 8.2+ |
| Base de données | MySQL 8.0 |
| Templating | Twig + Bootstrap 5.3 |
| Tests | PHPUnit 12.5 |
| Conteneurisation | Docker / Docker Compose |

## Prérequis

- PHP 8.2+
- Composer
- Docker & Docker Compose
- Node.js (optionnel, pour les assets)

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/Wil202/cnps-suivi-recommandation.git
cd cnps-suivi-recommandation
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env.local
```

Éditez `.env.local` et renseignez vos valeurs :

```dotenv
DATABASE_URL="mysql://cnps_user:votre_password@127.0.0.1:3307/cnps_recommandations?serverVersion=8.0&charset=utf8mb4"
APP_SECRET=votre_cle_secrete
MAILER_DSN=smtp://127.0.0.1:1025
```

### 4. Démarrer les conteneurs Docker

```bash
docker compose up -d
```

Cela lance :
- **MySQL 8.0** → port `3307`
- **PhpMyAdmin** → [http://localhost:8081](http://localhost:8081)
- **Mailpit** (emails dev) → [http://localhost:8025](http://localhost:8025)

### 5. Créer la base de données et appliquer les migrations

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

### 6. (Optionnel) Charger les données de démonstration

```bash
php bin/console doctrine:fixtures:load
```

### 7. Créer le compte administrateur

```bash
php bin/console app:create-admin
```

Crée l'utilisateur `admin@cnps.cm` avec le mot de passe `admin`.

### 8. Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

L'application est accessible sur [http://localhost:8000](http://localhost:8000).

---

## Architecture

### Entités principales

```
Structure (DRH, DSI…)
  └── Department (Services)
        └── User (8 rôles)

Meeting (Séance d'audit)
  └── Recommendation (S0 → S10)
        └── Event (Historique des transitions)
```

### Workflow des recommandations

```
S0 Projet  ──►  S1 Validée  ──►  S2 Affectée  ──►  S3 En cours
                                                         │
                     ◄──────── S6 Renvoyée ◄────────────┤
                                                         ▼
                                                    S4 Soumise CS
                                                    │         │
                                               S5 Validée CS  │
                                               │         │    │
                                          S7 Approuvée  S6   S6
                                          │         │
                                     S9 Clôturée  S10 Rejetée
                                                    │
                                               S3 En cours
```

### Rôles et droits

| Rôle | Responsabilité | Transitions autorisées |
|------|---------------|----------------------|
| `ROLE_ADMIN` | Administrateur système | Toutes |
| `ROLE_SECRETARY` | Secrétaire de séance | Créer séances et recommandations (S0) |
| `ROLE_COORDINATOR` | Coordonnateur | S0 → S1 (Valider), S0 → S10 (Rejeter) |
| `ROLE_CHIEF_STRUCTURE` | Chef de structure | S1 → S2 (Affecter), S5 → S7 (Approuver), S5 → S6 (Renvoyer) |
| `ROLE_CHIEF_SERVICE` | Chef de service | S2 → S3 (Démarrer), S4 → S5 (Valider), S4 → S6 (Renvoyer), S10 → S3 (Reprendre) |
| `ROLE_AGENT` | Agent opérationnel | S3 → S4 (Soumettre), S6 → S3 (Reprendre), S8 → S4 (Re-soumettre) |
| `ROLE_FOLLOWUP` | Structure de suivi | S7 → S9 (Clôturer), S7 → S10 (Rejeter) |
| `ROLE_DG` | Direction Générale | Consultation globale |

---

## Structure du projet

```
├── src/
│   ├── Controller/        # 11 contrôleurs (Dashboard, Recommendation, User, Meeting…)
│   ├── Entity/            # 6 entités Doctrine (User, Recommendation, Meeting, Structure, Department, Event)
│   ├── Form/              # 5 types de formulaires
│   ├── Repository/        # Repositories Doctrine
│   ├── Service/
│   │   └── WorkflowService.php   # Machine à états + matrice des droits
│   ├── Command/
│   │   └── CreateAdminCommand.php
│   └── DataFixtures/      # Données de démonstration
├── templates/
│   ├── dashboard/         # 7 dashboards (un par rôle)
│   ├── recommendation/    # CRUD + vue détail avec historique
│   ├── user/              # Gestion des utilisateurs
│   ├── structure/         # Gestion des structures
│   ├── department/        # Gestion des services
│   └── meeting/           # Gestion des séances
├── migrations/            # 7 migrations Doctrine
├── docker-compose.yml
├── .env.example
└── composer.json
```

---

## Commandes utiles

```bash
# Migrations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:status

# Cache
php bin/console cache:clear

# Générer du code (dev)
php bin/console make:controller NomController
php bin/console make:entity NomEntite

# Tests
php bin/phpunit

# Créer l'admin
php bin/console app:create-admin
```

---

## Accès Docker

| Service | URL | Identifiants |
|---------|-----|-------------|
| PhpMyAdmin | http://localhost:8081 | `cnps_user` / *(voir .env.local)* |
| Mailpit (emails) | http://localhost:8025 | aucun |
| MySQL | `127.0.0.1:3307` | `cnps_user` / *(voir .env.local)* |

---

## Contribuer

1. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
2. Committez vos changements : `git commit -m "feat: description"`
3. Poussez la branche : `git push origin feature/ma-fonctionnalite`
4. Ouvrez une Pull Request

---

## Licence

Projet développé dans le cadre d'un stage à la **CNPS – Caisse Nationale de Prévoyance Sociale**.
