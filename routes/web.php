<?php

use App\Livewire\Clearance\SignatoryDashboard;
use App\Livewire\Clearance\StudentDashboard;
use App\Livewire\Clearance\SubmitRequirements;
use App\Livewire\Club\AddClub;
use App\Livewire\Club\ClubIndex;
use App\Livewire\Club\ClubOverview;
use App\Livewire\Club\EditClub;
use App\Livewire\Club\ManageMembers;
use App\Livewire\Club\ManageRequirements as ClubManageRequirements;
use App\Livewire\Club\ManageSignatories as ClubManageSignatories;
use App\Livewire\Clearance\ManagePeriods;
use App\Livewire\Department\AddDepartment;
use App\Livewire\Department\DepartmentEdit;
use App\Livewire\Department\DepartmentIndex;
use App\Livewire\Department\DepartmentOverview;
use App\Livewire\Department\DepartmentStudents;
use App\Livewire\Department\ManageSignatories;
use App\Livewire\Homeroom\ManageAssignments;
use App\Livewire\Homeroom\ManageRequirements as HomeroomManageRequirements;
use App\Livewire\Office\AddOffice;
use App\Livewire\Office\EditOffice;
use App\Livewire\Office\OfficeIndex;
use App\Livewire\Office\OfficeOverview;
use App\Livewire\Office\ManageRequirements as OfficeManageRequirements;
use App\Livewire\Office\ManageSignatories as OfficeManageSignatories;
use App\Livewire\StudentGovernment\Index as StudentGovernmentIndex;
use App\Livewire\StudentGovernment\ManageOfficers;
use App\Livewire\Users\AddStaff;
use App\Livewire\Users\AddStudent;
use App\Livewire\Users\EditStaff;
use App\Livewire\Users\EditStudent;
use App\Livewire\Users\StaffIndex;
use App\Livewire\Users\StudentIndex;
use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('admin/dashboard', 'admin.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('admin.dashboard');


Route::middleware(['auth', 'verified'])->group(function() {

    Route::prefix('dashboard')->group(function() {
        Route::view('/', 'dashboard') -> name('dashboard') -> middleware('role:superadmin,admin,staff,student');
        Route::get('/users', UserIndex::class) -> name('users.index') -> middleware('role:superadmin,admin');


        Route::get('/students', StudentIndex::class) -> name('student.index') -> middleware('role:superadmin,admin');
        Route::get('/students/add', AddStudent::class) -> name('add.students') -> middleware('role:superadmin,admin');
        Route::get('/students/{id}/edit', EditStudent::class) -> name('edit.students') -> middleware('role:superadmin,admin');
        
        
        Route::get('/staffs', StaffIndex::class) -> name('staff.index') -> middleware('role:superadmin,admin');
        Route::get('/staffs/add', AddStaff::class) -> name('staff.add') -> middleware('role:superadmin,admin');
        Route::get('/staffs/{id}/edit', EditStaff::class) -> name('staff.edit') -> middleware('role:superadmin,admin');

        Route::get('/departments', DepartmentIndex::class) -> name('department.index') -> middleware('role:superadmin,admin');
        Route::get('/departments/add', AddDepartment::class) -> name('department.add') -> middleware('role:superadmin,admin');
        Route::get('/departments/{id}/edit', DepartmentEdit::class) -> name('department.edit') -> middleware('role:superadmin,admin,staff');
        Route::get('/departments/{id}/signatories', ManageSignatories::class) -> name('department.signatories') -> middleware('role:superadmin,admin,staff');

        Route::get('/clubs', ClubIndex::class) -> name('club.index') -> middleware('role:superadmin,admin');
        Route::get('/clubs/add', AddClub::class) -> name('club.add') -> middleware('role:superadmin,admin');
        Route::get('/clubs/{id}/edit', EditClub::class) -> name('club.edit')  -> middleware('role:superadmin,admin,staff');
        Route::get('/clubs/{id}/members', ManageMembers::class) -> name('club.members') -> middleware('role:superadmin,admin,staff,student');
        Route::get('/clubs/{id}/signatories', ClubManageSignatories::class) -> name('club.signatories') -> middleware('role:superadmin,admin,staff');
        Route::get('/clubs/{id}/requirements', ClubManageRequirements::class) -> name('club.requirements') -> middleware('role:superadmin,admin,staff,student');

        Route::get('/offices', OfficeIndex::class) -> name('office.index') -> middleware('role:superadmin,admin');
        Route::get('/offices/add', AddOffice::class) -> name('office.add') -> middleware('role:superadmin,admin');
        Route::get('/offices/{id}/edit', EditOffice::class) -> name('office.edit') -> middleware('role:superadmin,admin,staff');
        Route::get('/offices/{id}/signatories', OfficeManageSignatories::class) -> name('office.signatories') -> middleware('role:superadmin,admin,staff');
        Route::get('/offices/{id}/requirements', OfficeManageRequirements::class) -> name('office.requirements') -> middleware('role:superadmin,admin,staff,student');

        Route::get('/homeroom-assignments', ManageAssignments::class) -> name('homeroom.assignments') -> middleware('role:superadmin,admin');
        Route::get('/homeroom-assignments/{id}/requirements', HomeroomManageRequirements::class) -> name('homeroom.requirements') -> middleware('role:superadmin,admin,staff');

        Route::get('/student-government', StudentGovernmentIndex::class) -> name('student-government.index') -> middleware('role:superadmin,admin');
        Route::get('/student-government/{studentGovernmentId}/officers', ManageOfficers::class) -> name('student-government.officers') -> middleware('role:superadmin,admin');

        // Clearance Management
        Route::get('/clearance-periods', ManagePeriods::class) -> name('clearance.periods') -> middleware('role:superadmin,admin');

        // Student Clearance Dashboard
        Route::get('/my-clearance', StudentDashboard::class) -> name('clearance.student') -> middleware('role:student');
        Route::get('/my-clearance/{itemId}/requirements', SubmitRequirements::class) -> name('clearance.submit-requirements') -> middleware('role:student');

        // Signatory Dashboard
        Route::get('/signatory', SignatoryDashboard::class) -> name('clearance.signatory') -> middleware('role:superadmin,admin,staff,student');

        Route::prefix('department/{departmentId}')->middleware('role:superadmin,admin,staff')->group(function() {
            Route::get('/overview', DepartmentOverview::class)->name('department.overview');
            Route::get('/students', DepartmentStudents::class)->name('department.students');
            // Route::get('/clearances', DepartmentClearance::class)->name('department.clearances');
            // Route::get('/signatories', DepartmentSignatories::class)->name('department.signatories');
        });

        // Club Routes (for moderators and signatories)
        Route::prefix('club/{clubId}')->middleware('role:superadmin,admin,staff,student')->group(function() {
            Route::get('/overview', ClubOverview::class)->name('club.overview');
        });

        // Office Routes (for managers and signatories)
        Route::prefix('office/{officeId}')->middleware('role:superadmin,admin,staff')->group(function() {
            Route::get('/overview', OfficeOverview::class)->name('office.overview');
        });
    });
});




Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
