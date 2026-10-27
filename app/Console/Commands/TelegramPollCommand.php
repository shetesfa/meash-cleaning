<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\TelegramController;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Polls Telegram updates in real-time for local development (no webhook required)';

    private function tgCall(string $token, string $method, array $params = [], int $timeout = 10): ?array
    {
        $url = "https://api.telegram.org/bot{$token}/{$method}";
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res ? json_decode($res, true) : null;
    }

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
        $getMe = $this->tgCall($token, 'getMe', [], 10);
        if (!($getMe['ok'] ?? false)) {
            $this->error('Failed to authenticate with Telegram. Please check your bot token.');
            $this->line(json_encode($getMe));
            return;
        }

        $botUser = $getMe['result']['username'] ?? 'unknown';
        $this->info("Successfully connected as @{$botUser}!");

        // If webhook is active, clear it so local getUpdates doesn't conflict
        $info = $this->tgCall($token, 'getWebhookInfo', [], 10);
        if (!empty($info['result']['url'] ?? '')) {
            $this->warn("Active webhook detected ({$info['result']['url']}).");
            $this->info("Switching to Local Polling mode (clearing webhook)...");
            $this->tgCall($token, 'deleteWebhook', [], 10);
        }

        $this->info("Listening for Telegram messages & Inline Queries (@{$botUser} ...)");
        $this->info("Press Ctrl+C to stop.\n");

        $offset = 0;
        $controller = new TelegramController();

        while (true) {
            try {
                $data = $this->tgCall($token, 'getUpdates', [
                    'offset' => $offset,
                    'timeout' => 20,
                ], 25);

                if ($data && ($data['ok'] ?? false)) {
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
                $this->warn('Polling retry in 2 seconds: ' . $e->getMessage());
                sleep(2);
            }
            usleep(200000); // 0.2s pause between polls
        }
    }
}
