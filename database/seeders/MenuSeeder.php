<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('menus')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('menus')->insert([
            [
                'type' => 'main',
                'name' => 'Main Menu',
                'slug' => 'main-menu',
                'url' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'main',
                'name' => 'Sidebar Menu',
                'slug' => 'sidebar-menu',
                'url' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'main',
                'name' => 'Footer Menu',
                'slug' => 'footer-menu',
                'url' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'main',
                'name' => 'Header Menu',
                'slug' => 'header-menu',
                'url' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'main',
                'name' => 'Custom Menu',
                'slug' => 'custom-menu',
                'url' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
