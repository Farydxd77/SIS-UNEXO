<?php

namespace Database\Factories;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modulo>
 */
class ModuloFactory extends Factory
{
    public function definition(): array
    {
        $temas = ['Excel', 'Power BI', 'SQL', 'R Studio', 'Python', 'Tableau', 'Estadistica'];

        return [
            // El unique va en el numero: el codigo completo queda unico.
            'codigo' => strtoupper(fake()->lexify('???')).'-'.fake()->unique()->numberBetween(10, 9999),
            'nombre' => fake()->randomElement($temas).' '.fake()->numberBetween(1, 9),
            'descripcion' => fake()->sentence(12),
            'requisitos' => fake()->sentence(6),
            'temario' => "1. Introduccion\n2. Practica\n3. Proyecto",
            'horas' => fake()->randomElement([24, 32, 40]),
            'precio' => fake()->randomFloat(2, 500, 1200),
            'se_oferta_por_separado' => true,
            'estado_comercial' => EstadoComercial::Borrador,
        ];
    }

    public function abierto(): static
    {
        return $this->state(fn () => ['estado_comercial' => EstadoComercial::Abierto]);
    }
}
