<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("social_comments", function (Blueprint $table) {
            $table->foreignId("suggested_doctor_id")
                ->nullable()
                ->constrained("professionals")
                ->nullOnDelete()
                ->after("suggested_procedure_id");
        });
    }

    public function down(): void
    {
        Schema::table("social_comments", function (Blueprint $table) {
            $table->dropForeign(["suggested_doctor_id"]);
            $table->dropColumn("suggested_doctor_id");
        });
    }
};
