<?php

namespace App\Services;

use App\Events\PaymentCollectionStatusUpdated;
use App\Events\PaymentStatusUpdated;
use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentStatusSyncService
{
    public function updatePaymentItemStatus(PaymentItem $paymentItem, string $newStatus): void
    {
        $previousStatus = $paymentItem->status;

        if ($previousStatus === $newStatus) {
            return; // No change needed
        }

        // Update the payment item status
        $paymentItem->update(['status' => $newStatus]);

        // Invalidate cache
        $this->invalidatePaymentItemCache($paymentItem);

        // Broadcast event
        event(new PaymentStatusUpdated($paymentItem, $previousStatus));

        // Sync payment collection status
        $this->syncPaymentCollectionStatus($paymentItem->paymentCollection);

        Log::info('Payment item status updated', [
            'payment_item_id' => $paymentItem->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);
    }

    public function syncPaymentCollectionStatus(PaymentCollection $paymentCollection): void
    {
        $paymentItems = $paymentCollection->paymentItems;

        if ($paymentItems->isEmpty()) {
            return;
        }

        $previousStatus = $paymentCollection->status;
        $newStatus = $this->calculateCollectionStatus($paymentItems);

        if ($previousStatus === $newStatus) {
            return; // No change needed
        }

        // Update collection status
        $paymentCollection->update(['status' => $newStatus]);

        // Invalidate cache
        $this->invalidatePaymentCollectionCache($paymentCollection);

        // Broadcast event
        event(new PaymentCollectionStatusUpdated($paymentCollection, $previousStatus));

        Log::info('Payment collection status updated', [
            'payment_collection_id' => $paymentCollection->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);
    }

    protected function calculateCollectionStatus($paymentItems): string
    {
        $totalItems = $paymentItems->count();
        $completedItems = $paymentItems->where('status', 'completed')->count();
        $failedItems = $paymentItems->where('status', 'failed')->count();

        // All items completed
        if ($completedItems === $totalItems) {
            return 'completed';
        }

        // All items failed
        if ($failedItems === $totalItems) {
            return 'failed';
        }

        // Some items completed
        if ($completedItems > 0) {
            return 'partially_paid';
        }

        // Some items failed but not all
        if ($failedItems > 0) {
            return 'partial_failure';
        }

        // Default to pending
        return 'pending';
    }

    public function invalidatePaymentItemCache(PaymentItem $paymentItem): void
    {
        $cacheKeys = [
            "payment_item.{$paymentItem->id}",
            "payment_item.{$paymentItem->id}.status",
            "payment_collection.{$paymentItem->payment_collection_id}.items",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::debug('Payment item cache invalidated', [
            'payment_item_id' => $paymentItem->id,
            'cache_keys' => $cacheKeys,
        ]);
    }

    public function invalidatePaymentCollectionCache(PaymentCollection $paymentCollection): void
    {
        $cacheKeys = [
            "payment_collection.{$paymentCollection->id}",
            "payment_collection.{$paymentCollection->id}.status",
            "payment_collection.{$paymentCollection->id}.items",
            "payment_collection.{$paymentCollection->uuid}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::debug('Payment collection cache invalidated', [
            'payment_collection_id' => $paymentCollection->id,
            'cache_keys' => $cacheKeys,
        ]);
    }

    public function getPaymentCollectionProgress(PaymentCollection $paymentCollection): array
    {
        return Cache::remember(
            "payment_collection.{$paymentCollection->id}.progress",
            now()->addMinutes(5),
            function () use ($paymentCollection) {
                $paymentItems = $paymentCollection->paymentItems;

                $totalItems = $paymentItems->count();
                $completedItems = $paymentItems->where('status', 'completed')->count();
                $pendingItems = $paymentItems->where('status', 'pending')->count();
                $failedItems = $paymentItems->where('status', 'failed')->count();

                $totalAmount = $paymentItems->sum('amount');
                $paidAmount = $paymentItems->where('status', 'completed')->sum('amount');

                return [
                    'total_items' => $totalItems,
                    'completed_items' => $completedItems,
                    'pending_items' => $pendingItems,
                    'failed_items' => $failedItems,
                    'completion_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $totalAmount - $paidAmount,
                    'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                ];
            }
        );
    }
}
