<?php

namespace Database\Seeders;

use App\Models\Appointment;
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
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\TelegramUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. CORE USERS ACROSS ALL ROLES
        // ==========================================
        $owner = User::create([
            'name' => 'Meash General Manager',
            'email' => 'owner@meash.com',
            'phone' => '0911000001',
            'role' => 'owner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $reception = User::create([
            'name' => 'Bethlehem Tadesse',
            'email' => 'reception@meash.com',
            'phone' => '0911000002',
            'role' => 'reception',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $cleaner1 = User::create([
            'name' => 'Solomon Kebede',
            'email' => 'cleaner1@meash.com',
            'phone' => '0911000003',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $cleaner2 = User::create([
            'name' => 'Almaz Haile',
            'email' => 'cleaner2@meash.com',
            'phone' => '0911000004',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $cleaner3 = User::create([
            'name' => 'Dawit Haile',
            'email' => 'cleaner3@meash.com',
            'phone' => '0911000006',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $cleaner4 = User::create([
            'name' => 'Tariku Bekele',
            'email' => 'cleaner4@meash.com',
            'phone' => '0911000007',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $sales = User::create([
            'name' => 'Daniel Girma',
            'email' => 'sales@meash.com',
            'phone' => '0911000005',
            'role' => 'sales',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $finance = User::create([
            'name' => 'Yonas Tesfaye',
            'email' => 'finance@meash.com',
            'phone' => '0911000008',
            'role' => 'owner',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // ==========================================
        // 2. CLEANING TEAMS & VEHICLE DISPATCH
        // ==========================================
        $teamAlpha = CleaningTeam::create([
            'team_name' => 'Team Alpha (Upholstery & Carpet)',
            'team_leader_id' => $cleaner1->id,
            'phone' => '0911000003',
            'vehicle_plate' => 'Code 3 A/A 45892',
            'status' => 'active',
            'notes' => 'Equipped with heavy steam extraction machine, 100m extension cables, and organic fabric shampoos.',
        ]);

        TeamMember::create(['cleaning_team_id' => $teamAlpha->id, 'user_id' => $cleaner1->id, 'role_in_team' => 'leader']);
        TeamMember::create(['cleaning_team_id' => $teamAlpha->id, 'user_id' => $cleaner3->id, 'role_in_team' => 'technician']);

        $teamBravo = CleaningTeam::create([
            'team_name' => 'Team Bravo (Rotary Scrub & Mattress)',
            'team_leader_id' => $cleaner2->id,
            'phone' => '0911000004',
            'vehicle_plate' => 'Code 2 A/A 12903',
            'status' => 'active',
            'notes' => 'Specialized in high-pressure rotary floor scrubbing, stain chemistry, and mattress sanitization.',
        ]);

        TeamMember::create(['cleaning_team_id' => $teamBravo->id, 'user_id' => $cleaner2->id, 'role_in_team' => 'leader']);

        $teamDelta = CleaningTeam::create([
            'team_name' => 'Team Delta (Facade & Post-Construction)',
            'team_leader_id' => $cleaner4->id,
            'phone' => '0911000007',
            'vehicle_plate' => 'Code 3 A/A 88231',
            'status' => 'active',
            'notes' => 'Equipped with window washing extension poles, industrial vacuum units, and scaffolding harnesses.',
        ]);

        TeamMember::create(['cleaning_team_id' => $teamDelta->id, 'user_id' => $cleaner4->id, 'role_in_team' => 'leader']);

        // ==========================================
        // 3. SERVICE CATALOG (Bilingual)
        // ==========================================
        $servicesData = [
            [
                'code' => 'sofa',
                'name_en' => 'Sofa & Couch Deep Cleaning',
                'name_am' => 'የሶፋ ጥልቅ እጥበት እና ፅዳት',
                'description_en' => 'Deep shampoo extraction, stain removal, fabric conditioning and odor neutralization.',
                'description_am' => 'የቆሸሹ ሶፋዎችን በዘመናዊ ማሽን እና ኬሚካል ማጠብ፤ ነጠብጣብ ማስወገድ እና ማሽተት መከላከል::',
                'icon' => 'sofa',
                'base_price' => 350.00,
                'unit' => 'seat',
                'sort_order' => 1,
            ],
            [
                'code' => 'carpet',
                'name_en' => 'Carpet & Rug Washing',
                'name_am' => 'የምንጣፍ እጥበት እና ፅዳት',
                'description_en' => 'Deep rotary scrub, high pressure suction, dust mite elimination and fast drying.',
                'description_am' => 'ምንጣፍዎን ካሉበት ቦታ ድረስ በመምጣት ወይም ወስደን በዘመናዊ ቴክኖሎጂ አጥበን እናደርቃለን::',
                'icon' => 'sparkles',
                'base_price' => 80.00,
                'unit' => 'sqm',
                'sort_order' => 2,
            ],
            [
                'code' => 'mattress',
                'name_en' => 'Mattress Sanitization',
                'name_am' => 'የፍራሽ ጥልቅ እጥበት',
                'description_en' => 'Anti-bacterial steam cleaning, dust mite extermination and fabric renewal.',
                'description_am' => 'ፍራሽ ላይ ያሉ ጀርሞችን፤ አለርጂ አምጪ ተህዋሲያንን እና እድፎችን በስቲም ማስወገድ::',
                'icon' => 'bed',
                'base_price' => 600.00,
                'unit' => 'piece',
                'sort_order' => 3,
            ],
            [
                'code' => 'glass',
                'name_en' => 'Glass & Window Facade Cleaning',
                'name_am' => 'የመስታወት እና የፎቅ መስታወት ፅዳት',
                'description_en' => 'Streak-free window washing for residential homes, villas, and commercial buildings.',
                'description_am' => 'የቤት እና የህንፃ መስታወቶችን ከውጭ እና ከውስጥ ንፁህ እና ብሩህ አድርጎ ማፅዳት::',
                'icon' => 'sun',
                'base_price' => 70.00,
                'unit' => 'sqm',
                'sort_order' => 4,
            ],
            [
                'code' => 'home',
                'name_en' => 'Residential Post-Construction & Move-in Clean',
                'name_am' => 'የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት',
                'description_en' => 'Comprehensive top-to-bottom villa/apartment cleaning including floors, tiles, kitchen, and bathrooms.',
                'description_am' => 'የግንባታ ማጠናቀቂያ፤ የኪራይ መቀየሪያ ወይም መደበኛ የመኖሪያ ቤት ጥልቅ ፅዳት::',
                'icon' => 'home',
                'base_price' => 2500.00,
                'unit' => 'service',
                'sort_order' => 5,
            ],
            [
                'code' => 'office',
                'name_en' => 'Commercial & Office Cleaning',
                'name_am' => 'የቢሮ እና ተቋማት ፅዳት',
                'description_en' => 'Professional office workstations, carpet scrubbing, restrooms, and executive areas.',
                'description_am' => 'የድርጅቶች፤ ባንኮች፤ ኤምባሲዎች እና ሆቴሎች አስተማማኝ የኮንትራት ወይም የጊዜያዊ ፅዳት አገልግሎት::',
                'icon' => 'briefcase',
                'base_price' => 3500.00,
                'unit' => 'service',
                'sort_order' => 6,
            ],
            [
                'code' => 'curtain',
                'name_en' => 'Curtains & Drapes Steam Cleaning',
                'name_am' => 'የመጋረጃ ስቲም እጥበት',
                'description_en' => 'On-site steam cleaning without unhanging the curtains, dust removal and freshening.',
                'description_am' => 'መጋረጃውን ሳያወርዱ በቦታው ላይ በስቲም ማጠብ እና አቧራ ማስወገድ::',
                'icon' => 'wind',
                'base_price' => 120.00,
                'unit' => 'piece',
                'sort_order' => 7,
            ],
        ];

        $services = [];
        foreach ($servicesData as $sData) {
            $services[$sData['code']] = Service::create($sData);
        }

        // ==========================================
        // 4. REALISTIC CUSTOMERS ACROSS ADDIS ABABA
        // ==========================================
        $customersData = [
            [
                'full_name' => 'Abebe Kebede',
                'phone' => '0911223344',
                'address' => 'Bole Medhanialem, Behind Edna Mall',
                'subcity' => 'Bole',
                'landmark' => 'Near Morning Star Mall',
                'type' => 'individual',
                'notes' => 'VIP residential client. Has 7-seat cream leather sofa and wool carpets.',
            ],
            [
                'full_name' => 'Sara Yohannes',
                'phone' => '0912445566',
                'address' => 'CMC Michael, Real Estate Compound',
                'subcity' => 'Yeka',
                'landmark' => 'Next to St. Michael Church',
                'type' => 'individual',
                'notes' => 'Prefers afternoon appointments. Always books via Telegram.',
            ],
            [
                'full_name' => 'Dr. Tewodros Kassahun',
                'phone' => '0911556677',
                'address' => 'Old Airport, Near Bisrate Gabriel',
                'subcity' => 'Lideta',
                'landmark' => 'Behind International Community School',
                'type' => 'individual',
                'notes' => 'Requires hypo-allergenic chemical shampoo due to dust sensitivity.',
            ],
            [
                'full_name' => 'W/ro Aster Mamo',
                'phone' => '0913889900',
                'address' => 'Kazanchis, Near UNECA Complex',
                'subcity' => 'Kirkos',
                'landmark' => 'Opposite Inter Luxury Hotel',
                'type' => 'individual',
                'notes' => 'Regular quarterly customer for carpet and sofa steam cleaning.',
            ],
            [
                'full_name' => 'Ato Yonas Tadesse',
                'phone' => '0911122334',
                'address' => 'Sarbet, Behind Canadian Embassy',
                'subcity' => 'Nifas Silk-Lafto',
                'landmark' => 'Near Vatican Embassy Roundabout',
                'type' => 'individual',
                'notes' => 'Large duplex villa with extensive glass facades.',
            ],
            [
                'full_name' => 'Selamawit Bekele',
                'phone' => '0914223344',
                'address' => 'Piassa, Churchill Road',
                'subcity' => 'Arada',
                'landmark' => 'Near Eliana Hotel',
                'type' => 'individual',
                'notes' => 'Booked post-renovation apartment deep cleaning.',
            ],
            [
                'full_name' => 'Henok Assefa',
                'phone' => '0911334455',
                'address' => 'Gerji Imperial, Mebrat Hayel',
                'subcity' => 'Bole',
                'landmark' => 'Near Unity University Main Gate',
                'type' => 'individual',
                'notes' => 'Multiple living room rugs and dining chairs.',
            ],
            [
                'full_name' => 'Martha Hailu',
                'phone' => '0915667788',
                'address' => 'Megenagna, Lem Hotel Area',
                'subcity' => 'Yeka',
                'landmark' => 'Behind Zefmesh Grand Mall',
                'type' => 'individual',
                'notes' => 'Requested weekend morning cleaning.',
            ],
            [
                'full_name' => 'Dawit Wolde',
                'phone' => '0911778899',
                'address' => 'Gotera Condominium, Block 24',
                'subcity' => 'Kirkos',
                'landmark' => 'Near Gotera Interchange',
                'type' => 'individual',
                'notes' => 'Mattress and L-shape sofa steam shampoo.',
            ],
            [
                'full_name' => 'Bethlehem Tesfaye',
                'phone' => '0916990011',
                'address' => 'Jemo 1 Real Estate',
                'subcity' => 'Nifas Silk-Lafto',
                'landmark' => 'Near Jemo Michael Square',
                'type' => 'individual',
                'notes' => 'Referred by neighbor. Highly satisfied with first service.',
            ],
            [
                'full_name' => 'Kaleb Tsegaye',
                'phone' => '0911445566',
                'address' => 'Ayat Zone 3, Near Taxi Station',
                'subcity' => 'Bole',
                'landmark' => 'Near Ayat Real Estate Gate 2',
                'type' => 'individual',
                'notes' => 'Annual carpet washing before holiday celebration.',
            ],
            [
                'full_name' => 'Rahel Getachew',
                'phone' => '0912889900',
                'address' => 'Summit Condominium, Site 2',
                'subcity' => 'Bole',
                'landmark' => 'Near Pepsi Factory',
                'type' => 'individual',
                'notes' => '3 King mattresses and bedroom curtains.',
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::create([
                'customer_code' => Customer::generateNextCode(),
                'full_name' => $c['full_name'],
                'phone' => $c['phone'],
                'address' => $c['address'],
                'subcity' => $c['subcity'],
                'landmark' => $c['landmark'],
                'customer_type' => $c['type'],
                'preferred_contact_method' => 'phone',
                'marketing_consent' => true,
                'consent_timestamp' => now(),
                'notes' => $c['notes'],
            ]);
        }

        // ==========================================
        // 5. SAMPLE ORDERS, ITEMS & APPOINTMENTS
        // ==========================================

        // Order 1: Abebe Kebede - Today, Assigned to Alpha, Cleaning In-Progress
        $order1 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[0]->id,
            'created_by_user_id' => $reception->id,
            'assigned_team_id' => $teamAlpha->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'partially_paid',
            'subtotal' => 0,
            'discount' => 100,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::today(),
            'appointment_time_slot' => '09:00 - 12:00',
            'address' => $customers[0]->address,
            'subcity' => $customers[0]->subcity,
            'notes' => 'Please bring heavy-duty steam extractor for sofa.',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'service_id' => $services['sofa']->id,
            'item_name' => 'L-Shape Fabric Sofa (7 Seats)',
            'quantity' => 7,
            'unit_price' => 350.00,
            'subtotal' => 2450.00,
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'service_id' => $services['carpet']->id,
            'item_name' => 'Living Room Wool Carpet (4x3m)',
            'quantity' => 12,
            'unit_price' => 80.00,
            'subtotal' => 960.00,
        ]);

        Appointment::create([
            'order_id' => $order1->id,
            'customer_id' => $customers[0]->id,
            'cleaning_team_id' => $teamAlpha->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'in_progress',
        ]);

        Payment::create([
            'payment_number' => Payment::generateNextNumber(),
            'order_id' => $order1->id,
            'customer_id' => $customers[0]->id,
            'amount' => 1500.00,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-92817264',
            'payment_date' => Carbon::today(),
            'recorded_by_user_id' => $reception->id,
            'notes' => 'Advance payment via Telebirr',
        ]);

        // Order 2: Sara Yohannes - Yesterday, Completed by Bravo, Paid
        $order2 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[1]->id,
            'created_by_user_id' => $reception->id,
            'assigned_team_id' => $teamBravo->id,
            'source' => 'website',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::yesterday(),
            'appointment_time_slot' => '14:00 - 17:00',
            'address' => $customers[1]->address,
            'subcity' => $customers[1]->subcity,
            'completed_at' => Carbon::yesterday()->setHour(16)->setMinute(30),
            'completion_notes' => 'Customer was very satisfied with the mattress steam disinfection.',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'service_id' => $services['mattress']->id,
            'item_name' => 'King Size Master Bed Mattress',
            'quantity' => 2,
            'unit_price' => 600.00,
            'subtotal' => 1200.00,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'service_id' => $services['glass']->id,
            'item_name' => 'Balcony & Living Room Windows',
            'quantity' => 15,
            'unit_price' => 70.00,
            'subtotal' => 1050.00,
        ]);

        Appointment::create([
            'order_id' => $order2->id,
            'customer_id' => $customers[1]->id,
            'cleaning_team_id' => $teamBravo->id,
            'appointment_date' => Carbon::yesterday(),
            'start_time' => '14:00',
            'end_time' => '17:00',
            'status' => 'completed',
        ]);

        Payment::create([
            'payment_number' => Payment::generateNextNumber(),
            'order_id' => $order2->id,
            'customer_id' => $customers[1]->id,
            'amount' => 2250.00,
            'payment_method' => 'cbe_birr',
            'reference_number' => 'CBE-8874129',
            'payment_date' => Carbon::yesterday(),
            'recorded_by_user_id' => $reception->id,
            'notes' => 'Full payment settled via CBE Birr',
        ]);

        // Order 3: Dr. Tewodros Kassahun - Today, Afternoon Assigned to Team Delta
        $order3 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[2]->id,
            'created_by_user_id' => $reception->id,
            'assigned_team_id' => $teamDelta->id,
            'source' => 'phone',
            'order_status' => 'assigned',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::today(),
            'appointment_time_slot' => '14:00 - 17:00',
            'address' => $customers[2]->address,
            'subcity' => $customers[2]->subcity,
            'notes' => 'Doctor requested hypoallergenic disinfectant for curtains and carpets.',
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'service_id' => $services['curtain']->id,
            'item_name' => 'Velvet Living Room Drapes (Steam clean)',
            'quantity' => 6,
            'unit_price' => 120.00,
            'subtotal' => 720.00,
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'service_id' => $services['carpet']->id,
            'item_name' => 'Master Bedroom Turkish Carpet',
            'quantity' => 16,
            'unit_price' => 80.00,
            'subtotal' => 1280.00,
        ]);

        Appointment::create([
            'order_id' => $order3->id,
            'customer_id' => $customers[2]->id,
            'cleaning_team_id' => $teamDelta->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '14:00',
            'end_time' => '17:00',
            'status' => 'scheduled',
        ]);

        // Order 4: W/ro Aster Mamo - Pending Confirmation (New Web Booking)
        $order4 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[3]->id,
            'source' => 'website',
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::tomorrow(),
            'appointment_time_slot' => '09:00 - 12:00',
            'address' => $customers[3]->address,
            'subcity' => $customers[3]->subcity,
            'notes' => 'New online booking from website wizard. Needs confirmation call.',
        ]);

        OrderItem::create([
            'order_id' => $order4->id,
            'service_id' => $services['sofa']->id,
            'item_name' => 'Living Room Sofa (5 Seats)',
            'quantity' => 5,
            'unit_price' => 350.00,
            'subtotal' => 1750.00,
        ]);

        // Order 5: Ato Yonas Tadesse - Completed 3 days ago, Full Villa Cleaning
        $order5 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[4]->id,
            'created_by_user_id' => $reception->id,
            'assigned_team_id' => $teamAlpha->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 0,
            'discount' => 200,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::today()->subDays(3),
            'appointment_time_slot' => '09:00 - 17:00',
            'address' => $customers[4]->address,
            'subcity' => $customers[4]->subcity,
            'completed_at' => Carbon::today()->subDays(3)->setHour(17)->setMinute(0),
        ]);

        OrderItem::create([
            'order_id' => $order5->id,
            'service_id' => $services['home']->id,
            'item_name' => 'Two-Story Villa Deep Move-in Cleaning',
            'quantity' => 1,
            'unit_price' => 4500.00,
            'subtotal' => 4500.00,
        ]);
        OrderItem::create([
            'order_id' => $order5->id,
            'service_id' => $services['glass']->id,
            'item_name' => 'Full Perimeter Window Facades',
            'quantity' => 30,
            'unit_price' => 70.00,
            'subtotal' => 2100.00,
        ]);

        Payment::create([
            'payment_number' => Payment::generateNextNumber(),
            'order_id' => $order5->id,
            'customer_id' => $customers[4]->id,
            'amount' => 6400.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CBE-TX-9938102',
            'payment_date' => Carbon::today()->subDays(3),
            'recorded_by_user_id' => $reception->id,
            'notes' => 'Commercial Bank of Ethiopia direct mobile transfer',
        ]);

        // Order 6: Selamawit Bekele - Telegram Mini App Booking (Pending Dispatch)
        $order6 = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customers[5]->id,
            'source' => 'telegram',
            'order_status' => 'confirmed',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => Carbon::today()->addDays(2),
            'appointment_time_slot' => '10:00 - 13:00',
            'address' => $customers[5]->address,
            'subcity' => $customers[5]->subcity,
            'notes' => 'Booked via Telegram Mini App (@meash_cleaning_solution_bot).',
        ]);

        OrderItem::create([
            'order_id' => $order6->id,
            'service_id' => $services['carpet']->id,
            'item_name' => 'Office Reception Carpet (8x4m)',
            'quantity' => 32,
            'unit_price' => 80.00,
            'subtotal' => 2560.00,
        ]);

        // ==========================================
        // 6. BUSINESS EXPENSES (Itemized Cost Accounting)
        // ==========================================
        $expensesData = [
            [
                'category' => 'chemicals',
                'amount' => 2800.00,
                'ref' => 'REC-CHEM-901',
                'desc' => 'Purchased 40L industrial sofa extraction shampoo and stain spotters from Merkato chemical dealer.',
                'days_ago' => 0,
            ],
            [
                'category' => 'fuel',
                'amount' => 1200.00,
                'ref' => 'TOTAL-B-441',
                'desc' => 'Diesel refill for Team Alpha Toyota HiAce Van (TotalEnergies Bole).',
                'days_ago' => 0,
            ],
            [
                'category' => 'fuel',
                'amount' => 950.00,
                'ref' => 'OIL-CMC-120',
                'desc' => 'Vehicle fuel for Team Bravo van (OiLibya CMC station).',
                'days_ago' => 1,
            ],
            [
                'category' => 'materials',
                'amount' => 1400.00,
                'ref' => 'REC-MAT-309',
                'desc' => '12 packs high-density microfiber cleaning cloths, spray bottles, and safety rubber gloves.',
                'days_ago' => 2,
            ],
            [
                'category' => 'employee_payments',
                'amount' => 6500.00,
                'ref' => 'PAY-WK-38',
                'desc' => 'Weekly field technicians performance incentive and daily lunch allowance.',
                'days_ago' => 4,
            ],
            [
                'category' => 'equipment_repair',
                'amount' => 850.00,
                'ref' => 'REP-VAC-11',
                'desc' => 'High-pressure vacuum brass coupler replacement and hose re-crimping.',
                'days_ago' => 5,
            ],
            [
                'category' => 'marketing',
                'amount' => 1500.00,
                'ref' => 'MKT-TG-08',
                'desc' => 'Telegram channel promotional broadcast and SMS campaign gateway credits.',
                'days_ago' => 6,
            ],
        ];

        foreach ($expensesData as $exp) {
            Expense::create([
                'expense_number' => Expense::generateNextNumber(),
                'category' => $exp['category'],
                'amount' => $exp['amount'],
                'reference_number' => $exp['ref'],
                'description' => $exp['desc'],
                'date' => Carbon::today()->subDays($exp['days_ago']),
                'entered_by_user_id' => $reception->id,
            ]);
        }

        // ==========================================
        // 7. OUTDOOR SALES CRM & CORPORATE ACCOUNTS
        // ==========================================
        $skylight = Organization::create([
            'org_code' => Organization::generateNextCode(),
            'name' => 'Ethiopian Skylight Hotel',
            'industry' => 'hotel',
            'address' => 'Airport Road, Bole',
            'phone' => '0116888000',
            'email' => 'housekeeping@ethiopianskylighthotel.com',
            'notes' => 'Five-star hotel with 1,024 luxury guest rooms, grand ballrooms, and 5 restaurants.',
        ]);

        $cbeTower = Organization::create([
            'org_code' => Organization::generateNextCode(),
            'name' => 'Commercial Bank of Ethiopia HQ',
            'industry' => 'bank',
            'address' => 'Churchill Avenue, CBE Tower (53 floors)',
            'phone' => '0115515004',
            'email' => 'facilities@cbe.com.et',
            'notes' => 'Tallest building in East Africa with 50,000 sqm of curtain glass facade and executive carpets.',
        ]);

        $hyatt = Organization::create([
            'org_code' => Organization::generateNextCode(),
            'name' => 'Hyatt Regency Addis Ababa',
            'industry' => 'hotel',
            'address' => 'Meskel Square, Central Addis',
            'phone' => '0115171234',
            'email' => 'operations@hyattaddis.com',
            'notes' => 'Central luxury hotel hosting diplomatic and international NGO conferences.',
        ]);

        $novis = Organization::create([
            'org_code' => Organization::generateNextCode(),
            'name' => 'Novis Real Estate Compound',
            'industry' => 'real_estate',
            'address' => 'CMC St. Michael Road',
            'phone' => '0911505050',
            'email' => 'info@novisethiopia.com',
            'notes' => 'Premium gated villa community with 80 residential units needing turnover cleans.',
        ]);

        $zemen = Organization::create([
            'org_code' => Organization::generateNextCode(),
            'name' => 'Zemen Bank HQ',
            'industry' => 'bank',
            'address' => 'Ras Abebe Aregay Street, Financial District',
            'phone' => '0115501111',
            'email' => 'procurement@zemenbank.com',
            'notes' => 'Modern high-tech headquarters with marble floors and open office workstations.',
        ]);

        // Sales Visits across diverse pipeline stages
        $visitSkylight = SalesVisit::create([
            'visit_code' => SalesVisit::generateNextCode(),
            'organization_id' => $skylight->id,
            'contact_person' => 'Daniel Assefa',
            'contact_position' => 'Executive Housekeeping Director',
            'phone' => '0911887766',
            'address' => 'Airport Road, Bole',
            'services_introduced' => 'Grand Ballroom Carpet Restoration & Deep Steam Extraction',
            'visit_purpose' => 'Annual conference hall maintenance proposal',
            'interest_level' => 'high',
            'salesperson_user_id' => $sales->id,
            'visit_date' => Carbon::today()->subDays(3),
            'stage' => 'negotiation',
            'notes' => 'Client liked our trial demonstration on 50 sqm of ballroom carpet. Reviewing contract terms.',
            'next_followup_date' => Carbon::today()->addDays(1),
        ]);

        $proformaSkylight = Proforma::create([
            'proforma_number' => Proforma::generateNextNumber(),
            'organization_id' => $skylight->id,
            'sales_visit_id' => $visitSkylight->id,
            'prepared_by_user_id' => $sales->id,
            'subtotal' => 0,
            'discount' => 5000.00,
            'tax' => 15000.00,
            'total' => 0,
            'validity_date' => Carbon::today()->addDays(30),
            'status' => 'sent',
            'notes' => 'Heavy rotary scrub + hot water extraction + microbial deodorizer for 1,500 sqm.',
        ]);

        ProformaItem::create([
            'proforma_id' => $proformaSkylight->id,
            'service_id' => $services['carpet']->id,
            'description' => 'Skylight Grand Ballroom Wool Carpet Restoration (1,500 sqm)',
            'quantity' => 1500,
            'unit_price' => 70.00,
            'subtotal' => 105000.00,
        ]);

        // CBE Tower - WON CONTRACT
        $visitCbe = SalesVisit::create([
            'visit_code' => SalesVisit::generateNextCode(),
            'organization_id' => $cbeTower->id,
            'contact_person' => 'Ato Mulatu Gizaw',
            'contact_position' => 'Head of Corporate Facilities',
            'phone' => '0911776655',
            'address' => 'CBE Tower, Churchill Road',
            'services_introduced' => 'Executive Floors Carpet Care & Exterior Window Facades',
            'visit_purpose' => 'Quarterly maintenance agreement',
            'interest_level' => 'high',
            'salesperson_user_id' => $sales->id,
            'visit_date' => Carbon::today()->subDays(10),
            'stage' => 'won',
            'notes' => 'Contract signed! Annual quarterly maintenance for 4 executive boardroom floors.',
            'next_followup_date' => Carbon::today()->addDays(15),
        ]);

        Contract::create([
            'contract_number' => Contract::generateNextNumber(),
            'organization_id' => $cbeTower->id,
            'proforma_id' => null,
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addYear(),
            'agreed_value' => 240000.00,
            'cleaning_frequency' => 'quarterly',
            'terms' => 'Quarterly deep cleaning of 4 boardroom carpets and executive glass partition panels. Authorized by Ato Mulatu Gizaw.',
            'status' => 'active',
            'responsible_person_id' => $owner->id,
        ]);

        // Hyatt Regency - Proforma Requested
        SalesVisit::create([
            'visit_code' => SalesVisit::generateNextCode(),
            'organization_id' => $hyatt->id,
            'contact_person' => 'W/rt Helen Bekele',
            'contact_position' => 'Banquet & Events Manager',
            'phone' => '0912334455',
            'address' => 'Meskel Square',
            'services_introduced' => 'Banquet chairs fabric steam extraction',
            'visit_purpose' => 'Post-festival conference hall sanitization',
            'interest_level' => 'medium',
            'salesperson_user_id' => $sales->id,
            'visit_date' => Carbon::today()->subDays(1),
            'stage' => 'proforma_requested',
            'notes' => 'Requested formal quote for 300 banquet dining chairs before Friday.',
            'next_followup_date' => Carbon::today()->addDays(2),
        ]);

        // Novis Real Estate - New Qualified Lead
        SalesVisit::create([
            'visit_code' => SalesVisit::generateNextCode(),
            'organization_id' => $novis->id,
            'contact_person' => 'Engineer Brook Lemma',
            'contact_position' => 'Project Site Director',
            'phone' => '0911998877',
            'address' => 'CMC St. Michael',
            'services_introduced' => 'Post-construction turnover deep clean for 12 new luxury villas',
            'visit_purpose' => 'Bulk project turnover partnership',
            'interest_level' => 'high',
            'salesperson_user_id' => $sales->id,
            'visit_date' => Carbon::today(),
            'stage' => 'visited',
            'notes' => 'Inspected villa site. First 4 villas will be ready for turnover cleaning next week.',
            'next_followup_date' => Carbon::today()->addDays(3),
        ]);

        // ==========================================
        // 8. CUSTOMER CARE (Follow-ups & Feedback)
        // ==========================================

        // Completed Follow-up with 5-Star Testimonial (Sara Yohannes)
        Followup::create([
            'order_id' => $order2->id,
            'customer_id' => $customers[1]->id,
            'due_date' => Carbon::today(),
            'status' => 'completed',
            'outcome' => 'satisfied',
            'notes' => 'Sara stated her mattresses feel brand new, and praised the cleaners for their polite behavior and clean shoe covers.',
            'handled_by_user_id' => $reception->id,
            'completed_at' => Carbon::today()->subHours(2),
        ]);

        Feedback::create([
            'order_id' => $order2->id,
            'customer_id' => $customers[1]->id,
            'rating' => 5,
            'comment' => 'Excellent service! The team arrived right on time in CMC and my mattresses and windows look spotless. Will definitely call Meash again!',
            'source' => 'telegram',
        ]);

        // Due Follow-up for Today (Abebe Kebede)
        Followup::create([
            'order_id' => $order1->id,
            'customer_id' => $customers[0]->id,
            'due_date' => Carbon::today(),
            'status' => 'pending',
            'notes' => 'Call customer this afternoon to confirm sofa drying quality and overall satisfaction.',
        ]);

        // Resolved Complaint with Audit Trail
        Complaint::create([
            'complaint_number' => Complaint::generateNextNumber(),
            'order_id' => $order5->id,
            'customer_id' => $customers[4]->id,
            'category' => 'service_quality',
            'priority' => 'low',
            'description' => 'Ato Yonas noted that the upper corner of the second floor corridor glass had a slight water smudge.',
            'status' => 'resolved',
            'assigned_to_user_id' => $owner->id,
            'resolution_notes' => 'Team leader Solomon returned within 2 hours with extension pole and buffed the pane crystal clear. Customer thanked us for fast response.',
            'resolved_at' => Carbon::today()->subDays(2),
        ]);

        // ==========================================
        // 9. MARKETING CAMPAIGNS & NOTIFICATIONS
        // ==========================================
        $camp1 = Campaign::create([
            'title' => 'የመስከረም አዲስ ዓመት ልዩ ቅናሽ (Enkutatash New Year Promo)',
            'channel' => 'telegram',
            'audience_filter' => 'all_customers',
            'message_text' => 'እንኳን ለአዲሱ ዓመት በሰላም አደረሳችሁ! ሜአሽ የፅዳት አገልግሎት ለሶፋ እና ምንጣፍ እጥበት 15% የበዓል ቅናሽ አዘጋጅቷል:: አሁኑኑ በ @meash_cleaning_solution_bot ቀጠሮ ይያዙ!',
            'status' => 'completed',
            'total_targets' => 2,
            'sent_count' => 2,
            'failed_count' => 0,
            'sent_at' => Carbon::today()->subDays(4),
            'created_by_user_id' => $owner->id,
        ]);

        CampaignRecipient::create([
            'campaign_id' => $camp1->id,
            'recipient_id' => '987654321',
            'status' => 'sent',
            'sent_at' => Carbon::today()->subDays(4),
        ]);
        CampaignRecipient::create([
            'campaign_id' => $camp1->id,
            'recipient_id' => '0911223344',
            'status' => 'sent',
            'sent_at' => Carbon::today()->subDays(4),
        ]);

        // Telegram Bot User Mock Data for Analytics
        TelegramUser::create([
            'telegram_id' => 987654321,
            'username' => 'sarayohannes_eth',
            'first_name' => 'Sara',
            'last_name' => 'Yohannes',
            'phone_number' => '0912445566',
            'language_code' => 'am',
            'last_interaction_at' => now(),
        ]);
    }
}
