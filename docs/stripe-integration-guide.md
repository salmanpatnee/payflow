# Stripe Integration Guide for Payment Link Application

## What You Need for Stripe Integration

### 1. Stripe Account Setup

#### Create a Stripe Account
- Go to [stripe.com](https://stripe.com)
- Sign up for a free account
- Complete email verification
- Provide basic business information

#### Get Your API Keys
1. Log into Stripe Dashboard
2. Navigate to **Developers** → **API Keys**
3. You'll see two keys:
   - **Publishable Key** (starts with `pk_test_` or `pk_live_`)
   - **Secret Key** (starts with `sk_test_` or `sk_live_`)

**⚠️ IMPORTANT**:
- Keep Secret Key private (never commit to Git)
- Publishable Key is safe to expose in frontend code
- Test keys end with `_test`, Live keys end with nothing

#### Enable Required Features
1. **Payment Methods**: Ensure "Card" is enabled
2. **Webhooks**: Set up webhook endpoints (for production)
3. **Restricted API Keys** (optional): For enhanced security

---

## 2. Environment Variables (.env)

Add these to your `.env` file:

```env
# Stripe Keys (Test Mode)
STRIPE_PUBLIC_KEY=pk_test_YOUR_PUBLISHABLE_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_SECRET_KEY_HERE

# Stripe Settings
STRIPE_CURRENCY=usd
STRIPE_WEBHOOK_SECRET=whsec_test_YOUR_WEBHOOK_SECRET_HERE

# Optional: For production
# STRIPE_PUBLIC_KEY=pk_live_YOUR_LIVE_PUBLISHABLE_KEY
# STRIPE_SECRET_KEY=sk_live_YOUR_LIVE_SECRET_KEY
```

#### How to Get Webhook Secret
1. Go to **Developers** → **Webhooks**
2. Click **Add Endpoint**
3. Enter your webhook URL: `https://yourapp.com/stripe/webhook`
4. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
5. Copy the signing secret when created

---

## 3. Backend Dependencies

### Install Stripe PHP SDK
```bash
composer require stripe/stripe-php
```

### Install Webhook Verification Package (Optional but Recommended)
The Stripe PHP SDK includes webhook verification, so you don't need additional packages.

---

## 4. Backend Configuration

### Create `config/stripe.php`

```php
<?php

return [
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'currency' => env('STRIPE_CURRENCY', 'usd'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
```

### Initialize Stripe in Service Provider

Create `app/Providers/StripeServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class StripeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ... other providers
    App\Providers\StripeServiceProvider::class,
],
```

---

## 5. Frontend Dependencies

### Install Stripe.js
```bash
npm install @stripe/stripe-js
```

### Create Stripe Initialization File

Create `resources/js/lib/stripe.ts`:

```typescript
import { loadStripe, type Stripe } from '@stripe/stripe-js'

let stripeInstance: Stripe | null = null

export async function getStripe(): Promise<Stripe | null> {
  if (!stripeInstance) {
    stripeInstance = await loadStripe(
      import.meta.env.VITE_STRIPE_PUBLIC_KEY as string
    )
  }
  return stripeInstance
}
```

### Add Public Key to `.env`
```env
VITE_STRIPE_PUBLIC_KEY=pk_test_YOUR_PUBLISHABLE_KEY_HERE
```

---

## 6. Core Backend Classes

### Create StripePaymentService

Create `app/Services/StripePaymentService.php`:

```php
<?php

namespace App\Services;

use App\Models\PaymentItem;
use App\Models\PaymentTransaction;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    /**
     * Create a Stripe PaymentIntent for a payment item
     */
    public function createPaymentIntent(PaymentItem $item): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => (int) ($item->amount * 100), // Convert to cents
            'currency' => config('stripe.currency'),
            'metadata' => [
                'payment_item_id' => $item->id,
                'payment_collection_id' => $item->paymentCollection->id,
                'payment_collection_uuid' => $item->paymentCollection->uuid,
            ],
            'idempotency_key' => "payment_item_{$item->id}_" . time(),
        ]);
    }

    /**
     * Confirm payment and create transaction record
     */
    public function confirmPayment(string $paymentIntentId): bool
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            return false;
        }

        $itemId = $intent->metadata['payment_item_id'] ?? null;
        if (!$itemId) {
            return false;
        }

        $item = PaymentItem::findOrFail($itemId);

        // Create transaction record
        PaymentTransaction::create([
            'payment_item_id' => $item->id,
            'stripe_payment_intent_id' => $intent->id,
            'stripe_charge_id' => $intent->charges->data[0]->id ?? null,
            'amount' => $item->amount,
            'currency' => config('stripe.currency'),
            'status' => $intent->status,
            'metadata' => $intent->toArray(),
        ]);

        // Update payment item
        $item->update([
            'status' => 'completed',
            'stripe_payment_intent_id' => $intent->id,
            'stripe_charge_id' => $intent->charges->data[0]->id ?? null,
            'paid_at' => now(),
        ]);

        return true;
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['type'];
        $data = $payload['data']['object'];

        match ($event) {
            'payment_intent.succeeded' => $this->handlePaymentSuccess($data),
            'payment_intent.payment_failed' => $this->handlePaymentFailure($data),
            default => null,
        };
    }

    private function handlePaymentSuccess($data): void
    {
        $this->confirmPayment($data['id']);
    }

    private function handlePaymentFailure($data): void
    {
        $itemId = $data['metadata']['payment_item_id'] ?? null;
        if (!$itemId) {
            return;
        }

        PaymentItem::findOrFail($itemId)->update([
            'status' => 'failed',
            'stripe_payment_intent_id' => $data['id'],
        ]);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $chargeId): void
    {
        \Stripe\Refund::create(['charge' => $chargeId]);
    }

    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }
}
```

### Create ClientPaymentController

Create `app/Http/Controllers/Payment/ClientPaymentController.php`:

```php
<?php

namespace App\Http\Controllers\Payment;

use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientPaymentController
{
    public function __construct(private StripePaymentService $stripeService) {}

    /**
     * Display payment collection for client
     */
    public function show(string $uuid)
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->whereIn('status', ['active', 'processing'])
            ->firstOrFail();

        // Check if expired
        if ($collection->expires_at && $collection->expires_at->isPast()) {
            $collection->update(['status' => 'expired']);
            return Inertia::render('Payment/Expired');
        }

        return Inertia::render('Payment/Show', [
            'collection' => $collection->load('items'),
            'stripePublicKey' => config('stripe.public_key'),
        ]);
    }

    /**
     * Create PaymentIntent for specific item
     */
    public function createPaymentIntent(Request $request, string $uuid)
    {
        $collection = PaymentCollection::where('uuid', $uuid)->firstOrFail();
        $item = $collection->items()->findOrFail($request->input('item_id'));

        if ($item->status !== 'pending') {
            return response()->json(['error' => 'Item already paid or failed'], 422);
        }

        try {
            $intent = $this->stripeService->createPaymentIntent($item);

            // Update item status
            $item->update([
                'status' => 'processing',
                'stripe_payment_intent_id' => $intent->id,
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'paymentIntentId' => $intent->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Confirm payment completion
     */
    public function confirmPayment(Request $request, string $uuid)
    {
        $paymentIntentId = $request->input('payment_intent_id');

        try {
            $this->stripeService->confirmPayment($paymentIntentId);

            $collection = PaymentCollection::where('uuid', $uuid)->firstOrFail();
            $completedCount = $collection->items()
                ->where('status', 'completed')
                ->count();

            $allCompleted = $completedCount === $collection->items()->count();

            if ($allCompleted) {
                $collection->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'allCompleted' => $allCompleted,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get current collection status
     */
    public function checkStatus(string $uuid)
    {
        $collection = PaymentCollection::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'status' => $collection->status,
            'items' => $collection->items()->get(['id', 'status', 'amount', 'paid_at']),
            'completedCount' => $collection->items()
                ->where('status', 'completed')
                ->count(),
            'totalItems' => $collection->items()->count(),
        ]);
    }

    /**
     * Thank you page
     */
    public function thankYou(string $uuid)
    {
        $collection = PaymentCollection::where('uuid', $uuid)
            ->where('status', 'completed')
            ->firstOrFail();

        return Inertia::render('Payment/ThankYou', [
            'collection' => $collection->load('items'),
        ]);
    }
}
```

### Create Webhook Controller

Create `app/Http/Controllers/Payment/WebhookController.php`:

```php
<?php

namespace App\Http\Controllers\Payment;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Stripe\Webhook;

class WebhookController
{
    public function __construct(private StripePaymentService $stripeService) {}

    public function handleStripeWebhook(Request $request)
    {
        $payload = @json_decode($request->getContent(), true);
        $sig = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $sig,
                config('stripe.webhook_secret')
            );

            $this->stripeService->handleWebhook($event->toArray());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

---

## 7. Routes Setup

Add to `routes/web.php`:

```php
// Public payment routes (no authentication)
Route::prefix('pay')->group(function () {
    Route::get('{uuid}', [\App\Http\Controllers\Payment\ClientPaymentController::class, 'show'])->name('payment.show');
    Route::post('{uuid}/payment-intent', [\App\Http\Controllers\Payment\ClientPaymentController::class, 'createPaymentIntent'])->name('payment.intent');
    Route::post('{uuid}/confirm', [\App\Http\Controllers\Payment\ClientPaymentController::class, 'confirmPayment'])->name('payment.confirm');
    Route::get('{uuid}/check-status', [\App\Http\Controllers\Payment\ClientPaymentController::class, 'checkStatus'])->name('payment.status');
    Route::get('{uuid}/thank-you', [\App\Http\Controllers\Payment\ClientPaymentController::class, 'thankYou'])->name('payment.thank-you');
});

// Stripe webhook (unguarded, Stripe verifies signature)
Route::post('stripe/webhook', [\App\Http\Controllers\Payment\WebhookController::class, 'handleStripeWebhook']);

// Admin routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::resource('payment-collections', \App\Http\Controllers\Admin\PaymentCollectionController::class);
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
});
```

---

## 8. Frontend Stripe Payment Form

Create `resources/js/components/Payment/StripePaymentForm.vue`:

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { loadStripe, type Stripe, type StripeCardElement } from '@stripe/stripe-js'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'

interface Props {
  open: boolean
  amount: number
  paymentIntentId?: string
  collectionUuid: string
}

interface Emits {
  (e: 'close'): void
  (e: 'success'): void
  (e: 'error', error: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const stripe = ref<Stripe | null>(null)
const elements = ref<any>(null)
const cardElement = ref<StripeCardElement | null>(null)
const loading = ref(false)
const error = ref('')

onMounted(async () => {
  stripe.value = await loadStripe(import.meta.env.VITE_STRIPE_PUBLIC_KEY)
  elements.value = stripe.value?.elements()
  cardElement.value = elements.value?.create('card')
  cardElement.value?.mount('#card-element')
})

async function handlePayment() {
  if (!stripe.value || !cardElement.value) return

  loading.value = true
  error.value = ''

  try {
    // Create PaymentIntent on backend
    const intentResponse = await fetch(`/pay/${props.collectionUuid}/payment-intent`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ item_id: props.amount }),
    })

    const intentData = await intentResponse.json()
    if (!intentData.clientSecret) throw new Error('Failed to create payment intent')

    // Confirm payment with Stripe
    const { paymentIntent, error: stripeError } = await stripe.value.confirmCardPayment(
      intentData.clientSecret,
      { payment_method: { card: cardElement.value, billing_details: {} } }
    )

    if (stripeError) {
      error.value = stripeError.message || 'Payment failed'
      return
    }

    if (paymentIntent?.status === 'succeeded') {
      // Confirm on backend
      await fetch(`/pay/${props.collectionUuid}/confirm`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ payment_intent_id: paymentIntent.id }),
      })

      emit('success')
      emit('close')
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'An error occurred'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(v) => !v && emit('close')">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Enter Payment Details</DialogTitle>
      </DialogHeader>

      <div class="space-y-4">
        <div id="card-element" class="p-3 border rounded"></div>

        <div v-if="error" class="text-red-600 text-sm">
          {{ error }}
        </div>

        <div class="text-sm text-gray-600">
          Amount: <span class="font-bold">${{ amount.toFixed(2) }}</span>
        </div>

        <Button
          @click="handlePayment"
          :disabled="loading"
          class="w-full"
        >
          {{ loading ? 'Processing...' : 'Pay Now' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
```

