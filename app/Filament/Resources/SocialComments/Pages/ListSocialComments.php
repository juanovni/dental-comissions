<?php

namespace App\Filament\Resources\SocialComments\Pages;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialReputationRisk;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\SocialComment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSocialComments extends ListRecords
{
    protected static string $resource = SocialCommentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn (): int => SocialComment::count()),
            'leads' => Tab::make('Leads')
                ->icon('heroicon-o-fire')
                ->badge(fn (): int => self::leadsQuery()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('classification', [
                    SocialCommentClassification::SalesLead->value,
                    SocialCommentClassification::CommercialQuestion->value,
                ])),
            'crisis' => Tab::make('Crisis')
                ->icon('heroicon-o-bolt')
                ->badge(fn (): int => self::crisisQuery()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::applyCrisisQuery($query)),
            'vip_patients' => Tab::make('Pacientes VIP')
                ->icon('heroicon-o-heart')
                ->badge(fn (): int => self::vipQuery()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereHas('socialIdentity.patient')
                    ->whereHas('socialIdentity.patient.activityRecords')),
            'medical_attention' => Tab::make('Atencion Medica')
                ->icon('heroicon-o-plus-circle')
                ->badge(fn (): int => self::medicalQuery()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
                    'classification',
                    SocialCommentClassification::MedicalSensitive->value,
                )),
            'pending_patient_creation' => Tab::make('Pendiente de ficha')
                ->badge(fn (): int => SocialComment::where(
                    'conversion_status',
                    SocialConversionStatus::PendingPatientCreation->value,
                )->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
                    'conversion_status',
                    SocialConversionStatus::PendingPatientCreation->value,
                )),
            'converted' => Tab::make('Convertidos')
                ->badge(fn (): int => SocialComment::whereIn('conversion_status', [
                    SocialConversionStatus::IdentityLinked->value,
                    SocialConversionStatus::AppointmentCreated->value,
                    SocialConversionStatus::Converted->value,
                ])->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('conversion_status', [
                    SocialConversionStatus::IdentityLinked->value,
                    SocialConversionStatus::AppointmentCreated->value,
                    SocialConversionStatus::Converted->value,
                ])),
        ];
    }

    private static function leadsQuery(): Builder
    {
        return SocialComment::query()->whereIn('classification', [
            SocialCommentClassification::SalesLead->value,
            SocialCommentClassification::CommercialQuestion->value,
        ]);
    }

    private static function crisisQuery(): Builder
    {
        return self::applyCrisisQuery(SocialComment::query());
    }

    private static function vipQuery(): Builder
    {
        return SocialComment::query()
            ->whereHas('socialIdentity.patient')
            ->whereHas('socialIdentity.patient.activityRecords');
    }

    private static function medicalQuery(): Builder
    {
        return SocialComment::query()->where('classification', SocialCommentClassification::MedicalSensitive->value);
    }

    private static function applyCrisisQuery(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('reputation_risk', [
                SocialReputationRisk::High->value,
                SocialReputationRisk::Critical->value,
            ])->orWhereIn('classification', [
                SocialCommentClassification::Complaint->value,
                SocialCommentClassification::NegativeOpinion->value,
                SocialCommentClassification::LegalSensitive->value,
            ]);
        });
    }
}
