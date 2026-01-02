# Payment Processing Feature - Implementation Guide

## Overview

The Payment Processing feature allows clients to make secure payments through Stripe using a shareable payment collection link. This implementation follows PCI DSS compliance by using Stripe Elements for secure card input and avoiding storage of sensitive payment data.

## Architecture

### Components

**Backend (Laravel):**
- `PaymentCollectionController`: Handles payment page display and API endpoints
- `PaymentWebhookController`: Processes Stripe webhook events
- `StripePaymentService`: Core Stripe integration service
- `PaymentItemObserver`: Auto-updates collection status
- `ProcessPaymentRequest`: Validates payment requests
- `SecurePaymentHeaders`: Adds security headers to payment pages

**Frontend (Vue 3):**
- `PaymentPage.vue`: Main payment page with progress tracking
- `StripePaymentForm.vue`: Stripe Elements integration
- `ThankYouPage.vue`: Success page after payment completion

### Database Schema

**payment_collections**
- `uuid`: Unique identifier for shareable links (indexed)
- `title`, `description`: Collection details
- `status`: active, completed, expired (indexed)
- `expires_at`: Optional expiration timestamp
- `admin_user_id`: Creator reference

**payment_items**
- `payment_collection_id`: Parent collection (indexed, foreign key)
- `description`: Item description
- `amount`, `currency`: Payment amount and currency code
- `status`: pending, processing, completed, failed (indexed)
- `stripe_payment_intent_id`: Stripe reference (unique)
- `paid_at`: Completion timestamp

**payment_transactions**
- `payment_item_id`: Parent item (indexed, foreign key)
- `stripe_response`: Complete Stripe API response (JSON)
- `status`, `amount`, `currency`: Transaction details
- `failure_code`, `failure_message`: Error details

## Payment Flow

### Happy Path (Successful Payment)

1. **Client accesses payment link**: `/payment/{uuid}`
2. **Page loads** with collection details and pending payment items
3. **Client enters card details** in Stripe Elements (PCI-compliant iframe)
4. **JavaScript submits payment**:
   - Calls `POST /payment/{uuid}/payment-intent`
   - Backend creates PaymentIntent via Stripe API
   - PaymentItem status → `processing`
   - Returns `client_secret` to frontend
5. **Stripe confirms payment** on client-side
6. **Frontend confirms with backend**:
   - Calls `POST /payment/{uuid}/confirm-payment`
   - Backend verifies PaymentIntent with Stripe
   - PaymentItem status → `completed`
   - Records transaction details
7. **Observer triggers**: Checks if all items completed
   - If yes: PaymentCollection status → `completed`
8. **Page reloads**, auto-redirects to `/payment/{uuid}/thank-you`

### Failed Payment Path

1-4. Same as happy path
5. **Stripe payment fails** (insufficient funds, card declined, etc.)
6. **Error displayed** to client with retry option
7. **PaymentItem status** → `failed`
8. **Client retries** with same or different payment method
   - New PaymentIntent created with retry metadata
   - Unique idempotency key prevents duplicate charges

### Webhook Processing (Async)

Stripe sends webhooks for payment status updates:

1. **Webhook received**: `POST /webhooks/stripe`
2. **Signature verified**: HMAC SHA-256 validation
3. **Event processed**:
   - `payment_intent.succeeded` → Update status to completed
   - `payment_intent.payment_failed` → Update status to failed
   - `payment_intent.processing` → Keep status as processing
4. **Transaction recorded** in database

## Security Features

### PCI DSS Compliance
- ✅ Card data never touches our servers
- ✅ Stripe Elements (iframe) handles all card input
- ✅ HTTPS enforced for all payment pages
- ✅ No card data stored in database

### Security Headers
Applied via `SecurePaymentHeaders` middleware:
- `X-Frame-Options: SAMEORIGIN` - Prevent clickjacking
- `X-Content-Type-Options: nosniff` - Prevent MIME sniffing
- `Referrer-Policy: strict-origin-when-cross-origin` - Control referrer leakage
- `Content-Security-Policy` - Restrict resource loading (allows Stripe.js)
- `Permissions-Policy` - Disable unnecessary browser features

### CSRF Protection
- All POST endpoints require valid CSRF token
- Webhook endpoint excluded (verified via signature)

### Rate Limiting
- Payment intent creation: 10 req/min per IP
- Payment confirmation: 10 req/min per IP
- Status polling: 30 req/min per IP

### Duplicate Prevention
- Idempotency keys for all Stripe API calls
- Backend checks prevent processing completed payments
- Stale processing payments (>15 min) can be retried

## Configuration

### Environment Variables

