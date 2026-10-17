<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SI###')),
            'name' => Str::title(fake()->unique()->words(2, true)),
            'semester' => fake()->numberBetween(1, 5),
            'prodi' => 'SI',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
