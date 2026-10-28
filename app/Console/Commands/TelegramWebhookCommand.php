<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook {action=status : status, set, or delete}';
    protected $description = 'Manage Telegram Bot Webhook (status, set to Render, or delete for local polling)';

    public function handle(): int
    {
        $action = strtolower($this->argument('action'));
        $token = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');
            return 1;
        }

        if ($action === 'set') {
            $configuredWebhook = config('services.telegram.webhook_url');
            $appUrl = config('app.url') ?: env('APP_URL');

            if (!empty($configuredWebhook) && !str_contains($configuredWebhook, 'meash-cleaning.onrender.com')) {
                $url = $configuredWebhook;
            } elseif (!empty($appUrl) && $appUrl !== 'http://localhost') {
                $url = rtrim($appUrl, '/') . '/api/telegram/webhook';
            } else {
                $url = $configuredWebhook ?: 'https://meash-cleaning-gy5x.onrender.com/api/telegram/webhook';
            }

            $this->info("Setting Webhook to: {$url}...");

            $secret = config('services.telegram.webhook_secret') ?: env('TELEGRAM_WEBHOOK_SECRET');
            $params = [
                'url' => $url,
                'drop_pending_updates' => true,
            ];
            // Telegram secret_token must be 1-256 characters of [A-Za-z0-9_-]
            if (!empty($secret) && preg_match('/^[A-Za-z0-9_-]{1,256}$/', $secret)) {
                $params['secret_token'] = $secret;
            }

            try {
                $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setWebhook", $params)->json();
            } catch (\Throwable $e) {
                $this->error("Failed to connect to Telegram API: " . $e->getMessage());
                return 1;
            }

            if ($resp['ok'] ?? false) {
                $this->info(' Webhook successfully set to Render!');
                $this->line('The Telegram Bot is now active 24/7 on the cloud.');
                return 0;
            } else {
                $this->error(' Failed to set webhook: ' . json_encode($resp));
                return 1;
            }
        } elseif ($action === 'delete') {
            $this->info('Deleting Telegram Webhook...');

            try {
                $resp = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/deleteWebhook", [
                    'drop_pending_updates' => false,
                ])->json();
            } catch (\Throwable $e) {
                $this->error("Failed to delete Telegram webhook: " . $e->getMessage());
                return 1;
            }

            if ($resp['ok'] ?? false) {
                $this->info(' Webhook deleted! Bot is now ready for Local Polling.');
                return 0;
            } else {
                $this->error(' Failed to delete webhook: ' . json_encode($resp));
                return 1;
            }
        } else {
            // Status
            $this->info('Fetching Telegram Bot & Webhook Status...');
            try {
                $getMe = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe")->json();
                $info = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json();
            } catch (\Throwable $e) {
                $this->error("Failed to fetch Telegram bot info: " . $e->getMessage());
                return 1;
            }

            if (!($getMe['ok'] ?? false)) {
                $this->error(' Invalid Bot Token: ' . json_encode($getMe));
                return 1;
            }

            $botUser = $getMe['result']['username'] ?? 'unknown';
            $this->info(" Bot Username: @{$botUser}");

            $wh = $info['result'] ?? [];
            $whUrl = $wh['url'] ?? '';

            if (empty($whUrl)) {
                $this->warn(' Webhook Status: INACTIVE (Local Polling mode enabled)');
            } else {
                $this->info(" Webhook Status: ACTIVE pointing to {$whUrl}");
                $this->line("   Pending updates: " . ($wh['pending_update_count'] ?? 0));
                $this->line("   IP Address: " . ($wh['ip_address'] ?? 'N/A'));
            }

            return 0;
        }
    }
}
