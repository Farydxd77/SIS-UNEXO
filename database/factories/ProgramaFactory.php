<?php

namespace Database\Factories;

use App\Enums\EstadoComercial;
use App\Models\Programa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Programa>
 */
class ProgramaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->lexify('???')).'-'.fake()->unique()->numberBetween(10, 9999),
            'nombre' => 'Programa '.fake()->words(2, true),
            'descripcion' => fake()->sentence(14),
            'cantidad_modulos' => 3,
            'precio_contado' => fake()->randomFloat(2, 2000, 6000),
            'estado_comercial' => EstadoComercial::Borrador,
        ];
    }

    public function abierto(): static
    {
        return $this->state(fn () => ['estado_comercial' => EstadoComercial::Abierto]);
    }
}
