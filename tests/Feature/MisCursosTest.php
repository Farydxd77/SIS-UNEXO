<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Mis cursos": lo que cursa y lo que imparte, filtrado por fechas.
 */
class MisCursosTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-05');

        $rol = Rol::create(['nombre' => User::ROL_ESTUDIANTE]);
        $this->alumno = User::factory()->create();
        $this->alumno->roles()->sync([$rol->id]);

        $this->matricular('Modulo En Curso', '2026-09-21', '2026-10-18');
        $this->matricular('Modulo Futuro', '2026-11-02', '2026-11-29');
        $this->matricular('Modulo Pasado', '2026-06-01', '2026-06-28', EstadoMatricula::Finalizada);
    }

    public function test_por_defecto_muestra_todos_sus_cursos_y_ninguno_ajeno(): void
    {
        Grupo::factory()->create(['modulo_id' => Modulo::factory()->create(['nombre' => 'Modulo Ajeno'])->id]);

        $this->actingAs($this->alumno)
            ->get(route('mis-cursos.index'))
            ->assertOk()
            ->assertSee('Modulo En Curso')
            ->assertSee('Modulo Futuro')
            ->assertSee('Modulo Pasado')
            ->assertDontSee('Modulo Ajeno');
    }

    public function test_filtra_en_progreso_futuros_y_pasados_por_fechas(): void
    {
        $this->actingAs($this->alumno);

        // Se mira la lista de la pagina: el cajon lateral tambien nombra los cursos en marcha.
        $this->assertSame(['Modulo En Curso'], $this->nombres(['filtro' => 'en-progreso']));
        $this->assertSame(['Modulo Futuro'], $this->nombres(['filtro' => 'futuros']));
        $this->assertSame(['Modulo Pasado'], $this->nombres(['filtro' => 'pasados']));
    }

    public function test_un_grupo_de_cohorte_tomado_solo_se_muestra_como_modulo_suelto(): void
    {
        $cohorte = Cohorte::factory()->create();
        $grupo = Grupo::factory()->create(['cohorte_id' => $cohorte->id, 'modulo_id' => Modulo::factory()->create(['nombre' => 'Solo Este'])->id]);
        Grupo::factory()->create(['cohorte_id' => $cohorte->id]);
        Matricula::factory()->create(['user_id' => $this->alumno->id, 'grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Activa]);

        $curso = $this->actingAs($this->alumno)
            ->get(route('mis-cursos.index', ['buscar' => 'solo este']))
            ->viewData('cursos')
            ->sole();

        $this->assertSame('Modulo suelto', $curso['categoria']);
    }

    public function test_la_busqueda_filtra_por_nombre(): void
    {
        $this->actingAs($this->alumno);

        $this->assertSame(['Modulo Futuro'], $this->nombres(['buscar' => 'futuro']));
    }

    /**
     * @param  array<string, string>  $consulta
     * @return list<string>
     */
    private function nombres(array $consulta): array
    {
        return $this->get(route('mis-cursos.index', $consulta))
            ->assertOk()
            ->viewData('cursos')
            ->map(fn (array $curso) => $curso['grupo']->modulo->nombre)
            ->all();
    }

    public function test_el_docente_ve_los_grupos_que_imparte(): void
    {
        $docente = User::factory()->create();
        $docente->roles()->sync([Rol::create(['nombre' => User::ROL_DOCENTE])->id]);
        Grupo::factory()->create([
            'docente_id' => $docente->id,
            'modulo_id' => Modulo::factory()->create(['nombre' => 'Modulo Que Dicto'])->id,
        ]);

        $this->actingAs($docente)
            ->get(route('mis-cursos.index'))
            ->assertOk()
            ->assertSee('Modulo Que Dicto')
            ->assertSee('Calificar');
    }

    private function matricular(string $modulo, string $inicio, string $fin, EstadoMatricula $estado = EstadoMatricula::Activa): void
    {
        Matricula::factory()->create([
            'user_id' => $this->alumno->id,
            'estado' => $estado,
            'grupo_id' => Grupo::factory()->create([
                'modulo_id' => Modulo::factory()->create(['nombre' => $modulo])->id,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
            ])->id,
        ]);
    }
}
