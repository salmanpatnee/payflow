# Quickstart: Payment Processing Implementation

## Setup

### 1. Environment Configuration
```bash
# Copy environment variables
cp .env.example .env

# Add Stripe keys to .env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 2. Database Migrations
```bash
# Run migrations to create payment tables
php artisan migrate
```

### 3. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install
```

## Key Implementation Steps

### 1. Create Payment Models
```bash
# Generate models with factories and migrations
php artisan make:model PaymentCollection -mfr
php artisan make:model PaymentItem -mfr
php artisan make:model PaymentTransaction -mfr
```

### 2. Implement Stripe Service
Create `app/Services/StripePaymentService.php` to handle all Stripe interactions:
- PaymentIntent creation
- Payment confirmation
- Webhook processing

### 3. Create Payment Controllers
```bash
# Generate controllers
php artisan make:controller Payment/PaymentCollectionController
php artisan make:controller Payment/PaymentItemController
php artisan make:controller Payment/PaymentWebhookController
```

### 4. Frontend Components
Create Vue components in `resources/js/Pages/Payment/`:
- `PaymentPage.vue` - Main payment page
- `ThankYouPage.vue` - Success page
- `StripePaymentForm.vue` - Payment form component

### 5. Routes
Add payment routes to `routes/web.php`:
- `/pay/{uuid}` - Public payment page
- `/pay/{uuid}/thank-you` - Thank you page
- Webhook endpoint

## Testing

### Unit Tests
```bash
# Test Stripe service
php artisan test --filter="StripePaymentServiceTest"

# Test models
php artisan test --filter="PaymentItemTest"
```

### Feature Tests
```bash
# Test payment processing
php artisan test --filter="ProcessPaymentTest"

# Test webhook handling
php artisan test --filter="PaymentWebhookTest"
```

### Browser Tests
```bash
# Test complete payment flow
php artisan test --filter="PaymentFlowTest"
```

## Configuration

### Stripe Webhooks
1. Go to Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/webhooks/stripe`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`

### Environment Variables
```env
# Stripe keys
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Optional: Stripe API version
STRIPE_API_VERSION=2023-10-16
```

## Deployment Notes

### Production Setup
1. Switch to production Stripe keys (sk_live_, pk_live_)
2. Ensure HTTPS is enabled (required by Stripe)
3. Configure webhook endpoints in Stripe Dashboard
4. Set up queue worker for processing webhooks

### Security
- Never expose STRIPE_SECRET_KEY in frontend
- Verify webhook signatures with STRIPE_WEBHOOK_SECRET
- Validate payment amounts match database records
- Use idempotency keys for all Stripe API calls