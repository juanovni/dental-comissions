<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->timestamp('auto_replied_at')->nullable()->after('processed_at');
            $table->string('auto_reply_external_id')->nullable()->after('auto_replied_at');
            $table->text('auto_reply_error')->nullable()->after('auto_reply_external_id');
            $table->integer('auto_reply_attempts')->default(0)->after('auto_reply_error');
            $table->text('auto_reply_message')->nullable()->after('auto_reply_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('social_comments', function (Blueprint $table) {
            $table->dropColumn([
                'auto_replied_at',
                'auto_reply_external_id',
                'auto_reply_error',
                'auto_reply_attempts',
                'auto_reply_message',
            ]);
        });
    }
};
