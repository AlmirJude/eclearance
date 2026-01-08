<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Nursing',
                'description' => 'Bachelor of Science in Nursing',
                'Abbreviation' => 'BSN',
            ],
            [
                'name' => 'Computer Science',
                'description' => 'Bachelor of Science in Computer Science',
                'Abbreviation' => 'BSCS',
            ],
            [
                'name' => 'Business Administration',
                'description' => 'Bachelor of Science in Business Administration',
                'Abbreviation' => 'BSBA',
            ],
            [
                'name' => 'Accountancy',
                'description' => 'Bachelor of Science in Accountancy',
                'Abbreviation' => 'BSA',
            ],
            [
                'name' => 'Communication',
                'description' => 'Bachelor of Arts in Communication',
                'Abbreviation' => 'ABComm',
            ],
            [
                'name' => 'Psychology',
                'description' => 'Bachelor of Science in Psychology',
                'Abbreviation' => 'BSP',
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
