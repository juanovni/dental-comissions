<?php

namespace App\Filament\Pages;

use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\WhatsappMessage;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class BandejaWhatsApp extends Page
{
    use WithPagination;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Bandeja WhatsApp';

    protected static ?string $title = 'Bandeja WhatsApp';

    protected static ?string $slug = 'whatsapp-inbox';

    protected static ?int $navigationSort = 14;

    protected string $view = 'filament.pages.bandeja-whatsapp';

    public string $filter = 'needs_review';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function stats(): array
    {
        $base = WhatsappMessage::query()->where('direction', WhatsappMessageDirection::Incoming);

        return [
            'received' => (clone $base)->where('status', WhatsappMessageStatus::Received)->count(),
            'parsed' => (clone $base)->where('status', WhatsappMessageStatus::Parsed)->count(),
            'confirmed' => (clone $base)->where('status', WhatsappMessageStatus::Confirmed)->count(),
            'processed' => (clone $base)->where('status', WhatsappMessageStatus::Processed)->count(),
            'needs_review' => (clone $base)->where('status', WhatsappMessageStatus::NeedsReview)->count(),
            'failed' => (clone $base)->where('status', WhatsappMessageStatus::Failed)->count(),
        ];
    }

    public function messages(): LengthAwarePaginator
    {
        $query = WhatsappMessage::query()
            ->with('professional')
            ->where('direction', WhatsappMessageDirection::Incoming);

        $query->when($this->filter === 'needs_review', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::NeedsReview))
            ->when($this->filter === 'failed', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::Failed))
            ->when($this->filter === 'received', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::Received))
            ->when($this->filter === 'parsed', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::Parsed))
            ->when($this->filter === 'confirmed', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::Confirmed))
            ->when($this->filter === 'processed', fn (Builder $q) => $q->where('status', WhatsappMessageStatus::Processed))
            ->when($this->filter === 'all', fn (Builder $q) => $q);

        if ($this->search) {
            $search = $this->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('message_body', 'ilike', "%{$search}%")
                    ->orWhere('from_phone', 'ilike', "%{$search}%")
                    ->orWhere('error_message', 'ilike', "%{$search}%")
                    ->orWhereHas('professional', fn (Builder $pq) => $pq->where('name', 'ilike', "%{$search}%"));
            });
        }

        return $query->latest()->paginate(12);
    }
}
