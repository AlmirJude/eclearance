<?php

namespace App\Livewire\Club;

use App\Models\Club;
use Livewire\Component;

class ClubIndex extends Component
{
    public function delete ($id)
    {
        $club = Club::find($id);
        if ($club) {
            $club->delete();
            session()->flash('success', 'Club deleted successfully.');
        } else {
            session()->flash('error', 'Club not found.');
        }

    }

    public function render()
    {
        $clubs = Club::orderBy('type' , 'asc')
        ->get();

        return view('livewire.club.club-index', compact('clubs'));
    }
}
