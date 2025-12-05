<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradingComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_class_id',
        'component_name',
        'component_type',
        'weight_percentage',
        'formula',
        'order',
    ];

    protected $casts = [
        'weight_percentage' => 'decimal:2',
        'order' => 'integer',
    ];

    public function gradingClass(): BelongsTo
    {
        return $this->belongsTo(GradingClass::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComponentItem::class, 'component_id');
    }
}
