<?php

use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use Illuminate\Support\Str;

beforeEach(function () {
    // Mock Stripe client to avoid real API calls
    $this->stripeClient = Mockery::mock(\Stripe\StripeClient::class);
    $this->app->instance(\Stripe\StripeClient::class, $this->stripeClient);
});

it('auto-resets stale processing payments when viewing payment page', function () {
    // Create a payment collection with a stuck processing payment
    $collection = PaymentCollection::factory()->create([
        'uuid' => Str::uuid(),
        'status' => 'active',
    ]);

    $paymentItem = PaymentItem::factory()->create([
        'payment_collection_id' => $collection->id,
        'status' => 'processing',
        'stripe_payment_intent_id' => 'pi_test_declined',
        'amount' => 100.00,
        'currency' => 'usd',
    ]);

    // Mock Stripe PaymentIntent retrieve - simulate declined payment
    $mockPaymentIntent = new \stdClass();
    $mockPaymentIntent->id = 'pi_test_declined';
    $mockPaymentIntent->status = 'requires_payment_method'; // Declined status

    $this->stripeClient->paymentIntents = Mockery::mock();
    $this->stripeClient->paymentIntents
        ->shouldReceive('retrieve')
        ->with('pi_test_declined')
        ->once()
        ->andReturn($mockPaymentIntent);

    // Visit the payment page
    $response = $this->get(route('payment.show', $collection->uuid));

    $response->assertSuccessful();

    // Verify the payment item status was reset to 'failed'
    expect($paymentItem->fresh()->status)->toBe('failed');
});

it('allows retry after declined payment without blocking message', function () {
    // Create a payment collection with a declined payment
    $collection = PaymentCollection::factory()->create([
        'uuid' => Str::uuid(),
        'status' => 'active',
    ]);

    $paymentItem = PaymentItem::factory()->create([
        'payment_collection_id' => $collection->id,
        'status' => 'processing', // Stuck in processing from declined attempt
        'stripe_payment_intent_id' => 'pi_test_declined',
        'amount' => 100.00,
        'currency' => 'usd',
    ]);

    // Mock Stripe PaymentIntent retrieve - simulate declined payment
    $mockPaymentIntent = new \stdClass();
    $mockPaymentIntent->id = 'pi_test_declined';
    $mockPaymentIntent->status = 'requires_payment_method';

    $this->stripeClient->paymentIntents = Mockery::mock();
    $this->stripeClient->paymentIntents
        ->shouldReceive('retrieve')
        ->with('pi_test_declined')
        ->once()
        ->andReturn($mockPaymentIntent);

    // Attempt to create a new payment intent (retry)
    $response = $this->postJson(route('payment.create-intent', $collection->uuid), [
        'payment_item_id' => $paymentItem->id,
    ]);

    // Should NOT get "Payment is already being processed" error
    $response->assertStatus(200);
    expect($response->json('error'))->not->toBe('Payment is already being processed. Please wait.');
});

it('keeps truly processing payments in processing state', function () {
    // Create a payment collection with an actually processing payment
    $collection = PaymentCollection::factory()->create([
        'uuid' => Str::uuid(),
        'status' => 'active',
    ]);

    $paymentItem = PaymentItem::factory()->create([
        'payment_collection_id' => $collection->id,
        'status' => 'processing',
        'stripe_payment_intent_id' => 'pi_test_processing',
        'amount' => 100.00,
        'currency' => 'usd',
        'updated_at' => now(), // Recently updated
    ]);

    // Mock Stripe PaymentIntent retrieve - simulate still processing
    $mockPaymentIntent = new \stdClass();
    $mockPaymentIntent->id = 'pi_test_processing';
    $mockPaymentIntent->status = 'processing'; // Actually still processing

    $this->stripeClient->paymentIntents = Mockery::mock();
    $this->stripeClient->paymentIntents
        ->shouldReceive('retrieve')
        ->with('pi_test_processing')
        ->once()
        ->andReturn($mockPaymentIntent);

    // Visit the payment page
    $this->get(route('payment.show', $collection->uuid));

    // Verify the payment item status remains 'processing'
    expect($paymentItem->fresh()->status)->toBe('processing');
});

it('auto-completes succeeded payments that were not updated', function () {
    // Create a payment collection with a succeeded but not updated payment
    $collection = PaymentCollection::factory()->create([
        'uuid' => Str::uuid(),
        'status' => 'active',
    ]);

    $paymentItem = PaymentItem::factory()->create([
        'payment_collection_id' => $collection->id,
        'status' => 'processing',
        'stripe_payment_intent_id' => 'pi_test_succeeded',
        'amount' => 100.00,
        'currency' => 'usd',
        'paid_at' => null,
    ]);

    // Mock Stripe PaymentIntent retrieve - simulate succeeded payment
    $mockPaymentIntent = new \stdClass();
    $mockPaymentIntent->id = 'pi_test_succeeded';
    $mockPaymentIntent->status = 'succeeded';

    $this->stripeClient->paymentIntents = Mockery::mock();
    $this->stripeClient->paymentIntents
        ->shouldReceive('retrieve')
        ->with('pi_test_succeeded')
        ->once()
        ->andReturn($mockPaymentIntent);

    // Visit the payment page
    $this->get(route('payment.show', $collection->uuid));

    // Verify the payment item was updated to completed
    $freshItem = $paymentItem->fresh();
    expect($freshItem->status)->toBe('completed');
    expect($freshItem->paid_at)->not->toBeNull();
});

it('resets payment without stripe payment intent to pending', function () {
    // Create a payment collection with a processing payment without PaymentIntent ID
    $collection = PaymentCollection::factory()->create([
        'uuid' => Str::uuid(),
        'status' => 'active',
    ]);

    $paymentItem = PaymentItem::factory()->create([
        'payment_collection_id' => $collection->id,
        'status' => 'processing',
        'stripe_payment_intent_id' => null, // No PaymentIntent ID
        'amount' => 100.00,
        'currency' => 'usd',
    ]);

    // Visit the payment page
    $this->get(route('payment.show', $collection->uuid));

    // Verify the payment item status was reset to 'pending'
    expect($paymentItem->fresh()->status)->toBe('pending');
});
