<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
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

    public function getFullNameAttribute(){
        return "{$this->first_name} {$this->last_name}";
    }

    public function getDepartmentAttribute() {
        return $this->department()->first()->name;
    }
}
