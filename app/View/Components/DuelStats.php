<?php

namespace App\View\Components;

use App\Models\Duel;
use App\Models\User;
use Illuminate\View\Component;
use Illuminate\View\View;

class DuelStats extends Component
{
    public function __construct(public User $user)
    {
    }

    public function render(): View
    {
        $finished = $this->user->duels()->where('status', Duel::STATUS_FINISHED)->get(['winner_id']);
        $wins = $finished->where('winner_id', $this->user->id)->count();
        $draws = $finished->whereNull('winner_id')->count();

        return view('components.duel-stats', [
            'total' => $finished->count(),
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $finished->count() - $wins - $draws,
            'active' => $this->user->duels()
                ->where('status', '!=', Duel::STATUS_FINISHED)
                ->with(['player1', 'player2'])
                ->latest()
                ->first(),
        ]);
    }
}
