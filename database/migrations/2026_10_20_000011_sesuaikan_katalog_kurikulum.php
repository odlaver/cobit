<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Kode lama => [kode baru, nama, semester, prodi]
    private const PETA = [
        'SI101' => ['KSI', 'Konsep Sistem Informasi', 1, 'SI & Ilkom'],
        'SI102' => ['BD', 'Basis Data', 2, 'SI & Ilkom'],
        'SI103' => ['DPA', 'Dasar Pemrograman dan Algoritma', 1, 'SI & Ilkom'],
        'SI104' => ['MATDIS', 'Matematika Diskrit', 2, 'SI & Ilkom'],
        'SI201' => ['APSI', 'Analisis dan Perancangan Sistem Informasi', 4, 'SI & Ilkom'],
        'SI202' => ['PEMWEB', 'Pemrograman Web', 4, 'SI & Ilkom'],
        'SI203' => ['JARKOM', 'Jaringan Komputer dan Komunikasi Data', 3, 'SI & Ilkom'],
        'SI301' => ['MPSI', 'Manajemen Proyek Sistem Informasi', 5, 'SI & Ilkom'],
        'SI302' => ['IMK', 'Interaksi Manusia dan Komputer (UI/UX)', 3, 'SI & Ilkom'],
        'SI401' => ['KEAMSI', 'Keamanan Sistem Informasi', 5, 'SI & Ilkom'],
        'SI501' => ['TKTI', 'Tata Kelola Teknologi Informasi', 4, 'SI'],
    ];

    // Nilai asli untuk rollback
    private const ASLI = [
        'SI101' => ['Pengantar Sistem Informasi', 1],
        'SI102' => ['Basis Data', 1],
        'SI103' => ['Algoritma dan Pemrograman', 1],
        'SI104' => ['Matematika Diskrit', 1],
        'SI201' => ['Analisis dan Perancangan Sistem', 2],
        'SI202' => ['Pemrograman Web', 2],
        'SI203' => ['Jaringan Komputer', 2],
        'SI301' => ['Manajemen Proyek SI', 3],
        'SI302' => ['Interaksi Manusia dan Komputer', 3],
        'SI401' => ['Keamanan Sistem Informasi', 4],
        'SI501' => ['Tata Kelola TI', 5],
    ];

    public function up(): void
    {
        foreach (self::PETA as $old => [$code, $name, $semester, $prodi]) {
            if (DB::table('courses')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('courses')->where('code', $old)->update([
                'code' => $code, 'name' => $name, 'semester' => $semester, 'prodi' => $prodi, 'updated_at' => now(),
            ]);
        }

        // Audit SI semester 6, di luar katalog
        DB::table('courses')->where('code', 'SI402')->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (self::PETA as $old => [$code]) {
            [$name, $semester] = self::ASLI[$old];

            DB::table('courses')->where('code', $code)->update([
                'code' => $old, 'name' => $name, 'semester' => $semester, 'prodi' => 'SI', 'updated_at' => now(),
            ]);
        }

        DB::table('courses')->where('code', 'SI402')->update(['is_active' => true, 'updated_at' => now()]);
    }
};
