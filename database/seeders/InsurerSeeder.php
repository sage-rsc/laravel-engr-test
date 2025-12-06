<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurers = [
            [
                'name' => 'Insurer A',
                'code' => 'INS-A',
                'email' => 'insurer-a@example.com',
                'daily_capacity' => 1000,
                'min_batch_size' => 10,
                'max_batch_size' => 100,
                'date_preference' => 'encounter',
                'specialty_efficiencies' => json_encode(['cardiology' => 0.8, 'orthopedics' => 0.9]),
            ],
            [
                'name' => 'Insurer B',
                'code' => 'INS-B',
                'email' => 'insurer-b@example.com',
                'daily_capacity' => 1200,
                'min_batch_size' => 15,
                'max_batch_size' => 120,
                'date_preference' => 'submission',
                'specialty_efficiencies' => json_encode(['orthopedics' => 0.75, 'cardiology' => 0.95]),
            ],
            [
                'name' => 'Insurer C',
                'code' => 'INS-C',
                'email' => 'insurer-c@example.com',
                'daily_capacity' => 800,
                'min_batch_size' => 5,
                'max_batch_size' => 80,
                'date_preference' => 'encounter',
                'specialty_efficiencies' => json_encode(['cardiology' => 0.7]),
            ],
            [
                'name' => 'Insurer D',
                'code' => 'INS-D',
                'email' => 'insurer-d@example.com',
                'daily_capacity' => 1500,
                'min_batch_size' => 20,
                'max_batch_size' => 150,
                'date_preference' => 'submission',
                'specialty_efficiencies' => json_encode(['orthopedics' => 0.85]),
            ],
        ];

        DB::table('insurers')->insert($insurers);
    }
}
