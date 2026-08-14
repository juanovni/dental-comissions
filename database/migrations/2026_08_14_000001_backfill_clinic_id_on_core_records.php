<?php

use App\Models\Clinic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $clinicId = Clinic::query()->orderBy('id')->value('id');

        if ($clinicId === null) {
            return;
        }

        foreach (['patients', 'professionals', 'procedures'] as $table) {
            DB::table($table)
                ->whereNull('clinic_id')
                ->update(['clinic_id' => $clinicId]);
        }

        DB::table('appointments')
            ->whereNull('clinic_id')
            ->whereNotNull('patient_id')
            ->update([
                'clinic_id' => DB::raw('(select patients.clinic_id from patients where patients.id = appointments.patient_id)'),
            ]);

        DB::table('appointments')
            ->whereNull('clinic_id')
            ->whereNotNull('doctor_id')
            ->update([
                'clinic_id' => DB::raw('(select professionals.clinic_id from professionals where professionals.id = appointments.doctor_id)'),
            ]);

        DB::table('appointments')
            ->whereNull('clinic_id')
            ->update(['clinic_id' => $clinicId]);
    }

    public function down(): void
    {
        // Data backfill only; do not remove tenant assignments on rollback.
    }
};