```env
# Stripe API Keys
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

### Stripe Webhook Setup

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://your-domain.com/webhooks/stripe`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.processing`
4. Copy signing secret to `STRIPE_WEBHOOK_SECRET`

## Testing

### Test Cards (Stripe)

**Successful Payment:**
- Card: `4242 4242 4242 4242`
- Any future expiry, any CVC, any ZIP

**Declined Payment:**
- Card: `4000 0000 0000 0002`
- Simulates card declined error

**Insufficient Funds:**
- Card: `4000 0000 0000 9995`
- Simulates insufficient funds

**Processing Timeout:**
- Card: `4000 0000 0000 0077`
- Requires manual capture

### Feature Tests

Run payment processing tests:
```bash
php artisan test --filter=PaymentCollectionController
```

### Browser Tests

Test complete flow with Pest browser testing:
```bash
php artisan test tests/Browser/CompletePaymentFlowTest.php
```

## API Endpoints

### `GET /payment/{uuid}`
Display payment collection page.

**Response:** Inertia page with collection data

### `POST /payment/{uuid}/payment-intent`
Create Stripe PaymentIntent for a payment item.

**Request:**
```json
{
  "payment_item_id": 123
}
```

**Response:**
```json
{
  "client_secret": "pi_xxx_secret_xxx",
  "payment_intent_id": "pi_xxxxxxxxxxxxxxxxxx"
}
```

### `POST /payment/{uuid}/confirm-payment`
Confirm payment after Stripe processing.

**Request:**
```json
{
  "payment_item_id": 123,
  "payment_intent_id": "pi_xxxxxxxxxxxxxxxxxx"
}
```

**Response:**
```json
{
  "success": true,
  "payment_item": {...}
}
```

### `GET /payment/{uuid}/status`
Get current payment collection status (for polling).

**Response:**
```json
{
  "uuid": "xxx-xxx-xxx",
  "status": "active",
  "items": [...],
  "summary": {
    "total_items": 3,
    "completed_items": 1,
    "pending_items": 2,
    "completion_percentage": 33.33
  }
}
```

### `POST /webhooks/stripe`
Stripe webhook endpoint (excluded from CSRF).

**Headers:**
```
Stripe-Signature: t=xxx,v1=xxx
```

## Performance Optimizations

### Database Indexes
- `payment_collections.uuid` - Fast lookup by shareable link
- `payment_collections.status` - Filter collections by status
- `payment_items.payment_collection_id` - Efficient joins
- `payment_items.status` - Filter items by status
- `payment_items.stripe_payment_intent_id` - Unique Stripe reference

### Query Optimization
- Eager loading: `->with('paymentItems')` prevents N+1 queries
- Minimal data transfer: API resources return only needed fields

### Real-Time Updates
- Client-side polling every 3 seconds when processing
- Auto-stops when no processing payments
- Uses Inertia partial reloads (preserves scroll position)

## Accessibility Features

All payment components follow WCAG 2.1 Level AA:

- ✅ Semantic HTML with proper roles
- ✅ ARIA labels and live regions for screen readers
- ✅ Keyboard navigation support
- ✅ Color contrast ratios meet standards
- ✅ Focus management during form submission
- ✅ Error announcements via aria-live
- ✅ Progress bar with aria-valuenow
- ✅ Status updates announced to assistive technologies

## Error Handling

### Client-Side Errors
- Network failures: "Payment failed. Please try again."
- Card validation: Stripe provides real-time feedback
- Processing errors: Displayed with retry button

### Server-Side Errors
- Invalid requests: 422 Unprocessable Entity
- Duplicate payments: 409 Conflict
- Stripe API failures: Logged with context, user-friendly message shown

### Logging
All payment operations logged with:
- Payment item ID
- Stripe PaymentIntent ID
- Error messages and codes
- Request timestamps

## Troubleshooting

### Payment stuck in "processing"
- **Cause**: Webhook not received or failed
- **Fix**: Check Stripe Dashboard → Events for delivery status
- **Workaround**: Manual status update via webhook retry

### "Payment already completed" error
- **Cause**: Duplicate submission or stale client state
- **Fix**: Reload page to get latest status

### CSP blocks Stripe.js
- **Cause**: Missing Stripe domains in Content-Security-Policy
- **Fix**: Ensure `SecurePaymentHeaders` middleware is applied

## Maintenance

### Monitoring
- Check Stripe Dashboard daily for failed payments
- Monitor webhook delivery success rate
- Review error logs for unusual patterns

### Database Cleanup
- PaymentTransaction records can grow large
- Consider archiving old transactions (>1 year)
- Soft-deleted collections can be permanently deleted after retention period

## Future Enhancements

Potential improvements not in current scope:
- Multiple currency support with automatic conversion
- Partial payments / payment plans
- Refund processing via admin panel
- Email receipts after successful payment
- Payment history for clients
- Saved payment methods for recurring payments
