<?php
namespace App\Livewire;
use App\Domain\Cafe\Models\Category; use App\Domain\Cafe\Queries\HomeSections; use Livewire\Component;
class Home extends Component { public function render(){ $categories=Category::orderBy('sort_order')->get(); return view('livewire.home',['cafes'=>app(HomeSections::class)->trending(),'categories'=>$categories])->layout('components.layout.app'); } }
