<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('weekly_report_items');
        Schema::dropIfExists('weekly_reports');
        Schema::dropIfExists('activity_assistants');
        Schema::dropIfExists('activity_records');
        Schema::dropIfExists('payment_method_commission_rates');
        Schema::dropIfExists('payment_methods');
    }

    public function down(): void
    {
        // Obsolete modules were intentionally removed from OdonCRM.
    }
};
