<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->fullname)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function GetTheUserName():string {
        if ($this->isStudent()) {
            return $this->studentDetail->fullname;
        } elseif ($this->isStaff()) {
            return $this->staffDetail->fullname;
        }
        return '';
    } 

        //Relationships
    public function studentDetail(){
        return $this->hasOne(StudentDetail::class);
    }

    public function staffDetail(){
        return $this->hasOne(StaffDetail::class);
    }

    public function managedDepartments(){
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function managedClubs(){
        return $this->hasMany(Club::class, 'moderator_id');
    }

    public function managedOffices(){
        return $this->hasMany(Office::class, 'manager_id');
    }

    public function clubMemberships(){
        return $this->belongsToMany(Club::class, 'club_memberships', 'student_id', 'club_id')
                    ->withTimestamps();
    }

    public function clubs () {
        return $this->belongsToMany(Club::class, 'club_memberships', 'student_id', 'club_id')
                    ->withTimestamps();
    }


    public function departmentSignatories(){
        return $this->belongsToMany(Department::class, 'department_signatories')
                    ->withPivot(['title', 'clearance_type', 'year_levels', 'is_active'])
                    ->withTimestamps();
    }

    public function clubSignatories(){
        return $this->belongsToMany(Club::class, 'club_signatories')
                    ->withPivot(['position', 'is_active'])
                    ->withTimestamps();
    }

    public function officeSignatories(){
        return $this->belongsToMany(Office::class, 'office_signatories')
                    ->withPivot(['title', 'year_levels', 'is_active'])
                    ->withTimestamps();
    }

    public function studentGovernmentOfficerships(){
        return $this->hasMany(StudentGovernmentOfficer::class, 'user_id');
    }

    //Helper Methods

    public function getFullNameAttribute(): string {
        if ($this->isStudent() && $this->studentDetail) {
            return $this->studentDetail->first_name . ' ' . $this->studentDetail->last_name;
        } elseif ($this->isStaff() && $this->staffDetail) {
            return $this->staffDetail->first_name . ' ' . $this->staffDetail->last_name;
        }
        return '';
    }

    public function getDepartmentAttribute(): string {
        if ($this->isStaff() && $this->staffDetail) {
            return $this->staffDetail->department;
        }
        return '';
    }

    public function isSuperAdmin(){
        return $this->role === 'superadmin';
    }

    public function isAdmin () {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isStaff () {
        return in_array($this->role, ['super_admin', 'admin', 'staff']);
    }

    public function isStudent () {
        return $this->role === 'student';
    }

}
