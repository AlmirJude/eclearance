<?php

namespace App\Livewire\Users;

use App\Models\StaffDetail;
use Livewire\Component;

class StaffIndex extends Component
{
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $selectedStaff;

    public function render()
    {
        $staffs = StaffDetail::with('user')->get();
        return view('livewire.users.staff-index', compact('staffs'));
    }

    public function view($id)
    {
        $this->selectedStaff = StaffDetail::with('user')->find($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-staff-modal');
    }

    public function closeModalViewStaff()
    {
        $this->showViewModal = false;
        $this->selectedStaff = null;
        $this->dispatch('close-modal', name: 'view-staff-modal');
    }

    public function confirmDelete($id)
    {
        $this->selectedStaff = StaffDetail::with('user')->find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'showDeleteModal');
    }

    public function delete()
    {
        if ($this->selectedStaff) {
            $this->selectedStaff->user()->delete(); // This will cascade delete the staff_details
            session()->flash('success', 'Staff deleted successfully.');
            
            $this->showDeleteModal = false;
            $this->selectedStaff = null;
            $this->dispatch('close-modal', name: 'showDeleteModal');
        } else {
            session()->flash('error', 'Staff not found.');
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedStaff = null;
        $this->dispatch('close-modal', name: 'showDeleteModal');
    }
}