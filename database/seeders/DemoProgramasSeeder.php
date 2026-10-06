<?php

namespace Database\Seeders;

use App\Actions\Cohortes\GenerarGruposDeCohorte;
use App\Enums\DiaSemana;
use App\Enums\EstadoCohorte;
use App\Enums\EstadoComercial;
use App\Enums\EstadoGrupo;
use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Enums\ResultadoMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos de demostracion: cuatro programas mas, con cohortes en todos los
 * estados, y el estudiante de prueba cursando un programa COMPLETO
 * (Full Stack, a mitad de camino) y con otro ya terminado (Marketing).
 *
 * Las fechas son de 2026 para que, a octubre de 2026, haya modulos
 * aprobados, uno en curso y otros por venir. Es idempotente: si el
 * primer programa ya existe, no hace nada.
 */
class DemoProgramasSeeder extends Seeder
{
    public function run(): void
    {
        $estudiante = User::where('email', 'estudiante@unexo.test')->firstOrFail();

        if (Programa::where('codigo', 'DFS-2026')->exists()) {
            $this->command->warn('  Los programas de demostracion ya existen.');
        } else {
            $this->programasYCohortes($estudiante);
        }

        $this->modulosSueltos($estudiante);
    }

    /**
     * Grupos abiertos por fuera de una cohorte, para quien solo quiere un
     * modulo: uno ya aprobado y otro en curso.
     */
    private function modulosSueltos(User $estudiante): void
    {
        $docente = User::where('email', 'docente2@unexo.test')->firstOrFail();

        $sueltos = [
            // [codigo de modulo, inicio, fin, estado del grupo, nota o estado de la matricula]
            ['CIB-02', '2026-07-04', '2026-08-29', EstadoGrupo::Finalizado, 91],
            ['PYT-01', '2026-09-19', '2026-11-14', EstadoGrupo::EnCurso, 'activa'],
        ];

        foreach ($sueltos as [$codigo, $inicio, $fin, $estadoGrupo, $avance]) {
            $modulo = Modulo::where('codigo', $codigo)->first();

            if (! $modulo || Matricula::where('user_id', $estudiante->id)
                ->whereHas('grupo', fn ($q) => $q->where('modulo_id', $modulo->id)->whereNull('cohorte_id'))
                ->exists()) {
                continue;
            }

            $grupo = Grupo::create([
                'modulo_id' => $modulo->id,
                'cohorte_id' => null,
                'docente_id' => $docente->id,
                'version' => (int) Grupo::where('modulo_id', $modulo->id)->max('version') + 1,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'cupo_minimo' => 20,
                'cupo_maximo' => 30,
                'enlace_meet' => 'https://meet.google.com/'.strtolower($codigo).'-suelto',
                'estado' => $estadoGrupo,
            ]);

            // Sabados por la manana: no choca con los grupos de la semana.
            Horario::create([
                'grupo_id' => $grupo->id,
                'dia_semana' => DiaSemana::Sabado,
                'hora_inicio' => '09:00',
                'hora_fin' => '12:00',
            ]);

            $this->matricular($estudiante, collect([$grupo]), [$avance]);
        }
    }

