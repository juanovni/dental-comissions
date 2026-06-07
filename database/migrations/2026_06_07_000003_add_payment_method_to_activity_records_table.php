<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_records', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('procedure_id')->constrained()->nullOnDelete();
            $table->string('payment_method_raw')->nullable()->after('payment_method_id');
            $table->decimal('payment_method_commission_snapshot', 10, 2)->nullable()->after('payment_method_raw');
        });
    }

    public function down(): void
    {
        Schema::table('activity_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn(['payment_method_raw', 'payment_method_commission_snapshot']);
        });
    }
};
