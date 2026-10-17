<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Material;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@cobit.test'], [
            'name' => 'Administrator', 'npm' => 'ADMIN001', 'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN, 'prodi' => 'SI', 'angkatan' => 24, 'points' => 0,
        ]);

        foreach (range(1, 6) as $index) {
            User::updateOrCreate(['email' => "mahasiswa{$index}@cobit.test"], [
                'name' => "Mahasiswa {$index}", 'npm' => '241000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'), 'role' => User::ROLE_MAHASISWA,
                'prodi' => 'SI', 'angkatan' => 24, 'points' => 0,
            ]);
        }

        $courses = [
            ['SI101', 'Pengantar Sistem Informasi', 1], ['SI102', 'Basis Data', 1], ['SI103', 'Algoritma dan Pemrograman', 1],
            ['SI104', 'Matematika Diskrit', 1], ['SI201', 'Analisis dan Perancangan Sistem', 2], ['SI202', 'Pemrograman Web', 2],
            ['SI203', 'Jaringan Komputer', 2], ['SI301', 'Manajemen Proyek SI', 3], ['SI302', 'Interaksi Manusia dan Komputer', 3],
            ['SI401', 'Keamanan Sistem Informasi', 4], ['SI402', 'Audit Sistem Informasi', 4], ['SI501', 'Tata Kelola TI', 5],
        ];

        foreach ($courses as [$code, $name, $semester]) {
            $course = Course::updateOrCreate(['code' => $code], ['name' => $name, 'semester' => $semester, 'prodi' => 'SI', 'is_active' => true]);
            $material = Material::firstOrCreate(['course_id' => $course->id, 'title' => 'Materi Dasar '.$name]);
            for ($number = 1; $number <= 10; $number++) {
                Question::firstOrCreate(['material_id' => $material->id, 'question_text' => "Contoh soal {$number} untuk {$name}"], [
                    'option_a' => 'Pilihan A', 'option_b' => 'Pilihan B', 'option_c' => 'Pilihan C', 'option_d' => 'Pilihan D',
                    'correct_answer' => 'a', 'status' => Question::STATUS_ACTIVE, 'source' => 'manual',
                ]);
            }
        }
    }
}
