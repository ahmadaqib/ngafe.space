<?php

namespace App\Livewire;

use App\Domain\Cafe\Models\Category;
use App\Domain\Cafe\Queries\SearchCafes;
use Illuminate\Http\Response;
use Livewire\Component;

/**
 * Server-rendered, indexable /{city}/cafe-{categorySlug} landing page — the
 * "SEO programatik" engine (Docs/Spec.md §10, Docs/plan.md Task 5.1). Plain
 * category+city filter, no client interactivity required for the content
 * itself to be crawlable on first response.
 */
class CategoryCity extends Component
{
    public string $city;

    public string $categorySlug;

    public function mount(string $city, string $categorySlug): void
    {
        $this->city = $city;
        $this->categorySlug = $categorySlug;
    }

    public function render()
    {
        $category = Category::query()->where('slug', $this->categorySlug)->first();
        if (! $category) {
            abort(404);
        }

        $cafes = app(SearchCafes::class)->run(null, [$this->categorySlug], null, null, null, $this->city);

        $canonical = route('category-city', ['city' => $this->city, 'categorySlug' => $this->categorySlug]);

        return view('livewire.category-city', [
            'category' => $category,
            'cafes' => $cafes,
            'city' => $this->city,
            'canonical' => $canonical,
        ])->layout('components.layout.app', [
            'title' => "{$category->name} di ".ucfirst($this->city).' · ngafe.space',
            'description' => "Daftar cafe {$category->name} di ".ucfirst($this->city).' — direktori review jujur dari pengunjung asli, bukan endorse.',
            'canonical' => $canonical,
        ]);
    }
}
