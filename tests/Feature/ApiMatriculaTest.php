<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Inscripciones del CRM por POST /api/v1/matriculas.
 */
class ApiMatriculaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);
        Rol::create(['nombre' => User::ROL_DOCENTE]);
        Rol::create(['nombre' => User::ROL_ESTUDIANTE]);

        $this->admin = User::factory()->create(['email' => 'admin@unexo.test']);
        $this->admin->roles()->sync([$rolAdmin->id]);

        // Salvo el test del modo abierto, se prueba la API protegida con token.
        config(['services.crm.requiere_token' => true]);
    }

    public function test_con_la_api_abierta_inscribe_sin_token(): void
    {
        config(['services.crm.requiere_token' => false]);
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva($grupo))
            ->assertCreated()
            ->assertJsonPath('matriculas.0.estado', 'activa');
    }

    public function test_sin_token_responde_401(): void
    {
        $this->postJson(route('api.v1.matriculas.store'), [])->assertUnauthorized();
    }

    public function test_un_token_sin_el_permiso_responde_403(): void
    {
        Sanctum::actingAs($this->admin, ['otra-cosa']);

        $this->postJson(route('api.v1.matriculas.store'), [])->assertForbidden();
    }

    public function test_un_token_de_quien_no_es_admin_responde_403(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['matriculas:crear']);

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva(Grupo::factory()->enConvocatoria()->create()))
            ->assertForbidden();
    }

    public function test_el_crm_inscribe_a_una_persona_nueva_y_la_matricula_nace_activa(): void
    {
        Sanctum::actingAs($this->admin, ['matriculas:crear']);
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva($grupo))
            ->assertCreated()
            ->assertJsonPath('usuario.documento', '77777777')
            ->assertJsonPath('matriculas.0.estado', 'activa')
            ->assertJsonPath('matriculas.0.origen', 'api')
            ->assertJsonPath('matriculas.0.crm_lead_id', 'LEAD-500')
            ->assertJsonStructure(['usuario' => ['password_temporal']]);

        $matricula = Matricula::sole();
        $this->assertSame(EstadoMatricula::Activa, $matricula->estado);
        $this->assertSame(OrigenMatricula::Api, $matricula->origen);
        $this->assertTrue($matricula->estudiante->esEstudiante());
    }

    public function test_en_modo_prueba_el_alumno_nuevo_recibe_la_contrasena_fija(): void
    {
        config(['services.crm.requiere_token' => false, 'services.crm.password_prueba' => 'password']);

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva(Grupo::factory()->enConvocatoria()->create()))
            ->assertCreated()
            ->assertJsonPath('usuario.password_temporal', 'password');

        $alumno = User::where('documento', '77777777')->sole();
        $this->assertTrue(Hash::check('password', $alumno->password));
        $this->assertFalse($alumno->debe_cambiar_password);
    }

    public function test_sin_modo_prueba_la_contrasena_es_al_azar_y_debe_cambiarse(): void
    {
        config(['services.crm.requiere_token' => false, 'services.crm.password_prueba' => null]);

        $respuesta = $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva(Grupo::factory()->enConvocatoria()->create()))
            ->assertCreated();

        $this->assertNotSame('password', $respuesta->json('usuario.password_temporal'));
        $this->assertTrue(User::where('documento', '77777777')->sole()->debe_cambiar_password);
    }

    public function test_el_crm_inscribe_al_programa_completo(): void
    {
        Sanctum::actingAs($this->admin, ['matriculas:crear']);
        $cohorte = Cohorte::factory()->create();
        Grupo::factory()->count(3)->enConvocatoria()->sequence(
            ['modulo_id' => Modulo::factory()],
            ['modulo_id' => Modulo::factory()],
            ['modulo_id' => Modulo::factory()],
        )->create(['cohorte_id' => $cohorte->id]);

        $this->postJson(route('api.v1.matriculas.store'), [
            ...$this->datosPersonaNueva(),
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertCreated()->assertJsonCount(3, 'matriculas');

        $this->assertSame(3, Matricula::where('estado', EstadoMatricula::Activa)->count());
    }

    public function test_el_mismo_lead_no_se_procesa_dos_veces(): void
    {
        Sanctum::actingAs($this->admin, ['matriculas:crear']);
        $grupo = Grupo::factory()->enConvocatoria()->create();

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva($grupo))->assertCreated();

        $this->postJson(route('api.v1.matriculas.store'), $this->datosPersonaNueva($grupo))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El lead LEAD-500 del CRM ya fue procesado.');

        $this->assertSame(1, Matricula::count());
    }

    public function test_sin_lead_ni_grupo_responde_los_errores_de_validacion(): void
    {
        Sanctum::actingAs($this->admin, ['matriculas:crear']);

        $this->postJson(route('api.v1.matriculas.store'), ['documento' => '1', 'tipo' => 'grupo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['crm_lead_id', 'grupo_id', 'acepto_requisitos', 'name', 'email']);
    }

    public function test_el_comando_genera_un_token_nuevo_y_revoca_el_anterior(): void
    {
        $this->artisan('crm:token')->assertSuccessful();
        $primero = PersonalAccessToken::sole();

        $this->artisan('crm:token')->assertSuccessful();

        $this->assertModelMissing($primero);
        $this->assertSame(['matriculas:crear'], PersonalAccessToken::sole()->abilities);
    }

    public function test_el_comando_rechaza_a_quien_no_es_admin(): void
    {
        $alumno = User::factory()->create(['email' => 'alumno@example.com']);

        $this->artisan('crm:token', ['email' => $alumno->email])->assertFailed();

        $this->assertSame(0, PersonalAccessToken::count());
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPersonaNueva(?Grupo $grupo = null): array
    {
        return [
            'crm_lead_id' => 'LEAD-500',
            'documento' => '77777777',
            'name' => 'Lucia',
            'apellidos' => 'Rojas',
            'email' => 'lucia@example.com',
            'telefono' => '70000001',
            'tipo' => 'grupo',
            'grupo_id' => $grupo?->id,
            'acepto_requisitos' => true,
        ];
    }
}
