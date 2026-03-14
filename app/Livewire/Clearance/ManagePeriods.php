<?php

namespace App\Livewire\Clearance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\User;
use App\Services\ClearanceResolver;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 2. Add this attribute to force the wrapper
class ManagePeriods extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editingId = null;
    
    public $name = '';
    public $academicYear = '';
    public $semester = '1st';
    public $startDate = '';
    public $endDate = '';
    public $isActive = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'academicYear' => 'required|string|max:20',
        'semester' => 'required|in:1st,2nd,summer',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate',
    ];

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->editingId = $id;

        if ($id) {
            $period = ClearancePeriod::find($id);
            $this->name = $period->name;
            $this->academicYear = $period->academic_year;
            $this->semester = $period->semester;
            $this->startDate = $period->start_date->format('Y-m-d');
            $this->endDate = $period->end_date->format('Y-m-d');
            $this->isActive = $period->is_active;
        } else {
            $currentYear = date('Y');
            $this->academicYear = $currentYear . '-' . ($currentYear + 1);
        }

        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->reset(['name', 'academicYear', 'semester', 'startDate', 'endDate', 'isActive', 'editingId']);
        $this->semester = '1st';
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'academic_year' => $this->academicYear,
            'semester' => $this->semester,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'is_active' => $this->isActive,
        ];

        // If setting this period as active, deactivate others
        if ($this->isActive) {
            ClearancePeriod::where('is_active', true)->update(['is_active' => false]);
        }

        if ($this->editingId) {
            ClearancePeriod::find($this->editingId)->update($data);
            session()->flash('success', 'Clearance period updated successfully.');
        } else {
            ClearancePeriod::create($data);
            session()->flash('success', 'Clearance period created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function activate($id)
    {
        // Deactivate all periods
        ClearancePeriod::where('is_active', true)->update(['is_active' => false]);
        
        // Activate selected period
        ClearancePeriod::find($id)->update(['is_active' => true]);
        
        session()->flash('success', 'Clearance period activated.');
    }

    public function delete($id)
    {
        $period = ClearancePeriod::find($id);

        if (!$period) {
            session()->flash('error', 'Period not found.');
            return;
        }

        if ($period->is_active) {
            session()->flash('error', 'Cannot delete an active clearance period. Deactivate it first.');
            return;
        }
        
        $period->delete();
        session()->flash('success', 'Clearance period deleted.');
    }

    public function generateRequests($periodId)
    {
        $period = ClearancePeriod::find($periodId);
        
        if (!$period || !$period->is_active) {
            session()->flash('error', 'Period must be active to generate requests.');
            return;
        }
        
        // Get all students
        $students = User::where('role', 'student')->get();
        $resolver = new ClearanceResolver();
        $created = 0;
        
        foreach ($students as $student) {
            // Check if request already exists
            $exists = ClearanceRequest::where('student_id', $student->id)
                ->where('period_id', $periodId)
                ->exists();
            
            if (!$exists) {
                $request = ClearanceRequest::create([
                    'student_id' => $student->id,
                    'period_id' => $periodId,
                    'status' => 'pending',
                ]);
                
                // Generate clearance items for this request
                $resolver->generateClearanceItems($request);
                $created++;
            }
        }
        
        session()->flash('success', "Generated {$created} clearance requests.");
    }

    public function setupDependencies()
    {
        $resolver = new ClearanceResolver();
        $resolver->setupDefaultDependencies();
        
        session()->flash('success', 'Default clearance dependencies have been configured.');
    }

    public function render()
    {
        $periods = ClearancePeriod::orderBy('academic_year', 'desc')
            ->orderBy('semester')
            ->paginate(10);

        return view('livewire.clearance.manage-periods', [
            'periods' => $periods,
        ]);
    }
}
