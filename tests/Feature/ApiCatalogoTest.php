<?php

namespace Tests\Feature;

use App\Enums\DiaSemana;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Catalogo que consulta el CRM por GET /api/v1/catalogo.
 */
class ApiCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$rolAdmin->id]);

        config(['services.crm.requiere_token' => true]);
    }

    public function test_sin_token_responde_401(): void
    {
        $this->getJson(route('api.v1.catalogo.index'))->assertUnauthorized();
    }

    public function test_un_token_sin_el_permiso_de_catalogo_responde_403(): void
    {
        Sanctum::actingAs($this->admin, ['matriculas:crear']);

        $this->getJson(route('api.v1.catalogo.index'))->assertForbidden();
    }

    public function test_un_token_de_quien_no_es_admin_responde_403(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))->assertForbidden();
    }

    public function test_muestra_solo_los_programas_abiertos_con_sus_cohortes_en_oferta(): void
    {
        $abierto = Programa::factory()->abierto()->create(['nombre' => 'Full Stack', 'precio_contado' => 1200]);
        $enConvocatoria = Cohorte::factory()->for($abierto)->create(['estado' => EstadoCohorte::EnConvocatoria]);
        Cohorte::factory()->for($abierto)->create(['estado' => EstadoCohorte::Finalizado]);
        Programa::factory()->create(['nombre' => 'En Borrador']);

        Sanctum::actingAs($this->admin, ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))
            ->assertOk()
            ->assertJsonCount(1, 'programas')
            ->assertJsonPath('programas.0.nombre', 'Full Stack')
            ->assertJsonPath('programas.0.precio_contado', 1200)
            ->assertJsonCount(1, 'programas.0.cohortes')
            ->assertJsonPath('programas.0.cohortes.0.id', $enConvocatoria->id);
    }

    public function test_la_cohorte_solo_admite_inscripciones_si_todos_sus_grupos_vigentes_las_aceptan(): void
    {
        $programa = Programa::factory()->abierto()->create();

        $lista = Cohorte::factory()->for($programa)->create(['fecha_inicio' => '2027-01-01']);
        Grupo::factory()->enConvocatoria()->for($lista)->create();
        Grupo::factory()->for($lista)->create(['estado' => EstadoGrupo::Cancelado]);

        $aMedias = Cohorte::factory()->for($programa)->create(['fecha_inicio' => '2027-06-01']);
        Grupo::factory()->enConvocatoria()->for($aMedias)->create();
        Grupo::factory()->for($aMedias)->create(['estado' => EstadoGrupo::Planificado]);

        Sanctum::actingAs($this->admin, ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))
            ->assertOk()
            ->assertJsonPath('programas.0.cohortes.0.id', $lista->id)
            ->assertJsonPath('programas.0.cohortes.0.admite_inscripciones', true)
            // El grupo cancelado no se lista: la inscripcion lo salta.
            ->assertJsonCount(1, 'programas.0.cohortes.0.grupos')
            ->assertJsonPath('programas.0.cohortes.1.id', $aMedias->id)
            ->assertJsonPath('programas.0.cohortes.1.admite_inscripciones', false);
    }

    public function test_los_modulos_sueltos_muestran_sus_grupos_abiertos_con_cupos_disponibles(): void
    {
        $modulo = Modulo::factory()->abierto()->create(['nombre' => 'Excel Avanzado']);
        $grupo = Grupo::factory()->enConvocatoria()->for($modulo)->create(['cupo_minimo' => 5, 'cupo_maximo' => 10]);
        Horario::factory()->for($grupo)->create(['dia_semana' => DiaSemana::Martes, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);
        Matricula::factory()->for($grupo)->create();
        Matricula::factory()->activa()->count(2)->for($grupo)->create();
        Matricula::factory()->for($grupo)->create(['estado' => EstadoMatricula::Anulada]);

        Grupo::factory()->for($modulo)->create(['version' => 2, 'estado' => EstadoGrupo::Planificado]);
        Modulo::factory()->abierto()->create(['se_oferta_por_separado' => false]);

        Sanctum::actingAs($this->admin, ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))
            ->assertOk()
            ->assertJsonCount(1, 'modulos_sueltos')
            ->assertJsonPath('modulos_sueltos.0.nombre', 'Excel Avanzado')
            ->assertJsonCount(1, 'modulos_sueltos.0.grupos')
            ->assertJsonPath('modulos_sueltos.0.grupos.0.id', $grupo->id)
            ->assertJsonPath('modulos_sueltos.0.grupos.0.cupos_disponibles', 7)
            ->assertJsonPath('modulos_sueltos.0.grupos.0.admite_inscripciones', true)
            ->assertJsonPath('modulos_sueltos.0.grupos.0.horarios.0', [
                'dia_semana' => 2,
                'dia' => 'Martes',
                'hora_inicio' => '19:00',
                'hora_fin' => '21:00',
            ]);
    }

    public function test_un_grupo_lleno_no_admite_inscripciones(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->for(Modulo::factory()->abierto())->create(['cupo_minimo' => 1, 'cupo_maximo' => 2]);
        Matricula::factory()->activa()->count(2)->for($grupo)->create();

        Sanctum::actingAs($this->admin, ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))
            ->assertOk()
            ->assertJsonPath('modulos_sueltos.0.grupos.0.cupos_disponibles', 0)
            ->assertJsonPath('modulos_sueltos.0.grupos.0.admite_inscripciones', false);
    }

    public function test_no_expone_el_enlace_de_meet(): void
    {
        $grupo = Grupo::factory()->enConvocatoria()->conMeet()->for(Modulo::factory()->abierto())->create();

        Sanctum::actingAs($this->admin, ['catalogo:leer']);

        $this->getJson(route('api.v1.catalogo.index'))
            ->assertOk()
            ->assertJsonPath('modulos_sueltos.0.grupos.0.id', $grupo->id)
            ->assertDontSee($grupo->enlace_meet);
    }
}