---

## 9. Database Setup

Run migrations to create tables:

```bash
php artisan migrate
```

Ensure these tables exist:
- `payment_collections` (with `uuid` field indexed)
- `payment_items` (with `stripe_payment_intent_id`, `stripe_charge_id`)
- `payment_transactions` (with `stripe_payment_intent_id`, `stripe_charge_id`, `metadata`)

---

## 10. Testing with Stripe

### Test Card Numbers
Use these in test mode:

| Card Type | Number | Expiry | CVC |
|-----------|--------|--------|-----|
| Visa | 4242 4242 4242 4242 | Any future date | Any 3 digits |
| Visa (declined) | 4000 0000 0000 0002 | Any future date | Any 3 digits |
| 3D Secure (success) | 4000 0025 0000 3155 | Any future date | Any 3 digits |

### Use Stripe CLI for Local Webhook Testing
```bash
# Install Stripe CLI
# https://stripe.com/docs/stripe-cli

# Start listening for events
stripe listen --forward-to localhost:8000/stripe/webhook

# This gives you a signing secret to add to .env
# STRIPE_WEBHOOK_SECRET=whsec_test_...
```

---

## 11. Security Best Practices

✅ **DO**:
- Keep `STRIPE_SECRET_KEY` in `.env` (never commit)
- Use webhook signing secret verification
- Validate payment amounts on backend
- Use idempotency keys to prevent duplicate charges
- Store full Stripe responses for audit trail
- Use HTTPS in production (required by Stripe)

