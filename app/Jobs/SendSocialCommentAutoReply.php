<?php

namespace App\Jobs;

use App\Models\SocialComment;
use App\Services\SocialAutoReplyService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSocialCommentAutoReply implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $socialCommentId,
        public ?int $clinicId = null,
    ) {}

    public function handle(SocialAutoReplyService $service, TenantContext $tenantContext): void
    {
        $comment = SocialComment::query()->find($this->socialCommentId);

        if (! $comment) {
            Log::warning('Auto-reply job omitido: comentario social no encontrado.', [
                'social_comment_id' => $this->socialCommentId,
            ]);

            return;
        }

        $clinicId = $this->clinicId ?? $comment->clinic_id;

        if ($clinicId === null) {
            Log::warning('Auto-reply job omitido: comentario sin clinic_id.', [
                'social_comment_id' => $this->socialCommentId,
            ]);

            return;
        }

        $tenantContext->run($clinicId, function () use ($service, $comment): void {
            $service->handle($comment);
        });
    }
}
