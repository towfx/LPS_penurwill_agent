<?php

namespace App\Providers;

use App\Services\CommissionCalculator;
use App\Services\CommissionGenerator;
use Illuminate\Support\ServiceProvider;

class CommissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommissionCalculator::class);
        $this->app->singleton(CommissionGenerator::class);
    }
}
