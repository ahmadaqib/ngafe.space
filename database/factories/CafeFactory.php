<?php

namespace Database\Factories;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\CafeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Cafe> */
class CafeFactory extends Factory
{
    protected $model = Cafe::class;
    public function definition(): array
    {
        $name = fake()->randomElement(['Kopi Anjis Perintis', 'Kedai Kala', 'Bilik Kayu Tamalanrea', 'Ruang Teduh Panakkukang']);
        return ['name' => $name, 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999), 'city' => 'makassar', 'area' => fake()->randomElement(['tamalanrea', 'panakkukang', 'rappocini']), 'lat' => fake()->latitude(-5.20, -5.10), 'lng' => fake()->longitude(119.38, 119.50), 'status' => CafeStatus::Active];
    }
}
