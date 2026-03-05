<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeroomAssignment extends Model
{
    protected $fillable = [
        'adviser_id',
        'department_id',
        'year_levels',
        'section',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'year_levels' => 'array',
        'is_active' => 'boolean',
    ];

    // Virtual name attribute so morphTo->name works like other signable models
    public function getNameAttribute(): string
    {
        $adviser = $this->adviser;
        if ($adviser && $adviser->staffDetail) {
            $name = $adviser->staffDetail->first_name . ' ' . $adviser->staffDetail->last_name;
        } else {
            $name = $adviser?->email ?? 'Homeroom';
        }
        return $name . ' (Homeroom Adviser)';
    }

    // Relationships
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requirements()
    {
        return $this->morphMany(ClearanceRequirement::class, 'requirable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademicYear($query, string $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }
}
