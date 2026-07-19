<?php

namespace Database\Factories;

use App\Domain\Cafe\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Cocok nugas & WFC', 'Wifi kencang', 'Banyak colokan', 'Buka 24 jam', 'Ramah kantong', 'Aesthetic', 'Tenang', 'Rame/nongkrong', 'Hidden gem / baru buka', 'Outdoor/smoking area', 'Ramah keluarga', 'Musala & parkir gampang']);

        return ['name' => $name, 'slug' => Str::slug($name), 'icon' => 'lucide-coffee', 'sort_order' => fake()->numberBetween(0, 11)];
    }
}
