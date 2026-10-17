<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Duel;
use App\Models\DuelDraft;
use App\Models\DuelRound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDuels;
use Tests\TestCase;

class DraftTest extends TestCase
{
    use BuildsDuels, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_turn_order_in_round_one_is_p1_p2_p2_p1(): void
    {
        $duel = $this->makeDuel();
        $round = $duel->rounds()->firstOrFail();

        $this->assertSame(
            [$this->p1($duel), $this->p2($duel), $this->p2($duel), $this->p1($duel)],
            $this->draftService()->orderForRound($round)
        );
        $this->assertSame('ban', $this->draftService()->stepAction(0));
        $this->assertSame('ban', $this->draftService()->stepAction(1));
        $this->assertSame('pick', $this->draftService()->stepAction(2));
        $this->assertSame('pick', $this->draftService()->stepAction(3));
    }

    public function test_player_cannot_act_when_it_is_not_their_turn(): void
    {
        $duel = $this->makeDuel();
        $courseId = $this->availableIds($duel)[0];

        $this->actingAs(User::find($this->p2($duel)))
            ->post(route('duels.draft.store', $duel), ['course_id' => $courseId])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('duel_drafts', 0);
        $this->assertSame(0, (int) $duel->fresh()->draft_step);
    }

    public function test_out_of_turn_action_is_rejected_by_the_service(): void
    {
        $duel = $this->makeDuel();
        $courseId = $this->availableIds($duel)[0];

        $this->expectExceptionMessage('Bukan giliranmu untuk memilih.');
        $this->draftService()->act($duel, $this->p2($duel), $courseId);
    }

    public function test_picking_a_course_outside_the_pool_is_rejected(): void
    {
        $duel = $this->makeDuel();
        $outside = Course::whereNotIn('id', $duel->pool)->firstOrFail();

        $this->actingAs(User::find($this->p1($duel)))
            ->post(route('duels.draft.store', $duel), ['course_id' => $outside->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('duel_drafts', 0);
    }

    public function test_draft_room_renders_turn_and_pool(): void
    {
        $duel = $this->makeDuel();

        $this->actingAs(User::find($this->p1($duel)))
            ->get(route('duels.draft', $duel))
            ->assertOk()
            ->assertSee('Giliran kamu')
            ->assertSee('Pool matkul')
            ->assertSee('Ban matkul')
            ->assertSee('Riwayat draft');
    }

    public function test_non_participant_cannot_open_the_draft_room(): void
    {
        $duel = $this->makeDuel();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('duels.draft', $duel))->assertForbidden();
        $this->actingAs($stranger)->getJson(route('duels.state', $duel))->assertStatus(403);
        $this->actingAs($stranger)
            ->post(route('duels.draft.store', $duel), ['course_id' => $duel->pool[0]])
            ->assertForbidden();
    }

    public function test_state_endpoint_returns_the_draft_payload(): void
    {
        $duel = $this->makeDuel();

        $response = $this->actingAs(User::find($this->p1($duel)))
            ->getJson(route('duels.state', $duel));

        $response->assertOk()
            ->assertJsonPath('done', false)
            ->assertJsonPath('status', Duel::STATUS_DRAFTING)
            ->assertJsonPath('current_round', 1)
            ->assertJsonPath('step', 0)
            ->assertJsonPath('action', 'ban')
            ->assertJsonPath('turn_user_id', $this->p1($duel))
            ->assertJsonPath('is_my_turn', true);

        $this->assertCount(10, $response->json('pool'));
        $this->assertEmpty($response->json('history'));
        $this->assertTrue($response->json('seconds_left') > 0);
    }

    public function test_state_endpoint_auto_picks_when_the_turn_times_out(): void
    {
        $duel = $this->makeDuel();
        $duel->update(['turn_deadline' => now()->subSecond()]);

        $response = $this->actingAs(User::find($this->p1($duel)))
            ->getJson(route('duels.state', $duel));

        $response->assertOk()->assertJsonPath('step', 1);

        $this->assertDatabaseHas('duel_drafts', [
            'duel_id' => $duel->id,
            'round_number' => 1,
            'step' => 0,
            'user_id' => $this->p1($duel),
            'is_auto' => true,
        ]);
        $this->assertSame($this->p2($duel), (int) $response->json('turn_user_id'));
    }

    public function test_tick_auto_plays_expired_step_and_starts_a_fresh_deadline(): void
    {
        $duel = $this->makeDuel();
        $duel->update(['turn_deadline' => now()->subSecond()]);

        $this->draftService()->tick($duel);
        $duel->refresh();

        $this->assertSame(1, (int) $duel->draft_step);
        $this->assertSame(Duel::STATUS_DRAFTING, $duel->status);
        $this->assertTrue(now()->lessThan($duel->turn_deadline));
        $this->assertSame(1, DuelDraft::where('is_auto', true)->count());
    }

    public function test_completing_four_steps_starts_the_battle(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());

        foreach ($order as $userId) {
            $courseId = $this->availableIds($duel)[0];
            $this->draft($duel, (int) $userId, $courseId);
        }

        $this->assertSame(Duel::STATUS_BATTLE, $duel->fresh()->status);
        $this->assertNotNull($duel->fresh()->started_at);
        $this->assertNull($duel->fresh()->turn_deadline);
        $this->assertSame(4, DuelDraft::count());
        $this->assertSame(2, DuelDraft::where('action', 'ban')->count());
        $this->assertSame(2, DuelDraft::where('action', 'pick')->count());
    }

