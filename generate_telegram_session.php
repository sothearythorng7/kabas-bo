<?php
require __DIR__ . '/vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Logger;

// Choisis ton mode : "user" ou "bot"
$mode = 'user'; // ou 'bot'

// Chemin du fichier de session
$sessionFile = __DIR__ . '/storage/app/telegram.session';

// Créer les settings
$settings = new Settings();
$appInfo = new AppInfo();
$appInfo->setApiId((int) getenv('TELEGRAM_API_ID'));
$appInfo->setApiHash(getenv('TELEGRAM_API_HASH'));
$settings->setAppInfo($appInfo);

// Désactiver les logs
$settings->setLogger(new Logger(null, Logger::NONE));

$MadelineProto = new API($sessionFile, $settings);

if ($mode === 'bot') {
    $botToken = getenv('TELEGRAM_BOT_TOKEN'); // Token de ton bot
    $MadelineProto->botLogin($botToken);
    echo "Session bot créée avec succès !\n";
} else {
    $phone = getenv('TELEGRAM_PHONE'); // Numéro de téléphone
    $MadelineProto->phoneLogin($phone);
    echo "Session utilisateur créée avec succès !\n";
}

