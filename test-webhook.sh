#!/bin/bash

echo "=== Testing Stripe Webhook Processing ==="
echo ""

# Test 1: Payment Intent Succeeded
echo "Test 1: Triggering payment_intent.succeeded..."
stripe trigger payment_intent.succeeded

echo ""
echo "Waiting 5 seconds..."
sleep 5

# Test 2: Payment Intent Failed
echo ""
echo "Test 2: Triggering payment_intent.payment_failed..."
stripe trigger payment_intent.payment_failed

echo ""
echo "Waiting 5 seconds..."
sleep 5

# Test 3: Checkout Session Completed
echo ""
echo "Test 3: Triggering checkout.session.completed..."
stripe trigger checkout.session.completed

echo ""
echo "=== All test webhooks triggered ==="
echo "Check your queue worker terminal and application logs for processing details"
