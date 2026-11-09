<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CleaningTeam;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\SalesVisit;
use App\Models\SeedProvenance;
use App\Models\TeamMember;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanDemoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meash:clean-demo-data
                            {--dry-run : Only inspect and report non-production demo records that would be purged}
                            {--backup-confirmed : Acknowledge that a verified database backup exists before permanent purge}
                            {--force : Force the operation without manual interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and permanently purge all development seed records verified by explicit SeedProvenance IDs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning database for development seed records with explicit, verified SeedProvenance...');

        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $backupConfirmed = (bool) $this->option('backup-confirmed');

        // Extract explicit IDs ONLY from SeedProvenance registry
        $provenances = SeedProvenance::where('seed_key', 'DatabaseSeeder')->get()->groupBy('entity_type');

        $provUserIds = $provenances->get('User')?->pluck('entity_id')->toArray() ?? [];
        $provCustIds = $provenances->get('Customer')?->pluck('entity_id')->toArray() ?? [];
        $provOrderIds = $provenances->get('Order')?->pluck('entity_id')->toArray() ?? [];
        $provExpenseIds = $provenances->get('Expense')?->pluck('entity_id')->toArray() ?? [];
        $provTeamIds = $provenances->get('CleaningTeam')?->pluck('entity_id')->toArray() ?? [];
        $provOrgIds = $provenances->get('Organization')?->pluck('entity_id')->toArray() ?? [];
        $provVisitIds = $provenances->get('SalesVisit')?->pluck('entity_id')->toArray() ?? [];
        $provProformaIds = $provenances->get('Proforma')?->pluck('entity_id')->toArray() ?? [];
        $provContractIds = $provenances->get('Contract')?->pluck('entity_id')->toArray() ?? [];
        $provCampIds = $provenances->get('Campaign')?->pluck('entity_id')->toArray() ?? [];
        $provTgIds = $provenances->get('TelegramUser')?->pluck('entity_id')->toArray() ?? [];

        // 1. Identify demo users from provenance ONLY, strictly protecting real owner 'amha'
        $demoUserIds = [];
        if (!empty($provUserIds)) {
            $demoUserIds = User::whereIn('id', $provUserIds)
                ->where(function ($q) {
                    $q->whereNull('username')->orWhere('username', '!=', 'amha');
                })
                ->pluck('id')
                ->toArray();
        }

        // 2. Identify demo customers from provenance ONLY
        $demoCustomerIds = [];
        if (!empty($provCustIds)) {
            $demoCustomerIds = Customer::whereIn('id', $provCustIds)->pluck('id')->toArray();
        }

        // 3. Identify demo orders from provenance ONLY
        $demoOrderIds = [];
        if (!empty($provOrderIds)) {
            $demoOrderIds = Order::whereIn('id', $provOrderIds)->pluck('id')->toArray();
        }

        // 4. Identify demo expenses from provenance ONLY
        $demoExpenseIds = [];
        if (!empty($provExpenseIds)) {
            $demoExpenseIds = Expense::whereIn('id', $provExpenseIds)->pluck('id')->toArray();
        }

        // 5. Identify demo cleaning teams from provenance ONLY
        $demoTeamIds = [];
        if (!empty($provTeamIds)) {
            $demoTeamIds = CleaningTeam::whereIn('id', $provTeamIds)->pluck('id')->toArray();
        }

        // 6. Identify demo organizations from provenance ONLY
        // Never delete legitimate organizations matching names, emails, or domains without explicit seed provenance!
        $demoOrgIds = [];
        if (!empty($provOrgIds)) {
            $demoOrgIds = Organization::whereIn('id', $provOrgIds)->pluck('id')->toArray();
        }

        // 7. Identify demo sales visits from provenance ONLY
        $demoVisitIds = [];
        if (!empty($provVisitIds)) {
            $demoVisitIds = SalesVisit::whereIn('id', $provVisitIds)->pluck('id')->toArray();
        }

        // 8. Identify demo proformas and contracts from provenance ONLY
        $demoProformaIds = [];
        if (!empty($provProformaIds)) {
            $demoProformaIds = Proforma::whereIn('id', $provProformaIds)->pluck('id')->toArray();
        }

        $demoContractIds = [];
        if (!empty($provContractIds)) {
            $demoContractIds = Contract::whereIn('id', $provContractIds)->pluck('id')->toArray();
        }

        // 9. Identify demo marketing campaigns from provenance ONLY
        $demoCampaignIds = [];
        if (!empty($provCampIds)) {
            $demoCampaignIds = Campaign::whereIn('id', $provCampIds)->pluck('id')->toArray();
        }

        // 10. Identify demo telegram users from provenance ONLY
        $demoTgUserIds = [];
        if (!empty($provTgIds)) {
            $demoTgUserIds = TelegramUser::whereIn('id', $provTgIds)->pluck('id')->toArray();
        }

        $summaryTable = [
            ['Demo Users', count($demoUserIds), implode(', ', array_slice($demoUserIds, 0, 10)) ?: 'None'],
            ['Demo Customers', count($demoCustomerIds), implode(', ', array_slice($demoCustomerIds, 0, 10)) ?: 'None'],
            ['Demo Orders & Items', count($demoOrderIds), implode(', ', array_slice($demoOrderIds, 0, 10)) ?: 'None'],
            ['Demo Expenses', count($demoExpenseIds), implode(', ', array_slice($demoExpenseIds, 0, 10)) ?: 'None'],
            ['Demo Cleaning Teams', count($demoTeamIds), implode(', ', array_slice($demoTeamIds, 0, 10)) ?: 'None'],
            ['Demo CRM Organizations', count($demoOrgIds), implode(', ', array_slice($demoOrgIds, 0, 10)) ?: 'None'],
            ['Demo Sales Visits', count($demoVisitIds), implode(', ', array_slice($demoVisitIds, 0, 10)) ?: 'None'],
            ['Demo Proformas & Contracts', count($demoProformaIds) + count($demoContractIds), implode(', ', array_slice(array_merge($demoProformaIds, $demoContractIds), 0, 10)) ?: 'None'],
            ['Demo Marketing Campaigns', count($demoCampaignIds), implode(', ', array_slice($demoCampaignIds, 0, 10)) ?: 'None'],
            ['Demo Telegram Users', count($demoTgUserIds), implode(', ', array_slice($demoTgUserIds, 0, 10)) ?: 'None'],
        ];

        $totalRecords = count($demoUserIds) + count($demoCustomerIds) + count($demoOrderIds) +
            count($demoExpenseIds) + count($demoTeamIds) + count($demoOrgIds) +
            count($demoVisitIds) + count($demoProformaIds) + count($demoContractIds) +
            count($demoCampaignIds) + count($demoTgUserIds);

        $this->table(['Entity Type', 'Sample Records Identified', 'Exact Sample IDs'], $summaryTable);

        if ($totalRecords === 0) {
            $this->info('No development seed demo records found with verified provenance. Database is already clean.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN COMPLETE: ' . $totalRecords . ' sample records identified with exact IDs. No records were deleted.');
            return Command::SUCCESS;
        }

        // Verified backup check: require actual backup file or explicit confirmation
        $backupPath = storage_path('backups');
        $hasBackupFile = File::exists($backupPath) && count(File::glob("{$backupPath}/*.sql*")) > 0;

        if (!$backupConfirmed && !$hasBackupFile) {
            $this->warn('Verified SQL backup check: No backup file detected in storage/backups/*.sql.');
            if ($this->input->isInteractive()) {
                if (!$this->confirm('Have you created and verified a full database backup before purging demo records?')) {
                    $this->error('Operation aborted. Please perform a verified database backup first.');
                    return Command::FAILURE;
                }
            } else {
                $this->error('Operation aborted. In non-interactive mode, ensure a backup exists in storage/backups/ or pass --backup-confirmed.');
                return Command::FAILURE;
            }
        }

        if (! $isForce) {
            if (! $this->confirm('WARNING: Are you sure you want to permanently purge all ' . $totalRecords . ' demo records? This action cannot be undone.')) {
                $this->info('Operation cancelled by user.');
                return Command::SUCCESS;
            }
        }

        $this->info('Executing atomic database purge of sample records...');

        DB::transaction(function () use (
            $provenances,
            $demoUserIds, $demoCustomerIds, $demoOrderIds,
            $demoExpenseIds, $demoTeamIds, $demoOrgIds,
            $demoVisitIds, $demoProformaIds, $demoContractIds,
            $demoCampaignIds, $demoTgUserIds
        ) {
            $provCampRecipIds = $provenances->get('CampaignRecipient')?->pluck('entity_id')->toArray() ?? [];
            $provProfItemIds = $provenances->get('ProformaItem')?->pluck('entity_id')->toArray() ?? [];
            $provOrderItemIds = $provenances->get('OrderItem')?->pluck('entity_id')->toArray() ?? [];
            $provPaymentIds = $provenances->get('Payment')?->pluck('entity_id')->toArray() ?? [];
            $provTeamMemIds = $provenances->get('TeamMember')?->pluck('entity_id')->toArray() ?? [];
            $provApptIds = $provenances->get('Appointment')?->pluck('entity_id')->toArray() ?? [];
            $provFeedbackIds = $provenances->get('Feedback')?->pluck('entity_id')->toArray() ?? [];
            $provComplaintIds = $provenances->get('Complaint')?->pluck('entity_id')->toArray() ?? [];
            $provFollowupIds = $provenances->get('Followup')?->pluck('entity_id')->toArray() ?? [];

            // Delete dependent marketing campaign recipients
            CampaignRecipient::where(function ($q) use ($demoCampaignIds, $provCampRecipIds) {
                if (!empty($demoCampaignIds)) {
                    $q->whereIn('campaign_id', $demoCampaignIds);
                }
                if (!empty($provCampRecipIds)) {
                    $q->orWhereIn('id', $provCampRecipIds);
                }
                if (empty($demoCampaignIds) && empty($provCampRecipIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            if (!empty($demoCampaignIds)) {
                Campaign::whereIn('id', $demoCampaignIds)->delete();
            }

            // Delete Telegram demo users
            if (!empty($demoTgUserIds)) {
                TelegramUser::whereIn('id', $demoTgUserIds)->delete();
            }

            // Delete Proforma Items and Proformas
            ProformaItem::where(function ($q) use ($demoProformaIds, $provProfItemIds) {
                if (!empty($demoProformaIds)) {
                    $q->whereIn('proforma_id', $demoProformaIds);
                }
                if (!empty($provProfItemIds)) {
                    $q->orWhereIn('id', $provProfItemIds);
                }
                if (empty($demoProformaIds) && empty($provProfItemIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            if (!empty($demoProformaIds)) {
                Proforma::whereIn('id', $demoProformaIds)->delete();
            }

            // Delete Contracts
            if (!empty($demoContractIds)) {
                Contract::whereIn('id', $demoContractIds)->delete();
            }

            // Delete Sales Visits
            if (!empty($demoVisitIds)) {
                SalesVisit::whereIn('id', $demoVisitIds)->delete();
            }

            // Delete Organizations
            if (!empty($demoOrgIds)) {
                Organization::whereIn('id', $demoOrgIds)->delete();
            }

            // Delete Order Items, Appointments, Payments, Followups, Feedbacks, Complaints, and Orders
            OrderItem::where(function ($q) use ($demoOrderIds, $provOrderItemIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provOrderItemIds)) {
                    $q->orWhereIn('id', $provOrderItemIds);
                }
                if (empty($demoOrderIds) && empty($provOrderItemIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            DB::table('appointments')->where(function ($q) use ($demoOrderIds, $provApptIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provApptIds)) {
                    $q->orWhereIn('id', $provApptIds);
                }
                if (empty($demoOrderIds) && empty($provApptIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            Payment::where(function ($q) use ($demoOrderIds, $provPaymentIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provPaymentIds)) {
                    $q->orWhereIn('id', $provPaymentIds);
                }
                if (empty($demoOrderIds) && empty($provPaymentIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            DB::table('followups')->where(function ($q) use ($demoOrderIds, $provFollowupIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provFollowupIds)) {
                    $q->orWhereIn('id', $provFollowupIds);
                }
                if (empty($demoOrderIds) && empty($provFollowupIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            DB::table('feedbacks')->where(function ($q) use ($demoOrderIds, $provFeedbackIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provFeedbackIds)) {
                    $q->orWhereIn('id', $provFeedbackIds);
                }
                if (empty($demoOrderIds) && empty($provFeedbackIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            DB::table('complaints')->where(function ($q) use ($demoOrderIds, $provComplaintIds) {
                if (!empty($demoOrderIds)) {
                    $q->whereIn('order_id', $demoOrderIds);
                }
                if (!empty($provComplaintIds)) {
                    $q->orWhereIn('id', $provComplaintIds);
                }
                if (empty($demoOrderIds) && empty($provComplaintIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            if (!empty($demoOrderIds)) {
                Order::whereIn('id', $demoOrderIds)->delete();
            }

            // Delete Expenses
            if (!empty($demoExpenseIds)) {
                Expense::whereIn('id', $demoExpenseIds)->delete();
            }

            // Delete Team Members and Cleaning Teams
            TeamMember::where(function ($q) use ($demoTeamIds, $demoUserIds, $provTeamMemIds) {
                if (!empty($demoTeamIds)) {
                    $q->whereIn('cleaning_team_id', $demoTeamIds);
                }
                if (!empty($demoUserIds)) {
                    $q->orWhereIn('user_id', $demoUserIds);
                }
                if (!empty($provTeamMemIds)) {
                    $q->orWhereIn('id', $provTeamMemIds);
                }
                if (empty($demoTeamIds) && empty($demoUserIds) && empty($provTeamMemIds)) {
                    $q->whereRaw('1 = 0');
                }
            })->delete();

            if (!empty($demoTeamIds)) {
                CleaningTeam::whereIn('id', $demoTeamIds)->delete();
            }

            // Delete Customers
            if (!empty($demoCustomerIds)) {
                Customer::whereIn('id', $demoCustomerIds)->delete();
            }

            // Delete Demo Users (Except real owner 'amha')
            if (!empty($demoUserIds)) {
                DB::table('personal_access_tokens')->whereIn('tokenable_id', $demoUserIds)->where('tokenable_type', User::class)->delete();
                User::whereIn('id', $demoUserIds)->delete();
            }

            // Delete provenance records
            SeedProvenance::where('seed_key', 'DatabaseSeeder')->delete();
        });

        $this->info("SUCCESS: Successfully purged {$totalRecords} sample records with verified provenance.");
        return Command::SUCCESS;
    }
}