❌ **DON'T**:
- Store card data (Stripe handles this)
- Send payment intent secrets to frontend directly
- Expose secret keys in frontend code
- Trust client-side amount validation alone

---

## 12. Checklist for Stripe Integration

- [ ] Create Stripe account and get API keys
- [ ] Add keys to `.env` file
- [ ] Install `stripe/stripe-php` package
- [ ] Install `@stripe/stripe-js` package
- [ ] Create `config/stripe.php`
- [ ] Create `app/Services/StripePaymentService.php`
- [ ] Create `app/Http/Controllers/Payment/ClientPaymentController.php`
- [ ] Create `app/Http/Controllers/Payment/WebhookController.php`
- [ ] Create `app/Providers/StripeServiceProvider.php`
- [ ] Add routes for payment endpoints
- [ ] Create `resources/js/lib/stripe.ts`
- [ ] Create `resources/js/components/Payment/StripePaymentForm.vue`
- [ ] Create database migrations
- [ ] Test with Stripe test cards
- [ ] Set up Stripe CLI for webhook testing
- [ ] Test full payment flow (pending → processing → completed)
- [ ] Verify transaction records are created
- [ ] Test failed payments and retries
- [ ] Configure webhooks in Stripe Dashboard (for production)
- [ ] Enable HTTPS and test in production

---

## 13. Common Issues & Solutions

### Issue: "Invalid API Key"
**Solution**: Ensure `STRIPE_SECRET_KEY` is correctly set in `.env` and restart your Laravel server.

### Issue: "Webhook signature verification failed"
**Solution**: Make sure `STRIPE_WEBHOOK_SECRET` matches the signing secret from Stripe Dashboard.

### Issue: "Card Element not rendering"
**Solution**: Ensure `@stripe/stripe-js` is installed and `VITE_STRIPE_PUBLIC_KEY` is set in `.env`.

### Issue: "PaymentIntent amount mismatch"
**Solution**: Always verify payment amount on backend matches database record (prevent client tampering).

### Issue: "CORS errors when calling Stripe"
**Solution**: This is normal - Stripe.js handles CORS. No backend config needed.

---

## Next Steps

1. Set up Stripe account and get API keys
2. Add all files from this guide to your project
3. Run database migrations
4. Test with Stripe test cards locally
5. Deploy to production with live keys
6. Configure webhooks in Stripe Dashboard
7. Monitor transactions in Stripe Dashboard

You're ready to accept payments!
