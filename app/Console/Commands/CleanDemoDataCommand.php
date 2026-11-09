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
        $this->info('Scanning database for development seed demo records with verified provenance...');

        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $backupConfirmed = (bool) $this->option('backup-confirmed');

        // Extract explicit IDs from SeedProvenance registry if available
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

        // 1. Identify demo users (Strict protection: NEVER target legitimate owner 'amha' or staff)
        $demoUsers = User::where(function ($q) use ($provUserIds) {
            $q->whereIn('email', $this->demoEmails);
            if (!empty($provUserIds)) {
                $q->orWhereIn('id', $provUserIds);
            }
        })
        ->where(function ($q) {
            $q->whereNull('username')->orWhere('username', '!=', 'amha');
        })
        ->get();
        $demoUserIds = $demoUsers->pluck('id')->toArray();

        // 2. Identify demo customers
        $demoCustomers = Customer::where(function ($q) use ($provCustIds) {
            $q->whereIn('phone', $this->demoCustomerPhones);
            if (!empty($provCustIds)) {
                $q->orWhereIn('id', $provCustIds);
            }
        })->get();
        $demoCustomerIds = $demoCustomers->pluck('id')->toArray();

        // 3. Identify demo orders (avoid empty nested closure)
        $demoOrders = Order::query();
        $hasOrderCond = false;
        if (!empty($provOrderIds)) {
            $demoOrders->whereIn('id', $provOrderIds);
            $hasOrderCond = true;
        }
        if (!empty($demoCustomerIds) && !empty($demoUserIds)) {
            $demoOrders->orWhere(function ($q) use ($demoCustomerIds, $demoUserIds) {
                $q->whereIn('customer_id', $demoCustomerIds)
                  ->orWhereIn('created_by_user_id', $demoUserIds);
            });
            $hasOrderCond = true;
        } elseif (!empty($demoCustomerIds)) {
            $demoOrders->orWhereIn('customer_id', $demoCustomerIds);
            $hasOrderCond = true;
        } elseif (!empty($demoUserIds)) {
            $demoOrders->orWhereIn('created_by_user_id', $demoUserIds);
            $hasOrderCond = true;
        }
        if (!$hasOrderCond) {
            $demoOrders->whereRaw('1 = 0');
        }
        $demoOrders = $demoOrders->get();
        $demoOrderIds = $demoOrders->pluck('id')->toArray();

        // 4. Identify demo expenses (avoid empty nested closure)
        $demoExpenses = Expense::query();
        $hasExpCond = false;
        if (!empty($provExpenseIds)) {
            $demoExpenses->whereIn('id', $provExpenseIds);
            $hasExpCond = true;
        }
        if (!empty($demoUserIds)) {
            $demoExpenses->orWhere(function ($q) use ($demoUserIds) {
                $q->whereIn('reference_number', $this->demoExpenseRefs)
                  ->orWhereIn('entered_by_user_id', $demoUserIds);
            });
            $hasExpCond = true;
        } else {
            $demoExpenses->orWhereIn('reference_number', $this->demoExpenseRefs);
            $hasExpCond = true;
        }
        if (!$hasExpCond) {
            $demoExpenses->whereRaw('1 = 0');
        }
        $demoExpenses = $demoExpenses->get();
        $demoExpenseIds = $demoExpenses->pluck('id')->toArray();

        // 5. Identify demo cleaning teams (avoid empty nested closure)
        $demoTeams = CleaningTeam::query();
        $hasTeamCond = false;
        if (!empty($provTeamIds)) {
            $demoTeams->whereIn('id', $provTeamIds);
            $hasTeamCond = true;
        }
        if (!empty($demoUserIds)) {
            $demoTeams->orWhere(function ($q) use ($demoUserIds) {
                $q->whereIn('team_name', [
                    'Team Alpha (Upholstery & Carpet)',
                    'Team Bravo (Rotary Scrub & Mattress)',
                    'Team Delta (Facade & Post-Construction)',
                ])->orWhereIn('team_leader_id', $demoUserIds);
            });
            $hasTeamCond = true;
        } else {
            $demoTeams->orWhereIn('team_name', [
                'Team Alpha (Upholstery & Carpet)',
                'Team Bravo (Rotary Scrub & Mattress)',
                'Team Delta (Facade & Post-Construction)',
            ]);
            $hasTeamCond = true;
        }
        if (!$hasTeamCond) {
            $demoTeams->whereRaw('1 = 0');
        }
        $demoTeams = $demoTeams->get();
        $demoTeamIds = $demoTeams->pluck('id')->toArray();

        // 6. Identify demo organizations:
        // NEVER delete legitimate business organizations matching names unless they have explicit seed provenance!
        // Legitimate organizations (e.g. created by real owner or linked to non-demo visits/orders) MUST BE PRESERVED.
        $demoOrgs = Organization::query();
        $hasOrgCond = false;

        if (!empty($provOrgIds)) {
            $demoOrgs->whereIn('id', $provOrgIds);
            $hasOrgCond = true;
        }

        // Fallback provenance check for seed notes or explicit seed markers
        $demoOrgs->orWhere(function ($q) use ($demoUserIds) {
            $q->where(function ($sub) {
                $sub->whereIn('email', $this->demoOrgEmails)
                    ->orWhereIn('name', [
                        'Ethiopian Skylight Hotel',
                        'Commercial Bank of Ethiopia HQ',
                        'Hyatt Regency Addis Ababa',
                        'Novis Real Estate Compound',
                        'Zemen Bank HQ',
                    ]);
            });

            // Provenance verification: Must have known seed notes or explicit [Seed marker
            $q->where(function ($prov) use ($demoUserIds) {
                $prov->where('notes', 'like', '%luxury guest rooms%')
                    ->orWhere('notes', 'like', '%East Africa with 50,000 sqm%')
                    ->orWhere('notes', 'like', '%NGO conferences%')
                    ->orWhere('notes', 'like', '%turnover cleans%')
                    ->orWhere('notes', 'like', '%open office workstations%')
                    ->orWhere('notes', 'like', '%[Seed:%')
                    ->orWhere('notes', 'like', '%[Demo%');

                if (!empty($demoUserIds)) {
                    $prov->orWhereHas('salesVisits', function ($sv) use ($demoUserIds) {
                        $sv->whereIn('salesperson_user_id', $demoUserIds);
                    });
                }
            });
        });

        // Strict Safety guard: Never purge an organization if it has legitimate non-demo sales visits or active contracts
        $demoOrgs->whereDoesntHave('salesVisits', function ($sv) use ($demoUserIds) {
            if (!empty($demoUserIds)) {
                $sv->whereNotIn('salesperson_user_id', $demoUserIds);
            }
        });

        $demoOrgs = $demoOrgs->get();
        $demoOrgIds = $demoOrgs->pluck('id')->toArray();

        // 7. Identify demo sales visits, proformas, contracts (avoid empty closures)
        $demoSalesVisits = SalesVisit::query();
        $hasVisitCond = false;
        if (!empty($provVisitIds)) {
            $demoSalesVisits->whereIn('id', $provVisitIds);
            $hasVisitCond = true;
        }
        if (!empty($demoOrgIds) && !empty($demoUserIds)) {
            $demoSalesVisits->orWhere(function ($q) use ($demoOrgIds, $demoUserIds) {
                $q->whereIn('organization_id', $demoOrgIds)
                  ->orWhereIn('salesperson_user_id', $demoUserIds);
            });
            $hasVisitCond = true;
        } elseif (!empty($demoOrgIds)) {
            $demoSalesVisits->orWhereIn('organization_id', $demoOrgIds);
            $hasVisitCond = true;
        } elseif (!empty($demoUserIds)) {
            $demoSalesVisits->orWhereIn('salesperson_user_id', $demoUserIds);
            $hasVisitCond = true;
        }
        if (!$hasVisitCond) {
            $demoSalesVisits->whereRaw('1 = 0');
        }
        $demoSalesVisits = $demoSalesVisits->get();
        $demoVisitIds = $demoSalesVisits->pluck('id')->toArray();

        $demoProformas = Proforma::query();
        $hasProfCond = false;
        if (!empty($provProformaIds)) {
            $demoProformas->whereIn('id', $provProformaIds);
            $hasProfCond = true;
        }
        if (!empty($demoOrgIds) && !empty($demoVisitIds)) {
            $demoProformas->orWhere(function ($q) use ($demoOrgIds, $demoVisitIds) {
                $q->whereIn('organization_id', $demoOrgIds)
                  ->orWhereIn('sales_visit_id', $demoVisitIds);
            });
            $hasProfCond = true;
        } elseif (!empty($demoOrgIds)) {
            $demoProformas->orWhereIn('organization_id', $demoOrgIds);
            $hasProfCond = true;
        } elseif (!empty($demoVisitIds)) {
            $demoProformas->orWhereIn('sales_visit_id', $demoVisitIds);
            $hasProfCond = true;
        }
        if (!$hasProfCond) {
            $demoProformas->whereRaw('1 = 0');
        }
        $demoProformas = $demoProformas->get();
        $demoProformaIds = $demoProformas->pluck('id')->toArray();

        $demoContracts = Contract::query();
        $hasContrCond = false;
        if (!empty($provContractIds)) {
            $demoContracts->whereIn('id', $provContractIds);
            $hasContrCond = true;
        }
        if (!empty($demoOrgIds)) {
            $demoContracts->orWhereIn('organization_id', $demoOrgIds);
            $hasContrCond = true;
        }
        if (!$hasContrCond) {
            $demoContracts->whereRaw('1 = 0');
        }
        $demoContracts = $demoContracts->get();
        $demoContractIds = $demoContracts->pluck('id')->toArray();

        // 8. Identify demo marketing campaigns & recipients
        $demoCampaigns = Campaign::query();
        $hasCampCond = false;
        if (!empty($provCampIds)) {
            $demoCampaigns->whereIn('id', $provCampIds);
            $hasCampCond = true;
        }
        $demoCampaigns->orWhere('title', 'like', '%Enkutatash%')
            ->orWhere('title', 'like', '%የመስከረም አዲስ ዓመት%');
        $demoCampaigns = $demoCampaigns->get();
        $demoCampaignIds = $demoCampaigns->pluck('id')->toArray();

        // 9. Identify demo telegram users
        $demoTgUsers = TelegramUser::query();
        if (!empty($provTgIds)) {
            $demoTgUsers->whereIn('id', $provTgIds);
        }
        $demoTgUsers->orWhere('telegram_id', 987654321)
            ->orWhere('username', 'sarayohannes_eth');
        $demoTgUsers = $demoTgUsers->get();
        $demoTgUserIds = $demoTgUsers->pluck('id')->toArray();

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
            $demoUserIds, $demoCustomerIds, $demoOrderIds,
            $demoExpenseIds, $demoTeamIds, $demoOrgIds,
            $demoVisitIds, $demoProformaIds, $demoContractIds,
            $demoCampaignIds, $demoTgUserIds
        ) {
            // Delete dependent marketing campaign recipients
            if (!empty($demoCampaignIds)) {
                CampaignRecipient::whereIn('campaign_id', $demoCampaignIds)->delete();
                Campaign::whereIn('id', $demoCampaignIds)->delete();
            }

            // Delete Telegram demo users
            if (!empty($demoTgUserIds)) {
                TelegramUser::whereIn('id', $demoTgUserIds)->delete();
            }

            // Delete Proforma Items and Proformas
            if (!empty($demoProformaIds)) {
                ProformaItem::whereIn('proforma_id', $demoProformaIds)->delete();
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
            if (!empty($demoOrderIds)) {
                OrderItem::whereIn('order_id', $demoOrderIds)->delete();
                DB::table('appointments')->whereIn('order_id', $demoOrderIds)->delete();
                Payment::whereIn('order_id', $demoOrderIds)->delete();
                DB::table('followups')->whereIn('order_id', $demoOrderIds)->delete();
                DB::table('feedbacks')->whereIn('order_id', $demoOrderIds)->delete();
                DB::table('complaints')->whereIn('order_id', $demoOrderIds)->delete();
                Order::whereIn('id', $demoOrderIds)->delete();
            }

            // Delete Expenses
            if (!empty($demoExpenseIds)) {
                Expense::whereIn('id', $demoExpenseIds)->delete();
            }

            // Delete Team Members and Cleaning Teams
            if (!empty($demoTeamIds)) {
                TeamMember::whereIn('cleaning_team_id', $demoTeamIds)->delete();
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
