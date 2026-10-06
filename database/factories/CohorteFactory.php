<?php

namespace Database\Factories;

use App\Enums\EstadoCohorte;
use App\Models\Cohorte;
use App\Models\Programa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohorte>
 */
class CohorteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'programa_id' => Programa::factory(),
            'nombre' => 'Version '.fake()->numberBetween(1, 9).', '.fake()->monthName().' 2027',
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => null,
            'estado' => EstadoCohorte::Planificado,
        ];
    }
}
