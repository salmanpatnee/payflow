<?php

use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use App\Models\User;

describe('Payment Links - Admin Generation', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create();
        $this->collection = PaymentCollection::factory()
            ->for($this->admin, 'adminUser')
            ->create();
        PaymentItem::factory(3)->for($this->collection)->create();
    });

    test('admin can generate a payment link', function () {
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/payment-collections/{$this->collection->id}/generate-link");

        $response->assertSuccessful();
        $response->assertJsonStructure(['token', 'url', 'expires_at']);

        $this->collection->refresh();
        expect($this->collection->payment_link_token)->not->toBeNull();
        expect($this->collection->payment_link_expires_at)->not->toBeNull();
    });

    test('admin can retrieve payment link info', function () {
        $this->collection->generatePaymentLink();

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/payment-collections/{$this->collection->id}/payment-link");

        $response->assertSuccessful();
        $response->assertJsonStructure(['token', 'url', 'expires_at', 'is_active']);
    });

    test('admin can revoke a payment link', function () {
        $this->collection->generatePaymentLink();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/admin/payment-collections/{$this->collection->id}/payment-link");

        $response->assertSuccessful();

        $this->collection->refresh();
        expect($this->collection->payment_link_token)->toBeNull();
        expect($this->collection->payment_link_expires_at)->toBeNull();
    });
});

describe('Payment Links - Client Access', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create();
        $this->collection = PaymentCollection::factory()
            ->for($this->admin, 'adminUser')
            ->create();
        PaymentItem::factory(3)->for($this->collection)->create();
    });

    test('client can access payment details via valid link', function () {
        $this->collection->generatePaymentLink();
        $token = $this->collection->payment_link_token;

        $response = $this->get("/pay/{$token}");

        $response->assertSuccessful();
    });

    test('client cannot access payment with invalid token', function () {
        $response = $this->get('/pay/invalid-token');

        $response->assertNotFound();
    });

    test('client cannot access expired payment link', function () {
        $this->collection->update([
            'payment_link_token' => 'test-token',
            'payment_link_expires_at' => now()->subDay(),
        ]);

        $response = $this->get('/pay/test-token');

        $response->assertNotFound();
    });

    test('access to payment link creates access record', function () {
        $this->collection->generatePaymentLink();
        $token = $this->collection->payment_link_token;

        $this->get("/pay/{$token}");

        $this->assertDatabaseHas('client_access_records', [
            'payment_collection_id' => $this->collection->id,
            'access_token' => $token,
        ]);
    });
});

describe('Payment Links - Link Status', function () {
    test('link is active when token exists and not expired', function () {
        $collection = PaymentCollection::factory()->create();
        $collection->generatePaymentLink();

        expect($collection->isPaymentLinkActive())->toBeTrue();
    });

    test('link is inactive when token is null', function () {
        $collection = PaymentCollection::factory()->create();

        expect($collection->isPaymentLinkActive())->toBeFalse();
    });

    test('link is inactive when expired', function () {
        $collection = PaymentCollection::factory()->create([
            'payment_link_token' => 'token',
            'payment_link_expires_at' => now()->subDay(),
        ]);

        expect($collection->isPaymentLinkActive())->toBeFalse();
    });
});
