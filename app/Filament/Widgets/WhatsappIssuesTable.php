<?php

namespace App\Filament\Widgets;

use App\Enums\WhatsappMessageStatus;
use App\Models\WhatsappMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WhatsappIssuesTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mensajes con problemas')
            ->description('Ultimos mensajes rechazados o pendientes de revision.')
            ->query($this->messagesQuery())
            ->striped()
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->sortable(),
                TextColumn::make('professional.name')
                    ->label('Doctor')
                    ->weight('semibold')
                    ->placeholder('No identificado')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (WhatsappMessageStatus $state): string => $state->label())
                    ->color(fn (WhatsappMessageStatus $state): string => $state->color()),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->color('danger')
                    ->placeholder('Sin detalle')
                    ->limit(45),
                TextColumn::make('message_body')
                    ->label('Mensaje')
                    ->limit(55),
            ]);
    }

    private function messagesQuery(): Builder
    {
        return WhatsappMessage::query()
            ->with('professional')
            ->whereIn('status', [
                WhatsappMessageStatus::Failed,
                WhatsappMessageStatus::NeedsReview,
            ])
            ->latest();
    }
}
