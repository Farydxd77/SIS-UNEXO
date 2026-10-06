<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_formulario_de_login_carga(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesion');
    }

    public function test_un_usuario_activo_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create([
            'email' => 'carlos@test.com',
            'password' => 'password123',
        ]);

        $respuesta = $this->post('/login', [
            'email' => 'carlos@test.com',
            'password' => 'password123',
        ]);

        $respuesta->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_no_entra_con_la_contrasena_equivocada(): void
    {
        User::factory()->create([
            'email' => 'carlos@test.com',
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'carlos@test.com',
            'password' => 'equivocada',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_usuario_inactivo_no_puede_entrar(): void
    {
        // Aunque la contraseña sea correcta, activo = false lo bloquea.
        User::factory()->inactivo()->create([
            'email' => 'inactivo@test.com',
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'inactivo@test.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_se_puede_cerrar_sesion(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_un_invitado_no_entra_a_la_zona_privada(): void
    {
        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_quien_ya_tiene_sesion_no_ve_el_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect();
    }
}
