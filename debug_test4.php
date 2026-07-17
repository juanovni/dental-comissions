<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-07-13 09:00:00'));

try {
    // Replicate test setup
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_show_doctor'],
        ['value' => true, 'value_type' => 'boolean', 'label' => 'x', 'is_active' => true]
    );
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_slot_duration'],
        ['value' => 45, 'value_type' => 'integer', 'label' => 'x', 'is_active' => true]
    );
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_lead_time_hours'],
        ['value' => 2, 'value_type' => 'integer', 'label' => 'x', 'is_active' => true]
    );
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_clinic_days'],
        ['value' => [1,2,3,4,5], 'value_type' => 'array', 'label' => 'x', 'is_active' => true]
    );
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_clinic_open'],
        ['value' => '09:00', 'value_type' => 'string', 'label' => 'x', 'is_active' => true]
    );
    \App\Models\SocialCrmSetting::updateOrCreate(
        ['key' => 'social_appointment_clinic_close'],
        ['value' => '18:00', 'value_type' => 'string', 'label' => 'x', 'is_active' => true]
    );

    // Clear cache
    \Illuminate\Support\Facades\Cache::forget('social_crm_settings.active');

    $procedure = \App\Models\Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
    $doctor = \App\Models\Professional::factory()->doctor()->create(['name' => 'Dra. Ana Morales']);
    
    $account = \App\Models\SocialAccount::create([
        'platform' => 'instagram',
        'account_name' => 'Clinica Test',
        'external_account_id' => 'test_'.uniqid(),
        'is_active' => true,
    ]);
    $post = \App\Models\SocialPost::create([
        'social_account_id' => $account->id,
        'procedure_id' => $procedure->id,
        'platform' => 'instagram',
        'external_post_id' => 'post_'.uniqid(),
        'caption' => 'Test',
    ]);
    $identity = \App\Models\SocialIdentity::create([
        'platform' => 'instagram',
        'platform_user_id' => 'user_'.uniqid(),
        'username' => 'test_user',
        'display_name' => 'Test User',
        'status' => 'new_lead',
        'first_seen_at' => now(),
        'last_seen_at' => now(),
    ]);
    $comment = \App\Models\SocialComment::create([
        'social_account_id' => $account->id,
        'social_identity_id' => $identity->id,
        'social_post_id' => $post->id,
        'suggested_procedure_id' => $procedure->id,
        'suggested_doctor_id' => $doctor->id,
        'platform' => 'instagram',
        'external_comment_id' => 'comm_'.uniqid(),
        'author_name' => 'Test User',
        'author_username' => 'test_user',
        'comment_text' => 'Quiero agendar',
        'tracking_token' => 'DNT-TEST'.uniqid(),
    ]);
    $offer = \App\Models\AppointmentSlotOffer::create([
        'social_comment_id' => $comment->id,
        'token' => 'test-token-'.uniqid(),
        'status' => 'pending',
        'expires_at' => now()->addHour(),
        'metadata' => [
            'procedure_id' => $comment->suggested_procedure_id,
            'doctor_id' => $comment->suggested_doctor_id,
            'options' => [
                ['index' => 1, 'datetime' => '2026-07-15 10:00:00', 'doctor_id' => $doctor->id],
                ['index' => 2, 'datetime' => '2026-07-15 11:15:00', 'doctor_id' => $doctor->id],
            ],
        ],
    ]);

    echo "Offer token: {$offer->token}\n";
    echo "Offer pending: " . ($offer->isPending() ? 'yes' : 'no') . "\n";

    $request = \Illuminate\Http\Request::create('/social/appointments/' . $offer->token, 'GET');
    $response = $kernel->handle($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() === 500) {
        $content = $response->getContent();
        echo "Error content (first 500 chars): " . substr($content, 0, 500) . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
