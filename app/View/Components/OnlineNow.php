<?php

namespace App\View\Components;

use App\Models\Challenge;
use App\Models\Duel;
use App\Models\User;
use Illuminate\View\Component;
use Illuminate\View\View;

class OnlineNow extends Component
{
    private const LIMIT = 6;

    public function __construct(public User $user)
    {
    }

    public function render(): View
    {
        $online = User::mahasiswa()->online()->whereKeyNot($this->user->id)->latest('last_seen_at');

        $busy = Duel::busyPlayerIds();

        $pending = Challenge::query()->pending()->involving($this->user->id)->get(['challenger_id', 'opponent_id']);

        return view('components.online-now', [
            'players' => (clone $online)->limit(self::LIMIT)->get(),
            'total' => $online->count(),
            'busy' => $busy,
            'sent' => $pending->where('challenger_id', $this->user->id)->pluck('opponent_id')->map(fn ($id) => (int) $id)->all(),
            'received' => $pending->where('opponent_id', $this->user->id)->pluck('challenger_id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }
}
