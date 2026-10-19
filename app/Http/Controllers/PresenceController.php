<?php

namespace App\Http\Controllers;

use App\Models\Duel;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PresenceController extends Controller
{
    // Id pengguna yang online dan yang berduel
    public function __invoke(): JsonResponse
    {
        $online = User::query()->online()->pluck('id')->map(fn ($id) => (int) $id);

        return response()->json([
            'online' => $online->values(),
            'dueling' => $online->intersect(Duel::busyPlayerIds())->values(),
        ]);
    }
}
