<?php

namespace Database\Factories;

use App\Enums\EstadoGrupo;
use App\Models\Grupo;
use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'modulo_id' => Modulo::factory(),
            'cohorte_id' => null,
            'docente_id' => null,
            'version' => 1,
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-28',
            'cupo_minimo' => 20,
            'cupo_maximo' => 30,
            'enlace_meet' => null,
            'estado' => EstadoGrupo::Planificado,
        ];
    }

    public function enConvocatoria(): static
    {
        return $this->state(fn () => ['estado' => EstadoGrupo::EnConvocatoria]);
    }

    public function conMeet(): static
    {
        return $this->state(fn () => [
            'enlace_meet' => 'https://meet.google.com/'.fake()->unique()->lexify('???-????-???'),
        ]);
    }
}
