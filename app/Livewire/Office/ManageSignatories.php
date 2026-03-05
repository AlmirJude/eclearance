<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\User;
use App\Models\Department;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageSignatories extends Component
{
    public $officeId;
    public $office;
    public $signatories;
    public $availableStaff;
    public $allDepartments;
    public $showAddModal = false;
    public $editingId = null;

    // Form fields
    public $user_id = '';
    public $title = '';
    public $departments = [];
    public $year_levels = [];
    public $is_active = true;
    public $staffSearch = '';

    public function mount($id)
    {
        $this->officeId = $id;
        $this->loadOfficeData();
        $this->authorizeAccess();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        if ($this->office->manager_id !== $user->id) {
            abort(403, 'You are not the manager of this office.');
        }
    }

    public function loadOfficeData()
    {
        $this->office = Office::with('manager')->findOrFail($this->officeId);
        
        // Get current signatories
        $this->signatories = DB::table('office_signatories')
            ->where('office_id', $this->officeId)
            ->get();

        // Load user details for signatories
        $userIds = $this->signatories->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->with('staffDetail')->get()->keyBy('id');
        
        // Attach user data to signatories
        $this->signatories = $this->signatories->map(function($signatory) use ($users) {
            $signatory->user = $users[$signatory->user_id] ?? null;
            $signatory->departments = json_decode($signatory->departments, true);
            $signatory->year_levels = json_decode($signatory->year_levels, true);
            return $signatory;
        });

        // Get available staff (not already signatories)
        $this->availableStaff = User::where('role', 'staff')
            ->orWhere('role', 'admin')
            ->whereNotIn('id', $userIds)
            ->with('staffDetail')
            ->get();

        // Load all departments for selection
        $this->allDepartments = Department::orderBy('name')->get();
    }

    public function openAddModal()
    {
        $this->reset(['user_id', 'title', 'departments', 'year_levels', 'editingId', 'staffSearch']);
        $this->is_active = true;
        $this->showAddModal = true;
    }

    public function updatedStaffSearch()
    {
        // Filter available staff based on search
        $userIds = $this->signatories->pluck('user_id')->toArray();
        
        $query = User::where(function($q) {
            $q->where('role', 'staff')->orWhere('role', 'admin');
        })
        ->whereNotIn('id', $userIds)
        ->with('staffDetail');

        if (!empty($this->staffSearch)) {
            $query->where(function($q) {
                $q->whereHas('staffDetail', function($subQ) {
                    $subQ->where('first_name', 'like', '%' . $this->staffSearch . '%')
                         ->orWhere('last_name', 'like', '%' . $this->staffSearch . '%')
                         ->orWhere('employee_id', 'like', '%' . $this->staffSearch . '%');
                });
            });
        }

        $this->availableStaff = $query->get();
    }

    public function openEditModal($id)
    {
        $signatory = DB::table('office_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            $this->editingId = $signatory->id;
            $this->user_id = $signatory->user_id;
            $this->title = $signatory->title;
            $this->departments = json_decode($signatory->departments, true) ?? [];
            $this->year_levels = json_decode($signatory->year_levels, true) ?? [];
            $this->is_active = $signatory->is_active;
            
            $this->showAddModal = true;
        }
    }

    protected function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
            'year_levels' => 'nullable|array',
            'year_levels.*' => 'integer|min:1|max:6',
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
            'title' => $this->title,
            'departments' => !empty($this->departments) ? json_encode($this->departments) : null,
            'year_levels' => !empty($this->year_levels) ? json_encode($this->year_levels) : null,
            'is_active' => $this->is_active,
            'updated_at' => now(),
        ];

        if ($this->editingId) {
            // Update existing signatory
            DB::table('office_signatories')
                ->where('id', $this->editingId)
                ->update($data);

            session()->flash('success', 'Signatory updated successfully.');
        } else {
            // Create new signatory
            $data['office_id'] = $this->officeId;
            $data['user_id'] = $this->user_id;
            $data['created_at'] = now();

            DB::table('office_signatories')->insert($data);

            session()->flash('success', 'Signatory added successfully.');
        }

        $this->showAddModal = false;
        $this->loadOfficeData();
    }

    public function remove($id)
    {
        DB::table('office_signatories')->where('id', $id)->delete();
        session()->flash('success', 'Signatory removed successfully.');
        $this->loadOfficeData();
    }

    public function toggleActive($id)
    {
        $signatory = DB::table('office_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            DB::table('office_signatories')
                ->where('id', $id)
                ->update(['is_active' => !$signatory->is_active]);
            
            session()->flash('success', 'Signatory status updated.');
            $this->loadOfficeData();
        }
    }

    public function render()
    {
        return view('livewire.office.manage-signatories');
    }
}
