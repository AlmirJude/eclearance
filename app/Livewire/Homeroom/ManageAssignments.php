<?php

namespace App\Livewire\Homeroom;

use App\Models\Department;
use App\Models\HomeroomAssignment;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class ManageAssignments extends Component
{
    public $assignments;
    public bool $showAddModal    = false;
    public $editingId            = null;

    // Form fields
    public $adviser_id           = null;
    public string $adviserSearch = '';
    public $department_id        = '';
    public $year_levels          = [];
    public $section              = '';
    public $academic_year        = '';
    public $is_active            = true;

    // Delete modal
    public bool $showDeleteModal                  = false;
    public ?HomeroomAssignment $assignmentToDelete = null;

    public function mount()
    {
        $this->loadAssignments();
        $this->academic_year = $this->getCurrentAcademicYear();
    }

    public function loadAssignments()
    {
        $this->assignments = HomeroomAssignment::with(['adviser.staffDetail', 'department'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('department_id')
            ->orderBy('section')
            ->get();
    }

    public function getCurrentAcademicYear()
    {
        $year = now()->year;
        $month = now()->month;
        
        // If month is June or later, it's the start of new academic year
        if ($month >= 6) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    public function openAddModal(): void
    {
        $this->reset(['adviser_id', 'adviserSearch', 'department_id', 'year_levels', 'section', 'editingId']);
        $this->academic_year = $this->getCurrentAcademicYear();
        $this->is_active = true;
        $this->showAddModal = true;
    }

    public function openEditModal($id): void
    {
        $assignment = HomeroomAssignment::with(['adviser.staffDetail'])->findOrFail($id);

        $this->editingId        = $assignment->id;
        $this->adviser_id       = $assignment->adviser_id;
        $this->department_id    = $assignment->department_id;
        $this->year_levels      = $assignment->year_levels ?? [];
        $this->section          = $assignment->section;
        $this->academic_year    = $assignment->academic_year;
        $this->is_active        = $assignment->is_active;

        // Pre-fill the search box with the current adviser label
        $adviser = $assignment->adviser;
        if ($adviser) {
            $name = $adviser->staffDetail->fullname ?? $adviser->email;
            $this->adviserSearch = $name . ' (' . $adviser->user_id . ')';
        }

        $this->showAddModal = true;
    }

    public function selectAdviser(int $id, string $label): void
    {
        $this->adviser_id    = $id;
        $this->adviserSearch = $label;
    }

    public function clearAdviser(): void
    {
        $this->adviser_id    = null;
        $this->adviserSearch = '';
    }

    public function confirmDelete(int $id): void
    {
        $this->assignmentToDelete = HomeroomAssignment::with(['adviser.staffDetail', 'department'])->findOrFail($id);
        $this->showDeleteModal    = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal    = false;
        $this->assignmentToDelete = null;
    }

    protected function rules()
    {
        $rules = [
            'adviser_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'year_levels' => 'required|array|min:1',
            'year_levels.*' => 'integer|min:1|max:6',
            'section' => 'nullable|string|max:10',
            'academic_year' => 'required|string',
            'is_active' => 'boolean',
        ];

        return $rules;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            // Update existing
            $assignment = HomeroomAssignment::findOrFail($this->editingId);
            $assignment->update([
                'adviser_id' => $this->adviser_id,
                'department_id' => $this->department_id,
                'year_levels' => $this->year_levels,
                'section' => $this->section,
                'academic_year' => $this->academic_year,
                'is_active' => $this->is_active,
            ]);

            session()->flash('success', 'Homeroom assignment updated successfully.');
        } else {
            // Create new
            HomeroomAssignment::create([
                'adviser_id' => $this->adviser_id,
                'department_id' => $this->department_id,
                'year_levels' => $this->year_levels,
                'section' => $this->section,
                'academic_year' => $this->academic_year,
                'is_active' => $this->is_active,
            ]);

            session()->flash('success', 'Homeroom assignment created successfully.');
        }

        $this->showAddModal = false;
        $this->loadAssignments();
    }

    public function delete(): void
    {
        if (! $this->assignmentToDelete) return;

        $this->assignmentToDelete->delete();
        $this->showDeleteModal    = false;
        $this->assignmentToDelete = null;

        session()->flash('success', 'Homeroom assignment deleted successfully.');
        $this->loadAssignments();
    }

    public function toggleActive($id)
    {
        $assignment = HomeroomAssignment::findOrFail($id);
        $assignment->update(['is_active' => !$assignment->is_active]);

        session()->flash('success', 'Assignment status updated.');
        $this->loadAssignments();
    }

    private function searchAdvisers(string $query): \Illuminate\Support\Collection
    {
        $q = trim($query);
        return User::where(function ($builder) use ($q) {
                $builder->where('role', 'staff')->orWhere('role', 'admin');
            })
            ->where(function ($builder) use ($q) {
                $builder->where('user_id', 'like', "%{$q}%")
                    ->orWhereHas('staffDetail', fn($s) => $s
                        ->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name',  'like', "%{$q}%")
                    );
            })
            ->with('staffDetail')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        $departments = Department::orderBy('name')->get();

        $adviserResults = collect();
        if (strlen(trim($this->adviserSearch)) >= 1 && ! $this->adviser_id) {
            $adviserResults = $this->searchAdvisers($this->adviserSearch);
        }

        return view('livewire.homeroom.manage-assignments',
            compact('departments', 'adviserResults'));
    }
}
