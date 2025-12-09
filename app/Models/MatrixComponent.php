<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatrixComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'component_name',
        'max_score',
        'formula',
        'order',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'order' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(MatrixComponentScore::class);
    }
}
