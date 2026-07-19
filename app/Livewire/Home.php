<?php

namespace App\Livewire;

use App\Domain\Cafe\Models\Category;
use App\Domain\Cafe\Queries\HomeSections;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $now = CarbonImmutable::now('Asia/Makassar');
        $priority = $now->isWeekend() ? ['hidden-gem-baru-buka', 'aesthetic'] : ($now->isWeekday() && $now->hour >= 9 && $now->hour < 16 ? ['cocok-nugas-wfc'] : []);
        $categories = Category::orderBy('sort_order')->get()->sortBy(function (Category $category) use ($priority): string {
            $position = array_search($category->slug, $priority, true);

            return sprintf('%02d-%06d', $position === false ? 99 : $position, $category->sort_order);
        })->values();

        return view('livewire.home', ['cafes' => app(HomeSections::class)->trending(), 'categories' => $categories])->layout('components.layout.app');
    }
}
