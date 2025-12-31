<?php

namespace App\Providers;

use App\Models\PaymentCollection;
use App\Models\PaymentItem;
use App\Observers\PaymentItemObserver;
use App\Policies\PaymentCollectionPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        PaymentCollection::class => PaymentCollectionPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PaymentItem::observe(PaymentItemObserver::class);
    }
}
