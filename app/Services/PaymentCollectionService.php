<?php

namespace App\Services;

use App\Models\PaymentCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentCollectionService
{
    /**
     * Get paginated list of payment collections.
     */
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentCollection::query()
            ->with('paymentItems')
            ->withCount('paymentItems')
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a single payment collection with items.
     */
    public function show(int $id): PaymentCollection
    {
        return PaymentCollection::with('paymentItems')->findOrFail($id);
    }

    /**
     * Create a new payment collection with items.
     */
    public function create(array $data, int $adminUserId): PaymentCollection
    {
        return DB::transaction(function () use ($data, $adminUserId) {
            // Create the collection
            $collection = PaymentCollection::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'admin_user_id' => $adminUserId,
            ]);

            // Create payment items
            if (! empty($data['items'])) {
                foreach ($data['items'] as $index => $itemData) {
                    $collection->paymentItems()->create([
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'type' => $itemData['type'],
                        'sort_order' => $index,
                    ]);
                }
            }

            return $collection->load('paymentItems');
        });
    }

    /**
     * Update a payment collection and its items.
     */
    public function update(int $id, array $data): PaymentCollection
    {
        return DB::transaction(function () use ($id, $data) {
            $collection = PaymentCollection::findOrFail($id);

            // Update collection details
            $collection->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Sync payment items
            $this->syncPaymentItems($collection, $data['items'] ?? []);

            return $collection->load('paymentItems');
        });
    }

    /**
     * Delete a payment collection.
     */
    public function delete(int $id): bool
    {
        $collection = PaymentCollection::findOrFail($id);

        return (bool) $collection->delete();
    }

    /**
     * Sync payment items for a collection.
     */
    protected function syncPaymentItems(PaymentCollection $collection, array $items): void
    {
        $existingItemIds = [];

        foreach ($items as $index => $itemData) {
            if (! empty($itemData['id'])) {
                // Update existing item
                $item = $collection->paymentItems()->findOrFail($itemData['id']);
                $item->update([
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'type' => $itemData['type'],
                    'sort_order' => $index,
                ]);
                $existingItemIds[] = $item->id;
            } else {
                // Create new item
                $newItem = $collection->paymentItems()->create([
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'] ?? 1,
                    'type' => $itemData['type'],
                    'sort_order' => $index,
                ]);
                $existingItemIds[] = $newItem->id;
            }
        }

        // Delete items that were not in the request
        $collection->paymentItems()
            ->whereNotIn('id', $existingItemIds)
            ->delete();
    }
}
