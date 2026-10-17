<?php

namespace Tests\Concerns;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Duel;
use App\Models\Material;
use App\Models\Question;
use App\Models\User;
use App\Services\DraftService;
use Illuminate\Support\Collection;

trait BuildsDuels
{
    protected function makeCourses(int $count = 11, int $questions = 10): Collection
    {
        return Course::factory()->count($count)->create()
            ->map(function (Course $course) use ($questions) {
                $material = Material::factory()->create(['course_id' => $course->id]);
                Question::factory()->count($questions)->create([
                    'material_id' => $material->id,
                    'status' => Question::STATUS_ACTIVE,
                ]);

                return $course;
            })
            ->values();
    }

    protected function makeDuel(int $courses = 11): Duel
    {
        if (Course::count() < $courses) {
            $this->makeCourses($courses);
        }

        $challenger = User::factory()->create();
        $opponent = User::factory()->create();

        $challenge = Challenge::create([
            'challenger_id' => $challenger->id,
            'opponent_id' => $opponent->id,
            'status' => Challenge::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return $this->draftService()->createDuel($challenge);
    }

    protected function draftService(): DraftService
    {
        return app(DraftService::class);
    }

    protected function draft(Duel $duel, int $userId, ?int $courseId = null, bool $isAuto = false): void
    {
        $this->draftService()->act($duel, $userId, $courseId, $isAuto);
        $duel->refresh();
    }

    protected function availableIds(Duel $duel): array
    {
        $duel->refresh();

        return $this->draftService()->availableCourses($duel)->modelKeys();
    }

    protected function p1(Duel $duel): int
    {
        return (int) $duel->player1_id;
    }

    protected function p2(Duel $duel): int
    {
        return (int) $duel->player2_id;
    }

    protected function openRound(Duel $duel, int $roundNumber): void
    {
        $duel->update([
            'status' => Duel::STATUS_DRAFTING,
            'current_round' => $roundNumber,
            'draft_step' => 0,
            'turn_deadline' => now()->addSeconds(DraftService::SECONDS),
        ]);
        $duel->refresh();
    }
}
