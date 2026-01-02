# Payment Processing - Testing Guide

## Quick Start Testing

### 1. Environment Setup

Ensure your `.env` has Stripe test keys:

```env
STRIPE_KEY=pk_test_51...  # Your Stripe publishable test key
STRIPE_SECRET=sk_test_51...  # Your Stripe secret test key
STRIPE_WEBHOOK_SECRET=whsec_...  # Optional for webhook testing
```

### 2. Run Migrations

```bash
php artisan migrate:fresh --seed
```

### 3. Create Test Data

Use Tinker to create a test payment collection:

```bash
php artisan tinker
```

```php
// Create an admin user if you don't have one
$admin = \App\Models\User::factory()->create([
    'email' => 'admin@test.com',
    'password' => bcrypt('password')
]);

// Create a payment collection
$collection = \App\Models\PaymentCollection::create([
    'uuid' => \Illuminate\Support\Str::uuid(),
    'title' => 'Test Payment Collection',
    'description' => 'Testing Stripe integration',
    'status' => 'active',
    'admin_user_id' => $admin->id,
]);

// Create payment items
$item1 = \App\Models\PaymentItem::create([
    'payment_collection_id' => $collection->id,
    'name' => 'Service Fee',
    'description' => 'Professional service fee',
    'amount' => 50.00,
    'currency' => 'usd',
    'status' => 'pending',
    'price' => 50.00,
    'quantity' => 1,
    'type' => 'service',
]);

$item2 = \App\Models\PaymentItem::create([
    'payment_collection_id' => $collection->id,
    'name' => 'Consultation Fee',
    'description' => 'Initial consultation',
    'amount' => 75.00,
    'currency' => 'usd',
    'status' => 'pending',
    'price' => 75.00,
    'quantity' => 1,
    'type' => 'service',
]);

// Get the payment URL
echo "Payment URL: " . url("/payment/{$collection->uuid}") . "\n";
```

### 4. Access the Payment Page

Visit the URL from step 3, e.g.:
```
http://payflow.test/payment/9c7f8e2a-1234-5678-90ab-cdef12345678
```

## Stripe Test Cards

### Successful Payment
**Card Number:** `4242 4242 4242 4242`
- **Expiry:** Any future date (e.g., 12/25)
- **CVC:** Any 3 digits (e.g., 123)
- **ZIP:** Any 5 digits (e.g., 12345)
- **Expected Result:** Payment succeeds immediately

### Declined Card
**Card Number:** `4000 0000 0000 0002`
- **Expected Result:** Generic decline error

### Insufficient Funds
**Card Number:** `4000 0000 0000 9995`
- **Expected Result:** Insufficient funds error

### Card Declined (Fraudulent)
**Card Number:** `4100 0000 0000 0019`
- **Expected Result:** Card declined (fraudulent)

### Expired Card
**Card Number:** `4000 0000 0000 0069`
- **Expected Result:** Expired card error

### Processing Error
**Card Number:** `4000 0000 0000 0119`
- **Expected Result:** Processing error

### More Test Cards
See: https://stripe.com/docs/testing#cards

## Manual Testing Scenarios

### Scenario 1: Happy Path (Single Item)

**Steps:**
1. Create a payment collection with 1 item
2. Visit the payment URL
3. Enter test card: `4242 4242 4242 4242`
4. Fill in expiry, CVC, ZIP
5. Click "Complete Payment"

**Expected Results:**
- ✅ Loading spinner appears on button
- ✅ Status changes from "Pending" to "Processing"
- ✅ Status changes to "Paid" with green checkmark
- ✅ Page shows completion timestamp
- ✅ Auto-redirects to thank you page after ~1.5 seconds
- ✅ Thank you page shows bouncing checkmark animation
- ✅ Database: payment_item.status = 'completed'
- ✅ Database: payment_item.paid_at is set
- ✅ Database: payment_collection.status = 'completed'

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

Look for:
```
PaymentIntent created successfully
Payment confirmed successfully
duration_ms: [time in milliseconds]
```

### Scenario 2: Failed Payment with Retry

**Steps:**
1. Create a payment collection with 1 item
2. Visit the payment URL
3. Enter declined card: `4000 0000 0000 0002`
4. Fill in expiry, CVC, ZIP
5. Click "Complete Payment"
6. See error message
7. Enter successful card: `4242 4242 4242 4242`
8. Click "Retry Payment"

**Expected Results:**
- ✅ First attempt shows error message in red box
- ✅ Status badge changes to "Failed" (red)
- ✅ Retry button appears
- ✅ Second attempt succeeds
- ✅ Status changes to "Paid"
- ✅ Redirects to thank you page

