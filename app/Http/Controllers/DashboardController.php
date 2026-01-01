<?php

namespace App\Http\Controllers;

use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        // Get collection statistics
        $totalCollections = PaymentCollection::where('admin_user_id', $user->id)->count();

        $collectionsByStatus = PaymentCollection::where('admin_user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get payment statistics
        $totalRevenue = PaymentItem::whereHas('paymentCollection', function ($query) use ($user) {
            $query->where('admin_user_id', $user->id);
        })
            ->where('status', 'completed')
            ->sum(DB::raw('price * quantity'));

        $totalPendingAmount = PaymentItem::whereHas('paymentCollection', function ($query) use ($user) {
            $query->where('admin_user_id', $user->id);
        })
            ->whereIn('status', ['pending', 'processing'])
            ->sum(DB::raw('price * quantity'));

        $completedPayments = PaymentItem::whereHas('paymentCollection', function ($query) use ($user) {
            $query->where('admin_user_id', $user->id);
        })
            ->where('status', 'completed')
            ->count();

        // Get recent collections
        $recentCollections = PaymentCollection::where('admin_user_id', $user->id)
            ->with('paymentItems')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'status' => $collection->status,
                    'items_count' => $collection->paymentItems->count(),
                    'total_amount' => $collection->paymentItems->sum(function ($item) {
                        return $item->price * $item->quantity;
                    }),
                    'created_at' => $collection->created_at->toISOString(),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_collections' => $totalCollections,
                'total_revenue' => $totalRevenue,
                'total_pending' => $totalPendingAmount,
                'completed_payments' => $completedPayments,
                'collections_by_status' => [
                    'active' => $collectionsByStatus['active'] ?? 0,
                    'completed' => $collectionsByStatus['completed'] ?? 0,
                    'pending' => $collectionsByStatus['pending'] ?? 0,
                    'partially_paid' => $collectionsByStatus['partially_paid'] ?? 0,
                    'expired' => $collectionsByStatus['expired'] ?? 0,
                ],
            ],
            'recent_collections' => $recentCollections,
        ]);
    }
}
