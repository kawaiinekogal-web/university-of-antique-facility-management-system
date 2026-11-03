<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_reference');
        });

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->text('failure_reason')->nullable()->after('admin_remarks');
            $table->text('resolution_action')->nullable()->after('failure_reason');
            $table->text('replacement_recommendation')->nullable()->after('resolution_action');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_amount']);
        });

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropColumn(['failure_reason', 'resolution_action', 'replacement_recommendation']);
        });
    }
};