    public function test_every_step_is_recorded_once_so_double_submit_is_impossible(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());
        $courseId = $this->availableIds($duel)[0];

        $this->draft($duel, $order[0], $courseId);

        $this->expectExceptionMessage('Bukan giliranmu untuk memilih.');
        $this->draftService()->act($duel, $order[0], $courseId);
    }

    public function test_round_two_first_ban_belongs_to_player_two(): void
    {
        $duel = $this->makeDuel();
        $this->openRound($duel, 2);

        $round = $this->draftService()->ensureRound($duel);

        $this->assertSame($this->p2($duel), (int) $round->first_ban_user_id);
        $this->assertSame(
            [$this->p2($duel), $this->p1($duel), $this->p1($duel), $this->p2($duel)],
            $this->draftService()->orderForRound($round)
        );
    }

    public function test_round_three_first_ban_belongs_to_the_round_two_winner(): void
    {
        $duel = $this->makeDuel();

        DuelRound::create([
            'duel_id' => $duel->id,
            'round_number' => 2,
            'first_ban_user_id' => $this->p2($duel),
            'status' => DuelRound::STATUS_FINISHED,
            'winner_id' => $this->p2($duel),
        ]);

        $this->openRound($duel, 3);
        $round = $this->draftService()->ensureRound($duel);

        $this->assertSame($this->p2($duel), (int) $round->first_ban_user_id);
    }

    public function test_round_three_falls_back_to_player_one_when_round_two_was_a_draw(): void
    {
        $duel = $this->makeDuel();

        DuelRound::create([
            'duel_id' => $duel->id,
            'round_number' => 2,
            'first_ban_user_id' => $this->p2($duel),
            'status' => DuelRound::STATUS_FINISHED,
            'winner_id' => null,
        ]);

        $this->openRound($duel, 3);
        $round = $this->draftService()->ensureRound($duel);

        $this->assertSame($this->p1($duel), (int) $round->first_ban_user_id);
    }

    public function test_picked_courses_are_consumed_and_banned_courses_return_in_the_next_round(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());

        $pool = collect($duel->pool);
        $banA = (int) $pool[0];
        $banB = (int) $pool[1];
        $pickA = (int) $pool[2];
        $pickB = (int) $pool[3];

        $this->draft($duel, $order[0], $banA);
        $this->draft($duel, $order[1], $banB);
        $this->draft($duel, $order[2], $pickA);
        $this->draft($duel, $order[3], $pickB);

        $this->assertSame(4, DuelDraft::count());

        $this->openRound($duel, 2);
        $available = $this->availableIds($duel);

        $this->assertNotContains($pickA, $available);
        $this->assertNotContains($pickB, $available);
        $this->assertContains($banA, $available);
        $this->assertContains($banB, $available);
        $this->assertCount(8, $available);
    }

    public function test_a_course_banned_this_round_cannot_be_picked(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());

        $banned = (int) $this->availableIds($duel)[0];
        $this->draft($duel, $order[0], $banned);

        $this->expectExceptionMessage('sudah tidak tersedia');
        $this->draftService()->act($duel, $order[1], $banned);
    }

    public function test_banned_courses_are_available_again_in_the_same_duel(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());
        $banned = (int) $this->availableIds($duel)[0];

        $this->draft($duel, $order[0], $banned);
        $this->assertNotContains($banned, $this->availableIds($duel));
    }

    public function test_draft_state_marks_course_takens_and_bans(): void
    {
        $duel = $this->makeDuel();
        $order = $this->draftService()->orderForRound($duel->rounds()->firstOrFail());
        $banned = (int) $this->availableIds($duel)[0];
        $this->draft($duel, $order[0], $banned);

        $response = $this->actingAs(User::find($this->p2($duel)))
            ->getJson(route('duels.state', $duel));

        $response->assertOk()->assertJsonPath('step', 1)->assertJsonPath('is_my_turn', true);

        $this->assertContains($banned, array_column($response->json('history'), 'course_id'));
        $this->assertSame(['ban'], array_column($response->json('history'), 'action'));
        $this->assertEmpty($response->json('picks'));
        $this->assertSame([0, 0], $response->json('round_wins'));
    }

    public function test_guest_cannot_reach_draft_routes(): void
    {
        $duel = $this->makeDuel();

        $this->get(route('duels.draft', $duel))->assertRedirect(route('login'));
        $this->getJson(route('duels.state', $duel))->assertStatus(401);
    }

    public function test_admin_cannot_reach_draft_routes(): void
    {
        $duel = $this->makeDuel();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('duels.draft', $duel))->assertForbidden();
    }
}
