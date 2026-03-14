<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class UserIndex extends Component
{
    public function render()
    {
        $users = User::get();
        return view('livewire.users.user-index', compact('users'));
    }
}
