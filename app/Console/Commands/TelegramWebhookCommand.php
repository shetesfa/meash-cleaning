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
            $url = config('services.telegram.webhook_url') ?: 'https://meash-cleaning.onrender.com/api/telegram/webhook';
            $this->info("Setting Webhook to: {$url}...");

            $resp = Http::get("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $url,
                'drop_pending_updates' => true,
            ])->json();

            if ($resp['ok'] ?? false) {
                $this->info('✅ Webhook successfully set to Render!');
                $this->line('The Telegram Bot is now active 24/7 on the cloud.');
                return 0;
            } else {
                $this->error('❌ Failed to set webhook: ' . json_encode($resp));
                return 1;
            }
        } elseif ($action === 'delete') {
            $this->info('Deleting Telegram Webhook...');

            $resp = Http::get("https://api.telegram.org/bot{$token}/deleteWebhook", [
                'drop_pending_updates' => false,
            ])->json();

            if ($resp['ok'] ?? false) {
                $this->info('✅ Webhook deleted! Bot is now ready for Local Polling.');
                return 0;
            } else {
                $this->error('❌ Failed to delete webhook: ' . json_encode($resp));
                return 1;
            }
        } else {
            // Status
            $this->info('Fetching Telegram Bot & Webhook Status...');
            $getMe = Http::get("https://api.telegram.org/bot{$token}/getMe")->json();
            $info = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json();

            if (!($getMe['ok'] ?? false)) {
                $this->error('❌ Invalid Bot Token: ' . json_encode($getMe));
                return 1;
            }

            $botUser = $getMe['result']['username'] ?? 'unknown';
            $this->info("🤖 Bot Username: @{$botUser}");

            $wh = $info['result'] ?? [];
            $whUrl = $wh['url'] ?? '';

            if (empty($whUrl)) {
                $this->warn('⚡ Webhook Status: INACTIVE (Local Polling mode enabled)');
            } else {
                $this->info("🌐 Webhook Status: ACTIVE pointing to {$whUrl}");
                $this->line("   Pending updates: " . ($wh['pending_update_count'] ?? 0));
                $this->line("   IP Address: " . ($wh['ip_address'] ?? 'N/A'));
            }

            return 0;
        }
    }
}
