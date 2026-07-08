<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->foreignId('social_identity_id')->nullable()->after('social_account_id')->constrained()->nullOnDelete();
            $table->foreignId('suggested_procedure_id')->nullable()->after('suggested_reply')->constrained('procedures')->nullOnDelete();
            $table->string('tracking_token')->nullable()->unique()->after('suggested_procedure_id');
            $table->string('conversion_status')->default('none')->after('tracking_token');
            $table->foreignId('converted_patient_id')->nullable()->after('conversion_status')->constrained('patients')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('converted_patient_id');
            $table->boolean('is_emergency')->default(false)->after('converted_at');
            $table->timestamp('whatsapp_redirected_at')->nullable()->after('is_emergency');

            $table->index(['social_identity_id', 'conversion_status']);
            $table->index(['suggested_procedure_id', 'conversion_status']);
            $table->index('whatsapp_redirected_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->dropIndex(['social_identity_id', 'conversion_status']);
            $table->dropIndex(['suggested_procedure_id', 'conversion_status']);
            $table->dropIndex(['whatsapp_redirected_at']);

            $table->dropConstrainedForeignId('social_identity_id');
            $table->dropConstrainedForeignId('suggested_procedure_id');
            $table->dropConstrainedForeignId('converted_patient_id');
            $table->dropUnique(['tracking_token']);
            $table->dropColumn([
                'tracking_token',
                'conversion_status',
                'converted_at',
                'is_emergency',
                'whatsapp_redirected_at',
            ]);
        });
    }
};