**Database Checks:**
```php
// In tinker
$item = \App\Models\PaymentItem::find([item_id]);
$item->status; // Should be 'completed' after retry
$item->paymentTransactions; // Should have 2 records (failed + succeeded)
```

### Scenario 3: Multiple Items with Progress

**Steps:**
1. Create a payment collection with 3 items
2. Visit the payment URL
3. Pay first item with: `4242 4242 4242 4242`
4. Observe progress bar (1/3 = 33%)
5. Pay second item
6. Observe progress bar (2/3 = 67%)
7. Pay third item
8. Observe progress bar (3/3 = 100%)

**Expected Results:**
- ✅ Progress bar appears at top
- ✅ Progress bar updates after each payment
- ✅ Completed items show green "Paid" badge
- ✅ Pending items show amber "Pending Payment" badge
- ✅ Each item has its own Stripe payment form
- ✅ After final payment, auto-redirects to thank you page
- ✅ Thank you page shows itemized list of all payments

### Scenario 4: Real-Time Status Updates

**Steps:**
1. Create a payment collection with 1 item
2. Open payment page in browser
3. Open browser console
4. Start payment process
5. Watch network tab for polling requests

**Expected Results:**
- ✅ No polling when status is "pending"
- ✅ Polling starts every 3 seconds when status is "processing"
- ✅ GET requests to `/payment/{uuid}/status` visible in network tab
- ✅ Polling stops when payment completes
- ✅ Page data updates without full refresh

### Scenario 5: Duplicate Payment Prevention

**Steps:**
1. Create a payment collection with 1 item
2. Complete payment successfully
3. Try to refresh the page
4. Try to create another PaymentIntent via API

**Expected Results:**
- ✅ Completed payment shows "Paid" status
- ✅ No payment form displayed for completed items
- ✅ API returns 409 Conflict if trying to pay again
- ✅ Error message: "This payment has already been completed"

### Scenario 6: Expired Collection

**Steps:**
1. Create a payment collection with `expires_at` in the past:
```php
$collection = \App\Models\PaymentCollection::create([
    'uuid' => \Illuminate\Support\Str::uuid(),
    'title' => 'Expired Collection',
    'status' => 'active',
    'expires_at' => now()->subDay(),
    'admin_user_id' => $admin->id,
]);
```
2. Visit the payment URL

**Expected Results:**
- ✅ 403 Forbidden error
- ✅ Message: "This payment collection has expired."
- ✅ Database: collection.status = 'expired'

## Testing Webhooks

Stripe sends webhooks asynchronously after payment events. Test this:

### Option 1: Stripe CLI (Recommended)

**Install Stripe CLI:**
```bash
# macOS
brew install stripe/stripe-cli/stripe

# Windows
scoop bucket add stripe https://github.com/stripe/scoop-stripe-cli.git
scoop install stripe
```

**Forward webhooks to local:**
```bash
stripe login
stripe listen --forward-to http://payflow.test/webhooks/stripe
```

**Trigger test webhook:**
```bash
stripe trigger payment_intent.succeeded
stripe trigger payment_intent.payment_failed
```

**Expected Results:**
- ✅ Webhook received at `/webhooks/stripe`
- ✅ Signature verified
- ✅ Event processed
- ✅ Payment status updated in database
- ✅ Transaction record created

### Option 2: Manual Webhook Testing

**Send test webhook with curl:**
```bash
curl -X POST http://payflow.test/webhooks/stripe \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: t=test,v1=test" \
  -d '{
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_test123",
        "status": "succeeded",
        "metadata": {
          "payment_item_id": "1"
        }
      }
    }
  }'
```

**Note:** This won't work with signature verification enabled. Use Stripe CLI instead.

## Testing Rate Limiting

**Test payment endpoint rate limits:**

```bash
# Run this script to test rate limiting
for i in {1..15}; do
  curl -X POST http://payflow.test/payment/{uuid}/payment-intent \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: {token}" \
    -d '{"payment_item_id": 1}' &
done
wait
```

**Expected Results:**
- ✅ First 10 requests succeed
- ✅ Requests 11+ return 429 Too Many Requests
- ✅ Error message indicates rate limit exceeded

## Testing Security Headers

**Check security headers:**

```bash
curl -I http://payflow.test/payment/{uuid}
```

**Expected Headers:**
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com; ...
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(self)
```

## Testing Accessibility

**Keyboard Navigation:**
1. Visit payment page
2. Use Tab key to navigate
3. Use Enter/Space to activate buttons

**Expected Results:**
- ✅ All interactive elements are keyboard accessible
- ✅ Focus indicators visible
- ✅ Logical tab order
- ✅ Can complete payment using only keyboard

**Screen Reader Testing:**
1. Enable screen reader (NVDA, JAWS, VoiceOver)
2. Navigate payment page

**Expected Results:**
- ✅ Form labeled "Payment form"
- ✅ Progress bar announces current progress
- ✅ Status changes announced
- ✅ Errors announced with aria-live
- ✅ Button states announced (processing, etc.)

## Automated Testing

### Run Feature Tests

```bash
# Run all payment tests
php artisan test --filter=Payment

