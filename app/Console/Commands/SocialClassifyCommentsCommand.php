<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachClinic;
use App\Enums\SocialCommentStatus;
use App\Models\SocialComment;
use App\Services\SocialCommentClassificationService;
use Illuminate\Console\Command;

class SocialClassifyCommentsCommand extends Command
{
    use RunsForEachClinic;

    protected $signature = 'social:classify-comments
        {--clinic= : ID de clinica a procesar}
        {--limit=50 : Numero maximo de comentarios a clasificar}';

    protected $description = 'Clasifica comentarios sociales pendientes usando IA y fallback local.';

    public function handle(SocialCommentClassificationService $classificationService): int
    {
        return $this->runForEachClinic(function () use ($classificationService): void {
            $limit = max(1, (int) $this->option('limit'));
            $classified = 0;

            SocialComment::query()
                ->with(['socialPost', 'socialAccount'])
                ->where('status', SocialCommentStatus::New->value)
                ->forCurrentTenant()
                ->oldest('id')
                ->limit($limit)
                ->get()
                ->each(function (SocialComment $comment) use ($classificationService, &$classified): void {
                    $classificationService->classify($comment);
                    $classified++;
                });

            $this->components->info("Comentarios clasificados: {$classified}.");
        });
    }
}
