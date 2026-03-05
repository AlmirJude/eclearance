<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\User;
use Livewire\Component;

class DepartmentIndex extends Component
{
    // ── Add modal ─────────────────────────────────────────────────────────────
    public bool   $showAddModal    = false;
    public string $name            = '';
    public string $abbreviation    = '';
    public string $description     = '';
    public        $manager_id      = null;
    public string $managerSearch   = '';

    // ── Show (view) modal ─────────────────────────────────────────────────────
    public bool        $showViewModal       = false;
    public ?Department $selectedDepartment  = null;

    // ── Delete modal ──────────────────────────────────────────────────────────
    public bool        $showDeleteModal            = false;
    public ?Department $selectedDepartmentToDelete = null;

    // ── Edit modal ────────────────────────────────────────────────────────────
    public bool   $showEditModal        = false;
    public        $editId               = null;
    public string $editName             = '';
    public string $editAbbreviation     = '';
    public string $editDescription      = '';
    public        $editManagerId        = null;
    public string $editManagerSearch    = '';

    // ── Add validation ────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'         => 'required|string|max:255|unique:departments,name',
            'abbreviation' => 'required|string|max:20|unique:departments,Abbreviation',
            'description'  => 'nullable|string|max:500',
            'manager_id'   => 'nullable|exists:users,id',
        ];
    }

    protected $messages = [
        'name.required'         => 'Department name is required.',
        'name.unique'           => 'This department name already exists.',
        'abbreviation.required' => 'Abbreviation is required.',
        'abbreviation.unique'   => 'This abbreviation already exists.',
    ];

    // ── Add modal methods ─────────────────────────────────────────────────────
    public function openAddModal(): void
    {
        $this->reset(['name', 'abbreviation', 'description', 'manager_id', 'managerSearch']);
        $this->resetErrorBag();
        $this->showAddModal = true;
        $this->dispatch('open-modal', name: 'add-department-modal');
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->dispatch('close-modal', name: 'add-department-modal');
    }

    public function selectManager(int $id, string $label): void
    {
        $this->manager_id    = $id;
        $this->managerSearch = $label;
    }

    public function clearManager(): void
    {
        $this->manager_id    = null;
        $this->managerSearch = '';
    }

    public function saveNewDepartment(): void
    {
        $this->validate();

        Department::create([
            'name'         => $this->name,
            'abbreviation' => $this->abbreviation,
            'description'  => $this->description,
            'manager_id'   => $this->manager_id ?: null,
        ]);

        $this->closeAddModal();
        session()->flash('message', 'Department added successfully.');
    }

    // ── Show (view) modal methods ─────────────────────────────────────────────
    public function openShowModal(int $id): void
    {
        $this->selectedDepartment = Department::with(['manager.staffDetail'])->findOrFail($id);
        $this->showViewModal      = true;
        $this->dispatch('open-modal', name: 'view-department-modal');
    }

    public function closeShowModal(): void
    {
        $this->showViewModal      = false;
        $this->selectedDepartment = null;
        $this->dispatch('close-modal', name: 'view-department-modal');
    }

    // ── Edit modal methods ────────────────────────────────────────────────────
    public function openEditModal(int $id): void
    {
        $dept = Department::with('manager.staffDetail')->findOrFail($id);

        $this->editId            = $dept->id;
        $this->editName          = $dept->name;
        $this->editAbbreviation  = $dept->Abbreviation ?? '';
        $this->editDescription   = $dept->description ?? '';
        $this->editManagerId     = $dept->manager_id;

        if ($dept->manager) {
            $this->editManagerSearch = $dept->manager->staffDetail
                ? $dept->manager->staffDetail->fullname . ' (' . $dept->manager->user_id . ')'
                : $dept->manager->email . ' (' . $dept->manager->user_id . ')';
        } else {
            $this->editManagerSearch = '';
        }

        $this->resetErrorBag();
        $this->showEditModal = true;
        $this->dispatch('open-modal', name: 'edit-department-modal');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal     = false;
        $this->editId            = null;
        $this->editManagerSearch = '';
        $this->dispatch('close-modal', name: 'edit-department-modal');
    }

    public function selectEditManager(int $id, string $label): void
    {
        $this->editManagerId     = $id;
        $this->editManagerSearch = $label;
    }

    public function clearEditManager(): void
    {
        $this->editManagerId     = null;
        $this->editManagerSearch = '';
    }

    public function saveEditDepartment(): void
    {
        $this->validate([
            'editName'          => 'required|string|max:255|unique:departments,name,' . $this->editId,
            'editAbbreviation'  => 'required|string|max:20|unique:departments,Abbreviation,' . $this->editId,
            'editDescription'   => 'nullable|string|max:500',
            'editManagerId'     => 'nullable|exists:users,id',
        ], [
            'editName.required'         => 'Department name is required.',
            'editName.unique'           => 'This department name already exists.',
            'editAbbreviation.required' => 'Abbreviation is required.',
            'editAbbreviation.unique'   => 'This abbreviation already exists.',
        ]);

        $dept                = Department::findOrFail($this->editId);
        $dept->name          = $this->editName;
        $dept->Abbreviation  = $this->editAbbreviation;
        $dept->description   = $this->editDescription;
        $dept->manager_id    = $this->editManagerId ?: null;
        $dept->save();

        $this->closeEditModal();
        session()->flash('message', 'Department updated successfully.');
    }

    // ── Delete modal methods ──────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $this->selectedDepartmentToDelete = Department::find($id);
        $this->showDeleteModal            = true;
        $this->dispatch('open-modal', name: 'delete-department-modal');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal            = false;
        $this->selectedDepartmentToDelete = null;
        $this->dispatch('close-modal', name: 'delete-department-modal');
    }

    public function delete(): void
    {
        if ($this->selectedDepartmentToDelete) {
            $this->selectedDepartmentToDelete->delete();
            session()->flash('message', 'Department deleted successfully.');
        }
        $this->closeDeleteModal();
    }

    // ── Render ────────────────────────────────────────────────────────────────
    public function render()
    {
        $departments = Department::with('manager')->get();

        // Add modal manager search
        $addSearch      = trim($this->managerSearch);
        $managerResults = collect();
        if ($addSearch !== '' && $this->manager_id === null) {
            $managerResults = $this->searchManagers($addSearch);
        }

        // Edit modal manager search
        $editSearch          = trim($this->editManagerSearch);
        $editManagerResults  = collect();
        if ($editSearch !== '' && $this->editManagerId === null) {
            $editManagerResults = $this->searchManagers($editSearch);
        }

        return view('livewire.department.department-index',
            compact('departments', 'managerResults', 'editManagerResults'));
    }

    private function searchManagers(string $search)
    {
        return User::where(function ($q) {
                $q->where('role', 'staff')->orWhere('role', 'admin');
            })
            ->where(function ($q) use ($search) {
                $q->where('user_id', 'like', "%{$search}%")
                  ->orWhereHas('staffDetail', fn($sq) =>
                      $sq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name',  'like', "%{$search}%")
                  );
            })
            ->with('staffDetail')
            ->limit(8)
            ->get();
    }
}
