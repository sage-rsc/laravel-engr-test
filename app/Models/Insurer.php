<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'daily_capacity',
        'min_batch_size',
        'max_batch_size',
        'date_preference',
        'specialty_efficiencies',
    ];

    protected $casts = [
        'specialty_efficiencies' => 'array',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
} 