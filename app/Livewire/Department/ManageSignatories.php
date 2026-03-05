<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageSignatories extends Component
{
    public $departmentId;
    public $department;
    public $signatories;
    public $availableStaff;
    public $showAddModal = false;
    public $editingId = null;

    // Form fields
    public $user_id = '';
    public $title = '';
    public $clearance_type = '';
    public $year_levels = [];
    public $is_active = true;
    public $staffSearch = '';

    public function mount($id)
    {
        $this->departmentId = $id;
        $this->loadDepartmentData();
        $this->authorizeAccess();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        if ($this->department->manager_id !== $user->id) {
            abort(403, 'You are not the manager of this department.');
        }
    }

    public function loadDepartmentData()
    {
        $this->department = Department::with('manager')->findOrFail($this->departmentId);
        
        // Get current signatories
        $this->signatories = DB::table('department_signatories')
            ->where('department_id', $this->departmentId)
            ->get();

        // Load user details for signatories
        $userIds = $this->signatories->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->with('staffDetail')->get()->keyBy('id');
        
        // Attach user data to signatories
        $this->signatories = $this->signatories->map(function($signatory) use ($users) {
            $signatory->user = $users[$signatory->user_id] ?? null;
            $signatory->year_levels = json_decode($signatory->year_levels, true);
            return $signatory;
        });

        // Get available staff (not already signatories)
        $this->availableStaff = User::where('role', 'staff')
            ->orWhere('role', 'admin')
            ->whereNotIn('id', $userIds)
            ->with('staffDetail')
            ->get();
    }

    public function openAddModal()
    {
        $this->reset(['user_id', 'title', 'clearance_type', 'year_levels', 'editingId', 'staffSearch']);
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
        $signatory = DB::table('department_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            $this->editingId = $signatory->id;
            $this->user_id = $signatory->user_id;
            $this->title = $signatory->title;
            $this->clearance_type = $signatory->clearance_type;
            $this->year_levels = json_decode($signatory->year_levels, true) ?? [];
            $this->is_active = $signatory->is_active;
            
            $this->showAddModal = true;
        }
    }

    protected function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'clearance_type' => 'nullable|string|max:255',
            'year_levels' => 'required|array|min:1',
            'year_levels.*' => 'integer|min:1|max:6',
            'is_active' => 'boolean',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'user_id' => $this->user_id,
            'title' => $this->title,
            'clearance_type' => $this->clearance_type,
            'year_levels' => json_encode($this->year_levels),
            'is_active' => $this->is_active,
            'updated_at' => now(),
        ];

        if ($this->editingId) {
            // Update existing
            DB::table('department_signatories')
                ->where('id', $this->editingId)
                ->update($data);

            session()->flash('success', 'Signatory updated successfully.');
        } else {
            // Create new
            $data['department_id'] = $this->departmentId;
            $data['created_at'] = now();
            
            DB::table('department_signatories')->insert($data);

            session()->flash('success', 'Signatory added successfully.');
        }

        $this->showAddModal = false;
        $this->loadDepartmentData();
    }

    public function remove($id)
    {
        DB::table('department_signatories')->where('id', $id)->delete();

        session()->flash('success', 'Signatory removed successfully.');
        $this->loadDepartmentData();
    }

    public function toggleActive($id)
    {
        $signatory = DB::table('department_signatories')->where('id', $id)->first();
        
        if ($signatory) {
            DB::table('department_signatories')
                ->where('id', $id)
                ->update(['is_active' => !$signatory->is_active]);

            session()->flash('success', 'Signatory status updated.');
            $this->loadDepartmentData();
        }
    }

    public function render()
    {
        return view('livewire.department.manage-signatories');
    }
}
