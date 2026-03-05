<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClearanceRequirement extends Model
{
    protected $fillable = [
        'requirable_type',
        'requirable_id',
        'name',
        'description',
        'type',
        'is_required',
        'is_active',
        'year_levels',
        'departments',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'year_levels' => 'array',
        'departments' => 'array',
    ];

    /**
     * Get the parent requirable model (Office, Club, Department, etc.)
     */
    public function requirable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the submissions for this requirement
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'requirement_id');
    }

    /**
     * Check if this requirement applies to a student
     */
    public function appliesToStudent($student): bool
    {
        $studentDetail = $student->studentDetail;
        
        if (!$studentDetail) {
            return false;
        }

        // Check year level scope
        if (!empty($this->year_levels)) {
            if (!in_array($studentDetail->year_level, $this->year_levels)) {
                return false;
            }
        }

        // Check department scope
        if (!empty($this->departments)) {
            if (!in_array($studentDetail->department_id, $this->departments)) {
                return false;
            }
        }

        return true;
    }
}
