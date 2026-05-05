<?php

namespace App\Providers;

use App\Services\AgentHierarchy;
use App\Services\CommissionCalculator;
use App\Services\CommissionConfig;
use App\Services\CommissionGenerator;
use App\Services\FeeService;
use App\Services\PayoutReportGenerator;
use App\Services\RefundService;
use App\Services\RenewalService;
use Illuminate\Support\ServiceProvider;

class CommissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommissionCalculator::class);
        $this->app->singleton(AgentHierarchy::class);
        $this->app->singleton(CommissionConfig::class);
        $this->app->singleton(PayoutReportGenerator::class);
        $this->app->singleton(FeeService::class);
        $this->app->singleton(RenewalService::class);
        $this->app->singleton(RefundService::class);

        $this->app->singleton(CommissionGenerator::class, function ($app) {
            return new CommissionGenerator(
                $app->make(CommissionCalculator::class),
                $app->make(AgentHierarchy::class),
            );
        });
    }

    public function boot(): void {}
}
