<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'abbreviation',
        'moderator_id',
    ];

    public function moderator () {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function signatories(){
        return $this->belongsToMany(User::class, 'club_signatories')
                    ->withPivot(['position', 'is_active'])
                    ->withTimestamps();
    }

    public function members () {
        return $this->belongsToMany(User::class, 'club_memberships', 'club_id', 'student_id')
                    ->withTimestamps();
    }

    public function getModeratorNameAttribute()
    {
        if (!$this->moderator){
            return 'Not Assigned';
        }
        
        if ($this->moderator->staffDetail) {
            return $this->moderator->staffDetail->fullname;
        }
    }

    public function requirements()
    {
        return $this->morphMany(ClearanceRequirement::class, 'requirable');
    }
}
