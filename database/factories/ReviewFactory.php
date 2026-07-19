<?php

namespace Database\Factories;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Identity\Models\User;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'cafe_id' => Cafe::factory(), 'rating' => fake()->numberBetween(3, 5), 'body' => fake()->randomElement(['Colokannya banyak, meja cukup lega, dan wifi stabil buat menyelesaikan tugas sampai sore.', 'Kopinya enak dan suasananya tidak terlalu bising. Cocok untuk ngobrol santai setelah kelas.']), 'display_alias' => 'Penikmat Kopi '.fake()->numberBetween(10, 999), 'status' => 'published'];
    }
}
