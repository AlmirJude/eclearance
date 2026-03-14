<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDetail extends Model
{
    protected static function booted(): void
    {
        static::deleted(function (StaffDetail $detail) {
            $detail->user?->delete();
        });
    }

    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'department',
        'position',
        'employee_type',
    ];

    //Relationships
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function getRoleAttribute(){
        return $this->user()->first()->role;
    }

    public function getFullNameAttribute(){
        return "{$this->first_name} {$this->last_name}";
    } 
}
