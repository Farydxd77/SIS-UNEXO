<?php

namespace Tests\Feature;

use App\Enums\EstadoCohorte;
use App\Models\Cohorte;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pagina publica de inicio: la raiz ya no salta directo al login.
 */
class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_ve_el_inicio_con_acceso_al_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Iniciar sesion')
            ->assertSee(route('login'));
    }

    public function test_el_inicio_muestra_solo_lo_abierto_y_proximo(): void
    {
        $abierto = Programa::factory()->abierto()->create(['nombre' => 'Programa Abierto']);
        Programa::factory()->create(['nombre' => 'Programa Borrador']);

        Cohorte::factory()->create(['programa_id' => $abierto->id, 'nombre' => 'Cohorte Proxima']);
        Cohorte::factory()->create([
            'programa_id' => $abierto->id,
            'nombre' => 'Cohorte Cerrada',
            'estado' => EstadoCohorte::Finalizado,
        ]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Programa Abierto')
            ->assertDontSee('Programa Borrador')
            ->assertSee('Cohorte Proxima')
            ->assertDontSee('Cohorte Cerrada');
    }

    public function test_con_sesion_el_inicio_lleva_al_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Ir a mi panel');
    }
}
