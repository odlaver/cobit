<?php

namespace App\Jobs;

use App\Models\Duel;
use App\Models\DuelRoundQuestion;
use App\Models\Material;
use App\Models\Question;
use App\Services\DraftService;
use App\Services\QuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshDuelQuestions implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $duelId) {}

    public function handle(QuestionGenerator $generator): void
    {
        $duel = Duel::find($this->duelId);

        if (! $duel || ! $generator->enabled() || app()->runningUnitTests()) {
            return;
        }

        $used = Question::with('material')
            ->whereIn('id', DuelRoundQuestion::whereIn('duel_round_id', $duel->rounds()->select('id'))->select('question_id'))
            ->get();

        foreach ($used->groupBy('material.course_id') as $courseId => $questions) {
            try {
                $this->refreshCourse((int) $courseId, $questions, $generator);
            } catch (Throwable $e) {
                Log::warning("Generate soal matkul {$courseId} dari duel {$duel->id} gagal: {$e->getMessage()}");
            }
        }
    }

    // Soal baru masuk, soal terpakai pensiun
    private function refreshCourse(int $courseId, $questions, QuestionGenerator $generator): void
    {
        $mix = QuestionGenerator::MIX;
        $cursor = 0;
        $created = collect();

        foreach ($questions->groupBy('material_id') as $materialId => $group) {
            $levels = [];
            foreach ($group as $_) {
                $levels[] = $mix[$cursor++ % count($mix)];
            }
            $created = $created->merge($generator->generate(Material::findOrFail($materialId), $levels));
        }

        $active = Question::where('status', Question::STATUS_ACTIVE)
            ->whereHas('material', fn ($q) => $q->where('course_id', $courseId))
            ->count();

        // Pool matkul tetap minimal 10 soal aktif
        $retire = max(0, min($created->count(), $active - DraftService::MIN_QUESTIONS));

        Question::whereIn('id', $questions->where('status', Question::STATUS_ACTIVE)->pluck('id')->take($retire))
            ->update(['status' => Question::STATUS_INACTIVE]);

        Log::info("Matkul {$courseId}: {$created->count()} soal AI baru, {$retire} soal lama dinonaktifkan.");
    }
}
