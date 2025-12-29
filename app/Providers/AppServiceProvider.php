<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use App\Models\ResellerStockDelivery;
use App\Observers\ResellerStockDeliveryObserver;
use App\Models\ResellerSalesReport;
use App\Observers\ResellerSalesReportObserver;
use App\Models\Store;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use App\Events\SaleCreated;
use App\Listeners\SendSaleTelegramNotification;

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

        // Event listeners
        Event::listen(SaleCreated::class, SendSaleTelegramNotification::class);

        // Partage des stores avec toutes les vues (sauf pendant migrations)
        if (Schema::hasTable('stores')) {
            View::share('stores', Store::all());
        }

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
