<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Events\CommandStarting;
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
        // Garde-fou production DB : refuse les commandes destructives quand la DB
        // cible s'appelle `kabas` (la prod), sauf flag explicite `--i-know-this-is-prod`.
        // Cf. incident 2026-05-13 (DB prod wipée par migrate:fresh contre kabas).
        if ($this->app->runningInConsole()) {
            Event::listen(CommandStarting::class, function (CommandStarting $event) {
                $destructive = ['migrate:fresh', 'migrate:reset', 'db:wipe'];
                if (! in_array($event->command, $destructive, true)) {
                    return;
                }
                if (config('database.connections.mysql.database') === 'kabas'
                    && ! $event->input->hasParameterOption('--i-know-this-is-prod')) {
                    fwrite(STDERR, "\nREFUSED: '{$event->command}' on production DB 'kabas'.\n"
                        . "If you really mean it, re-run with --i-know-this-is-prod.\n\n");
                    exit(1);
                }
            });
        }

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
