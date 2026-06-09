<?php

namespace App\Filament\Resources\SocialComments\Pages;

use App\Filament\Resources\SocialComments\SocialCommentResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSocialComment extends ViewRecord
{
    protected static string $resource = SocialCommentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('socialAccount.account_name')->label('Cuenta'),
            TextEntry::make('platform')->label('Red')->badge()->formatStateUsing(fn ($state) => $state->label()),
            TextEntry::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
            TextEntry::make('author_name')->label('Autor'),
            TextEntry::make('author_username')->label('Usuario'),
            TextEntry::make('published_at')->label('Publicado')->dateTime(),
            TextEntry::make('comment_text')->label('Comentario')->columnSpanFull(),
            TextEntry::make('socialPost.caption')->label('Publicacion')->columnSpanFull(),
            TextEntry::make('classification')->label('Clasificacion')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin clasificar'),
            TextEntry::make('sentiment')->label('Sentimiento')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin analizar'),
            TextEntry::make('priority')->label('Prioridad')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin prioridad')->color(fn ($state) => $state?->color() ?? 'gray'),
            TextEntry::make('reputation_risk')->label('Riesgo')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin riesgo'),
            TextEntry::make('suggested_action')->label('Accion sugerida')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin accion'),
            TextEntry::make('response_channel')->label('Canal')->badge()->formatStateUsing(fn ($state) => $state?->label() ?? 'Sin canal'),
            IconEntry::make('requires_human_review')->label('Requiere revision')->boolean(),
            TextEntry::make('suggested_reply')->label('Respuesta sugerida')->columnSpanFull(),
            TextEntry::make('ai_reason')->label('Motivo IA')->columnSpanFull(),
            TextEntry::make('processed_at')->label('Procesado')->dateTime(),
        ])->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return SocialCommentResource::commentActions();
    }
}
