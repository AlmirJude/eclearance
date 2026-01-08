<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;

class AddClub extends Component
{

    public $name = '';
    public $type = 'socio_civic';
    public $description = '';
    public $abbreviation = '';
    public $moderator_id = null;


    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:clubs,name',
            'type' => 'required|in:academic,religious,socio_civic',
            'description' => 'nullable|string|max:1000',
            'abbreviation' => 'nullable|string|max:50',
            'moderator_id' => 'nullable|exists:users,id',
        ];
    }

    protected $messages = [
        'name.required' => 'Club name is required',
        'name.unique' => 'This club name already exists',
        'type.required' => 'Club type is required',
        'type.in' => 'Invalid club type selected',
        'description.max' => 'Description cannot exceed 1000 characters',
        'abbreviation.max' => 'Abbreviation cannot exceed 50 characters',
        'moderator_id.exists' => 'Selected moderator does not exist',
    ];
    public function submit()
    {
        $this->validate();


        try {
            Club::create([
                'name' => $this->name,
                'type' => $this->type,
                'description' => $this->description,
                'Abbreviation' => $this->abbreviation,
                'moderator_id' => $this->moderator_id,
            ]);

            session()->flash('success', 'Club added successfully!', redirect()->route('club.index'));

            $this->reset();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while adding the club: ' . $e->getMessage());
        }

    }




    public function render()
    {
        $availableModerators = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->orWhere('role', 'superadmin')
            ->get();


        return view('livewire.club.add-club', [
            'availableModerators' => $availableModerators,
        ]);
    }
}
