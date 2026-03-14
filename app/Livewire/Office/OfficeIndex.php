<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class OfficeIndex extends Component
{
    // ── Add modal ──────────────────────────────────────────────────────────────
    public bool   $showAddModal      = false;
    public string $name              = '';
    public        $is_required       = true;
    public        $clearance_order   = 0;
    public        $manager_id        = null;
    public string $managerSearch     = '';

    // ── Show modal ─────────────────────────────────────────────────────────────
    public bool    $showViewModal   = false;
    public ?Office $selectedOffice  = null;

    // ── Edit modal ─────────────────────────────────────────────────────────────
    public bool   $showEditModal         = false;
    public        $editId                = null;
    public string $editName              = '';
    public        $editIsRequired        = true;
    public        $editClearanceOrder    = 0;
    public        $editManagerId         = null;
    public string $editManagerSearch     = '';

    // ── Delete modal ───────────────────────────────────────────────────────────
    public bool    $showDeleteModal  = false;
    public ?Office $officeToDelete   = null;

    // ── Add validation ─────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255|unique:offices,name',
            'manager_id'      => 'nullable|exists:users,id',
            'is_required'     => 'boolean',
            'clearance_order' => 'integer|min:0',
        ];
    }

    protected $messages = [
        'name.required' => 'Office name is required.',
        'name.unique'   => 'This office name already exists.',
    ];

    // ── Add modal methods ──────────────────────────────────────────────────────
    public function openAddModal(): void
    {
        $this->reset(['name', 'manager_id', 'managerSearch']);
        $this->is_required     = true;
        $this->clearance_order = 0;
        $this->resetErrorBag();
        $this->showAddModal = true;
        $this->dispatch('open-modal', name: 'add-office-modal');
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->dispatch('close-modal', name: 'add-office-modal');
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

    public function saveNewOffice(): void
    {
        $this->validate();

        Office::create([
            'name'            => $this->name,
            'manager_id'      => $this->manager_id ?: null,
            'is_required'     => $this->is_required,
            'clearance_order' => 0
        ]);

        $this->closeAddModal();
        session()->flash('success', 'Office added successfully.');
    }

    // ── Show modal methods ─────────────────────────────────────────────────────
    public function openShowModal(int $id): void
    {
        $this->selectedOffice = Office::with('manager.staffDetail')->findOrFail($id);
        $this->showViewModal  = true;
        $this->dispatch('open-modal', name: 'view-office-modal');
    }

    public function closeShowModal(): void
    {
        $this->showViewModal  = false;
        $this->selectedOffice = null;
        $this->dispatch('close-modal', name: 'view-office-modal');
    }

    // ── Edit modal methods ─────────────────────────────────────────────────────
    public function openEditModal(int $id): void
    {
        $office = Office::with('manager.staffDetail')->findOrFail($id);

        $this->editId             = $office->id;
        $this->editName           = $office->name;
        $this->editIsRequired     = (bool) $office->is_required;
        $this->editClearanceOrder = $office->clearance_order ?? 0;
        $this->editManagerId      = $office->manager_id;

        if ($office->manager) {
            $this->editManagerSearch = $office->manager->staffDetail
                ? $office->manager->staffDetail->fullname . ' (' . $office->manager->user_id . ')'
                : $office->manager->email . ' (' . $office->manager->user_id . ')';
        } else {
            $this->editManagerSearch = '';
        }

        $this->resetErrorBag();
        $this->showEditModal = true;
        $this->dispatch('open-modal', name: 'edit-office-modal');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal     = false;
        $this->editId            = null;
        $this->editManagerSearch = '';
        $this->dispatch('close-modal', name: 'edit-office-modal');
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

    public function saveEditOffice(): void
    {
        $this->validate([
            'editName'           => 'required|string|max:255|unique:offices,name,' . $this->editId,
            'editManagerId'      => 'nullable|exists:users,id',
            'editIsRequired'     => 'boolean',
            'editClearanceOrder' => 'integer|min:0',
        ], [
            'editName.required' => 'Office name is required.',
            'editName.unique'   => 'This office name already exists.',
        ]);

        $office                  = Office::findOrFail($this->editId);
        $office->name            = $this->editName;
        $office->manager_id      = $this->editManagerId ?: null;
        $office->is_required     = $this->editIsRequired;
        $office->clearance_order = $this->editClearanceOrder;
        $office->save();

        $this->closeEditModal();
        session()->flash('success', 'Office updated successfully.');
    }

    // ── Delete modal methods ───────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $this->officeToDelete  = Office::find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'delete-office-modal');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->officeToDelete  = null;
        $this->dispatch('close-modal', name: 'delete-office-modal');
    }

    public function delete(): void
    {
        if ($this->officeToDelete) {
            $this->officeToDelete->delete();
            session()->flash('success', 'Office deleted successfully.');
        }
        $this->closeDeleteModal();
    }

    // ── Render ─────────────────────────────────────────────────────────────────
    public function render()
    {
        $offices = Office::with('manager')->get();

        $addSearch      = trim($this->managerSearch);
        $managerResults = collect();
        if ($addSearch !== '' && $this->manager_id === null) {
            $managerResults = $this->searchManagers($addSearch);
        }

        $editSearch          = trim($this->editManagerSearch);
        $editManagerResults  = collect();
        if ($editSearch !== '' && $this->editManagerId === null) {
            $editManagerResults = $this->searchManagers($editSearch);
        }

        return view('livewire.office.office-index',
            compact('offices', 'managerResults', 'editManagerResults'));
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
