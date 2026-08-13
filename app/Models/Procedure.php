<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'code',
        'category',
        'internal_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'internal_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function suggestedSocialComments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'suggested_procedure_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
