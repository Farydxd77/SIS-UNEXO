<?php

namespace Database\Factories;

use App\Enums\DiaSemana;
use App\Models\Grupo;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
class HorarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grupo_id' => Grupo::factory(),
            'dia_semana' => DiaSemana::Lunes,
            'hora_inicio' => '19:00',
            'hora_fin' => '21:00',
        ];
    }
}
