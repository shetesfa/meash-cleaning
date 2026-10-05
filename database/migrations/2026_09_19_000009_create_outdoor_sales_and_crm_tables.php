<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('org_code', 32)->unique(); // e.g. ORG-0001
            $table->string('name')->index();
            $table->string('industry', 64)->default('office')->index(); // hotel, office, embassy, bank, hospital, retail, other
            $table->text('address');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_code', 32)->unique(); // e.g. VST-0001
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('contact_person');
            $table->string('contact_position', 128)->nullable(); // Operations Manager, General Manager, etc.
            $table->string('phone', 32);
            $table->text('address');
            $table->text('services_introduced')->nullable();
            $table->string('visit_purpose', 128)->nullable();
            $table->string('interest_level', 32)->default('medium'); // low, medium, high
            $table->foreignId('salesperson_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('visit_date')->index();
            $table->text('notes')->nullable();
            $table->date('next_followup_date')->nullable()->index();
            $table->string('stage', 64)->default('new_lead')->index();
            // new_lead, visited, contact_established, interested, proforma_requested, proforma_sent, negotiation, won, lost, followup_later
            $table->timestamps();
        });

        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_number', 32)->unique(); // e.g. PRF-0001
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('sales_visit_id')->nullable()->constrained('sales_visits')->nullOnDelete();
            $table->foreignId('prepared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('validity_date')->nullable();
            $table->string('status', 32)->default('draft')->index(); // draft, sent, accepted, rejected, expired
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 32)->unique(); // e.g. CNT-0001
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('proforma_id')->nullable()->constrained('proformas')->nullOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('agreed_value', 12, 2)->default(0);
            $table->string('cleaning_frequency', 32)->default('monthly'); // daily, weekly, biweekly, monthly
            $table->text('terms')->nullable();
            $table->string('status', 32)->default('active')->index(); // active, expired, terminated, pending_renewal
            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('proforma_items');
        Schema::dropIfExists('proformas');
        Schema::dropIfExists('sales_visits');
        Schema::dropIfExists('organizations');
    }
};
