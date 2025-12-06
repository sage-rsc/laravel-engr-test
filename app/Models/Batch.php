<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurer_id',
        'provider_name',
        'batch_date',
        'identifier',
        'claim_count',
        'total_amount',
        'estimated_cost',
        'processed',
    ];

    protected $casts = [
        'batch_date' => 'date',
        'total_amount' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'processed' => 'boolean',
    ];

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}
