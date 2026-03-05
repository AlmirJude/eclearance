<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RequirementSubmission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClearanceItem extends Model
{
    protected $fillable = [
        'request_id',
        'signable_type',
        'signable_id',
        'status',
        'signed_by',
        'signed_at',
        'remarks',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    // Relationships
    public function request(): BelongsTo
    {
        return $this->belongsTo(ClearanceRequest::class, 'request_id');
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function submissions()
    {
        return $this->hasMany(RequirementSubmission::class, 'clearance_item_id');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $user, ?string $remarks = null): void
    {
        $this->update([
            'status' => 'approved',
            'signed_by' => $user->id,
            'signed_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    public function reject(User $user, ?string $remarks = null): void
    {
        $this->update([
            'status' => 'rejected',
            'signed_by' => $user->id,
            'signed_at' => now(),
            'remarks' => $remarks,
        ]);
    }
}
