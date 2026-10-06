<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * MODULO 1 - CRUD de usuarios. Todo esto solo lo puede hacer un administrador.
 */
class UsuarioCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Rol $rolAdmin;

    private Rol $rolEstudiante;

    private Rol $rolDocente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolEstudiante = Rol::create(['nombre' => User::ROL_ESTUDIANTE]);
        $this->rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        $this->rolAdmin = Rol::create(['nombre' => User::ROL_ADMINISTRADOR]);

        // El actor de estos tests tiene que ser administrador: el middleware
        // 'rol:administrador' devuelve 403 a cualquier otro.
        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$this->rolAdmin->id]);

        $this->actingAs($this->admin);
    }

    public function test_el_listado_muestra_los_usuarios(): void
    {
        User::factory()->create(['name' => 'Pedro', 'apellidos' => 'Gomez Luna']);

        $this->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Pedro Gomez Luna');
    }

    public function test_un_usuario_sin_rol_de_admin_recibe_403(): void
    {
        $docente = User::factory()->create();
        $docente->roles()->sync([$this->rolDocente->id]);

        // Aunque escriba la URL a mano: la autorizacion es del servidor.
        $this->actingAs($docente)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_se_puede_crear_un_usuario_con_varios_roles(): void
    {
        $respuesta = $this->post(route('usuarios.store'), [
            'name' => 'Ana',
            'apellidos' => 'Torres Vega',
            'documento' => '12345678',
            'email' => 'ana@test.com',
            'correo_institucional' => 'a.torres@unexo.edu.bo',
            'telefono' => '77712345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'activo' => '1',
            'roles' => [$this->rolEstudiante->id, $this->rolDocente->id],
        ]);

        $respuesta->assertRedirect(route('usuarios.index'))->assertSessionHas('exito');

        $this->assertDatabaseHas('users', [
            'documento' => '12345678',
            'email' => 'ana@test.com',
            'activo' => true,
            // RN 1.7: la contrasena que carga el admin es temporal.
            'debe_cambiar_password' => true,
        ]);

        $ana = User::where('email', 'ana@test.com')->first();
        $this->assertCount(2, $ana->roles);
        $this->assertDatabaseHas('rol_user', ['user_id' => $ana->id, 'rol_id' => $this->rolDocente->id]);
    }

    public function test_la_contrasena_se_guarda_encriptada(): void
    {
        $this->post(route('usuarios.store'), $this->datosValidos([
            'password' => 'secreto12345',
            'password_confirmation' => 'secreto12345',
        ]))->assertRedirect(route('usuarios.index'));

        $usuario = User::where('email', 'nuevo@test.com')->firstOrFail();

        $this->assertNotSame('secreto12345', $usuario->password);
        $this->assertTrue(Hash::check('secreto12345', $usuario->password));
    }

    /** RN 1.1: documento unico en todo el sistema. */
    public function test_no_admite_documento_repetido(): void
    {
        User::factory()->create(['documento' => '99999999']);

        $this->post(route('usuarios.store'), $this->datosValidos(['documento' => '99999999']))
            ->assertSessionHasErrors('documento');
    }

    public function test_no_admite_correo_repetido(): void
    {
        User::factory()->create(['email' => 'repetido@test.com']);

        $this->post(route('usuarios.store'), $this->datosValidos(['email' => 'repetido@test.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_no_admite_correo_institucional_repetido(): void
    {
        User::factory()->create(['correo_institucional' => 'dup@unexo.edu.bo']);

        $this->post(route('usuarios.store'), $this->datosValidos(['correo_institucional' => 'dup@unexo.edu.bo']))
            ->assertSessionHasErrors('correo_institucional');
    }

    public function test_las_contrasenas_deben_coincidir(): void
    {
        $this->post(route('usuarios.store'), $this->datosValidos([
            'password' => 'password123',
            'password_confirmation' => 'otracosa123',
        ]))->assertSessionHasErrors('password');
    }

    /** RN 1.3: todo usuario debe tener al menos un rol. */
    public function test_no_se_puede_crear_un_usuario_sin_rol(): void
    {
        $this->post(route('usuarios.store'), $this->datosValidos(['roles' => []]))
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@test.com']);
    }

    public function test_rechaza_un_rol_que_no_existe(): void
    {
        $this->post(route('usuarios.store'), $this->datosValidos(['roles' => [9999]]))
            ->assertSessionHasErrors('roles.0');
    }

    public function test_al_editar_sin_tocar_la_contrasena_esta_se_conserva(): void
    {
        $usuario = User::factory()->create(['password' => 'original123']);
        $usuario->roles()->sync([$this->rolEstudiante->id]);
        $hashOriginal = $usuario->fresh()->password;

        $this->put(route('usuarios.update', $usuario), $this->datosEdicion($usuario, [
            'name' => 'Nombre Cambiado',
            'password' => '',
            'password_confirmation' => '',
        ]))->assertRedirect(route('usuarios.index'));

        $usuario->refresh();

        $this->assertSame('Nombre Cambiado', $usuario->name);
        $this->assertSame($hashOriginal, $usuario->password);
    }

    public function test_al_editar_con_contrasena_nueva_esta_cambia(): void
    {
        $usuario = User::factory()->create(['password' => 'original123']);
        $usuario->roles()->sync([$this->rolEstudiante->id]);
        $hashOriginal = $usuario->fresh()->password;

        $this->put(route('usuarios.update', $usuario), $this->datosEdicion($usuario, [
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ]))->assertRedirect(route('usuarios.index'));

        $usuario->refresh();

        $this->assertNotSame($hashOriginal, $usuario->password);
        $this->assertTrue(Hash::check('nueva12345', $usuario->password));
        // Contrasena puesta por el admin: vuelve a ser temporal.
        $this->assertTrue($usuario->debe_cambiar_password);
    }

    public function test_editar_reemplaza_los_roles(): void
    {
        $usuario = User::factory()->create();
        $usuario->roles()->sync([$this->rolEstudiante->id]);

        $this->put(route('usuarios.update', $usuario), $this->datosEdicion($usuario, [
            'roles' => [$this->rolDocente->id],
        ]))->assertRedirect(route('usuarios.index'));

        $usuario->refresh();

        // sync() deja EXACTAMENTE lo que le pasas.
        $this->assertCount(1, $usuario->roles);
        $this->assertSame(User::ROL_DOCENTE, $usuario->roles->first()->nombre);
    }

    /** RN 1.6: la baja es logica, nunca DELETE. */
    public function test_eliminar_desactiva_en_lugar_de_borrar(): void
    {
        $usuario = User::factory()->create();

        $this->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'activo' => false]);
    }

    public function test_se_puede_volver_a_activar(): void
    {
        $usuario = User::factory()->inactivo()->create();

        $this->post(route('usuarios.activar', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'activo' => true]);
    }

    /** RN 1.4: el admin no puede desactivarse a si mismo. */
    public function test_no_puedo_desactivarme_a_mi_mismo(): void
    {
        $this->delete(route('usuarios.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'activo' => true]);
    }

    /** RN 1.5: no se puede desactivar al ultimo administrador activo. */
    public function test_no_se_puede_desactivar_al_ultimo_administrador(): void
    {
        $otroAdmin = User::factory()->create();
        $otroAdmin->roles()->sync([$this->rolAdmin->id]);

        // Con dos admins, desactivar a uno si se puede.
        $this->delete(route('usuarios.destroy', $otroAdmin))->assertSessionHas('exito');
        $this->assertDatabaseHas('users', ['id' => $otroAdmin->id, 'activo' => false]);

        // Ahora solo queda el admin actual, y nadie mas puede quedarse sin admins.
        $tercero = User::factory()->create();
        $tercero->roles()->sync([$this->rolAdmin->id]);
        $this->actingAs($tercero);

        $this->delete(route('usuarios.destroy', $this->admin))->assertSessionHas('exito');

        // El tercero es el ultimo admin activo: no puede quitarse el rol.
        $this->put(route('usuarios.update', $tercero), $this->datosEdicion($tercero, [
            'roles' => [$this->rolEstudiante->id],
        ]))->assertSessionHas('error');

        $this->assertTrue($tercero->fresh()->esAdministrador());
    }

    public function test_restablecer_password_genera_una_temporal(): void
    {
        $usuario = User::factory()->create(['password' => 'original123']);
        $hashOriginal = $usuario->fresh()->password;

        $this->post(route('usuarios.restablecer', $usuario))
            ->assertRedirect(route('usuarios.show', $usuario))
            ->assertSessionHas('password_temporal');

        $usuario->refresh();

        $this->assertNotSame($hashOriginal, $usuario->password);
        $this->assertTrue($usuario->debe_cambiar_password);
    }

    public function test_el_buscador_filtra_por_documento(): void
    {
        User::factory()->create(['documento' => '55550000', 'name' => 'Buscado', 'apellidos' => 'Aaa']);
        User::factory()->create(['documento' => '11110000', 'name' => 'Otro', 'apellidos' => 'Bbb']);

        $this->get(route('usuarios.index', ['buscar' => '55550000']))
            ->assertOk()
            ->assertSee('Buscado')
            ->assertDontSee('Otro Bbb');
    }

    public function test_el_buscador_ignora_mayusculas_y_minusculas(): void
    {
        User::factory()->create(['name' => 'Buscado', 'apellidos' => 'Aaa']);
        User::factory()->create(['name' => 'Otro', 'apellidos' => 'Bbb']);

        $this->get(route('usuarios.index', ['buscar' => 'bUsCaDo']))
            ->assertOk()
            ->assertSee('Buscado Aaa')
            ->assertDontSee('Otro Bbb');
    }

    public function test_el_filtro_por_rol_funciona(): void
    {
        $docente = User::factory()->create(['name' => 'Soy', 'apellidos' => 'Docente']);
        $docente->roles()->sync([$this->rolDocente->id]);

        $alumno = User::factory()->create(['name' => 'Soy', 'apellidos' => 'Alumno']);
        $alumno->roles()->sync([$this->rolEstudiante->id]);

        $this->get(route('usuarios.index', ['rol' => $this->rolDocente->id]))
            ->assertOk()
            ->assertSee('Soy Docente')
            ->assertDontSee('Soy Alumno');
    }

    public function test_el_filtro_por_estado_funciona(): void
    {
        User::factory()->create(['name' => 'Activo', 'apellidos' => 'Uno']);
        User::factory()->inactivo()->create(['name' => 'Inactivo', 'apellidos' => 'Dos']);

        $this->get(route('usuarios.index', ['estado' => '0']))
            ->assertOk()
            ->assertSee('Inactivo Dos')
            ->assertDontSee('Activo Uno');
    }

    public function test_las_pantallas_de_ver_crear_y_editar_cargan(): void
    {
        $usuario = User::factory()->create(['name' => 'Visible', 'apellidos' => 'Enpantalla']);

        $this->get(route('usuarios.create'))->assertOk()->assertSee('Nuevo usuario');
        $this->get(route('usuarios.show', $usuario))->assertOk()->assertSee('Visible Enpantalla');
        $this->get(route('usuarios.edit', $usuario))->assertOk()->assertSee('Visible Enpantalla');
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Nuevo',
            'apellidos' => 'Usuario Test',
            'documento' => '87654321',
            'email' => 'nuevo@test.com',
            'telefono' => '77799999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'activo' => '1',
            'roles' => [$this->rolEstudiante->id],
        ], $cambios);
    }

    private function datosEdicion(User $usuario, array $cambios = []): array
    {
        return array_merge([
            'name' => $usuario->name,
            'apellidos' => $usuario->apellidos,
            'documento' => $usuario->documento,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'activo' => '1',
            'roles' => $usuario->roles->pluck('id')->all() ?: [$this->rolEstudiante->id],
        ], $cambios);
    }
}
