<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialPlatform;
use App\Filament\Resources\SocialComments\Pages\ViewSocialComment;
use App\Models\Clinic;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewSocialCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_comment_detail_shows_original_post_card(): void
    {
        $clinic = Clinic::create([
            'name' => 'Clinica Demo',
            'slug' => 'clinica-demo',
            'subdomain' => 'clinica-demo',
            'primary_domain' => 'clinica-demo.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $account = SocialAccount::create([
            'clinic_id' => $clinic->id,
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'clinic_id' => $clinic->id,
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Ortodoncia invisible para adultos. Consulta si eres candidato.',
            'media_url' => 'https://cdn.example.test/post.jpg',
            'permalink' => 'https://instagram.com/p/test',
            'published_at' => now(),
        ]);

        $comment = SocialComment::create([
            'clinic_id' => $clinic->id,
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'comment_text' => 'Me interesa esta promocion.',
        ]);

        $user = User::factory()->create();
        $clinic->users()->attach($user, [
            'role' => 'admin',
            'is_default' => true,
            'is_active' => true,
        ]);

        Filament::setCurrentPanel('clinic');
        Filament::setTenant($clinic, isQuiet: true);

        Livewire::actingAs($user)
            ->test(ViewSocialComment::class, ['record' => $comment->getRouteKey(), 'tenant' => $clinic->getRouteKey()])
            ->assertSee('Publicación original')
            ->assertSee('Ortodoncia invisible para adultos. Consulta si eres candidato.')
            ->assertSee('Me interesa esta promocion.')
            ->assertSee('Abrir publicación');
    }
}
