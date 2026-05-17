<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'currency' => 'PLN',
            'locale' => 'pl',
            'timezone' => 'Europe/Warsaw',
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