    private function programasYCohortes(User $estudiante): void
    {
        $docentes = [
            User::where('email', 'docente@unexo.test')->firstOrFail(),
            User::where('email', 'docente2@unexo.test')->firstOrFail(),
        ];
        $companeros = User::conRol(User::ROL_ESTUDIANTE)->activos()
            ->where('id', '!=', $estudiante->id)
            ->orderBy('id')
            ->take(18)
            ->get();

        $fullStack = $this->programa('DFS-2026', 'Desarrollo Web Full Stack', 4800,
            'Aprende a construir aplicaciones web completas: del maquetado con HTML y CSS '
            .'hasta APIs con Node.js, bases de datos y un proyecto final desplegado en la nube.', [
                ['WEB-01', 'HTML y CSS', 24, 'Manejo basico de computadora'],
                ['WEB-02', 'JavaScript Moderno', 32, 'HTML y CSS'],
                ['WEB-03', 'React', 32, 'JavaScript'],
                ['WEB-04', 'Node.js y Express', 32, 'JavaScript'],
                ['WEB-05', 'Bases de Datos con MySQL', 24, 'Ninguno'],
                ['WEB-06', 'Proyecto Integrador', 40, 'Modulos anteriores del programa'],
            ]);

        $marketing = $this->programa('MKD-2026', 'Marketing Digital Profesional', 3600,
            'Estrategia, contenido y publicidad en linea. Campanas reales en redes sociales '
            .'y Google, posicionamiento SEO y medicion de resultados con analitica web.', [
                ['MKD-01', 'Fundamentos de Marketing Digital', 20, 'Ninguno'],
                ['MKD-02', 'Redes Sociales y Contenido', 24, 'Ninguno'],
                ['MKD-03', 'Google Ads', 24, 'Fundamentos de Marketing Digital'],
                ['MKD-04', 'SEO y Posicionamiento Web', 24, 'Ninguno'],
                ['MKD-05', 'Analitica Web con GA4', 20, 'Excel basico'],
            ]);

        $ciber = $this->programa('CIB-2026', 'Ciberseguridad Esencial', 4200,
            'Bases para proteger sistemas y redes: protocolos, Linux, hacking etico '
            .'y seguridad de aplicaciones web, con laboratorios practicos.', [
                ['CIB-01', 'Redes y Protocolos', 24, 'Manejo basico de computadora'],
                ['CIB-02', 'Linux para Seguridad', 24, 'Ninguno'],
                ['CIB-03', 'Hacking Etico', 32, 'Redes y Linux'],
                ['CIB-04', 'Seguridad en Aplicaciones Web', 32, 'Nociones de programacion web'],
            ]);

        $agil = $this->programa('GPA-2026', 'Gestion de Proyectos Agiles', 3200,
            'Gestiona proyectos con marcos agiles: Scrum, Kanban y herramientas de '
            .'planificacion, con enfoque en liderazgo de equipos.', [
                ['GPA-01', 'Fundamentos de Gestion de Proyectos', 20, 'Ninguno'],
                ['GPA-02', 'Scrum en la Practica', 24, 'Ninguno'],
                ['GPA-03', 'Kanban y Lean', 20, 'Ninguno'],
                ['GPA-04', 'Liderazgo de Equipos', 20, 'Ninguno'],
            ]);

        // Full Stack: empezo en junio; 4 modulos terminados, el 5 en curso, el 6 por venir.
        $grupos = $this->cohorte($fullStack, 'FS - Version 1, Junio 2026', '2026-06-01', EstadoCohorte::EnCurso, $docentes,
            [EstadoGrupo::Finalizado, EstadoGrupo::Finalizado, EstadoGrupo::Finalizado, EstadoGrupo::Finalizado, EstadoGrupo::EnCurso, EstadoGrupo::Habilitado]);
        $this->matricular($estudiante, $grupos, [88, 92, 76, 85, 'activa', 'activa']);
        foreach ($companeros as $i => $companero) {
            $this->matricular($companero, $grupos, [70 + $i, 45 + $i * 3, 60 + $i, 75 + ($i % 20), 'activa', 'activa']);
        }

        // Marketing: cohorte terminada en mayo. Reprobo Google Ads: asi se ve el rojo.
        $grupos = $this->cohorte($marketing, 'MKD - Version 1, Enero 2026', '2026-01-12', EstadoCohorte::Finalizado, $docentes,
            array_fill(0, 5, EstadoGrupo::Finalizado));
        $this->matricular($estudiante, $grupos, [90, 81, 48, 87, 94]);
        foreach ($companeros->take(12) as $i => $companero) {
            $this->matricular($companero, $grupos, [65 + $i * 2, 55 + $i * 3, 40 + $i * 4, 72 + $i, 58 + $i * 3]);
        }

        // Proximas ediciones, para la pagina de inicio y la pestana de vigentes.
        $this->cohorte($marketing, 'MKD - Version 2, Noviembre 2026', '2026-11-02', EstadoCohorte::EnConvocatoria, $docentes,
            array_fill(0, 5, EstadoGrupo::EnConvocatoria));
        $this->cohorte($ciber, 'CIB - Version 1, Noviembre 2026', '2026-11-16', EstadoCohorte::EnConvocatoria, $docentes,
            array_fill(0, 4, EstadoGrupo::EnConvocatoria));
        $this->cohorte($agil, 'GPA - Version 1, Enero 2027', '2027-01-11', EstadoCohorte::Planificado, [],
            array_fill(0, 4, EstadoGrupo::Planificado));

        $this->command->info('  4 programas, 5 cohortes y el estudiante en Full Stack (4 de 6 aprobados).');
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int, 3: string}>  $modulos  [codigo, nombre, horas, requisitos]
     */
    private function programa(string $codigo, string $nombre, float $precio, string $descripcion, array $modulos): Programa
    {
        $programa = Programa::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'cantidad_modulos' => count($modulos),
            'precio_contado' => $precio,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        foreach ($modulos as $orden => [$codigoModulo, $nombreModulo, $horas, $requisitos]) {
            $modulo = Modulo::firstOrCreate(['codigo' => $codigoModulo], [
                'nombre' => $nombreModulo,
                'descripcion' => "Modulo de {$nombreModulo} del programa {$nombre}.",
                'requisitos' => $requisitos,
                'horas' => $horas,
                'precio' => round($precio / count($modulos) * 1.15, -1),
                'se_oferta_por_separado' => true,
                'estado_comercial' => EstadoComercial::Abierto,
            ]);

            $programa->modulos()->attach($modulo->id, ['orden' => $orden + 1]);
        }

        return $programa;
    }

