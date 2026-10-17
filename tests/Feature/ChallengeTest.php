<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Duel;
use App\Models\DuelRound;
use App\Models\Material;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDuels;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use BuildsDuels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function pending(User $challenger, User $opponent): Challenge
    {
        return Challenge::create([
            'challenger_id' => $challenger->id,
            'opponent_id' => $opponent->id,
            'status' => Challenge::STATUS_PENDING,
        ]);
    }

    public function test_mahasiswa_cannot_challenge_themselves(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->post(route('challenges.store'), ['opponent_id' => $me->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('challenges', 0);
    }

    public function test_challenge_target_must_be_a_mahasiswa(): void
    {
        $me = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($me)
            ->post(route('challenges.store'), ['opponent_id' => $admin->id])
            ->assertSessionHasErrors('opponent_id');

        $this->assertDatabaseCount('challenges', 0);
    }

    public function test_duplicate_pending_challenge_between_the_same_pair_is_rejected(): void
    {
        $this->makeCourses();
        $me = User::factory()->create();
        $rival = User::factory()->create();
        $this->pending($me, $rival);

        $this->actingAs($me)
            ->post(route('challenges.store'), ['opponent_id' => $rival->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('challenges', 1);
    }

    public function test_accept_creates_duel_with_ten_course_pool_and_round_one(): void
    {
        $this->makeCourses(12);
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();
        $challenge = $this->pending($challenger, $opponent);

        $this->actingAs($opponent)
            ->post(route('challenges.accept', $challenge))
            ->assertRedirect();

        $duel = Duel::firstOrFail();

        $this->assertCount(10, $duel->pool);
        $this->assertEqualsCanonicalizing([$challenger->id, $opponent->id], [$duel->player1_id, $duel->player2_id]);
        $this->assertSame(Challenge::STATUS_ACCEPTED, $challenge->fresh()->status);
        $this->assertNotNull($challenge->fresh()->responded_at);
        $this->assertSame(Duel::STATUS_DRAFTING, $duel->status);
        $this->assertNotNull($duel->turn_deadline);

        $round = DuelRound::firstOrFail();
        $this->assertSame(1, (int) $round->round_number);
        $this->assertSame($duel->player1_id, (int) $round->first_ban_user_id);
    }

    public function test_pool_only_contains_courses_with_at_least_ten_active_questions(): void
    {
        $this->makeCourses(6);
        $thin = Course::factory()->create();
        $material = Material::factory()->create(['course_id' => $thin->id]);
        Question::factory()->count(3)->create(['material_id' => $material->id]);

        $challenger = User::factory()->create();
        $opponent = User::factory()->create();
        $challenge = $this->pending($challenger, $opponent);

        $this->actingAs($opponent)
            ->post(route('challenges.accept', $challenge))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('duels', 0);
        $this->assertSame(Challenge::STATUS_PENDING, $challenge->fresh()->status);
    }

    public function test_only_opponent_can_accept(): void
    {
        $this->makeCourses();
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();
        $stranger = User::factory()->create();
        $challenge = $this->pending($challenger, $opponent);

        $this->actingAs($stranger)
            ->post(route('challenges.accept', $challenge))
            ->assertForbidden();

        $this->actingAs($challenger)
            ->post(route('challenges.accept', $challenge))
            ->assertForbidden();

        $this->assertDatabaseCount('duels', 0);
        $this->assertSame(Challenge::STATUS_PENDING, $challenge->fresh()->status);
    }

    public function test_opponent_can_decline_and_challenger_can_cancel(): void
    {
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();

        $declined = $this->pending($challenger, $opponent);
        $this->actingAs($challenger)
            ->post(route('challenges.decline', $declined))
            ->assertForbidden();
        $this->actingAs($opponent)
            ->post(route('challenges.decline', $declined))
            ->assertRedirect();
        $this->assertSame(Challenge::STATUS_DECLINED, $declined->fresh()->status);

        $cancelled = $this->pending($challenger, $opponent);
        $this->actingAs($opponent)
            ->post(route('challenges.cancel', $cancelled))
            ->assertForbidden();
        $this->actingAs($challenger)
            ->post(route('challenges.cancel', $cancelled))
            ->assertRedirect();
        $this->assertSame(Challenge::STATUS_CANCELLED, $cancelled->fresh()->status);
    }

    public function test_cannot_respond_to_an_already_answered_challenge(): void
    {
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();
        $challenge = $this->pending($challenger, $opponent);
        $challenge->update(['status' => Challenge::STATUS_DECLINED, 'responded_at' => now()]);

        $this->actingAs($opponent)
            ->post(route('challenges.accept', $challenge))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('duels', 0);
    }

    public function test_player_with_an_active_duel_cannot_be_challenged_or_accept(): void
    {
        $this->makeCourses();
        $duel = $this->makeDuel();
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();

        $incoming = $this->pending($challenger, User::find($duel->player1_id));
        $this->actingAs(User::find($duel->player1_id))
            ->post(route('challenges.accept', $incoming))
            ->assertSessionHas('error');

        $this->actingAs($challenger)
            ->post(route('challenges.store'), ['opponent_id' => $duel->player1_id])
            ->assertSessionHas('error');

        $this->assertSame(1, Duel::count());
        $this->assertSame(Challenge::STATUS_PENDING, $incoming->fresh()->status);
    }

    public function test_second_accept_does_not_create_a_second_duel(): void
    {
        $this->makeCourses();
        $challenger = User::factory()->create();
        $opponent = User::factory()->create();
        $challenge = $this->pending($challenger, $opponent);

        $this->actingAs($opponent)->post(route('challenges.accept', $challenge));
        $this->actingAs($opponent)
            ->post(route('challenges.accept', $challenge))
            ->assertSessionHas('error');

        $this->assertSame(1, Duel::count());
    }

    public function test_challenge_pages_require_login(): void
    {
        $this->get(route('challenges.index'))->assertRedirect(route('login'));
        $this->get(route('challenges.opponents'))->assertRedirect(route('login'));
        $this->post(route('challenges.store'), ['opponent_id' => 1])->assertRedirect(route('login'));
    }

    public function test_admin_cannot_open_challenge_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('challenges.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('challenges.opponents'))->assertForbidden();
    }

    public function test_inbox_renders_pending_incoming_outgoing_and_history(): void
    {
        $me = User::factory()->create();
        $sender = User::factory()->create();
        $rival = User::factory()->create();

        Challenge::create([
            'challenger_id' => $sender->id,
            'opponent_id' => $me->id,
            'status' => Challenge::STATUS_PENDING,
        ]);

        Challenge::create([
            'challenger_id' => $me->id,
            'opponent_id' => $rival->id,
            'status' => Challenge::STATUS_PENDING,
        ]);

        Challenge::create([
            'challenger_id' => $me->id,
            'opponent_id' => $rival->id,
            'status' => Challenge::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        $this->actingAs($me)
            ->get(route('challenges.index'))
            ->assertOk()
            ->assertSee('Tantangan masuk')
            ->assertSee('Tantangan terkirim')
            ->assertSee('Riwayat tantangan')
            ->assertSee('Ditolak');
    }

    public function test_opponent_list_filters_by_name(): void
    {
        User::factory()->create(['name' => 'Siti Rahmawati']);
        User::factory()->create(['name' => 'Budi Santoso']);

        $me = User::factory()->create();

        $this->actingAs($me)
            ->get(route('challenges.opponents', ['q' => 'siti']))
            ->assertOk()
            ->assertSee('Siti Rahmawati')
            ->assertDontSee('Budi Santoso');
    }

    public function test_opponent_list_excludes_self_and_shows_busy_state(): void
    {
        $me = User::factory()->create();
        $duel = $this->makeDuel();

        $this->actingAs($me)
            ->get(route('challenges.opponents'))
            ->assertOk()
            ->assertDontSee('name="opponent_id" value="'.$me->id.'"')
            ->assertSee('Sedang berduel');
    }
}
