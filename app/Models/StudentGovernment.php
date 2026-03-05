<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGovernment extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'description',
        'department_id',
        'academic_year',
        'adviser_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function officers()
    {
        return $this->hasMany(StudentGovernmentOfficer::class);
    }

    public function activeOfficers()
    {
        return $this->officers()->where('is_active', true);
    }

    public function signatories()
    {
        return $this->officers()->where('can_sign', true)->where('is_active', true);
    }

    // Accessors
    public function getAdviserNameAttribute()
    {
        return $this->adviser?->fullname ?? 'N/A';
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department?->name ?? 'University-wide';
    }
}
