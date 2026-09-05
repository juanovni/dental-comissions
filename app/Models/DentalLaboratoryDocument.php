<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalLaboratoryDocument extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'case_id',
        'case_item_id',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(DentalLaboratoryCase::class, 'case_id');
    }

    public function caseItem(): BelongsTo
    {
        return $this->belongsTo(DentalLaboratoryCaseItem::class, 'case_item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
