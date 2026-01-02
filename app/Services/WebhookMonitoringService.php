<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\WebhookProcessingFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WebhookMonitoringService
{
    protected int $successRateThreshold = 95;

    protected int $failureAlertThreshold = 3;

    public function recordWebhookAttempt(PaymentTransaction $transaction, bool $success, ?string $error = null): void
    {
        $cacheKey = 'webhook_stats.'.now()->format('Y-m-d-H');

        $stats = Cache::get($cacheKey, [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'last_updated' => now()->toIso8601String(),
        ]);

        $stats['total']++;

        if ($success) {
            $stats['successful']++;
        } else {
            $stats['failed']++;
        }

        $stats['last_updated'] = now()->toIso8601String();

        Cache::put($cacheKey, $stats, now()->addHours(25));

        // Check if we need to alert
        if (! $success && $transaction->processing_attempts >= $this->failureAlertThreshold) {
            $this->sendFailureAlert($transaction, $error ?? 'Unknown error');
        }

        // Check success rate
        $this->checkSuccessRate($stats);
    }

    public function sendFailureAlert(PaymentTransaction $transaction, string $errorMessage): void
    {
        $admins = $this->getAdminUsers();

        if ($admins->isEmpty()) {
            Log::warning('No admin users found to send webhook failure alert');

            return;
        }

        Notification::send(
            $admins,
            new WebhookProcessingFailed(
                $transaction,
                $errorMessage,
                $transaction->processing_attempts
            )
        );

        Log::info('Webhook failure alert sent', [
            'transaction_id' => $transaction->id,
            'stripe_event_id' => $transaction->stripe_event_id,
            'recipients' => $admins->count(),
        ]);
    }

    protected function checkSuccessRate(array $stats): void
    {
        if ($stats['total'] < 10) {
            return; // Not enough data
        }

        $successRate = ($stats['successful'] / $stats['total']) * 100;

        if ($successRate < $this->successRateThreshold) {
            Log::warning('Webhook success rate below threshold', [
                'success_rate' => round($successRate, 2),
                'threshold' => $this->successRateThreshold,
                'stats' => $stats,
            ]);

            // Could send an alert here for low success rate
        }
    }

    public function getWebhookStats(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        $hourlyStats = [];
        $totalStats = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        for ($hour = 0; $hour < 24; $hour++) {
            $cacheKey = 'webhook_stats.'.$date.'-'.str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
            $stats = Cache::get($cacheKey, ['total' => 0, 'successful' => 0, 'failed' => 0]);

            $hourlyStats[$hour] = $stats;
            $totalStats['total'] += $stats['total'];
            $totalStats['successful'] += $stats['successful'];
            $totalStats['failed'] += $stats['failed'];
        }

        $successRate = $totalStats['total'] > 0
            ? round(($totalStats['successful'] / $totalStats['total']) * 100, 2)
            : 0;

        return [
            'date' => $date,
            'hourly_stats' => $hourlyStats,
            'total_stats' => $totalStats,
            'success_rate' => $successRate,
            'failure_rate' => 100 - $successRate,
        ];
    }

    public function getFailedWebhooks(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentTransaction::where('processing_status', 'failed')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPendingWebhooks(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentTransaction::whereIn('processing_status', ['pending', 'processing'])
            ->where('processing_attempts', '>', 0)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function retryFailedWebhook(PaymentTransaction $transaction): void
    {
        if ($transaction->processing_status !== 'failed') {
            throw new \Exception('Can only retry failed webhooks');
        }

        // Reset the transaction for retry
        $transaction->update([
            'processing_status' => 'pending',
            'processing_error' => null,
        ]);

        Log::info('Webhook marked for retry', [
            'transaction_id' => $transaction->id,
            'stripe_event_id' => $transaction->stripe_event_id,
        ]);

        // Dispatch the job again
        \App\Jobs\Webhooks\ProcessStripeWebhookJob::dispatch(
            new \Stripe\Event($transaction->stripe_event_id, $transaction->payload)
        );
    }

    protected function getAdminUsers(): \Illuminate\Database\Eloquent\Collection
    {
        // Get users who should receive alerts
        // This could be based on roles, specific admin flag, or configuration
        return User::whereNotNull('email_verified_at')
            ->limit(10) // Limit to prevent spam
            ->get();
    }

    public function getRecentWebhookActivity(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $transactions = PaymentTransaction::where('created_at', '>=', $since)
            ->get();

        return [
            'period_hours' => $hours,
            'total_webhooks' => $transactions->count(),
            'successful' => $transactions->where('processing_status', 'completed')->count(),
            'failed' => $transactions->where('processing_status', 'failed')->count(),
            'pending' => $transactions->whereIn('processing_status', ['pending', 'processing'])->count(),
            'success_rate' => $transactions->count() > 0
                ? round(($transactions->where('processing_status', 'completed')->count() / $transactions->count()) * 100, 2)
                : 0,
            'average_attempts' => round($transactions->avg('processing_attempts'), 2),
        ];
    }
}
