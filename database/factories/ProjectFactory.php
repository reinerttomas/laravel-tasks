<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectColor;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => Str::title(fake()->word().' '.fake()->word()),
            'identifier' => Str::upper(fake()->unique()->lexify('????')),
            'color' => fake()->randomElement(ProjectColor::cases()),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the project has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
        ]);
    }
}
