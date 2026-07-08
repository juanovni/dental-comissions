<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("social_comments", function (Blueprint $table) {
            $table->timestamp("appointment_scheduled_at")->nullable()->after("estimated_value");
            $table->string("ai_intent", 100)->nullable()->after("appointment_scheduled_at");
            $table->integer("ai_confidence")->nullable()->after("ai_intent");
        });
    }

    public function down(): void
    {
        Schema::table("social_comments", function (Blueprint $table) {
            $table->dropColumn(["appointment_scheduled_at", "ai_intent", "ai_confidence"]);
        });
    }
};
