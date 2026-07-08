<?php

use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->string('pipeline_stage', 30)->nullable()->after('interest_score');
            $table->decimal('estimated_value', 10, 2)->nullable()->after('pipeline_stage');

            $table->index('pipeline_stage');
        });

        $mapping = [
            SocialConversionStatus::None->value => SocialPipelineStage::New->value,
            SocialConversionStatus::TokenGenerated->value => SocialPipelineStage::Qualified->value,
            SocialConversionStatus::WhatsappStarted->value => SocialPipelineStage::Qualified->value,
            SocialConversionStatus::IdentityLinked->value => SocialPipelineStage::Appointment->value,
            SocialConversionStatus::PendingPatientCreation->value => SocialPipelineStage::Appointment->value,
            SocialConversionStatus::AppointmentCreated->value => SocialPipelineStage::Proposal->value,
            SocialConversionStatus::Converted->value => SocialPipelineStage::Won->value,
            SocialConversionStatus::Lost->value => SocialPipelineStage::Lost->value,
        ];

        foreach ($mapping as $conversionStatus => $pipelineStage) {
            DB::table('social_comments')
                ->where('conversion_status', $conversionStatus)
                ->whereNull('pipeline_stage')
                ->update(['pipeline_stage' => $pipelineStage]);
        }

        DB::table('social_comments')
            ->whereNull('pipeline_stage')
            ->whereNull('lost_at')
            ->update(['pipeline_stage' => SocialPipelineStage::New->value]);
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->dropIndex(['pipeline_stage']);
            $table->dropColumn(['pipeline_stage', 'estimated_value']);
        });
    }
};
