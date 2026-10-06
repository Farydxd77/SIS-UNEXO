<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * El ORDEN importa: los roles antes que los usuarios,
     * porque UserSeeder los necesita para asignarlos.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UserSeeder::class,
        ]);
    }
}
