<?php

namespace App\Providers;

use App\Contract\Repositories\HouseholdRepositoryInterface;
use App\Repositories\HouseholdRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HouseholdRepositoryInterface::class, HouseholdRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
