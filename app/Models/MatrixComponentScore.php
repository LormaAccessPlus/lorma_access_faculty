<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatrixComponentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'matrix_component_id',
        'student_mapping_id',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function matrixComponent(): BelongsTo
    {
        return $this->belongsTo(MatrixComponent::class);
    }

    public function studentMapping(): BelongsTo
    {
        return $this->belongsTo(StudentMapping::class);
    }
}
