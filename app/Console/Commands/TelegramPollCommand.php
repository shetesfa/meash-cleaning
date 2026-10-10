<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\TelegramController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Polls Telegram updates in real-time for local development (no webhook required)';

    public function handle(): void
    {
        $token = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');

        if (empty($token)) {
            $this->error('ERROR: TELEGRAM_BOT_TOKEN is not set in your .env file.');
            $this->info('Please add your Telegram Bot Token in .env:');
            $this->warn('TELEGRAM_BOT_TOKEN=your_token_here');
            return;
        }

        $this->info('========================================================');
        $this->info('  MEASH CLEANING SOLUTION - TELEGRAM BOT POLLING ENGINE  ');
        $this->info('========================================================');
        $this->info('Connecting to Telegram Bot API...');

        // Verify token with getMe
        $getMe = Http::get("https://api.telegram.org/bot{$token}/getMe")->json();
        if (!($getMe['ok'] ?? false)) {
            $this->error('Failed to authenticate with Telegram. Please check your bot token.');
            $this->line(json_encode($getMe));
            return;
        }

        $botUser = $getMe['result']['username'] ?? 'unknown';
        $this->info("Successfully connected as @{$botUser}!");

        // If webhook is active, clear it so local getUpdates doesn't conflict
        $info = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json();
        if (!empty($info['result']['url'] ?? '')) {
            $this->warn("Active webhook detected ({$info['result']['url']}).");
            $this->info("Switching to Local Polling mode (clearing webhook)...");
            Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");
        }

        $this->info("Listening for Telegram messages & Inline Queries (@{$botUser} ...)");
        $this->info("Press Ctrl+C to stop.\n");

        $offset = 0;
        $controller = new TelegramController();

        while (true) {
            try {
                $response = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 25,
                ]);

                if ($response->ok()) {
                    $data = $response->json();
                    $updates = $data['result'] ?? [];

                    foreach ($updates as $update) {
                        $updateId = $update['update_id'];
                        $offset = $updateId + 1;

                        try {
                            if (isset($update['inline_query'])) {
                                $q = $update['inline_query']['query'] ?? '';
                                $user = $update['inline_query']['from']['first_name'] ?? 'User';
                                $this->line("<fg=cyan>[INLINE QUERY]</> from {$user}: \"@{$botUser} {$q}\"");
                                $controller->handleInlineQuery($update['inline_query']);
                            } elseif (isset($update['message'])) {
                                $text = $update['message']['text'] ?? (isset($update['message']['web_app_data']) ? '[🌐 MINI APP BOOKING DATA]' : (isset($update['message']['location']) ? ('[📍 LIVE GPS LOCATION: ' . $update['message']['location']['latitude'] . ', ' . $update['message']['location']['longitude'] . ']') : (isset($update['message']['contact']) ? ('[📱 CONTACT: ' . $update['message']['contact']['phone_number'] . ']') : '')));
                                $user = $update['message']['from']['first_name'] ?? 'User';
                                $this->line("<fg=green>[MESSAGE]</> from {$user}: \"{$text}\"");
                                $controller->handleMessage($update['message']);
                            } elseif (isset($update['callback_query'])) {
                                $dataCb = $update['callback_query']['data'] ?? '';
                                $this->line("<fg=yellow>[CALLBACK]</> button clicked: \"{$dataCb}\"");
                                $controller->handleCallbackQuery($update['callback_query']);
                            }
                        } catch (\Throwable $te) {
                            $this->error("Error handling update #{$updateId}: " . $te->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn('Polling retry in 3 seconds: ' . $e->getMessage());
                sleep(3);
            }
        }
    }
}
