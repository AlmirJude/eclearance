<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $fillable = [
        'name',
        'description',
        'abbreviation',
        'manager_id',
        'is_required',
        'clearance_order',
    ];

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function signatories () {
        return $this->belongsToMany(User::class, 'office_signatories')
                    ->withPivot(['title', 'year_levels', 'is_active'])
                    ->withTimestamps();
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
