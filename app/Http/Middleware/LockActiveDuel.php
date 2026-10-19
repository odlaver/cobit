<?php

namespace App\Http\Middleware;

use App\Models\Duel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LockActiveDuel
{
    // Rute yang tetap terbuka selama duel
    private const ALLOWED = [
        'logout',
        'challenges.status',
        'presence',
        'duels.show',
        'duels.result',
        'duels.draft',
        'duels.draft.store',
        'duels.state',
        'duels.battle',
        'duels.battle.answer',
        'duels.battle.state',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isMahasiswa()) {
            return $next($request);
        }

        $duel = $user->duels()->active()->latest('id')->first();

        if (! $duel) {
            return $next($request);
        }

        View::share('lockedDuel', $duel);

        $target = $request->route('duel');
        $sameDuel = ! $target || (int) ($target instanceof Duel ? $target->id : $target) === $duel->id;

        if ($request->routeIs(...self::ALLOWED) && $sameDuel) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Selesaikan duel yang sedang berjalan dulu.'], 409);
        }

        $page = $duel->status === Duel::STATUS_BATTLE ? 'duels.battle' : 'duels.draft';

        return redirect()->route($page, $duel)
            ->with('error', 'Duel masih berjalan. Selesaikan dulu sebelum membuka halaman lain.');
    }
}
