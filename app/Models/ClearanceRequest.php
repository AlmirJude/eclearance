<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClearanceRequest extends Model
{
    protected $fillable = [
        'student_id',
        'period_id',
        'status',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(ClearancePeriod::class, 'period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClearanceItem::class, 'request_id');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getCompletionPercentage(): float
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;
        
        $approved = $this->items()->where('status', 'approved')->count();
        return round(($approved / $total) * 100, 2);
    }
}
