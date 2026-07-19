<?php

namespace App\Livewire;

use App\Domain\Cafe\Models\Category;
use App\Domain\Cafe\Queries\SearchCafes;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Search extends Component
{
    #[Url]
    public string $q = '';

    #[Url(as: 'categories')]
    public array $categorySlugs = [];

    #[Url]
    public ?string $area = null;

    public ?float $lat = null;

    public ?float $lng = null;

    public string $locationState = 'idle';

    #[On('geo-ready')]
    public function geoReady(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->locationState = 'ready';
    }

    #[On('geo-failed')]
    public function geoFailed(string $reason = 'error'): void
    {
        $this->lat = null;
        $this->lng = null;
        $this->locationState = in_array($reason, ['denied', 'unsupported'], true) ? $reason : 'error';
    }

    public function removeCategory(string $slug): void
    {
        $this->categorySlugs = array_values(array_filter($this->categorySlugs, fn (string $selected): bool => $selected !== $slug));
    }

    public function render()
    {
        $query = app(SearchCafes::class);
        $results = $query->run($this->q, $this->categorySlugs, $this->lat, $this->lng, $this->area);
        $suggestions = [];
        if ($results->isEmpty() && count($this->categorySlugs) > 1) {
            foreach ($this->categorySlugs as $slug) {
                $without = array_values(array_diff($this->categorySlugs, [$slug]));
                $count = $query->run($this->q, $without, $this->lat, $this->lng, $this->area)->count();
                if ($count) {
                    $suggestions[] = ['slug' => $slug, 'count' => $count];
                }
            }
        }

        $suggestion = collect($suggestions)->sortByDesc('count')->first();
        if ($suggestion) {
            $suggestion['name'] = Category::query()->where('slug', $suggestion['slug'])->value('name') ?? $suggestion['slug'];
        }

        return view('livewire.search', ['results' => $results, 'suggestion' => $suggestion, 'categories' => Category::orderBy('sort_order')->get(), 'areas' => ['tamalanrea' => 'Tamalanrea', 'panakkukang' => 'Panakkukang', 'losari' => 'Losari/Pantai', 'antang' => 'Antang', 'hertasning' => 'Hertasning', 'daya' => 'Daya', 'sekitar-unhas' => 'Sekitar Unhas', 'sekitar-unm-uin' => 'Sekitar UNM/UIN']])->layout('components.layout.app');
    }
}
