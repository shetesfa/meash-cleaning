<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CleaningTeam;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\SalesVisit;
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
                            {--backup-confirmed : Acknowledge that a database backup exists before permanent purge}
                            {--force : Force the operation without manual interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and permanently purge all fictional demo users, orders, CRM, and financial records seeded in development';

    /**
     * Exact demo user emails from DatabaseSeeder.
     */
    protected array $demoEmails = [
        'owner@meash.com',
        'reception@meash.com',
        'cleaner1@meash.com',
        'cleaner2@meash.com',
        'cleaner3@meash.com',
        'cleaner4@meash.com',
        'sales@meash.com',
        'finance@meash.com',
    ];

    /**
     * Exact demo customer phones from DatabaseSeeder.
     */
    protected array $demoCustomerPhones = [
        '0911223344', '0912445566', '0911556677', '0913889900',
        '0911122334', '0914223344', '0911334455', '0915667788',
        '0911778899', '0916990011', '0911445566', '0912889900',
    ];

    /**
     * Exact demo organizations from DatabaseSeeder.
     */
    protected array $demoOrgEmails = [
        'housekeeping@ethiopianskylighthotel.com',
        'facilities@cbe.com.et',
        'operations@hyattaddis.com',
        'info@novisethiopia.com',
        'procurement@zemenbank.com',
    ];

    /**
     * Exact demo expense reference numbers from DatabaseSeeder.
     */
    protected array $demoExpenseRefs = [
        'REC-CHEM-901', 'TOTAL-B-441', 'OIL-CMC-120', 'REC-MAT-309',
        'PAY-WK-38', 'REP-VAC-11', 'MKT-TG-08',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning database for all fictional demo records from DatabaseSeeder...');

        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $backupConfirmed = (bool) $this->option('backup-confirmed');

        // 1. Identify demo users (never target real owner 'amha' or legitimate business staff)
        $demoUsers = User::whereIn('email', $this->demoEmails)
            ->where(function ($q) {
                $q->whereNull('username')->orWhere('username', '!=', 'amha');
            })
            ->get();
        $demoUserIds = $demoUsers->pluck('id')->toArray();

        // 2. Identify demo customers
        $demoCustomers = Customer::whereIn('phone', $this->demoCustomerPhones)->get();
        $demoCustomerIds = $demoCustomers->pluck('id')->toArray();

        // 3. Identify demo orders
        $demoOrders = Order::where(function ($q) use ($demoCustomerIds, $demoUserIds) {
            if (!empty($demoCustomerIds)) {
                $q->whereIn('customer_id', $demoCustomerIds);
            }
            if (!empty($demoUserIds)) {
                $q->orWhereIn('created_by_user_id', $demoUserIds);
            }
        })->get();
        $demoOrderIds = $demoOrders->pluck('id')->toArray();

        // 4. Identify demo expenses
        $demoExpenses = Expense::where(function ($q) use ($demoUserIds) {
            $q->whereIn('reference_number', $this->demoExpenseRefs);
            if (!empty($demoUserIds)) {
                $q->orWhereIn('entered_by_user_id', $demoUserIds);
            }
        })->get();
        $demoExpenseIds = $demoExpenses->pluck('id')->toArray();

        // 5. Identify demo cleaning teams
        $demoTeams = CleaningTeam::where(function ($q) use ($demoUserIds) {
            $q->whereIn('team_name', [
                'Team Alpha (Upholstery & Carpet)',
                'Team Bravo (Rotary Scrub & Mattress)',
                'Team Delta (Facade & Post-Construction)',
            ]);
            if (!empty($demoUserIds)) {
                $q->orWhereIn('team_leader_id', $demoUserIds);
            }
        })->get();
        $demoTeamIds = $demoTeams->pluck('id')->toArray();

        // 6. Identify demo organizations
        $demoOrgs = Organization::whereIn('email', $this->demoOrgEmails)
            ->orWhereIn('name', [
                'Ethiopian Skylight Hotel',
                'Commercial Bank of Ethiopia HQ',
                'Hyatt Regency Addis Ababa',
                'Novis Real Estate Compound',
                'Zemen Bank HQ',
            ])->get();
        $demoOrgIds = $demoOrgs->pluck('id')->toArray();

        // 7. Identify demo sales visits, proformas, contracts
        $demoSalesVisits = SalesVisit::whereIn('organization_id', $demoOrgIds)
            ->orWhereIn('salesperson_user_id', $demoUserIds)
            ->get();
        $demoVisitIds = $demoSalesVisits->pluck('id')->toArray();

        $demoProformas = Proforma::whereIn('organization_id', $demoOrgIds)
            ->orWhereIn('sales_visit_id', $demoVisitIds)
            ->get();
        $demoProformaIds = $demoProformas->pluck('id')->toArray();

        $demoContracts = Contract::whereIn('organization_id', $demoOrgIds)->get();
        $demoContractIds = $demoContracts->pluck('id')->toArray();

        // 8. Identify demo marketing campaigns & recipients
        $demoCampaigns = Campaign::where('title', 'like', '%Enkutatash%')
            ->orWhere('title', 'like', '%የመስከረም አዲስ ዓመት%')
            ->get();
        $demoCampaignIds = $demoCampaigns->pluck('id')->toArray();

        // 9. Identify demo telegram users
        $demoTgUsers = TelegramUser::where('telegram_id', 987654321)
            ->orWhere('username', 'sarayohannes_eth')
            ->get();
        $demoTgUserIds = $demoTgUsers->pluck('id')->toArray();

        $summaryTable = [
            ['Demo Users', count($demoUserIds)],
            ['Demo Customers', count($demoCustomerIds)],
            ['Demo Orders & Items', count($demoOrderIds)],
            ['Demo Expenses', count($demoExpenseIds)],
            ['Demo Cleaning Teams', count($demoTeamIds)],
            ['Demo CRM Organizations', count($demoOrgIds)],
            ['Demo Sales Visits', count($demoVisitIds)],
            ['Demo Proformas & Contracts', count($demoProformaIds) + count($demoContractIds)],
            ['Demo Marketing Campaigns', count($demoCampaignIds)],
            ['Demo Telegram Users', count($demoTgUserIds)],
        ];

        $totalRecords = count($demoUserIds) + count($demoCustomerIds) + count($demoOrderIds) +
            count($demoExpenseIds) + count($demoTeamIds) + count($demoOrgIds) +
            count($demoVisitIds) + count($demoProformaIds) + count($demoContractIds) +
            count($demoCampaignIds) + count($demoTgUserIds);

        $this->table(['Entity Type', 'Sample Records Identified'], $summaryTable);

        if ($totalRecords === 0) {
            $this->info('No development seed demo records found. Database is already clean.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN COMPLETE: ' . $totalRecords . ' sample records identified. No records were deleted.');
            return Command::SUCCESS;
        }

        // Require backup verification
        if (! $backupConfirmed) {
            $hasBackupFile = File::exists(storage_path('backups')) && count(File::files(storage_path('backups'))) > 0;
            if (! $hasBackupFile && ! $this->confirm('Have you created and verified a full database backup before purging demo records?')) {
                $this->error('Operation aborted. Please perform a database backup before cleaning sample records (e.g. mysqldump -u meash_user -p meash_prod > backup.sql).');
                return Command::FAILURE;
            }
        }

        if (! $isForce) {
            if (! $this->confirm('WARNING: Are you sure you want to permanently purge all ' . $totalRecords . ' demo records? This action cannot be undone.')) {
                $this->info('Operation cancelled by user.');
                return Command::SUCCESS;
            }
        }

        $this->info('Executing comprehensive atomic database purge of sample records...');

        DB::transaction(function () use (
            $demoUserIds,
            $demoCustomerIds,
            $demoOrderIds,
            $demoExpenseIds,
            $demoTeamIds,
            $demoOrgIds,
            $demoVisitIds,
            $demoProformaIds,
            $demoContractIds,
            $demoCampaignIds,
            $demoTgUserIds
        ) {
            // Delete proforma items & proformas
            if (!empty($demoProformaIds)) {
                ProformaItem::whereIn('proforma_id', $demoProformaIds)->delete();
                Proforma::whereIn('id', $demoProformaIds)->delete();
            }

            // Delete contracts & sales visits
            if (!empty($demoContractIds)) {
                Contract::whereIn('id', $demoContractIds)->delete();
            }
            if (!empty($demoVisitIds)) {
                SalesVisit::whereIn('id', $demoVisitIds)->delete();
            }

            // Delete organizations
            if (!empty($demoOrgIds)) {
                Organization::whereIn('id', $demoOrgIds)->delete();
            }

            // Delete order items, payments, appointments, feedback, complaints, followups
            if (!empty($demoOrderIds)) {
                OrderItem::whereIn('order_id', $demoOrderIds)->delete();
                Payment::whereIn('order_id', $demoOrderIds)->delete();
                Appointment::whereIn('order_id', $demoOrderIds)->delete();
                Feedback::whereIn('order_id', $demoOrderIds)->delete();
                Complaint::whereIn('order_id', $demoOrderIds)->delete();
                Followup::whereIn('order_id', $demoOrderIds)->delete();
                Order::whereIn('id', $demoOrderIds)->delete();
            }

            // Delete remaining customer records
            if (!empty($demoCustomerIds)) {
                Appointment::whereIn('customer_id', $demoCustomerIds)->delete();
                Payment::whereIn('customer_id', $demoCustomerIds)->delete();
                Feedback::whereIn('customer_id', $demoCustomerIds)->delete();
                Complaint::whereIn('customer_id', $demoCustomerIds)->delete();
                Followup::whereIn('customer_id', $demoCustomerIds)->delete();
                Customer::whereIn('id', $demoCustomerIds)->delete();
            }

            // Delete demo campaigns & recipients
            if (!empty($demoCampaignIds)) {
                CampaignRecipient::whereIn('campaign_id', $demoCampaignIds)->delete();
                Campaign::whereIn('id', $demoCampaignIds)->delete();
            }

            // Delete demo expenses
            if (!empty($demoExpenseIds)) {
                Expense::whereIn('id', $demoExpenseIds)->delete();
            }

            // Delete demo teams & team members
            if (!empty($demoTeamIds)) {
                TeamMember::whereIn('cleaning_team_id', $demoTeamIds)->delete();
                CleaningTeam::whereIn('id', $demoTeamIds)->delete();
            }
            if (!empty($demoUserIds)) {
                TeamMember::whereIn('user_id', $demoUserIds)->delete();
            }

            // Delete demo telegram users
            if (!empty($demoTgUserIds)) {
                TelegramUser::whereIn('id', $demoTgUserIds)->delete();
            }

            // Delete demo audit log entries
            AuditLog::where(function ($q) use ($demoUserIds) {
                if (!empty($demoUserIds)) {
                    $q->whereIn('user_id', $demoUserIds);
                }
            })->delete();

            // Delete demo users (preserves legitimate Owner amha)
            if (!empty($demoUserIds)) {
                User::whereIn('id', $demoUserIds)->delete();
            }
        });

        $this->info('Comprehensive demo cleanup completed successfully. All legitimate business data preserved.');

        return Command::SUCCESS;
    }
}
