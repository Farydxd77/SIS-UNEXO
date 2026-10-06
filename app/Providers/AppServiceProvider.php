<?php

namespace App\Providers;

use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Para que $usuarios->links() pinte la paginacion con estilos Bootstrap 5.
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $view->with('misCursos', $this->misCursos(auth()->user()));
        });
    }

    /**
     * Los grupos en marcha del usuario para el cajon lateral, como el
     * "Mis cursos" de Moodle: los que imparte y en los que esta inscrito.
     *
     * @return Collection<int, Grupo>
     */
    private function misCursos(?User $usuario): Collection
    {
        if (! $usuario) {
            return new Collection;
        }

        $enMarcha = fn ($q) => $q->whereNotIn('estado', [EstadoGrupo::Finalizado, EstadoGrupo::Cancelado]);

        return Grupo::with('modulo')
            ->where(fn ($q) => $q
                ->when($usuario->esDocente(), fn ($q) => $q->orWhere('docente_id', $usuario->id))
                ->when($usuario->esEstudiante(), fn ($q) => $q->orWhereHas(
                    'matriculas',
                    fn ($m) => $m->where('user_id', $usuario->id)->whereIn('estado', EstadoMatricula::vigentes())
                ))
                ->when(! $usuario->esDocente() && ! $usuario->esEstudiante(), fn ($q) => $q->whereRaw('1 = 0')))
            ->tap($enMarcha)
            ->orderBy('fecha_inicio')
            ->limit(10)
            ->get();
    }
}
