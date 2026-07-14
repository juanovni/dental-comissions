<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_language_patterns', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 80)->index();
            $table->string('phrase');
            $table->string('normalized_phrase')->index();
            $table->string('value', 120)->index();
            $table->string('locale', 20)->default('es_EC')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('source', 40)->default('manual')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['type', 'normalized_phrase', 'locale'], 'local_language_patterns_unique_phrase');
        });

        $now = now();
        $patterns = [
            ['period', 'tardecita', 'afternoon'],
            ['period', 'en la tardecita', 'afternoon'],
            ['period', 'después del almuerzo', 'afternoon'],
            ['period', 'luego del almuerzo', 'afternoon'],
            ['period', 'tempranito', 'morning'],
            ['period', 'primerita hora', 'morning'],
            ['period', 'a primera hora', 'morning'],
            ['appointment_intent', 'tiene chance', 'appointment_interest'],
            ['appointment_intent', 'hay chance', 'appointment_interest'],
            ['appointment_intent', 'me da un turno', 'appointment_interest'],
            ['appointment_intent', 'quiero sacar cita', 'appointment_interest'],
            ['appointment_intent', 'quiero separar una cita', 'appointment_interest'],
            ['appointment_intent', 'me puede atender', 'appointment_interest'],
            ['confirmation', 'de una', 'confirmed'],
            ['confirmation', 'ya pues', 'confirmed'],
            ['confirmation', 'dele', 'confirmed'],
            ['confirmation', 'bacan', 'confirmed'],
        ];

        DB::table('local_language_patterns')->insert(array_map(
            fn (array $pattern): array => [
                'type' => $pattern[0],
                'phrase' => $pattern[1],
                'normalized_phrase' => Str::of($pattern[1])->lower()->ascii()->squish()->toString(),
                'value' => $pattern[2],
                'locale' => 'es_EC',
                'is_active' => true,
                'source' => 'system',
                'approved_at' => $now,
                'metadata' => json_encode(['seed' => true]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $patterns,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('local_language_patterns');
    }
};
