<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'documento' => (string) fake()->unique()->numberBetween(1000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
            'correo_institucional' => null,
            'telefono' => '7'.fake()->numerify('#######'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'activo' => true,
            // Por defecto NO obliga a cambiarla: asi los tests y los datos de
            // demo navegan directo. Usa ->debeCambiarPassword() para probar la RN 1.7.
            'debe_cambiar_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /** Usuario desactivado (RN 1.2: no puede iniciar sesion). */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }

    /** Usuario obligado a cambiar la contrasena en el primer ingreso (RN 1.7). */
    public function debeCambiarPassword(): static
    {
        return $this->state(fn (array $attributes) => ['debe_cambiar_password' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }
}
