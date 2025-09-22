<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings;

class SendTelegramReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $recipients;
    protected string $message;
    protected ?string $file;

    /**
     * Create a new job instance.dd
     */
    public function __construct(array $recipients, string $message, ?string $file = null)
    {
        $this->recipients = $recipients;
        $this->message    = $message;
        $this->file       = $file;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // --- Préparer le dossier Telegram ---
        $telegramDir = storage_path('app/telegram');
        if (!is_dir($telegramDir)) {
            mkdir($telegramDir, 0775, true);
        }

        $sessionFile = $telegramDir . '/telegram.session';

        // --- Créer les settings ---
        $settings = new Settings;

        $appInfo = new AppInfo;
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash(env('TELEGRAM_API_HASH'));
        $settings->setAppInfo($appInfo);

        // Désactiver le logger (on ne veut pas de log fichier)
        // -> si ton problème de log revient, on peut utiliser un chemin dans storage
        //$settings->setLogger((new \danog\MadelineProto\Settings\Logger())->setLevel(5));

        // --- Initialiser l’API ---
        $MadelineProto = new API($sessionFile, $settings);

        // --- Connexion ---
        $MadelineProto->start();

        foreach ($this->recipients as $recipient) {
            if ($this->file) {
                $MadelineProto->messages->sendMedia([
                    'peer'  => $recipient,
                    'media' => [
                        '_'       => 'inputMediaUploadedDocument',
                        'file'    => $this->file,
                        'attributes' => [
                            ['_' => 'documentAttributeFilename', 'file_name' => basename($this->file)]
                        ],
                    ],
                    'message' => $this->message,
                ]);
            } else {
                $MadelineProto->messages->sendMessage([
                    'peer'    => $recipient,
                    'message' => $this->message,
                ]);
            }
        }
    }
}
