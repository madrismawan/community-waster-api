<?php

namespace App\Providers;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Contract\Repositories\PaymentRepositoryInterface;
use App\Contract\Repositories\WasteRepositoryInterface;
use App\Repositories\HouseholdRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\WasteRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HouseholdRepositoryInterface::class, HouseholdRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(WasteRepositoryInterface::class, WasteRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
