<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El actor debe ser administrador: el middleware 'rol' bloquea al resto.
        $admin = User::factory()->create();
        $admin->roles()->sync([
            Rol::create(['nombre' => User::ROL_ADMINISTRADOR])->id,
        ]);

        $this->actingAs($admin);
    }

    public function test_un_usuario_sin_rol_de_admin_recibe_403(): void
    {
        $rolDocente = Rol::create(['nombre' => User::ROL_DOCENTE]);
        $docente = User::factory()->create();
        $docente->roles()->sync([$rolDocente->id]);

        $this->actingAs($docente)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_el_listado_muestra_los_roles_con_su_numero_de_usuarios(): void
    {
        $rol = Rol::create(['nombre' => 'docente', 'descripcion' => 'Imparte modulos.']);
        User::factory()->count(3)->create()->each(fn (User $u) => $u->roles()->sync([$rol->id]));

        $this->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Docente')
            ->assertSee('Imparte modulos.');
    }

    public function test_se_puede_crear_un_rol(): void
    {
        $this->post(route('roles.store'), [
            'nombre' => 'coordinador',
            'descripcion' => 'Coordina programas.',
        ])->assertRedirect(route('roles.index'))->assertSessionHas('exito');

        $this->assertDatabaseHas('roles', ['nombre' => 'coordinador']);
    }

    public function test_el_nombre_del_rol_es_obligatorio(): void
    {
        $this->post(route('roles.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        // El rol administrador del setUp sigue ahi; lo que importa es que
        // no se haya creado ninguno con nombre vacio.
        $this->assertDatabaseMissing('roles', ['nombre' => '']);
    }

    public function test_no_admite_dos_roles_con_el_mismo_nombre(): void
    {
        Rol::create(['nombre' => 'docente']);

        $this->post(route('roles.store'), ['nombre' => 'docente'])
            ->assertSessionHasErrors('nombre');

        // Sigue habiendo un solo 'docente': el unique lo impidio.
        $this->assertSame(1, Rol::where('nombre', 'docente')->count());
    }

    public function test_se_puede_actualizar_un_rol(): void
    {
        $rol = Rol::create(['nombre' => 'antes']);

        $this->put(route('roles.update', $rol), [
            'nombre' => 'despues',
            'descripcion' => 'Cambiado.',
        ])->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['id' => $rol->id, 'nombre' => 'despues']);
    }

    public function test_al_editar_un_rol_su_propio_nombre_no_choca_consigo_mismo(): void
    {
        $rol = Rol::create(['nombre' => 'docente']);

        // Guardar sin cambiar el nombre no debe dar error de "ya existe".
        $this->put(route('roles.update', $rol), [
            'nombre' => 'docente',
            'descripcion' => 'Solo cambio la descripcion.',
        ])->assertSessionHasNoErrors();
    }

    public function test_se_puede_borrar_un_rol_que_nadie_tiene(): void
    {
        $rol = Rol::create(['nombre' => 'temporal']);

        $this->delete(route('roles.destroy', $rol))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    public function test_no_se_puede_borrar_un_rol_asignado_a_alguien(): void
    {
        $rol = Rol::create(['nombre' => 'estudiante']);
        User::factory()->create()->roles()->sync([$rol->id]);

        $this->delete(route('roles.destroy', $rol))
            ->assertSessionHas('error');

        // Sigue existiendo: no se perdio la asignacion de nadie.
        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_borrar_un_usuario_de_verdad_limpia_la_tabla_pivote(): void
    {
        $rol = Rol::create(['nombre' => 'estudiante']);
        $usuario = User::factory()->create();
        $usuario->roles()->sync([$rol->id]);

        $this->assertDatabaseHas('rol_user', ['user_id' => $usuario->id, 'rol_id' => $rol->id]);

        // cascadeOnDelete de la migracion: al irse el usuario se va su fila del pivote.
        $usuario->delete();

        $this->assertDatabaseMissing('rol_user', ['user_id' => $usuario->id]);
        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_los_formularios_de_rol_cargan(): void
    {
        $rol = Rol::create(['nombre' => 'editable']);

        $this->get(route('roles.create'))->assertOk()->assertSee('Nuevo rol');
        $this->get(route('roles.edit', $rol))->assertOk()->assertSee('editable');
    }
}
