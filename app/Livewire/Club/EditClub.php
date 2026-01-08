<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;

class EditClub extends Component
{
    public $clubId;
    public $name, $type, $description, $abbreviation, $moderator_id;

    public function mount ($id)
    {
        $this->clubId = $id;
        $club = Club::findOrFail($id);

        $this->name = $club->name;
        $this->type = $club->type;
        $this->description = $club->description;
        $this->abbreviation = $club->Abbreviation;
        $this->moderator_id = $club->moderator_id;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:clubs,name,' . $this->clubId,
            'type' => 'required|in:academic,religious,socio_civic',
            'description' => 'nullable|string|max:1000',
            'abbreviation' => 'nullable|string|max:50',
            'moderator_id' => 'nullable|exists:users,id',
        ];
    }

    public $messages = [
        'name.required' => 'Club name is required',
        'name.unique' => 'This club name already exists',
        'type.required' => 'Club type is required',
        'type.in' => 'Invalid club type selected',
        'description.max' => 'Description cannot exceed 1000 characters',
        'abbreviation.max' => 'Abbreviation cannot exceed 50 characters',
        'moderator_id.exists' => 'Selected moderator does not exist',
    ];

    public function save(){
        $this->validate();

        $club = Club::findOrFail($this->clubId);
        $club->name = $this->name;
        $club->type = $this->type;
        $club->description = $this->description;
        $club->Abbreviation = $this->abbreviation;
        $club->moderator_id = $this->moderator_id;
        $club->save();

        session()->flash('message', 'Club updated successfully.');
        return redirect()->route('club.index');
    }

    public function render()
    {
        $availableModerators = User::where('role', 'admin')
        ->orWhere('role', 'staff')
        ->orWhere('role', 'superadmin')
        ->get();
        return view('livewire.club.edit-club', compact('availableModerators'));
    }
}
