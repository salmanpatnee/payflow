<?php

/**
 * Webhook Verification Script
 * Run with: php verify-webhooks.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Webhook Processing Verification ===\n\n";

// Check payment transactions
echo "1. Recent Payment Transactions:\n";
echo str_repeat('-', 80)."\n";

$transactions = \App\Models\PaymentTransaction::orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($transactions as $transaction) {
    echo "ID: {$transaction->id}\n";
    echo "  Event Type: {$transaction->stripe_event_type}\n";
    echo "  Status: {$transaction->processing_status}\n";
    echo "  Attempts: {$transaction->processing_attempts}\n";
    echo "  Payment Item: {$transaction->payment_item_id}\n";
    echo '  Processed: '.($transaction->processed_at ? $transaction->processed_at->format('Y-m-d H:i:s') : 'No')."\n";
    if ($transaction->processing_error) {
        echo "  Error: {$transaction->processing_error}\n";
    }
    echo "\n";
}

// Check payment items status
echo "\n2. Payment Items Status:\n";
echo str_repeat('-', 80)."\n";

$items = \App\Models\PaymentItem::whereIn('id', [6, 7])->get();

foreach ($items as $item) {
    echo "Payment Item #{$item->id} - {$item->name}\n";
    echo "  Status: {$item->status}\n";
    echo '  Amount: $'.number_format($item->amount / 100, 2)."\n";
    echo '  Paid At: '.($item->paid_at ? $item->paid_at->format('Y-m-d H:i:s') : 'Not paid')."\n";
    echo "\n";
}

// Check receipts
echo "\n3. Generated Receipts:\n";
echo str_repeat('-', 80)."\n";

$receipts = \App\Models\PaymentReceipt::with('transaction')->get();

if ($receipts->isEmpty()) {
    echo "No receipts generated yet.\n";
} else {
    foreach ($receipts as $receipt) {
        echo "Receipt: {$receipt->receipt_number}\n";
        echo "  Transaction ID: {$receipt->payment_transaction_id}\n";
        echo "  Delivery Status: {$receipt->delivery_status}\n";
        echo '  Delivered At: '.($receipt->delivered_at ? $receipt->delivered_at->format('Y-m-d H:i:s') : 'Not delivered')."\n";
        echo "  Attempts: {$receipt->delivery_attempts}\n";
        if ($receipt->delivery_error) {
            echo "  Error: {$receipt->delivery_error}\n";
        }
        echo "\n";
    }
}

// Check payment collection status
echo "\n4. Payment Collection Status:\n";
echo str_repeat('-', 80)."\n";

$collection = \App\Models\PaymentCollection::find(8);
if ($collection) {
    echo "Collection: {$collection->name}\n";
    echo "  Status: {$collection->status}\n";
    echo "  Total Items: {$collection->paymentItems->count()}\n";
    echo "  Completed: {$collection->paymentItems->where('status', 'completed')->count()}\n";
    echo "  Failed: {$collection->paymentItems->where('status', 'failed')->count()}\n";
    echo "  Pending: {$collection->paymentItems->where('status', 'pending')->count()}\n";
}

// Check webhook monitoring stats
echo "\n5. Webhook Monitoring Stats (Last 24 Hours):\n";
echo str_repeat('-', 80)."\n";

$monitoring = app(\App\Services\WebhookMonitoringService::class);
$stats = $monitoring->getRecentWebhookActivity(24);

echo "Total Webhooks: {$stats['total_webhooks']}\n";
echo "Successful: {$stats['successful']}\n";
echo "Failed: {$stats['failed']}\n";
echo "Pending: {$stats['pending']}\n";
echo "Success Rate: {$stats['success_rate']}%\n";
echo "Average Attempts: {$stats['average_attempts']}\n";

echo "\n".str_repeat('=', 80)."\n";
