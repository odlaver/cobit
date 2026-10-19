<?php

namespace App\Http\Controllers;

use App\Models\Duel;
use App\Models\User;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __invoke(): View
    {
        $users = User::mahasiswa()
            ->orderByDesc('points')
            ->orderBy('name')
            ->paginate(20);

        return view('leaderboard', ['users' => $users, 'busy' => Duel::busyPlayerIds()]);
    }
}
