<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckSupervisor extends Command
{
    protected $signature = 'queue:check-supervisor';
    protected $description = 'Vérifie si Supervisor est installé et configure le worker de Laravel';

    public function handle()
    {
        // Vérifie si la commande supervisorctl existe
        $exists = (bool) shell_exec('which supervisorctl');

        if (!$exists) {
            $this->warn('Supervisor n\'est pas installé. Vous devez l\'installer manuellement.');
            return 0;
        }

        $this->info('Supervisor est installé, création du fichier de configuration.');

        $confPath = '/etc/supervisor/conf.d/laravel-queue.conf';
        $projectPath = base_path();

        // Contenu du fichier supervisor
        $confContent = <<<EOT
        [program:laravel-queue]
        process_name=%(program_name)s_%(process_num)02d
        command=php $projectPath/artisan queue:work --sleep=3 --tries=3 --timeout=90
        autostart=true
        autorestart=true
        user=www-data
        numprocs=1
        redirect_stderr=true
        stdout_logfile=$projectPath/storage/logs/queue.log
        EOT;

        // Écrit le fichier si pas déjà présent
        if (!file_exists($confPath)) {
            File::put($confPath, $confContent);
            $this->info("Fichier de configuration créé : $confPath");

            $this->info("Recharge Supervisor pour prendre en compte la config...");
            shell_exec('sudo supervisorctl reread');
            shell_exec('sudo supervisorctl update');
            shell_exec('sudo supervisorctl start laravel-queue:*');

            $this->info("Supervisor configuré et worker démarré automatiquement.");
        } else {
            $this->info("Le fichier de configuration existe déjà, rien à faire.");
        }

        return 0;
    }
}
