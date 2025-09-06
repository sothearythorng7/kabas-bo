<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(), // par défaut crée aussi un supplier si non fourni
            'last_name'   => $this->faker->lastName(),
            'first_name'  => $this->faker->firstName(),
            'email'       => $this->faker->unique()->safeEmail(),
            'phone'       => $this->faker->phoneNumber(),
        ];
    }
}
