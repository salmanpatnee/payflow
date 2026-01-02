<?php

namespace App\Observers;

use App\Models\PaymentCollection;
use App\Models\PaymentItem;

class PaymentItemObserver
{
    /**
     * Handle the PaymentItem "updated" event.
     *
     * Check if all payment items in the collection are completed,
     * and if so, mark the collection as completed.
     */
    public function updated(PaymentItem $paymentItem): void
    {
        // Only proceed if the payment item status changed to 'completed'
        if ($paymentItem->wasChanged('status') && $paymentItem->status === 'completed') {
            $this->checkCollectionCompletion($paymentItem->paymentCollection);
        }
    }

    /**
     * Check if all payment items in a collection are completed
     * and update collection status accordingly
     */
    private function checkCollectionCompletion(PaymentCollection $collection): void
    {
        // Check if all payment items are completed
        $allCompleted = $collection->paymentItems()
            ->whereNotIn('status', ['completed'])
            ->doesntExist();

        if ($allCompleted && $collection->status !== 'completed') {
            $collection->update([
                'status' => 'completed',
            ]);
        }
    }
}
