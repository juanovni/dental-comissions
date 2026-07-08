<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreignId('social_comment_id')
                ->nullable()
                ->constrained('social_comments')
                ->nullOnDelete()
                ->after('professional_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['social_comment_id']);
            $table->dropColumn('social_comment_id');
        });
    }
};
