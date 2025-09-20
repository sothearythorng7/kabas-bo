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
use Illuminate\Support\Facades\Blade;

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

        // Partage des stores avec toutes les vues
        View::share('stores', Store::all());

        // Active menu pour toutes les vues
        View::composer('*', function ($view) {
            $view->with('activeMenu', getActiveMenu()); // <-- fonction globale
        });

        // Directive Blade pour les traductions
        Blade::directive('t', function ($expression) {
            return "<?php echo trans('messages.' . $expression); ?>";
        });

        // Préparation du menu JS pour toutes les vues
        View::composer('*', function ($view) {
            $menuConfig = config('menu') ?? [];
            $menuForJs = prepareMenuForJs($menuConfig); // <-- fonction globale
            $view->with('menuForJs', $menuForJs);
        });
    }
}
