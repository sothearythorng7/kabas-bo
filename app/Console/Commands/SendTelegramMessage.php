<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger;

class SendTelegramMessage extends Command
{
    protected $signature = 'telegram:send {recipient} {message} {--file=}';
    protected $description = 'Envoie un message Telegram avec option pièce jointe via ton compte perso';

    public function handle()
    {
        $recipient = $this->argument('recipient'); 
        $message   = $this->argument('message');  
        $file      = $this->option('file');       

        // --- Préparer le dossier Telegram dans storage ---
        $telegramDir = storage_path('app/telegram');
        if (!is_dir($telegramDir)) {
            mkdir($telegramDir, 0775, true);
        }

        $sessionFile = $telegramDir . '/telegram.session';
        $logFile     = $telegramDir . '/MadelineProto.log';

        // --- Créer les settings ---
        $settings = new Settings;

        $appInfo = new AppInfo;
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash(env('TELEGRAM_API_HASH'));
        $settings->setAppInfo($appInfo);

        // Logger pointant vers storage (aucun souci de permission)
        $settings->setLogger(new Logger(function($level, $message) use ($logFile) {
            // on n’écrit rien pour désactiver le log
        }));

        // --- Initialiser l’API ---
        $MadelineProto = new API($sessionFile, $settings);

        // --- Connexion ---
        $MadelineProto->start();

        if ($file) {
            $this->info("Envoi avec pièce jointe...");
            $MadelineProto->messages->sendMedia([
                'peer'  => $recipient,
                'media' => [
                    '_'       => 'inputMediaUploadedDocument',
                    'file'    => $file,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => basename($file)]
                    ],
                ],
                'message' => $message,
            ]);
        } else {
            $this->info("Envoi texte simple...");
            $MadelineProto->messages->sendMessage([
                'peer'    => $recipient,
                'message' => $message,
            ]);
        }

        $this->info("Message envoyé à $recipient !");
        return Command::SUCCESS;
    }
}
    