<?php

namespace App\Livewire\StudentGovernment;

use Livewire\Component;
use App\Models\StudentGovernment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManageOfficers extends Component
{
    public $studentGovernmentId;
    public $studentGovernment;
    public $officers = [];
    
    // Form fields
    public $showModal = false;
    public $editingOfficerId = null;
    public $selectedStudentId = '';
    public $position = '';
    public $canSign = true;
    public $selectedYearLevels = [];
    
    // Search
    public $studentSearch = '';
    public $availableStudents = [];

    protected $rules = [
        'selectedStudentId' => 'required',
        'position' => 'required|string|max:100',
    ];

    public function mount($studentGovernmentId)
    {
        $this->studentGovernmentId = $studentGovernmentId;
        $this->loadData();
    }

    public function loadData()
    {
        $this->studentGovernment = StudentGovernment::with('department')->find($this->studentGovernmentId);
        
        if (!$this->studentGovernment) {
            abort(404);
        }

        $this->officers = DB::table('student_government_officers')
            ->join('users', 'student_government_officers.user_id', '=', 'users.id')
            ->leftJoin('student_details', 'users.id', '=', 'student_details.user_id')
            ->where('student_government_officers.student_government_id', $this->studentGovernmentId)
            ->select(
                'student_government_officers.*',
                'student_details.first_name',
                'student_details.last_name',
                'student_details.student_id',
                'student_details.year_level'
            )
            ->orderBy('student_government_officers.position')
            ->get();
    }

    public function updatedStudentSearch()
    {
        if (strlen($this->studentSearch) < 2) {
            $this->availableStudents = [];
            return;
        }

        $this->availableStudents = User::where('role', 'student')
            ->whereHas('studentDetail', function($query) {
                $query->where('student_id', 'like', '%' . $this->studentSearch . '%')
                    ->orWhere('first_name', 'like', '%' . $this->studentSearch . '%')
                    ->orWhere('last_name', 'like', '%' . $this->studentSearch . '%');
            })
            ->with('studentDetail.department')
            ->limit(10)
            ->get();
    }

    public function openModal($officerId = null)
    {
        $this->resetForm();
        $this->editingOfficerId = $officerId;

        if ($officerId) {
            $officer = DB::table('student_government_officers')
                ->where('id', $officerId)
                ->first();

            $this->selectedStudentId = $officer->user_id;
            $this->position = $officer->position;
            $this->canSign = (bool)$officer->can_sign;
            $this->selectedYearLevels = json_decode($officer->year_levels ?? '[]', true);
        }

        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->reset(['selectedStudentId', 'position', 'canSign', 'selectedYearLevels', 'studentSearch', 'availableStudents', 'editingOfficerId']);
        $this->canSign = true;
    }

    public function save()
    {
        $validated = $this->validate();

        try {
            $data = [
                'student_government_id' => $this->studentGovernmentId,
                'user_id' => $this->selectedStudentId,
                'position' => $this->position,
                'can_sign' => $this->canSign ? 1 : 0,
                'year_levels' => !empty($this->selectedYearLevels) ? json_encode(array_values($this->selectedYearLevels)) : null,
                'is_active' => 1,
            ];

            if ($this->editingOfficerId) {
                DB::table('student_government_officers')
                    ->where('id', $this->editingOfficerId)
                    ->update(array_merge($data, ['updated_at' => now()]));
                    
                $message = 'Officer updated successfully.';
            } else {
                // Check if student is already an officer
                $exists = DB::table('student_government_officers')
                    ->where('student_government_id', $this->studentGovernmentId)
                    ->where('user_id', $this->selectedStudentId)
                    ->exists();

                if ($exists) {
                    session()->flash('error', 'This student is already an officer.');
                    return;
                }

                DB::table('student_government_officers')->insert(array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                
                $message = 'Officer added successfully.';
            }

            $this->showModal = false;
            $this->resetForm();
            $this->loadData();
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save officer: ' . $e->getMessage());
        }
    }

    public function remove($officerId)
    {
        DB::table('student_government_officers')
            ->where('id', $officerId)
            ->delete();

        $this->loadData();
        session()->flash('success', 'Officer removed successfully.');
    }

    public function toggleActive($officerId)
    {
        $officer = DB::table('student_government_officers')
            ->where('id', $officerId)
            ->first();

        DB::table('student_government_officers')
            ->where('id', $officerId)
            ->update([
                'is_active' => !$officer->is_active,
                'updated_at' => now()
            ]);

        $this->loadData();
    }

    public function render()
    {
        return view('livewire.student-government.manage-officers');
    }
}
