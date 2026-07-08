<?php

namespace App\Jobs;

use App\Models\SocialComment;
use App\Services\SocialAutoReplyService;
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
    ) {}

    public function handle(SocialAutoReplyService $service): void
    {
        $comment = SocialComment::query()->find($this->socialCommentId);

        if (! $comment) {
            Log::warning('Auto-reply job omitido: comentario social no encontrado.', [
                'social_comment_id' => $this->socialCommentId,
            ]);

            return;
        }

        $service->handle($comment);
    }
}
