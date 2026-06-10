<?php

namespace App\Filament\Resources\SocialComments\Pages;

use App\Filament\Resources\SocialComments\SocialCommentResource;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSocialComment extends ViewRecord
{
    protected static string $resource = SocialCommentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            ViewEntry::make('case')
                ->view('filament.resources.social-comments.pages.view-social-comment-case')
                ->columnSpanFull(),
        ])->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return SocialCommentResource::commentActions();
    }
}
