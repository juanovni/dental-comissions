<?php

namespace App\Filament\Resources\SocialComments\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class ViewSocialComment extends ViewRecord
{
    protected static string $resource = SocialCommentResource::class;

    public function activeCaseTab(): string
    {
        $tab = request()->query('tab', 'resumen');

        return in_array($tab, ['resumen', 'conversacion', 'actividad', 'contexto-clinico'], true)
            ? $tab
            : 'resumen';
    }

    public function getHeader(): ?View
    {
        return null;
    }

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
        return [
            Action::make('view_patient')
                ->label('Ver ficha')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->socialIdentity?->patient_id) || filled($this->record->converted_patient_id))
                ->url(fn (): ?string => ($this->record->socialIdentity?->patient ?: $this->record->convertedPatient)
                    ? PatientResource::getUrl('edit', ['record' => $this->record->socialIdentity?->patient ?: $this->record->convertedPatient])
                    : null),
            ...SocialCommentResource::commentActions(),
        ];
    }
}
