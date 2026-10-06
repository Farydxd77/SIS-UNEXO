<?php

namespace Tests\Feature;

use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Asignar docentes y Meet": todos los grupos de una cohorte de una vez.
 */
class AsignacionCohorteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $docente;

    private Cohorte $cohorte;

    /** @var Collection<int, Grupo> */
    private Collection $grupos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([Rol::create(['nombre' => User::ROL_ADMINISTRADOR])->id]);
        $this->docente = User::factory()->create();
        $this->docente->roles()->sync([Rol::create(['nombre' => User::ROL_DOCENTE])->id]);

        $this->cohorte = Cohorte::factory()->create();
        $this->grupos = Grupo::factory()->count(2)->sequence(
            ['modulo_id' => Modulo::factory(), 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-28'],
            ['modulo_id' => Modulo::factory(), 'fecha_inicio' => '2027-03-29', 'fecha_fin' => '2027-04-25'],
        )->create(['cohorte_id' => $this->cohorte->id]);

        // Los modulos de los grupos deben ser del programa de la cohorte.
        foreach ($this->grupos as $orden => $grupo) {
            $this->cohorte->programa->modulos()->attach($grupo->modulo_id, ['orden' => $orden + 1]);
        }
    }

    public function test_la_pantalla_carga_con_todos_los_grupos(): void
    {
        $this->actingAs($this->admin)
            ->get(route('cohortes.asignar', $this->cohorte))
            ->assertOk()
            ->assertSee('Asignar docentes y Meet')
            ->assertSee($this->grupos[0]->modulo->nombre)
            ->assertSee($this->grupos[1]->modulo->nombre);
    }

    public function test_guarda_docente_meet_y_horarios_de_todos_en_un_solo_envio(): void
    {
        $this->actingAs($this->admin)
            ->put(route('cohortes.asignar.guardar', $this->cohorte), ['grupos' => [
                $this->grupos[0]->id => $this->fila('https://meet.google.com/aaa-bbbb-ccc'),
                $this->grupos[1]->id => $this->fila('https://meet.google.com/ddd-eeee-fff'),
            ]])
            ->assertRedirect(route('cohortes.show', $this->cohorte))
            ->assertSessionHas('exito');

        foreach ($this->grupos as $grupo) {
            $grupo->refresh();
            $this->assertSame($this->docente->id, $grupo->docente_id);
            $this->assertSame(2, $grupo->horarios()->count());
            $this->assertSame(EstadoGrupo::Planificado, $grupo->estado);
        }
    }

    public function test_puede_pasar_grupos_y_cohorte_a_convocatoria_al_guardar(): void
    {
        $this->actingAs($this->admin)
            ->put(route('cohortes.asignar.guardar', $this->cohorte), [
                'abrir_convocatoria' => '1',
                'grupos' => [
                    $this->grupos[0]->id => $this->fila('https://meet.google.com/aaa-bbbb-ccc'),
                    $this->grupos[1]->id => $this->fila('https://meet.google.com/ddd-eeee-fff'),
                ],
            ])
            ->assertSessionHas('exito', fn ($m) => str_contains($m, '2 grupo(s) pasaron a convocatoria'));

        $this->assertSame(EstadoGrupo::EnConvocatoria, $this->grupos[0]->fresh()->estado);
        $this->assertSame(EstadoGrupo::EnConvocatoria, $this->grupos[1]->fresh()->estado);
        $this->assertSame(EstadoCohorte::EnConvocatoria, $this->cohorte->fresh()->estado);
    }

    public function test_si_una_fila_falla_no_se_guarda_ninguna(): void
    {
        // El segundo grupo repite la sala del primero (RN 3.8).
        $this->actingAs($this->admin)
            ->from(route('cohortes.asignar', $this->cohorte))
            ->put(route('cohortes.asignar.guardar', $this->cohorte), ['grupos' => [
                $this->grupos[0]->id => $this->fila('https://meet.google.com/aaa-bbbb-ccc'),
                $this->grupos[1]->id => $this->fila('https://meet.google.com/aaa-bbbb-ccc'),
            ]])
            ->assertRedirect(route('cohortes.asignar', $this->cohorte))
            ->assertSessionHasErrors("grupos.{$this->grupos[1]->id}.enlace_meet");

        $this->assertNull($this->grupos[0]->fresh()->docente_id);
        $this->assertSame(0, $this->grupos[0]->horarios()->count());
    }

    public function test_editar_un_grupo_desde_la_cohorte_vuelve_a_la_cohorte(): void
    {
        $grupo = $this->grupos[0];

        $this->actingAs($this->admin)
            ->put(route('grupos.update', $grupo), [
                'volver' => 'cohorte',
                'cohorte_id' => $this->cohorte->id,
                'fecha_inicio' => '2027-03-01',
                'fecha_fin' => '2027-03-28',
                'cupo_minimo' => 20,
            ])
            ->assertRedirect(route('cohortes.show', $this->cohorte));
    }

    /**
     * @return array<string, mixed>
     */
    private function fila(string $meet): array
    {
        return [
            'docente_id' => $this->docente->id,
            'enlace_meet' => $meet,
            'horarios' => [
                ['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
                ['dia_semana' => 3, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
            ],
        ];
    }
}
