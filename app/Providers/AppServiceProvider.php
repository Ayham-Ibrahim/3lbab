<?php

namespace App\Providers;

use App\Models\Offer;
use App\Models\Store;
use App\Models\Complaint;
use App\Observers\OfferObserver;
use App\Observers\StoreObserver;
use App\Observers\ComplaintObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        Offer::observe(OfferObserver::class);
        Complaint::observe(ComplaintObserver::class);
        Store::observe(StoreObserver::class);
    }
}
