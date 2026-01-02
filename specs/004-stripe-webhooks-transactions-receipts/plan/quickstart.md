# Quickstart Guide: Stripe Webhooks, Transactions & Receipts

## Setup Instructions

### 1. Environment Configuration
Add the following to your `.env` file:
```
STRIPE_WEBHOOK_SECRET=whsec_xxx
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
PDF_GENERATION_ENABLED=true
```

### 2. Database Migrations
Run the following commands to set up the required database tables:
```bash
php artisan migrate
```

This will create:
- `payment_transactions` table
- `payment_receipts` table

### 3. Queue Setup
Configure and start the queue worker:
```bash
php artisan queue:work --queue=webhooks,receipts
```

### 4. Webhook Configuration
In your Stripe Dashboard, configure the webhook endpoint:
- Endpoint URL: `https://yourdomain.com/webhooks/stripe`
- Events to listen for: `payment_intent.succeeded`, `payment_intent.payment_failed`, `checkout.session.completed`

## Key Components

### Webhook Processing
- Endpoint: `POST /webhooks/stripe`
- Handles signature verification automatically
- Processes webhook events and updates payment status
- Prevents duplicate processing using event IDs

### Transaction Storage
- Stores complete webhook payloads for audit
- Tracks processing status and attempts
- Maintains links to payment items

### Receipt Generation
- Automatically generates receipts for successful payments
- Creates PDF receipts with transaction details
- Queues email delivery to customers

## Testing

### Unit Tests
```bash
php artisan test --filter="WebhookTest"
php artisan test --filter="ReceiptTest"
```

### Manual Testing
Use Stripe CLI to test webhooks:
```bash
stripe listen --forward-to localhost:8000/webhooks/stripe
stripe trigger payment_intent.succeeded
```

## Troubleshooting

### Webhook Issues
- Check that `STRIPE_WEBHOOK_SECRET` matches your Stripe Dashboard
- Verify your endpoint is publicly accessible
- Check logs for signature verification errors

### Receipt Issues
- Ensure PDF generation library is properly installed
- Verify email configuration
- Check queue worker is running