# Run specific test
php artisan test --filter=PaymentCollectionControllerTest

# Run with coverage
php artisan test --coverage
```

### Run Browser Tests (Pest v4)

```bash
# Run browser tests
php artisan test tests/Browser/

# Run specific browser test
php artisan test tests/Browser/CompletePaymentFlowTest.php
```

## Database Testing

**Verify database state after payment:**

```bash
php artisan tinker
```

```php
// Check payment item
$item = \App\Models\PaymentItem::with('paymentTransactions')->find(1);
$item->status; // Should be 'completed'
$item->stripe_payment_intent_id; // Should have Stripe ID
$item->paid_at; // Should have timestamp
$item->paymentTransactions->count(); // Should have at least 1

// Check collection
$collection = \App\Models\PaymentCollection::find(1);
$collection->status; // Should be 'completed' if all items paid

// Check transaction
$transaction = \App\Models\PaymentTransaction::latest()->first();
$transaction->status; // Should be 'succeeded'
$transaction->stripe_response; // Should have full Stripe response
```

## Performance Testing

**Monitor execution times in logs:**

```bash
# Watch for performance metrics
tail -f storage/logs/laravel.log | grep duration_ms
```

**Expected Performance:**
- PaymentIntent creation: < 500ms
- Payment confirmation: < 300ms
- Status check: < 100ms

**If times are high:**
- Check Stripe API latency
- Check database query performance
- Check network connection

## Common Issues & Solutions

### Issue: "Stripe is not defined"
**Cause:** Stripe.js not loaded
**Solution:** Check CSP headers allow https://js.stripe.com

### Issue: "Invalid client_secret"
**Cause:** PaymentIntent not created properly
**Solution:** Check backend logs for Stripe API errors

### Issue: Payment stuck in "processing"
**Cause:** Webhook not received
**Solution:**
1. Check Stripe Dashboard → Webhooks → Events
2. Manually retry webhook
3. Or use Stripe CLI to forward webhooks

### Issue: CSRF token mismatch
**Cause:** Session expired or CSRF token missing
**Solution:** Refresh page to get new CSRF token

### Issue: Rate limit exceeded
**Cause:** Too many requests from same IP
**Solution:** Wait 1 minute and try again

## Stripe Dashboard Verification

After testing, verify in Stripe Dashboard:

1. **Payments:** https://dashboard.stripe.com/test/payments
   - ✅ PaymentIntents should be listed
   - ✅ Status should match database
   - ✅ Metadata should have payment_item_id

2. **Customers:** https://dashboard.stripe.com/test/customers
   - Optional: If you implement customer creation

3. **Events:** https://dashboard.stripe.com/test/events
   - ✅ payment_intent.succeeded events
   - ✅ Webhook delivery status

4. **Logs:** https://dashboard.stripe.com/test/logs
   - ✅ API requests logged
   - ✅ No errors

## Checklist: Complete Test

Use this checklist to verify everything works:

- [ ] Can create payment collection with items
- [ ] Payment page loads correctly
- [ ] Progress bar shows for multiple items
- [ ] Stripe Elements loads in iframe
- [ ] Successful payment with test card works
- [ ] Status updates from pending → processing → completed
- [ ] Failed payment shows error message
- [ ] Retry payment works after failure
- [ ] Auto-redirects to thank you page
- [ ] Thank you page shows all completed items
- [ ] Database has correct status and timestamps
- [ ] Transaction records created
- [ ] Webhooks processed correctly
- [ ] Rate limiting works
- [ ] Security headers present
- [ ] Duplicate payment prevented
- [ ] Expired collection returns 403
- [ ] Keyboard navigation works
- [ ] Screen reader announces correctly
- [ ] Mobile responsive (test on phone)
- [ ] Dark mode works

## Next Steps

Once manual testing is complete:

1. **Write automated tests** (T049, T055)
2. **Set up staging environment** with real Stripe test mode
3. **Configure webhook endpoint** in Stripe Dashboard
4. **Test with real money** in small amounts (live mode)
5. **Monitor production** with logging and alerts

## Support Resources

- **Stripe Testing Docs:** https://stripe.com/docs/testing
- **Stripe CLI:** https://stripe.com/docs/stripe-cli
- **PayFlow Docs:** `specs/003-stripe-payment-processing/IMPLEMENTATION.md`
- **Troubleshooting:** See IMPLEMENTATION.md § Troubleshooting
