<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementSubmission extends Model
{
    protected $fillable = [
        'requirement_id',
        'clearance_item_id',
        'student_id',
        'file_path',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the requirement
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ClearanceRequirement::class, 'requirement_id');
    }

    /**
     * Get the clearance item
     */
    public function clearanceItem(): BelongsTo
    {
        return $this->belongsTo(ClearanceItem::class, 'clearance_item_id');
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
