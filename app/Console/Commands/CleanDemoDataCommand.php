<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
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
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDemoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meash:clean-demo-data
                            {--dry-run : Only inspect and report non-production demo records that would be purged}
                            {--force : Force the operation without manual interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and permanently purge fictional demo users, orders, customers, and expenses from the database';

    /**
     * Demo user emails seeded in development.
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
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning database for non-production demo records...');

        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        // Identify demo users
        $demoUsers = User::whereIn('email', $this->demoEmails)
            ->orWhere('name', 'like', '%Demo%')
            ->get();
        $demoUserIds = $demoUsers->pluck('id')->toArray();

        // Identify demo customers (seeded phone numbers or explicitly matching seeder data)
        $demoCustomerPhones = [
            '0911223344', '0912445566', '0911556677', '0913889900',
            '0911122334', '0914223344', '0911334455', '0915667788',
            '0911778899', '0916990011', '0911445566', '0912889900',
        ];
        $demoCustomers = Customer::whereIn('phone', $demoCustomerPhones)
            ->orWhere('full_name', 'like', '%Demo%')
            ->get();
        $demoCustomerIds = $demoCustomers->pluck('id')->toArray();

        // Identify demo orders (belonging to demo customers or created by demo users)
        $demoOrders = Order::whereIn('customer_id', $demoCustomerIds)
            ->orWhereIn('created_by_user_id', $demoUserIds)
            ->get();
        $demoOrderIds = $demoOrders->pluck('id')->toArray();

        // Identify demo expenses (recorded by demo users or matching seeder refs)
        $demoExpenseRefs = [
            'REC-CHEM-901', 'TOTAL-B-441', 'OIL-CMC-120', 'REC-MAT-309',
            'PAY-WK-38', 'REP-VAC-11', 'MKT-TG-08',
        ];
        $demoExpenses = Expense::whereIn('entered_by_user_id', $demoUserIds)
            ->orWhereIn('reference_number', $demoExpenseRefs)
            ->get();
        $demoExpenseIds = $demoExpenses->pluck('id')->toArray();

        // Identify demo cleaning teams
        $demoTeams = CleaningTeam::whereIn('team_leader_id', $demoUserIds)->get();
        $demoTeamIds = $demoTeams->pluck('id')->toArray();

        $summaryTable = [
            ['Demo Users', count($demoUserIds)],
            ['Demo Customers', count($demoCustomerIds)],
            ['Demo Orders', count($demoOrderIds)],
            ['Demo Expenses', count($demoExpenseIds)],
            ['Demo Teams', count($demoTeamIds)],
        ];

        $this->table(['Entity', 'Records Found'], $summaryTable);

        if (count($demoUserIds) === 0 && count($demoCustomerIds) === 0 && count($demoOrderIds) === 0 && count($demoExpenseIds) === 0) {
            $this->info('No demo records found. Database is clean of development seed artifacts.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No records were modified or deleted.');
            return Command::SUCCESS;
        }

        if (! $isForce) {
            if (! $this->confirm('WARNING: Are you sure you want to permanently purge all identified demo records? This action is irreversible.')) {
                $this->info('Operation cancelled by user.');
                return Command::SUCCESS;
            }
        }

        $this->info('Executing atomic database purge of demo records...');

        DB::transaction(function () use (
            $demoUserIds,
            $demoCustomerIds,
            $demoOrderIds,
            $demoExpenseIds,
            $demoTeamIds
        ) {
            // Delete child order items
            if (! empty($demoOrderIds)) {
                OrderItem::whereIn('order_id', $demoOrderIds)->delete();
                Payment::whereIn('order_id', $demoOrderIds)->delete();
                Appointment::whereIn('order_id', $demoOrderIds)->delete();
                Order::whereIn('id', $demoOrderIds)->delete();
            }

            // Delete appointments, feedback, complaints, followups for demo customers
            if (! empty($demoCustomerIds)) {
                Appointment::whereIn('customer_id', $demoCustomerIds)->delete();
                Payment::whereIn('customer_id', $demoCustomerIds)->delete();
                Feedback::whereIn('customer_id', $demoCustomerIds)->delete();
                Complaint::whereIn('customer_id', $demoCustomerIds)->delete();
                Followup::whereIn('customer_id', $demoCustomerIds)->delete();
                Customer::whereIn('id', $demoCustomerIds)->delete();
            }

            // Delete demo expenses
            if (! empty($demoExpenseIds)) {
                Expense::whereIn('id', $demoExpenseIds)->delete();
            }

            // Delete team members & teams
            if (! empty($demoTeamIds)) {
                TeamMember::whereIn('cleaning_team_id', $demoTeamIds)->delete();
                CleaningTeam::whereIn('id', $demoTeamIds)->delete();
            }
            if (! empty($demoUserIds)) {
                TeamMember::whereIn('user_id', $demoUserIds)->delete();
            }

            // Delete demo users
            if (! empty($demoUserIds)) {
                User::whereIn('id', $demoUserIds)->delete();
            }
        });

        $this->info('Demo records successfully purged. Production database integrity preserved.');

        return Command::SUCCESS;
    }
}
