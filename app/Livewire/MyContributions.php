<?php

namespace App\Livewire;

use Livewire\Component;

final class MyContributions extends Component
{
    public function render()
    {
        return view('livewire.my-contributions', [
            'reviews' => auth()->user()->reviews()->with('cafe')->latest()->get(),
        ])->layout('components.layout.app', ['title' => 'Kontribusimu']);
    }
}
