<?php

namespace App\Jobs;

use App\Models\Course;
use App\Services\QuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCourseQuestions implements ShouldQueue
{
    use Queueable;

    // Komposisi 10 soal awal
    public const MIX = ['mudah', 'mudah', 'mudah', 'mudah', 'sedang', 'sedang', 'sedang', 'sedang', 'sulit', 'sulit'];

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $courseId) {}

    public function handle(QuestionGenerator $generator): void
    {
        $course = Course::with('materials')->find($this->courseId);

        if (! $course || $course->materials->isEmpty() || ! $generator->enabled() || app()->runningUnitTests()) {
            return;
        }

        // Bagi rata ke semua materi
        $perMaterial = [];
        foreach (self::MIX as $i => $level) {
            $material = $course->materials[$i % $course->materials->count()];
            $perMaterial[$material->id]['material'] = $material;
            $perMaterial[$material->id]['levels'][] = $level;
        }

        $created = 0;
        foreach ($perMaterial as ['material' => $material, 'levels' => $levels]) {
            try {
                $created += $generator->generate($material, $levels)->count();
            } catch (Throwable $e) {
                Log::warning("Generate soal materi {$material->id} gagal: {$e->getMessage()}");
            }
        }

        Log::info("Matkul {$course->code}: {$created} soal AI dibuat dari tombol generate.");
    }
}
