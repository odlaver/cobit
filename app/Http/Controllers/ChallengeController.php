<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Duel;
use App\Models\User;
use App\Services\DraftService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChallengeController extends Controller
{
    public function __construct(private DraftService $draft) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $received = Challenge::query()
            ->where('opponent_id', $user->id)
            ->with('challenger:id,name,npm,email,points,avatar,role')
            ->latest()
            ->get();

        $sent = Challenge::query()
            ->where('challenger_id', $user->id)
            ->with('opponent:id,name,npm,email,points,avatar,role')
            ->latest()
            ->get();

        return view('challenges.index', [
            'user' => $user,
            'inboxPending' => $received->where('status', Challenge::STATUS_PENDING)->values(),
            'inboxDone' => $received->where('status', '!=', Challenge::STATUS_PENDING)->values(),
            'sentPending' => $sent->where('status', Challenge::STATUS_PENDING)->values(),
            'sentDone' => $sent->where('status', '!=', Challenge::STATUS_PENDING)->values(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $duel = $user->duels()->where('status', '!=', Duel::STATUS_FINISHED)->latest()->first();

        return response()->json([
            'duel' => $duel?->id,
            'redirect' => $duel ? route('duels.show', $duel) : null,
            'inbox' => Challenge::where('opponent_id', $user->id)->where('status', Challenge::STATUS_PENDING)->count(),
            'sent' => Challenge::where('challenger_id', $user->id)->where('status', Challenge::STATUS_PENDING)->count(),
        ]);
    }

    public function opponents(Request $request): View
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $query = User::mahasiswa()->where('id', '!=', $user->id);

        if ($q !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $operator, '%'.$q.'%');
        }

        return view('challenges.opponents', [
            'opponents' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'q' => $q,
            'busy' => $this->busyUserIds(),
            'pending' => $this->pendingPartnerIds($user->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'opponent_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_MAHASISWA)),
            ],
        ]);

        $opponentId = (int) $data['opponent_id'];

        if ($opponentId === (int) $user->id) {
            return back()->with('error', 'Kamu tidak bisa menantang diri sendiri.');
        }

        $busy = $this->busyUserIds();

        if (in_array((int) $user->id, $busy, true) || in_array($opponentId, $busy, true)) {
            return back()->with('error', 'Tantangan hanya bisa dikirim kalau kedua pemain tidak sedang berduel.');
        }

        if ($this->hasPendingPair($user->id, $opponentId)) {
            return back()->with('error', 'Sudah ada tantangan yang menunggu jawaban antara kamu dan lawanmu.');
        }

        Challenge::create([
            'challenger_id' => $user->id,
            'opponent_id' => $opponentId,
            'status' => Challenge::STATUS_PENDING,
        ]);

        return back()->with('success', 'Tantangan terkirim. Menunggu jawaban lawanmu.');
    }

    public function accept(Request $request, Challenge $challenge): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int) $challenge->opponent_id === (int) $user->id, 403);

        if ($challenge->status !== Challenge::STATUS_PENDING) {
            return back()->with('error', 'Tantangan ini sudah tidak berlaku.');
        }

        $busy = $this->busyUserIds();

        if (in_array((int) $challenge->challenger_id, $busy, true) || in_array((int) $challenge->opponent_id, $busy, true)) {
            return back()->with('error', 'Salah satu pemain sedang berduel. Tantangan tidak bisa diterima sekarang.');
        }

        try {
            $duel = DB::transaction(function () use ($challenge) {
                $locked = Challenge::query()->whereKey($challenge->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== Challenge::STATUS_PENDING) {
                    throw new DomainException('Tantangan ini sudah tidak berlaku.');
                }

                $locked->update([
                    'status' => Challenge::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                return $this->draft->createDuel($locked);
            });
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('duels.draft', $duel)
            ->with('success', 'Tantangan diterima. Duel dimulai, silakan draft matkul!');
    }

    public function decline(Request $request, Challenge $challenge): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int) $challenge->opponent_id === (int) $user->id, 403);

        if ($challenge->status !== Challenge::STATUS_PENDING) {
            return back()->with('error', 'Tantangan ini sudah tidak berlaku.');
        }

        $challenge->update([
            'status' => Challenge::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Tantangan ditolak.');
    }

    public function cancel(Request $request, Challenge $challenge): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int) $challenge->challenger_id === (int) $user->id, 403);

        if ($challenge->status !== Challenge::STATUS_PENDING) {
            return back()->with('error', 'Tantangan ini sudah tidak berlaku.');
        }

        $challenge->update([
            'status' => Challenge::STATUS_CANCELLED,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Tantangan dibatalkan.');
    }

    private function busyUserIds(): array
    {
        return Duel::query()
            ->where('status', '!=', Duel::STATUS_FINISHED)
            ->get(['player1_id', 'player2_id'])
            ->flatMap(fn (Duel $duel) => [$duel->player1_id, $duel->player2_id])
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function pendingPartnerIds(int $userId): array
    {
        return Challenge::query()
            ->where('status', Challenge::STATUS_PENDING)
            ->where(fn ($query) => $query->where('challenger_id', $userId)->orWhere('opponent_id', $userId))
            ->get(['challenger_id', 'opponent_id'])
            ->flatMap(fn (Challenge $challenge) => [$challenge->challenger_id, $challenge->opponent_id])
            ->unique()
            ->reject(fn ($id) => (int) $id === $userId)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function hasPendingPair(int $a, int $b): bool
    {
        return Challenge::query()
            ->where('status', Challenge::STATUS_PENDING)
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('challenger_id', $a)->where('opponent_id', $b))
                ->orWhere(fn ($inner) => $inner->where('challenger_id', $b)->where('opponent_id', $a)))
            ->exists();
    }
}
