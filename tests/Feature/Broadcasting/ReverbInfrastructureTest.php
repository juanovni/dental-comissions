<?php

namespace Tests\Feature\Broadcasting;

use App\Events\ClosingOpportunityDetected;
use App\Events\LeadActivityDetected;
use App\Models\Clinic;
use App\Models\SocialComment;
use App\Models\SocialLeadAlert;
use App\Models\SocialLinkEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReverbInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    private function createClinic(): Clinic
    {
        return Clinic::create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic',
            'subdomain' => 'test-clinic',
            'primary_domain' => 'test-clinic.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    public function test_lead_activity_detected_uses_clinic_scoped_channel(): void
    {
        $clinic = $this->createClinic();

        $comment = new SocialComment([
            'tracking_token' => 'DNT-TEST',
            'interest_score' => 85,
            'hot_lead_at' => now(),
            'clinic_id' => $clinic->id,
        ]);
        $comment->id = 123;

        $linkEvent = new SocialLinkEvent([
            'event_type' => 'whatsapp_click',
        ]);
        $linkEvent->created_at = now();

        $event = new LeadActivityDetected($comment, $linkEvent);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals("private-clinic.{$clinic->id}.notifications", (string) $channel);
        $this->assertSame('LeadActivityDetected', $event->broadcastAs());
    }

    public function test_closing_opportunity_detected_uses_clinic_scoped_channel(): void
    {
        $clinic = $this->createClinic();

        $comment = new SocialComment([
            'tracking_token' => 'DNT-CLOSE',
            'author_name' => 'Maria Perez',
            'clinic_id' => $clinic->id,
        ]);
        $comment->id = 456;

        $alert = new SocialLeadAlert([
            'alert_type' => 'closing_opportunity',
        ]);
        $alert->id = 789;

        $event = new ClosingOpportunityDetected($comment, [
            'intent' => 'ready_to_book',
            'closing_opportunity_score' => 88,
            'handoff_reason' => 'Quiere agendar.',
            'clinical_safety_flag' => false,
        ], $alert);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals("private-clinic.{$clinic->id}.notifications", (string) $channel);
        $this->assertSame('ClosingOpportunityDetected', $event->broadcastAs());
    }

    public function test_lead_activity_detected_payload_includes_required_fields(): void
    {
        $clinic = $this->createClinic();

        $comment = new SocialComment([
            'tracking_token' => 'DNT-TEST',
            'interest_score' => 85,
            'hot_lead_at' => now(),
            'clinic_id' => $clinic->id,
        ]);
        $comment->id = 123;

        $linkEvent = new SocialLinkEvent([
            'event_type' => 'whatsapp_click',
        ]);
        $linkEvent->created_at = now();

        $event = new LeadActivityDetected($comment, $linkEvent);
        $payload = $event->broadcastWith();

        $this->assertSame(123, $payload['lead_id']);
        $this->assertSame('DNT-TEST', $payload['tracking_token']);
        $this->assertSame('whatsapp_click', $payload['event_type']);
        $this->assertSame(85, $payload['interest_score']);
        $this->assertTrue($payload['hot_lead']);
    }

    public function test_closing_opportunity_detected_payload_includes_required_fields(): void
    {
        $clinic = $this->createClinic();

        $comment = new SocialComment([
            'tracking_token' => 'DNT-CLOSE',
            'author_name' => 'Maria Perez',
            'clinic_id' => $clinic->id,
        ]);
        $comment->id = 456;

        $alert = new SocialLeadAlert([
            'alert_type' => 'closing_opportunity',
        ]);
        $alert->id = 789;

        $event = new ClosingOpportunityDetected($comment, [
            'intent' => 'ready_to_book',
            'closing_opportunity_score' => 88,
            'handoff_reason' => 'Quiere agendar.',
            'clinical_safety_flag' => false,
        ], $alert);

        $payload = $event->broadcastWith();

        $this->assertSame(456, $payload['lead_id']);
        $this->assertSame(789, $payload['alert_id']);
        $this->assertSame('DNT-CLOSE', $payload['tracking_token']);
        $this->assertSame('Maria Perez', $payload['lead_name']);
        $this->assertSame('ready_to_book', $payload['intent']);
        $this->assertSame(88, $payload['closing_opportunity_score']);
        $this->assertFalse($payload['clinical_safety_flag']);
    }

    public function test_different_clinics_get_different_channels(): void
    {
        $clinicA = $this->createClinic();
        $clinicB = Clinic::create([
            'name' => 'Other Clinic',
            'slug' => 'other-clinic',
            'subdomain' => 'other-clinic',
            'primary_domain' => 'other-clinic.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $commentA = new SocialComment(['clinic_id' => $clinicA->id]);
        $commentA->id = 1;
        $commentB = new SocialComment(['clinic_id' => $clinicB->id]);
        $commentB->id = 2;

        $linkEvent = new SocialLinkEvent(['event_type' => 'click']);
        $linkEvent->created_at = now();

        $eventA = new LeadActivityDetected($commentA, $linkEvent);
        $eventB = new LeadActivityDetected($commentB, $linkEvent);

        $this->assertNotEquals(
            (string) $eventA->broadcastOn(),
            (string) $eventB->broadcastOn(),
        );
        $this->assertEquals("private-clinic.{$clinicA->id}.notifications", (string) $eventA->broadcastOn());
        $this->assertEquals("private-clinic.{$clinicB->id}.notifications", (string) $eventB->broadcastOn());
    }
}
