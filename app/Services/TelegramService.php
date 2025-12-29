<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $chatId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $message, ?string $parseMode = 'HTML'): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram credentials not configured');
            return false;
        }

        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
