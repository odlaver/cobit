<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'question_text' => fake()->sentence(8).'?',
            'option_a' => fake()->unique()->words(3, true),
            'option_b' => fake()->unique()->words(3, true),
            'option_c' => fake()->unique()->words(3, true),
            'option_d' => fake()->unique()->words(3, true),
            'correct_answer' => fake()->randomElement(Question::ANSWERS),
            'status' => Question::STATUS_ACTIVE,
            'source' => 'manual',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Question::STATUS_DRAFT]);
    }
}
