<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialPlatform;
use App\Filament\Resources\SocialComments\Pages\ViewSocialComment;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewSocialCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_comment_detail_shows_original_post_card(): void
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Ortodoncia invisible para adultos. Consulta si eres candidato.',
            'media_url' => 'https://cdn.example.test/post.jpg',
            'permalink' => 'https://instagram.com/p/test',
            'published_at' => now(),
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'comment_text' => 'Me interesa esta promocion.',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ViewSocialComment::class, ['record' => $comment->getRouteKey()])
            ->assertSee('Publicacion original')
            ->assertSee('Ortodoncia invisible para adultos. Consulta si eres candidato.')
            ->assertSee('Me interesa esta promocion.')
            ->assertSee('Abrir publicacion');
    }
}
