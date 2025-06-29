<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->sentence(3, false),
            'descricao' => $this->faker->optional(0.7)->paragraph(2),
            'finalizado' => $this->faker->boolean(20), // 20% de chance de estar finalizada
            'data_limite' => $this->faker->optional(0.6)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'finalizado' => true,
        ]);
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'finalizado' => false,
        ]);
    }

    /**
     * Indicate that the task has a deadline.
     */
    public function withDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_limite' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the task has an urgent deadline (within 3 days).
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_limite' => $this->faker->dateTimeBetween('now', '+3 days'),
        ]);
    }

    /**
     * Indicate that the task has a past deadline.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_limite' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the task has a long description.
     */
    public function withLongDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'descricao' => $this->faker->paragraphs(3, true),
        ]);
    }

    /**
     * Indicate that the task has no description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'descricao' => null,
        ]);
    }

    /**
     * Indicate that the task has no deadline.
     */
    public function withoutDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_limite' => null,
        ]);
    }
} 