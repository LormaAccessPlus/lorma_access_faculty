<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComponentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'item_name',
        'max_score',
        'date',
        'activity_id',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'date' => 'date',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(GradingComponent::class, 'component_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class, 'component_item_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
