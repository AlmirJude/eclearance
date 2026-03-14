<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 2. Add this attribute to force the wrapper
class ClubIndex extends Component
{
    // ── Add modal ──────────────────────────────────────────────────────────────
    public bool   $showAddModal      = false;
    public string $name              = '';
    public string $type              = 'socio_civic';
    public string $description       = '';
    public string $abbreviation      = '';
    public        $moderator_id      = null;
    public string $moderatorSearch   = '';

    // ── Show modal ─────────────────────────────────────────────────────────────
    public bool  $showViewModal  = false;
    public ?Club $selectedClub   = null;

    // ── Edit modal ─────────────────────────────────────────────────────────────
    public bool   $showEditModal         = false;
    public        $editId                = null;
    public string $editName              = '';
    public string $editType              = 'socio_civic';
    public string $editDescription       = '';
    public string $editAbbreviation      = '';
    public        $editModeratorId       = null;
    public string $editModeratorSearch   = '';

    // ── Delete modal ───────────────────────────────────────────────────────────
    public bool  $showDeleteModal    = false;
    public ?Club $clubToDelete       = null;

    // ── Add validation ─────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'         => 'required|string|max:255|unique:clubs,name',
            'type'         => 'required|in:academic,religious,socio_civic',
            'description'  => 'nullable|string|max:1000',
            'abbreviation' => 'nullable|string|max:50',
            'moderator_id' => 'nullable|exists:users,id',
        ];
    }

    protected $messages = [
        'name.required' => 'Club name is required.',
        'name.unique'   => 'This club name already exists.',
        'type.required' => 'Club type is required.',
        'type.in'       => 'Invalid club type selected.',
    ];

    // ── Add modal methods ──────────────────────────────────────────────────────
    public function openAddModal(): void
    {
        $this->reset(['name', 'type', 'description', 'abbreviation', 'moderator_id', 'moderatorSearch']);
        $this->type = 'socio_civic';
        $this->resetErrorBag();
        $this->showAddModal = true;
        $this->dispatch('open-modal', name: 'add-club-modal');
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->dispatch('close-modal', name: 'add-club-modal');
    }

    public function selectModerator(int $id, string $label): void
    {
        $this->moderator_id    = $id;
        $this->moderatorSearch = $label;
    }

    public function clearModerator(): void
    {
        $this->moderator_id    = null;
        $this->moderatorSearch = '';
    }

    public function saveNewClub(): void
    {
        $this->validate();

        Club::create([
            'name'         => $this->name,
            'type'         => $this->type,
            'description'  => $this->description,
            'abbreviation' => $this->abbreviation,
            'moderator_id' => $this->moderator_id ?: null,
        ]);

        $this->closeAddModal();
        session()->flash('success', 'Club added successfully.');
    }

    // ── Show modal methods ─────────────────────────────────────────────────────
    public function openShowModal(int $id): void
    {
        $this->selectedClub = Club::with('moderator.staffDetail')->findOrFail($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-club-modal');
    }

    public function closeShowModal(): void
    {
        $this->showViewModal = false;
        $this->selectedClub  = null;
        $this->dispatch('close-modal', name: 'view-club-modal');
    }

    // ── Edit modal methods ─────────────────────────────────────────────────────
    public function openEditModal(int $id): void
    {
        $club = Club::with('moderator.staffDetail')->findOrFail($id);

        $this->editId           = $club->id;
        $this->editName         = $club->name;
        $this->editType         = $club->type;
        $this->editDescription  = $club->description ?? '';
        $this->editAbbreviation = $club->Abbreviation ?? '';
        $this->editModeratorId  = $club->moderator_id;

        if ($club->moderator) {
            $this->editModeratorSearch = $club->moderator->staffDetail
                ? $club->moderator->staffDetail->fullname . ' (' . $club->moderator->user_id . ')'
                : $club->moderator->email . ' (' . $club->moderator->user_id . ')';
        } else {
            $this->editModeratorSearch = '';
        }

        $this->resetErrorBag();
        $this->showEditModal = true;
        $this->dispatch('open-modal', name: 'edit-club-modal');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal      = false;
        $this->editId             = null;
        $this->editModeratorSearch = '';
        $this->dispatch('close-modal', name: 'edit-club-modal');
    }

    public function selectEditModerator(int $id, string $label): void
    {
        $this->editModeratorId     = $id;
        $this->editModeratorSearch = $label;
    }

    public function clearEditModerator(): void
    {
        $this->editModeratorId     = null;
        $this->editModeratorSearch = '';
    }

    public function saveEditClub(): void
    {
        $this->validate([
            'editName'         => 'required|string|max:255|unique:clubs,name,' . $this->editId,
            'editType'         => 'required|in:academic,religious,socio_civic',
            'editDescription'  => 'nullable|string|max:1000',
            'editAbbreviation' => 'nullable|string|max:50',
            'editModeratorId'  => 'nullable|exists:users,id',
        ], [
            'editName.required' => 'Club name is required.',
            'editName.unique'   => 'This club name already exists.',
            'editType.required' => 'Club type is required.',
            'editType.in'       => 'Invalid club type selected.',
        ]);

        $club               = Club::findOrFail($this->editId);
        $club->name         = $this->editName;
        $club->type         = $this->editType;
        $club->description  = $this->editDescription;
        $club->Abbreviation = $this->editAbbreviation;
        $club->moderator_id = $this->editModeratorId ?: null;
        $club->save();

        $this->closeEditModal();
        session()->flash('success', 'Club updated successfully.');
    }

    // ── Delete modal methods ───────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $this->clubToDelete    = Club::find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'delete-club-modal');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->clubToDelete    = null;
        $this->dispatch('close-modal', name: 'delete-club-modal');
    }

    public function delete(): void
    {
        if ($this->clubToDelete) {
            $this->clubToDelete->delete();
            session()->flash('success', 'Club deleted successfully.');
        }
        $this->closeDeleteModal();
    }

    // ── Render ─────────────────────────────────────────────────────────────────
    public function render()
    {
        $clubs = Club::with('moderator')->orderBy('type')->get();

        $addSearch         = trim($this->moderatorSearch);
        $moderatorResults  = collect();
        if ($addSearch !== '' && $this->moderator_id === null) {
            $moderatorResults = $this->searchModerators($addSearch);
        }

        $editSearch             = trim($this->editModeratorSearch);
        $editModeratorResults   = collect();
        if ($editSearch !== '' && $this->editModeratorId === null) {
            $editModeratorResults = $this->searchModerators($editSearch);
        }

        return view('livewire.club.club-index',
            compact('clubs', 'moderatorResults', 'editModeratorResults'));
    }

    private function searchModerators(string $search)
    {
        return User::where(function ($q) {
                $q->where('role', 'staff')
                  ->orWhere('role', 'admin')
                  ->orWhere('role', 'superadmin');
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
