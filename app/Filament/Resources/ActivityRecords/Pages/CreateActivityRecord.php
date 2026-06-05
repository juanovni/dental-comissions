<?php

namespace App\Filament\Resources\ActivityRecords\Pages;

use App\Filament\Resources\ActivityRecords\ActivityRecordResource;
use App\Models\ActivityRecord;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityRecord extends CreateRecord
{
    protected static string $resource = ActivityRecordResource::class;

    protected function afterCreate(): void
    {
        /** @var ActivityRecord $record */
        $record = $this->record;

        $assistantIds = $this->data['assistants'] ?? [];

        if (!empty($assistantIds)) {
            $record->assistants()->sync($assistantIds);
        }

        $record->calculateCommissions();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['assistants']);
        return $data;
    }
}
