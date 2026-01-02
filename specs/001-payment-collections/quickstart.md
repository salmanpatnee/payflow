# Quickstart Guide: Payment Collections & Data Model

## Overview
This guide provides a quick start for developers to understand and implement the payment collections feature. This feature enables admins to create, read, update, and delete payment collections with associated items.

## Prerequisites
- PHP 8.3+
- Laravel 12
- Node.js 18+
- MySQL database
- Composer and npm installed

## Setup Instructions

### 1. Clone and Install Dependencies
```bash
# Clone the repository
git clone <repository-url>
cd payflow

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Setup
```bash
# Configure your database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payflow
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed the database if needed
php artisan db:seed
```

### 3. Environment Configuration
Set the following environment variables in your `.env` file:
```
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=payflow
DB_USERNAME=your_username
DB_PASSWORD=your_password

# App
APP_NAME="PayFlow"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Frontend
VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
```

### 4. Build Frontend Assets
```bash
# For development
npm run dev

# For production
npm run build
```

## Key Components

### Backend Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── PaymentCollectionController.php
│   ├── Requests/
│   │   └── PaymentCollectionRequest.php
│   └── Resources/
│       └── PaymentCollectionResource.php
├── Models/
│   ├── PaymentCollection.php
│   └── PaymentItem.php
└── Services/
    └── PaymentCollectionService.php
```

### Frontend Structure
```
resources/js/
├── Pages/
│   └── Admin/
│       ├── PaymentCollections/
│       │   ├── Index.vue
│       │   └── Form.vue
│       └── Shared/
│           └── RepeatableItems.vue
└── Components/
    └── UI/
```

## Running the Application

### 1. Start the Development Server
```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Watch frontend assets
npm run dev
```

### 2. Access the Application
- Admin Interface: http://localhost:8000/admin
- Login with your admin credentials

## Key Endpoints

### API Endpoints
- `GET /admin/api/payment-collections` - List all collections
- `GET /admin/api/payment-collections/{id}` - Get single collection
- `POST /admin/api/payment-collections` - Create collection
- `PUT /admin/api/payment-collections/{id}` - Update collection
- `DELETE /admin/api/payment-collections/{id}` - Delete collection

### Frontend Pages
- `/admin/payment-collections` - List all collections
- `/admin/payment-collections/create` - Create new collection
- `/admin/payment-collections/{id}/edit` - Edit existing collection

## Running Tests

### Backend Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=PaymentCollectionTest

# Run feature tests only
php artisan test --testsuite=Feature
```

### Frontend Tests
```bash
# Run JavaScript tests
npm run test
```

## Database Schema

### payment_collections table
- id: BIGINT (Primary Key)
- uuid: CHAR(36) (Unique, Indexed)
- name: VARCHAR(255) (Not null)
- description: TEXT (Nullable)
- status: VARCHAR(50) (Not null, Default: 'active')
- admin_user_id: BIGINT (Foreign key, Indexed)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP

### payment_items table
- id: BIGINT (Primary Key)
- payment_collection_id: BIGINT (Foreign key, Indexed)
- name: VARCHAR(255) (Not null)
- description: TEXT (Nullable)
- price: DECIMAL(10,2) (Not null)
- quantity: INTEGER (Not null, Default: 1)
- type: VARCHAR(100) (Not null)
- sort_order: INTEGER (Default: 0)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP

## Development Workflow

### 1. Creating a New Payment Collection
1. Navigate to `/admin/payment-collections/create`
2. Fill in the collection details
3. Add items using the repeatable form
4. Submit the form

### 2. Modifying an Existing Collection
1. Navigate to `/admin/payment-collections`
2. Click "Edit" on the desired collection
3. Make changes to the collection or items
4. Submit the form

### 3. Deleting a Collection
1. Navigate to `/admin/payment-collections`
2. Click "Delete" on the desired collection
3. Confirm deletion in the modal

## Common Tasks

### Generate New Migration
```bash
php artisan make:migration create_payment_collections_table
```

### Generate New Controller
```bash
php artisan make:controller Admin/PaymentCollectionController
```

### Generate New Model
```bash
php artisan make:model PaymentCollection
```

### Generate New Request
```bash
php artisan make:request PaymentCollectionRequest
```

### Generate New Test
```bash
php artisan make:test PaymentCollectionTest --pest
```

## Troubleshooting

### Common Issues
1. **Database connection errors**: Verify your `.env` database configuration
2. **Asset compilation errors**: Run `npm run build` or `npm run dev`
3. **Authentication issues**: Ensure you're logged in as an admin user
4. **API errors**: Check the browser console and server logs

### Useful Commands
```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate autoload files
composer dump-autoload

# Run migrations with seeders
php artisan migrate:fresh --seed
```

## Next Steps
1. Implement the payment processing functionality (Phase 3)
2. Add more advanced validation rules
3. Implement role-based access control
4. Add reporting and analytics features