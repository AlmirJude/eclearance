<?php

namespace App\Livewire\Office;

use App\Models\Office;
use Livewire\Component;

class OfficeIndex extends Component
{
    public function render()
    {
        $offices = Office::with('manager')->get();
        return view('livewire.office.office-index', compact('offices'));
    }
}
