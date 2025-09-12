<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\ResellerStockDelivery;
use App\Observers\ResellerStockDeliveryObserver;
use App\Models\ResellerSalesReport;
use App\Observers\ResellerSalesReportObserver;
use App\Models\Store;
use Illuminate\Support\Facades\View;
use App\Helpers\MenuHelper;


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
        Paginator::useBootstrapFive();
        ResellerStockDelivery::observe(ResellerStockDeliveryObserver::class);

        View::share('stores', Store::all());
        View::composer('*', function ($view) {
            $view->with('activeMenu', MenuHelper::getActiveMenu());
        });
    }
}
