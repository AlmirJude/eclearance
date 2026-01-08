<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'abbreviation',
        'manager_id',
    ];

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function signatories() {
        return $this->belongsToMany(User::class, 'department_signatories')
                    ->withPivot(['title', 'clearance_type', 'year_levels', 'is_active' ])
                    ->withTimestamps();
    }

    public function students() {
        return $this->hasMany(StudentDetail::class);
    }

    public function getManagerNameAttribute()
    {
        if (!$this->manager) {
            return 'Not Assigned';
        }
        
        if ($this->manager->staffDetail) {
            return $this->manager->staffDetail->fullname;
        }
        
        return $this->manager->email;
    }

}
