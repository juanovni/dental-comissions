<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialInbox;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_inbox_hides_archived_leads_from_main_filter(): void
    {
        $visible = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'tracking_token' => 'DNT-VISIB',
        ]);

        $archived = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
            'tracking_token' => 'DNT-ARCH1',
            'is_hidden' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->set('filter', 'leads')
            ->assertSee($visible->comment_text)
            ->assertDontSee($archived->comment_text)
            ->set('filter', 'archived')
            ->assertSee($archived->comment_text);
    }

    public function test_mark_reviewed_archives_comment(): void
    {
        $comment = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->call('markReviewed', $comment->id);

        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'status' => SocialCommentStatus::Classified->value,
            'is_hidden' => true,
        ]);
    }

    public function test_navigation_badge_counts_pending_social_comments(): void
    {
        $this->socialComment(['status' => SocialCommentStatus::New]);
        $this->socialComment(['status' => SocialCommentStatus::ReviewRequired]);
        $this->socialComment(['status' => SocialCommentStatus::Classified]);
        $this->socialComment(['status' => SocialCommentStatus::Ignored]);
        $this->socialComment(['status' => SocialCommentStatus::MarkedAsSpam]);
        $this->socialComment([
            'status' => SocialCommentStatus::New,
            'is_hidden' => true,
        ]);
        $this->socialComment([
            'status' => SocialCommentStatus::Classified,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
        ]);

        $this->assertSame('3', SocialInbox::getNavigationBadge());
        $this->assertSame('primary', SocialInbox::getNavigationBadgeColor());
    }

    public function test_social_inbox_excludes_comments_from_own_connected_account(): void
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'macbotdata',
            'external_account_id' => 'ig_account_1',
            'instagram_business_account_id' => 'ig_account_1',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_own_1',
            'caption' => 'Ortodoncia invisible',
        ]);

        SocialComment::create([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_own_1',
            'author_name' => 'macbotdata',
            'author_username' => 'macbotdata',
            'author_external_id' => 'ig_account_1',
            'comment_text' => '@detanlinfodeunaec Hola! Gracias por escribirnos.',
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::None,
            'status' => SocialCommentStatus::New,
            'is_hidden' => false,
        ]);

        $external = $this->socialComment([
            'comment_text' => 'Quiero informacion de ortodoncia',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->set('filter', 'leads')
            ->assertSee($external->comment_text)
            ->assertDontSee('@detanlinfodeunaec Hola! Gracias por escribirnos.');

        $this->assertSame('1', SocialInbox::getNavigationBadge());
    }

    public function test_route_to_whatsapp_shows_final_tracking_reply_text(): void
    {
        config(['services.whatsapp.business_phone' => '+593999999999']);

        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_smart_link_content_blocks'],
            [
                'setting_group' => 'smart_links',
                'label' => 'Contenido dinamico de landing por categoria',
                'value_type' => 'array',
                'value' => [
                    'implantes' => [
                        'eyebrow' => 'Implantes dentales',
                        'title' => 'Recupera seguridad al morder, sonreir y hablar.',
                        'subtitle' => 'Explora resultados visuales y resuelve tus dudas por WhatsApp.',
                        'visual_label' => 'Rehabilitacion oral',
                        'video_url' => '/videos/smart-links/implantes/hero.mp4',
                        'before_video_url' => '/videos/smart-links/implantes/before.mp4',
                        'after_video_url' => '/videos/smart-links/implantes/after.mp4',
                    ],
                ],
                'is_active' => true,
            ],
        );

        $procedure = Procedure::create([
            'name' => 'Implantes dentales',
            'code' => 'implantes',
            'category' => 'implantes',
            'is_active' => true,
        ]);

        $comment = $this->socialComment([
            'classification' => SocialCommentClassification::SalesLead,
            'suggested_procedure_id' => $procedure->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialInbox::class)
            ->call('routeToWhatsapp', $comment->id)
            ->assertSet('whatsappGenerated', false)
            ->assertSet('whatsappProcedureId', $procedure->id)
            ->assertSee('Preview del Smart Link')
            ->assertSee('/videos/smart-links/implantes/before.mp4')
            ->assertSee('/videos/smart-links/implantes/after.mp4')
            ->assertSee('Generar seguimiento')
            ->call('confirmWhatsappRouting')
            ->assertSet('whatsappGenerated', true)
            ->assertSee('Texto final para copiar y responder')
            ->assertSee('DNT-');

        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'suggested_procedure_id' => $procedure->id,
        ]);
    }

    private function socialComment(array $overrides = []): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create(array_merge([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero informacion '.uniqid(),
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::None,
            'status' => SocialCommentStatus::New,
            'is_hidden' => false,
        ], $overrides));
    }
}
