<?php

namespace Database\Factories;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Matricula>
 */
class MatriculaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'grupo_id' => Grupo::factory(),
            'estado' => EstadoMatricula::Reservada,
            'origen' => OrigenMatricula::Manual,
            'crm_lead_id' => null,
            'acepto_requisitos' => true,
            'fecha_aceptacion' => now(),
        ];
    }

    public function activa(): static
    {
        return $this->state(fn () => ['estado' => EstadoMatricula::Activa]);
    }
}
