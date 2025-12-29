# Quickstart Guide: Foundation & System Setup

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+ and npm
- PostgreSQL (or MySQL)
- Stripe account for payment processing

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
```

### 2. Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database settings in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Configure Stripe keys in .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 3. Database Setup

```bash
# Run database migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

### 4. Frontend Assets

```bash
# Build frontend assets for development
npm run dev

# Or for production
npm run build
```

### 5. Serve the Application

```bash
# Start the Laravel development server
php artisan serve

# In another terminal, watch for frontend changes
npm run dev
```

## Key Components

### Authentication
- The application uses Laravel's built-in authentication system
- Admin users can register and log in via the `/login` and `/register` routes

### Dashboard
- After login, admins are redirected to the dashboard at `/dashboard`
- The dashboard provides an overview of payment collections and statistics

### Payment Collections
- Admins can create payment collections via the `/payment-collections` route
- Each collection has a unique UUID for sharing with clients
- Collections contain multiple payment items with individual amounts

### Frontend Stack
- Vue 3 with Inertia.js for seamless page transitions
- shadcn-vue components for consistent UI elements
- Tailwind CSS v4 for styling

### Stripe Integration
- The Stripe PHP SDK is configured for payment processing
- Payment intents are created securely via API endpoints
- Webhook handling is configured for payment status updates

## API Endpoints

### Authentication
- `POST /login` - Admin login
- `POST /logout` - Admin logout

### Payment Collections
- `GET /payment-collections` - List all collections
- `POST /payment-collections` - Create a new collection
- `GET /payment-collections/{id}` - Get a specific collection
- `PUT /payment-collections/{id}` - Update a collection
- `DELETE /payment-collections/{id}` - Delete a collection

### Public Payment Pages
- `GET /pay/{uuid}` - Public payment page
- `POST /pay/{uuid}/payment-intent` - Create payment intent
- `POST /pay/{uuid}/confirm` - Confirm payment

## Running Tests

```bash
# Run all tests
php artisan test

# Run only feature tests
php artisan test --testsuite=Feature

# Run only unit tests
php artisan test --testsuite=Unit

# Run browser tests
php artisan test --testsuite=Browser
```

## Development Commands

```bash
# Format code with Laravel Pint
vendor/bin/pint

# Run development server with hot reloading
npm run dev

# Build assets for production
npm run build
```