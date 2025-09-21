<?php
// fichier : app/Console/Commands/GenerateTabsConfig.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateTabsConfig extends Command
{
    protected $signature = 'tabs:generate';
    protected $description = 'Génère le fichier config/tabs.php à partir des routes';

    public function handle()
    {
        $routes = Route::getRoutes();
        $tabs = [];

        foreach ($routes as $route) {
            $name = $route->getName();

            if (!$name) {
                continue; // on ignore les routes sans nom
            }

            // On tente de créer un label lisible
            $controller = $route->getActionName(); // ex: App\Http\Controllers\SupplierOrderController@show
            $label = null;

            if ($controller && strpos($controller, '@') !== false) {
                [$class, $method] = explode('@', $controller);
                $classParts = explode('\\', $class);
                $controllerName = end($classParts);
                $controllerName = str_replace('Controller', '', $controllerName);
                $controllerName = Str::title(Str::snake($controllerName, ' '));
                $methodName = Str::title(Str::snake($method, ' '));
                $label = "$controllerName - $methodName";
            } else {
                $label = Str::title(str_replace(['.', '_'], ' ', $name));
            }

            $tabs[$name] = $label;
        }

        // Générer le contenu du fichier config/tabs.php
        $content = "<?php\n\nreturn [\n";
        foreach ($tabs as $route => $label) {
            $label = addslashes($label);
            $content .= "    '$route' => '$label',\n";
        }
        $content .= "];\n";

        // Sauvegarder le fichier
        $file = config_path('tabs.php');
        file_put_contents($file, $content);

        $this->info("Fichier tabs.php généré avec succès !");
    }
}
