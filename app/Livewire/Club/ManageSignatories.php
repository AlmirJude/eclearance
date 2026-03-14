<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class ManageSignatories extends Component
{
    public $clubId;
    public $club;
    public $signatories;
    public $availableUsers;
    public $showAddModal = false;
    public $editingId = null;

    // Form fields
    public $user_id = '';
    public $position = 'moderator';
    public $is_active = true;
    public $userSearch = '';
    
    public $positionOptions = ['moderator', 'president', 'treasurer'];

    public function mount($id)
    {
        $this->clubId = $id;
        $this->loadClubData();
        $this->authorizeAccess();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        if ($this->club->moderator_id !== $user->id) {
            abort(403, 'You are not the moderator of this club.');
        }
    }

    public function loadClubData()
    {
        $this->club = Club::with('moderator')->findOrFail($this->clubId);
        
        // Get current signatories
        $this->signatories = DB::table('club_signatories')
            ->where('club_id', $this->clubId)
            ->get();

        // Load user details for signatories
        $userIds = $this->signatories->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->with(['staffDetail', 'studentDetail'])->get()->keyBy('id');
        
        // Attach user data to signatories
        $this->signatories = $this->signatories->map(function($signatory) use ($users) {
            $signatory->user = $users[$signatory->user_id] ?? null;
            return $signatory;
        });

        // Get available users (staff, admin, or students - not already signatories)
        $this->availableUsers = User::whereIn('role', ['staff', 'admin', 'student'])
            ->whereNotIn('id', $userIds)
            ->with(['staffDetail', 'studentDetail'])
            ->get();
    }

    public function openAddModal()
    {
        $this->reset(['user_id', 'position', 'editingId', 'userSearch']);
        $this->position = 'moderator';
        $this->is_active = true;
        $this->showAddModal = true;
    }

    public function updatedUserSearch()
    {
        // Filter available users based on search
        $userIds = $this->signatories->pluck('user_id')->toArray();
        
        $query = User::whereIn('role', ['staff', 'admin', 'student'])
            ->whereNotIn('id', $userIds)
            ->with(['staffDetail', 'studentDetail']);

        if (!empty($this->userSearch)) {
            $query->where(function($q) {
                $q->whereHas('staffDetail', function($subQ) {
                    $subQ->where('first_name', 'like', '%' . $this->userSearch . '%')
                         ->orWhere('last_name', 'like', '%' . $this->userSearch . '%')
                         ->orWhere('employee_id', 'like', '%' . $this->userSearch . '%');
                })
                ->orWhereHas('studentDetail', function($subQ) {
                    $subQ->where('first_name', 'like', '%' . $this->userSearch . '%')
                         ->orWhere('last_name', 'like', '%' . $this->userSearch . '%')
                         ->orWhere('student_id', 'like', '%' . $this->userSearch . '%');
                });
            });
        }

        $this->availableUsers = $query->get();
    }

    public function openEditModal($id)
    {
        $signatory = DB::table('club_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            $this->editingId = $signatory->id;
            $this->user_id = $signatory->user_id;
            $this->position = $signatory->position;
            $this->is_active = $signatory->is_active;
            
            $this->showAddModal = true;
        }
    }

    protected function rules()
    {
        $rules = [
            'position' => 'required|in:moderator,president,treasurer',
            'is_active' => 'boolean',
        ];

        if (!$this->editingId) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'position' => $this->position,
            'is_active' => $this->is_active,
            'updated_at' => now(),
        ];

        if ($this->editingId) {
            // Update existing signatory
            DB::table('club_signatories')
                ->where('id', $this->editingId)
                ->update($data);

            session()->flash('success', 'Signatory updated successfully.');
        } else {
            // Create new signatory
            $data['club_id'] = $this->clubId;
            $data['user_id'] = $this->user_id;
            $data['created_at'] = now();

            DB::table('club_signatories')->insert($data);

            session()->flash('success', 'Signatory added successfully.');
        }

        $this->showAddModal = false;
        $this->loadClubData();
    }

    public function remove($id)
    {
        DB::table('club_signatories')->where('id', $id)->delete();
        session()->flash('success', 'Signatory removed successfully.');
        $this->loadClubData();
    }

    public function toggleActive($id)
    {
        $signatory = DB::table('club_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            DB::table('club_signatories')
                ->where('id', $id)
                ->update(['is_active' => !$signatory->is_active]);
            
            session()->flash('success', 'Signatory status updated.');
            $this->loadClubData();
        }
    }

    public function render()
    {
        return view('livewire.club.manage-signatories');
    }
}
