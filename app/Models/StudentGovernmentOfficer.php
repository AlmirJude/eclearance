<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGovernmentOfficer extends Model
{
    protected $fillable = [
        'student_government_id',
        'user_id',
        'position',
        'can_sign',
        'year_levels',
        'is_active',
    ];

    protected $casts = [
        'can_sign' => 'boolean',
        'year_levels' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function studentGovernment()
    {
        return $this->belongsTo(StudentGovernment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessors
    public function getStudentNameAttribute()
    {
        return $this->user?->fullname ?? 'N/A';
    }

    // Check if officer can sign for a specific year level
    public function canSignForYearLevel($yearLevel)
    {
        if (!$this->can_sign || !$this->is_active) {
            return false;
        }

        // If no specific year levels set, can sign for all
        if (empty($this->year_levels)) {
            return true;
        }

        return in_array($yearLevel, $this->year_levels);
    }
}
