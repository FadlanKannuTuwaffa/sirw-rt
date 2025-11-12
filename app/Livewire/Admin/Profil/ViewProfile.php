<?php

namespace App\Livewire\Admin\Profil;

use Livewire\Component;

class ViewProfile extends Component
{
    public function render()
    {
        return view('livewire.admin.profil.view-profile', [
            'user' => auth()->user(),
        ])->layout('layouts.admin', [
            'title' => 'Profil Admin',
        ]);
    }
}