<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Payable;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Policies\CustomerPolicy;
use App\Policies\PayablePolicy;
use App\Policies\PurchasePolicy;
use App\Policies\ReceivablePolicy;
use App\Policies\SupplierPolicy;
use App\Services\MidtransService;
use App\Services\PayableService;
use App\Services\PurchaseService;
use App\Services\ReceivableService;
use App\Services\StockService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends AuthServiceProvider
{
    /**
     * The model-to-policy map.
     */
    protected $policies = [
        Purchase::class   => PurchasePolicy::class,
        Receivable::class => ReceivablePolicy::class,
        Payable::class    => PayablePolicy::class,
        Supplier::class   => SupplierPolicy::class,
        Customer::class   => CustomerPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StockService::class);
        $this->app->singleton(PurchaseService::class);
        $this->app->singleton(ReceivableService::class);
        $this->app->singleton(PayableService::class);
        $this->app->singleton(MidtransService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super-admin gate: bypass all policy checks
        Gate::before(function ($user, $ability) {
            if ($user->branches()->wherePivot('role', 'super_admin')->exists()) {
                return true;
            }
        });
    }
}
