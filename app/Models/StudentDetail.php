<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    protected static function booted(): void
    {
        static::deleted(function (StudentDetail $detail) {
            $detail->user?->delete();
        });
    }

    protected $fillable = [
        'user_id',
        'student_id',
        'first_name',
        'last_name',
        'department_id',
        'year_level'
    ];

    //Relationships
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function department(){
        return $this->belongsTo(Department::class);
    }

    public function getDepartmentNameAttribute() {
        return $this->department?->name ?? 'N/A';
    }

    public function getFullNameAttribute(){
        return "{$this->first_name} {$this->last_name}";
    }
}
