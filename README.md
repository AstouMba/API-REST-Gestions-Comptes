# API Gestion Comptes

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-green.svg)](https://postgresql.org)

Une API RESTful pour la gestion de comptes bancaires avec calcul de solde dynamique, utilisant Laravel et PostgreSQL.

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données](#base-de-données)
- [API Documentation](#api-documentation)
- [Modèles et Relations](#modèles-et-relations)
- [Controllers](#controllers)
- [Middleware](#middleware)
- [Tests](#tests)
- [Postman Collection](#postman-collection)
- [Contributing](#contributing)
- [License](#license)

## 🚀 Fonctionnalités

- ✅ Gestion des utilisateurs avec UUID
- ✅ Création de clients par admins
- ✅ Gestion de comptes bancaires (épargne, chèque)
- ✅ Transactions (dépôts, retraits, virements)
- ✅ Calcul de solde dynamique
- ✅ Pagination et filtres avancés
- ✅ Recherche et tri
- ✅ Rate limiting
- ✅ API versionnée (/v1)
- ✅ Documentation complète

## 🛠 Technologies

- **Framework**: Laravel 11.x
- **Base de données**: PostgreSQL 15+
- **Langage**: PHP 8.2+
- **ORM**: Eloquent
- **Testing**: PHPUnit
- **Documentation**: Postman

## 📦 Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- PostgreSQL 15+
- Node.js et npm (pour les assets)

### Étapes

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-repo/api-gestion-comptes.git
   cd api-gestion-comptes
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   npm install
   ```

3. **Copier l'environnement**
   ```bash
   cp .env.example .env
   ```

4. **Générer la clé d'application**
   ```bash
   php artisan key:generate
   ```

5. **Compiler les assets**
   ```bash
   npm run build
   ```

## ⚙️ Configuration

### Variables d'environnement

Mettre à jour `.env` :

```env
APP_NAME="API Gestion Comptes"
APP_ENV=local
APP_KEY=base64:votre-cle
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=api_gestion_comptes
DB_USERNAME=postgres
DB_PASSWORD=votre-mot-de-passe

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

## 🗄 Base de données

### Migration

```bash
php artisan migrate
```

### Seeding

```bash
php artisan db:seed
```

### Structure des tables

#### Users
- `id` (UUID) - Clé primaire
- `login` (string) - Unique
- `password` (string) - Hashé
- `timestamps`

#### Admins
- `id` (UUID) - Clé primaire
- `user_id` (UUID) - FK vers users
- `timestamps`

#### Clients
- `id` (UUID) - Clé primaire
- `utilisateur_id` (UUID) - FK vers users
- `nom` (string)
- `email` (string) - Unique
- `adresse` (string) - Nullable
- `telephone` (string) - Nullable
- `timestamps`

#### Comptes
- `id` (UUID) - Clé primaire
- `client_id` (UUID) - FK vers clients
- `numero` (string) - Unique
- `type` (enum: epargne, cheque)
- `statut` (enum: actif, bloque, ferme)
- `devise` (string) - Default: FCFA
- `motifBlocage` (string) - Nullable
- `deleted_at` (timestamp) - Nullable
- `timestamps`

#### Transactions
- `id` (UUID) - Clé primaire
- `compte_id` (UUID) - FK vers comptes
- `type` (enum: depot, retrait, virement)
- `montant` (decimal)
- `description` (string) - Nullable
- `timestamps`

## 🌐 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
Pour l'instant, pas d'authentification requise (middleware commenté).

### Endpoints

#### 1. Lister les Comptes
**GET /api/v1/comptes**

Récupère la liste des comptes avec pagination, filtres et tri.

**Query Parameters:**
- `page` (int) - Numéro de page (default: 1)
- `limit` (int) - Éléments par page (default: 10, max: 100)
- `type` (string) - Filtre par type (epargne, cheque)
- `statut` (string) - Filtre par statut (actif, bloque, ferme)
- `search` (string) - Recherche par numéro ou titulaire
- `sort` (string) - Tri (dateCreation, solde, titulaire)
- `order` (string) - Ordre (asc, desc)

**Exemple de requête:**
```bash
curl "http://localhost:8000/api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=solde&order=desc"
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Comptes retrieved successfully",
  "data": {
    "data": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "numeroCompte": "CPT123456",
        "titulaire": "Amadou Diallo",
        "type": "epargne",
        "solde": 1250000,
        "devise": "FCFA",
        "dateCreation": "2023-03-15T00:00:00Z",
        "statut": "actif",
        "motifBlocage": null,
        "metadata": {
          "derniereModification": "2023-06-10T14:30:00Z",
          "version": 1
        }
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 3,
      "totalItems": 25,
      "itemsPerPage": 10,
      "hasNext": true,
      "hasPrevious": false
    },
    "links": {
      "self": "/api/v1/comptes?page=1&limit=10",
      "next": "/api/v1/comptes?page=2&limit=10",
      "first": "/api/v1/comptes?page=1&limit=10",
      "last": "/api/v1/comptes?page=3&limit=10"
    }
  }
}
```

#### 2. Créer un Client (Admin)
**POST /api/admin/clients**

Crée un nouveau client (réservé aux admins).

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
  "nom": "John Doe",
  "email": "john@example.com",
  "adresse": "123 Rue Exemple",
  "telephone": "1234567890",
  "login": "johndoe"
}
```

**Réponse (201):**
```json
{
  "success": true,
  "message": "Client created successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "utilisateur_id": "660e8400-e29b-41d4-a716-446655440000",
    "nom": "John Doe",
    "email": "john@example.com",
    "adresse": "123 Rue Exemple",
    "telephone": "1234567890",
    "created_at": "2023-10-23T13:00:00Z",
    "updated_at": "2023-10-23T13:00:00Z"
  }
}
```

## 🏗 Modèles et Relations

### User
- **Clé primaire**: UUID
- **Relations**:
  - `admin()` - hasOne Admin
  - `clients()` - hasMany Client (via utilisateur_id)

### Admin
- **Clé primaire**: UUID
- **Relations**:
  - `user()` - belongsTo User

### Client
- **Clé primaire**: UUID
- **Relations**:
  - `utilisateur()` - belongsTo User
  - `comptes()` - hasMany Compte

### Compte
- **Clé primaire**: UUID
- **Relations**:
  - `client()` - belongsTo Client
  - `transactions()` - hasMany Transaction
- **Attributs calculés**:
  - `solde` - Somme des dépôts - retraits - virements
  - `titulaire` - Nom du client
  - `metadata` - Informations de modification
- **Scopes**:
  - `type($type)` - Filtre par type
  - `statut($statut)` - Filtre par statut
  - `search($search)` - Recherche par numéro ou titulaire
- **Global Scope**: `notDeleted` - Exclut les supprimés

### Transaction
- **Clé primaire**: UUID
- **Relations**:
  - `compte()` - belongsTo Compte

## 🎮 Controllers

### AdminController
- **store()**: Crée un client et un utilisateur associé
- **Middleware**: admin, rate.limit
- **Validation**: StoreClientRequest

### CompteController
- **index()**: Liste les comptes avec pagination et filtres
- **Middleware**: rate.limit
- **Validation**: Query parameters
- **Response**: CompteResource avec pagination

## 🛡️ Middleware

### AdminMiddleware
- Vérifie si l'utilisateur est admin (relation avec table admins)
- Appliqué aux routes admin

### RateLimitingMiddleware
- Limite à 100 requêtes/minute par IP
- Utilise cache pour le comptage

## 🧪 Tests

### Exécuter les tests
```bash
php artisan test
```

### Tests inclus
- Tests unitaires pour les modèles
- Tests de fonctionnalités pour les controllers
- Tests d'intégration pour les API

### Exemple de test
```php
public function test_can_list_comptes()
{
    $response = $this->get('/api/v1/comptes');
    $response->assertStatus(200)
             ->assertJsonStructure(['success', 'data' => ['data', 'pagination']]);
}
```

## 📮 Postman Collection

Importez `API-gestionComptes.postman_collection.json` dans Postman pour tester les endpoints.

### Variables
- `base_url`: http://localhost:8000/api/v1

### Requêtes incluses
- List Comptes (avec filtres)
- List Comptes (tous)
- List Comptes (recherche)
- Create Client

## 🤝 Contributing

1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**Développé avec ❤️ par Laravel**