    /**
     * Crea la cohorte con la misma Action del sistema y completa cada grupo.
     *
     * @param  list<User>  $docentes  Se alternan; vacio = sin asignar.
     * @param  list<EstadoGrupo>  $estados  Uno por grupo, en orden.
     * @return Collection<int, Grupo>
     */
    private function cohorte(Programa $programa, string $nombre, string $inicio, EstadoCohorte $estado, array $docentes, array $estados): Collection
    {
        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => $nombre,
            'fecha_inicio' => $inicio,
            'estado' => $estado,
        ]);

        (new GenerarGruposDeCohorte)->ejecutar($cohorte, semanasPorModulo: 4);

        $grupos = $cohorte->grupos()->with('modulo')->orderBy('fecha_inicio')->orderBy('id')->get();

        foreach ($grupos as $i => $grupo) {
            $grupo->update(['estado' => $estados[$i], 'cupo_maximo' => 30]);

            if ($docentes === []) {
                continue;
            }

            // Docente 1: lunes y miercoles. Docente 2: martes y jueves. Nunca chocan.
            $par = $i % 2;
            $grupo->update([
                'docente_id' => $docentes[$par]->id,
                'enlace_meet' => 'https://meet.google.com/'.strtolower($grupo->modulo->codigo).'-v'.$grupo->version,
            ]);

            foreach ($par === 0 ? [DiaSemana::Lunes, DiaSemana::Miercoles] : [DiaSemana::Martes, DiaSemana::Jueves] as $dia) {
                Horario::create([
                    'grupo_id' => $grupo->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '19:00',
                    'hora_fin' => '21:00',
                ]);
            }
        }

        return $grupos;
    }

    /**
     * Una matricula por grupo. Cada valor es la nota final (modulo terminado)
     * o el estado de una matricula vigente ('activa').
     *
     * @param  Collection<int, Grupo>  $grupos
     * @param  list<int|string>  $avance
     */
    private function matricular(User $alumno, Collection $grupos, array $avance): void
    {
        foreach ($grupos as $i => $grupo) {
            $valor = $avance[$i];
            $nota = is_int($valor) ? min(100, $valor) : null;

            Matricula::create([
                'user_id' => $alumno->id,
                'grupo_id' => $grupo->id,
                'estado' => $nota !== null ? EstadoMatricula::Finalizada : EstadoMatricula::from($valor),
                'origen' => OrigenMatricula::Manual,
                'acepto_requisitos' => true,
                'fecha_aceptacion' => $grupo->fecha_inicio->copy()->subWeek(),
                'nota_final' => $nota,
                'resultado' => $nota !== null ? ResultadoMatricula::segunNota($nota) : null,
            ]);
        }
    }
}
