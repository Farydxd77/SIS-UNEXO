<?php

namespace Database\Seeders;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Database\Seeder;

/**
 * Catalogo real de UNEXO: el programa "Data Analysis Expert" con sus 6 modulos.
 */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $programa = Programa::firstOrCreate(
            ['codigo' => 'DAE-2025'],
            [
                'nombre' => 'Data Analysis Expert',
                'descripcion' => 'Programa integral de analisis de datos: desde Excel hasta Python, '
                    .'pasando por Power BI, SQL y R. Seis modulos 100% virtuales por Google Meet.',
                'cantidad_modulos' => 6,
                'precio_contado' => 4200.00,
                'estado_comercial' => EstadoComercial::Abierto,
            ],
        );

        // Los 6 modulos en el orden exacto de la spec.
        $modulos = [
            [
                'orden' => 1,
                'codigo' => 'EXC-01',
                'nombre' => 'Excel',
                'requisitos' => 'Manejo basico de computadora',
                'horas' => 24,
                'precio' => 700.00,
                'descripcion' => 'Hojas de calculo desde cero hasta tablas dinamicas y funciones de busqueda.',
                'temario' => "1. Interfaz y navegacion\n2. Formulas y referencias\n3. Funciones logicas y de texto\n"
                    ."4. BUSCARV / BUSCARX\n5. Tablas dinamicas\n6. Graficos y formato condicional",
            ],
            [
                'orden' => 2,
                'codigo' => 'PBI-01',
                'nombre' => 'Power BI',
                'requisitos' => 'Excel intermedio',
                'horas' => 24,
                'precio' => 750.00,
                'descripcion' => 'Construccion de tableros interactivos y modelado de datos con Power BI Desktop.',
                'temario' => "1. Power Query y transformacion\n2. Modelado y relaciones\n3. Medidas DAX basicas\n"
                    ."4. Visualizaciones\n5. Publicacion en el servicio",
            ],
            [
                'orden' => 3,
                'codigo' => 'SQL-01',
                'nombre' => 'SQL',
                'requisitos' => 'Nociones de bases de datos',
                'horas' => 32,
                'precio' => 850.00,
                'descripcion' => 'Consulta y manipulacion de datos con SQL sobre motores relacionales.',
                'temario' => "1. SELECT, WHERE y ORDER BY\n2. JOINs\n3. Agregaciones y GROUP BY\n"
                    ."4. Subconsultas\n5. Funciones de ventana\n6. Vistas e indices",
            ],
            [
                'orden' => 4,
                'codigo' => 'PBI-02',
                'nombre' => 'Power BI Nivel 2',
                'requisitos' => 'Haber cursado Power BI',
                'horas' => 24,
                'precio' => 800.00,
                'descripcion' => 'DAX avanzado, modelos en estrella y optimizacion de tableros.',
                'temario' => "1. DAX avanzado\n2. Contexto de fila y de filtro\n3. Time intelligence\n"
                    ."4. Modelo en estrella\n5. Optimizacion y buenas practicas",
            ],
            [
                'orden' => 5,
                'codigo' => 'RST-01',
                'nombre' => 'R Studio',
                'requisitos' => 'Estadistica basica',
                'horas' => 32,
                'precio' => 850.00,
                'descripcion' => 'Analisis estadistico y visualizacion con R y el ecosistema tidyverse.',
                'temario' => "1. Sintaxis de R\n2. dplyr y tidyr\n3. ggplot2\n4. Estadistica descriptiva\n"
                    ."5. Pruebas de hipotesis\n6. Regresion lineal",
            ],
            [
                'orden' => 6,
                'codigo' => 'PYT-01',
                'nombre' => 'Python',
                'requisitos' => 'Logica de programacion',
                'horas' => 40,
                'precio' => 950.00,
                'descripcion' => 'Python aplicado al analisis de datos con pandas, numpy y matplotlib.',
                'temario' => "1. Sintaxis y estructuras\n2. numpy\n3. pandas\n4. Limpieza de datos\n"
                    ."5. matplotlib y seaborn\n6. Proyecto final",
            ],
        ];

        foreach ($modulos as $datos) {
            $orden = $datos['orden'];
            unset($datos['orden']);

            $modulo = Modulo::firstOrCreate(
                ['codigo' => $datos['codigo']],
                $datos + [
                    'se_oferta_por_separado' => true,
                    'estado_comercial' => EstadoComercial::Abierto,
                ],
            );

            // syncWithoutDetaching no duplica si el seeder se relanza.
            $programa->modulos()->syncWithoutDetaching([
                $modulo->id => ['orden' => $orden],
            ]);
        }
    }
}
