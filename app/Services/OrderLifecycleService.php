<?php

namespace App\Services;

use App\Http\Controllers\Api\TelegramController;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TelegramUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderLifecycleService
{
    /**
     * Single shared transition method for approving and verifying payments.
     * Unified across OrderController@approvePayment, OrderController@verifyPayment, and FinanceController@verifyPayment.
     */
    public static function approveAndVerifyPayment(Order $order, array $data, ?User $user = null, ?Payment $payment = null): array
    {
        return DB::transaction(function () use ($order, $data, $user, $payment) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $lockedOrder->loadMissing('customer');

            $alreadyPaid = (float)$lockedOrder->payments()
                ->when($payment, fn($q) => $q->where('id', '!=', $payment->id))
                ->sum('amount');
            $remaining = max(0, (float)$lockedOrder->total - $alreadyPaid);

            $amount = !empty($data['amount']) ? (float)$data['amount'] : ($remaining > 0 ? $remaining : (float)$lockedOrder->total);
            $method = $data['payment_method'] ?? $lockedOrder->payment_method ?? 'cash';
            if ($method === 'cbe_birr') {
                $method = 'cbe';
            }
            $ref = $data['reference_number'] ?? $data['payment_reference'] ?? $lockedOrder->payment_reference ?? null;

            if ($payment) {
                $payment->update([
                    'amount' => $amount,
                    'payment_method' => $method,
                    'reference_number' => $ref,
                    'payment_date' => $data['payment_date'] ?? $payment->payment_date ?? Carbon::today(),
                    'notes' => $data['notes'] ?? $payment->notes,
                ]);
            } else {
                $payment = Payment::create([
                    'payment_number' => Payment::generateNextNumber(),
                    'order_id' => $lockedOrder->id,
                    'customer_id' => $lockedOrder->customer_id,
                    'amount' => $amount,
                    'payment_method' => $method,
                    'reference_number' => $ref,
                    'payment_date' => $data['payment_date'] ?? Carbon::today(),
                    'recorded_by_user_id' => $user?->id,
                    'notes' => $data['notes'] ?? ("በ" . ($user?->name ?? 'ሲስተም') . " የጸደቀ ክፍያ"),
                ]);
            }

            $old = $lockedOrder->toArray();

            $lockedOrder->payment_status = 'paid';
            $lockedOrder->payment_method = $method;
            $lockedOrder->payment_reference = $ref;
            $lockedOrder->payment_approved_at = now();
            $lockedOrder->payment_approved_by_user_id = $user?->id;
            $lockedOrder->version += 1;
            $lockedOrder->save();

            AuditLog::logAction(
                $user?->id,
                'payment_approved',
                Order::class,
                $lockedOrder->id,
                $old,
                [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_status' => 'paid',
                    'payment_method' => $method,
                    'amount' => $amount,
                    'approved_by' => $user?->name,
                ]
            );

            // Execute automatic post-payment review lifecycle transition
            self::checkCompletionAndTriggerReview($lockedOrder);

            return [
                'success' => true,
                'message' => 'ክፍያው በተሳካ ሁኔታ ጸድቋል (Payment approved and recorded).',
                'order' => $lockedOrder->fresh(['customer', 'assignedTeam', 'items', 'payments']),
                'payment' => $payment,
            ];
        });
    }

    /**
     * Checks if the order is both paid and completed.
     * When met:
     * 1. Updates Customer lifecycle_stage to 'comment_waiting'
     * 2. Creates a pending Followup record with type = 'review' and due_date = now() (today)
     * 3. Sends 5-star review request via Telegram Bot (if available) and SMS review link
     */
    public static function checkCompletionAndTriggerReview(Order $order): bool
    {
        $order->refresh();
        $isPaid = ($order->payment_status === 'paid');
        $isCompleted = ($order->order_status === 'completed');

        if (!$isPaid || !$isCompleted) {
            return false;
        }

        $customer = $order->customer ?: Customer::find($order->customer_id);
        if ($customer) {
            $customer->update(['lifecycle_stage' => 'comment_waiting']);
            self::syncCustomerToTelegramUsers($customer, 'Comment Waiting');
        }

        // Create pending review followup for TODAY (now)
        Followup::firstOrCreate(
            [
                'order_id' => $order->id,
                'type' => 'review',
            ],
            [
                'customer_id' => $order->customer_id,
                'due_date' => Carbon::today(), // today, not tomorrow!
                'status' => 'pending',
                'notes' => 'Post-service customer satisfaction & review follow-up',
            ]
        );

        // 1. Send via Telegram Bot (if telegram user / chat ID exists)
        try {
            TelegramController::sendOrderReviewRequest($order);
        } catch (\Throwable $th) {
            Log::warning("Lifecycle Telegram review send error for order #{$order->id}: " . $th->getMessage());
        }

        // 2. Send via SMS Review Link
        try {
            SmsService::sendReviewRequest($order);
        } catch (\Throwable $th) {
            Log::warning("Lifecycle SMS review send error for order #{$order->id}: " . $th->getMessage());
        }

        return true;
    }

    /**
     * Records a customer review/feedback and promotes the customer out of Comment Waiting:
     * 1. Saves/updates the Feedback record (rating, comment, order_id, customer_id).
     * 2. Marks the corresponding Followup record for that order as completed (completed_at = now()).
     * 3. Updates Customer lifecycle_stage = 'normal_active', status = 'active'.
     * 4. Recalculates Customer total_orders and total_spent.
     * 5. Syncs the customer into telegram_users (Message Send contacts).
     */
    public static function recordCustomerReviewAndPromote(
        Customer $customer,
        ?Order $order,
        int $rating,
        ?string $comment = null,
        string $source = 'web',
        ?int $handledByUserId = null
    ): Feedback {
        return DB::transaction(function () use ($customer, $order, $rating, $comment, $source, $handledByUserId) {
            $rating = max(1, min(5, (int)$rating));
            $cleanComment = $comment !== null ? trim($comment) : null;

            // 1. Save or update Feedback record
            $feedbackMatch = [];
            if ($order) {
                $feedbackMatch['order_id'] = $order->id;
            } else {
                $feedbackMatch['customer_id'] = $customer->id;
            }

            $feedback = Feedback::updateOrCreate(
                $feedbackMatch,
                [
                    'customer_id' => $customer->id,
                    'order_id' => $order?->id,
                    'rating' => $rating,
                    'comment' => $cleanComment ?: ($order ? 'አስተያየት ተመዝግቧል' : 'ደንበኛ የሰጠው ደረጃ'),
                    'source' => $source,
                    'is_approved' => ($rating >= 4),
                ]
            );

            // 2. Automatically mark the corresponding Followup record as completed
            $followupQuery = Followup::where('customer_id', $customer->id)
                ->where('status', '!=', 'completed');

            if ($order) {
                $followupQuery->where(function ($q) use ($order) {
                    $q->where('order_id', $order->id)
                      ->orWhereNull('order_id');
                });
            }

            $outcome = ($rating >= 4) ? 'satisfied' : (($rating <= 2) ? 'complaint' : 'satisfied');

            $followupQuery->update([
                'status' => 'completed',
                'outcome' => $outcome,
                'completed_at' => now(),
                'handled_by_user_id' => $handledByUserId,
            ]);

            // 3 & 4. Update Customer lifecycle_stage, status, total_orders and total_spent
            $totalOrders = (int)Order::where('customer_id', $customer->id)->count();
            $totalSpent = (float)Payment::where('customer_id', $customer->id)->sum('amount');
            if ($totalSpent <= 0 && $order) {
                $totalSpent = (float)Order::where('customer_id', $customer->id)
                    ->where('payment_status', 'paid')
                    ->sum('total');
            }

            $customer->update([
                'lifecycle_stage' => 'normal_active',
                'status' => 'active',
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
            ]);

            // 5. Sync the customer into telegram_users (Message Send contacts)
            self::syncCustomerToTelegramUsers($customer);

            return $feedback;
        });
    }

    /**
     * Synchronizes customer contact into telegram_users table for marketing and broadcasts with lifecycle tags.
     */
    public static function syncCustomerToTelegramUsers(Customer $customer, ?string $tag = null): ?TelegramUser
    {
        if (empty($customer->phone)) {
            return null;
        }

        $stage = $customer->lifecycle_stage ?: 'normal_active';
        $assignedTag = $tag ?: match ($stage) {
            'normal_active' => 'Normal Active Customers',
            'comment_waiting' => 'Comment Waiting',
            'in_service' => 'In Service',
            default => 'Normal Active Customers',
        };

        $cleanPhone = SmsService::normalizeEthiopianPhone($customer->phone);
        $localPhone = str_starts_with($cleanPhone, '+251')
            ? '0' . substr($cleanPhone, 4)
            : (str_starts_with($cleanPhone, '251') ? '0' . substr($cleanPhone, 3) : $cleanPhone);
        $intlPhone = str_starts_with($cleanPhone, '+') ? $cleanPhone : '+' . ltrim($cleanPhone, '+');

        $names = explode(' ', trim($customer->full_name), 2);
        $firstName = $names[0] ?? $customer->full_name;
        $lastName = $names[1] ?? '';

        $telegramId = !empty($customer->telegram_user_id) && is_numeric($customer->telegram_user_id) 
            ? (int)$customer->telegram_user_id 
            : null;

        // 1. Try finding by telegram_id first (if known)
        if ($telegramId) {
            $userByTg = TelegramUser::where('telegram_id', $telegramId)->first();
            if ($userByTg) {
                $existingTags = array_filter(array_map('trim', explode(',', $userByTg->tags ?? '')));
                if (!in_array($assignedTag, $existingTags)) {
                    $existingTags[] = $assignedTag;
                }

                $userByTg->update([
                    'phone_number' => $localPhone,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'is_active' => true,
                    'is_excluded' => false,
                    'source' => 'customer_sync',
                    'lifecycle_stage' => $stage,
                    'tags' => implode(', ', $existingTags),
                ]);
                return $userByTg;
            }
        }

        // 2. Try finding by possible phone variations
        $possiblePhones = array_unique([$localPhone, $intlPhone, $cleanPhone, ltrim($cleanPhone, '+'), $customer->phone]);
        $userByPhone = TelegramUser::whereIn('phone_number', $possiblePhones)->first();
        if ($userByPhone) {
            $existingTags = array_filter(array_map('trim', explode(',', $userByPhone->tags ?? '')));
            if (!in_array($assignedTag, $existingTags)) {
                $existingTags[] = $assignedTag;
            }

            $updateData = [
                'phone_number' => $localPhone,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => true,
                'is_excluded' => false,
                'source' => 'customer_sync',
                'lifecycle_stage' => $stage,
                'tags' => implode(', ', $existingTags),
            ];
            // Only update telegram_id if not already claimed by another record
            if ($telegramId && $userByPhone->telegram_id !== $telegramId) {
                $tgOwner = TelegramUser::where('telegram_id', $telegramId)->first();
                if (!$tgOwner) {
                    $updateData['telegram_id'] = $telegramId;
                }
            }
            $userByPhone->update($updateData);
            return $userByPhone;
        }

        // 3. Create new record safely
        return TelegramUser::create([
            'phone_number' => $localPhone,
            'telegram_id' => $telegramId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
            'is_excluded' => false,
            'source' => 'customer_sync',
            'lifecycle_stage' => $stage,
            'tags' => $assignedTag,
        ]);
    }
}
