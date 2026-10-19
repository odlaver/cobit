<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Duel;
use App\Models\User;
use App\Services\DraftService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChallengeController extends Controller
{
    private const TOAST_WINDOW_MINUTES = 2;
    private const TOAST_FRESH_SECONDS = 20;

    public function __construct(private DraftService $draft) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        Challenge::expireStale($user->id);

        $received = Challenge::query()
            ->where('opponent_id', $user->id)
            ->with(['challenger:id,name,npm,email,points,avatar,role,last_seen_at', 'duel:id,challenge_id'])
            ->latest()
            ->get();

        $sent = Challenge::query()
            ->where('challenger_id', $user->id)
            ->with(['opponent:id,name,npm,email,points,avatar,role,last_seen_at', 'duel:id,challenge_id'])
            ->latest()
            ->get();

        return view('challenges.index', [
            'user' => $user,
            'inboxPending' => $received->where('status', Challenge::STATUS_PENDING)->values(),
            'inboxDone' => $received->where('status', '!=', Challenge::STATUS_PENDING)->values(),
            'sentPending' => $sent->where('status', Challenge::STATUS_PENDING)->values(),
            'sentDone' => $sent->where('status', '!=', Challenge::STATUS_PENDING)->values(),
            'roomCourses' => Challenge::roomCourses($received->concat($sent)),
            'busy' => $this->busyUserIds(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        Challenge::expireStale($user->id);

        $duel = $user->duels()->active()->latest('id')->first();

        $incoming = Challenge::query()
            ->pending()
            ->where('opponent_id', $user->id)
            ->with('challenger')
            ->latest()
            ->get();

        // Kabar tantangan yang baru selesai
        $resolved = Challenge::query()
            ->where('responded_at', '>=', now()->subMinutes(self::TOAST_WINDOW_MINUTES))
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('challenger_id', $user->id)
                    ->whereIn('status', [Challenge::STATUS_ACCEPTED, Challenge::STATUS_DECLINED, Challenge::STATUS_EXPIRED]))
                ->orWhere(fn ($inner) => $inner->where('opponent_id', $user->id)->where('status', Challenge::STATUS_CANCELLED)))
            ->with(['challenger', 'opponent'])
            ->latest('responded_at')
            ->get();

        $courses = Challenge::roomCourses($incoming);
        $busy = $this->busyUserIds();

        $toasts = $incoming->map(fn (Challenge $challenge) => $this->toast('incoming', $challenge, $courses, $busy))
            ->concat($resolved->map(fn (Challenge $challenge) => $this->toast($challenge->status, $challenge)));

        return response()->json([
            'duel' => $duel?->id,
            'redirect' => $duel ? route('duels.show', $duel) : null,
            'inbox' => $incoming->count(),
            'sent' => Challenge::query()->pending()->where('challenger_id', $user->id)->count(),
            'toasts' => $toasts->values(),
        ]);
    }

    public function opponents(Request $request): View
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $onlineOnly = $request->boolean('online');

        $query = User::mahasiswa()->whereKeyNot($user->id);

        if ($q !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $operator, '%'.$q.'%');
        }

        if ($onlineOnly) {
            $query->online();
        }

        // Yang online tampil duluan
        $query->orderByRaw('CASE WHEN last_seen_at >= ? THEN 0 ELSE 1 END', [now()->subMinutes(config('cobit.online_minutes'))])
            ->orderBy('name');

        return view('challenges.opponents', [
            'opponents' => $query->paginate(12)->withQueryString(),
            'q' => $q,
            'onlineOnly' => $onlineOnly,
            'onlineCount' => User::mahasiswa()->whereKeyNot($user->id)->online()->count(),
            'busy' => $this->busyUserIds(),
            'pending' => $this->pendingPartnerIds($user->id),
            'incoming' => Challenge::query()->pending()->where('opponent_id', $user->id)->pluck('challenger_id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function room(Request $request): View
    {
        $user = $request->user();
        $opponent = User::mahasiswa()->whereKeyNot($user->id)->findOrFail($request->integer('opponent'));

        // Isi awal dari duel sebelumnya
        $from = Duel::query()->with('challenge')->find($request->integer('from'));
        $previous = $from?->hasPlayer($user) ? $from->challenge : null;

        $courses = Course::query()->eligible()->orderBy('semester')->orderBy('name')->get(['id', 'code', 'name', 'semester']);
        $selected = old('course_ids', $previous?->course_ids ?? []);

        return view('challenges.room', [
            'opponent' => $opponent,
            'courses' => $courses,
            'canRandom' => $courses->count() >= DraftService::POOL_SIZE,
            'mode' => old('mode', $selected === [] && $courses->count() >= DraftService::POOL_SIZE ? 'acak' : 'pilih'),
            'selected' => array_map('intval', $selected),
            'rounds' => (int) old('rounds', $previous?->roundCount() ?? Duel::DEFAULT_ROUNDS),
            'busy' => in_array($opponent->id, $this->busyUserIds(), true),
            'pending' => in_array($opponent->id, $this->pendingPartnerIds($user->id), true),
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
            'mode' => ['nullable', Rule::in(['acak', 'pilih'])],
            'course_ids' => ['exclude_unless:mode,pilih', 'required', 'array', 'min:1', 'max:'.DraftService::POOL_SIZE],
            'course_ids.*' => ['integer', 'distinct'],
            'rounds' => ['nullable', 'integer', Rule::in(Challenge::ROUND_OPTIONS)],
        ], [
            'course_ids.required' => 'Pilih minimal 1 matkul untuk room ini.',
            'course_ids.min' => 'Pilih minimal 1 matkul untuk room ini.',
            'course_ids.max' => 'Room berisi maksimal '.DraftService::POOL_SIZE.' matkul.',
        ]);

        $opponentId = (int) $data['opponent_id'];
        $courseIds = isset($data['course_ids'])
            ? collect($data['course_ids'])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all()
            : null;

        if ($opponentId === (int) $user->id) {
            return back()->with('error', 'Kamu tidak bisa menantang diri sendiri.');
        }

        if ($courseIds && Course::query()->eligible()->whereIn('id', $courseIds)->count() !== count($courseIds)) {
            return back()->withInput()->with('error', 'Ada matkul yang belum siap duel. Pilih matkul yang punya minimal '.DraftService::MIN_QUESTIONS.' soal aktif.');
        }

        if ($this->isBusy((int) $user->id, $opponentId)) {
            return back()->with('error', 'Tantangan hanya bisa dikirim kalau kedua pemain tidak sedang berduel.');
        }

        Challenge::expireStale($user->id);

        if ($this->hasPendingPair($user->id, $opponentId)) {
            return back()->with('error', 'Sudah ada tantangan yang menunggu jawaban antara kamu dan lawanmu.');
        }

        Challenge::create([
            'challenger_id' => $user->id,
            'opponent_id' => $opponentId,
            'status' => Challenge::STATUS_PENDING,
            'course_ids' => $courseIds,
            'rounds' => (int) ($data['rounds'] ?? Duel::DEFAULT_ROUNDS),
        ]);

        $message = 'Tantangan terkirim. Menunggu jawaban lawanmu.';

        return $request->boolean('from_room')
            ? redirect()->route('challenges.index')->with('success', $message)
            : back()->with('success', $message);
    }

    public function accept(Request $request, Challenge $challenge): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int) $challenge->opponent_id === (int) $user->id, 403);

        Challenge::expireStale($user->id);

        try {
            $duel = DB::transaction(function () use ($challenge) {
                $players = [(int) $challenge->challenger_id, (int) $challenge->opponent_id];

                // Kunci pemain dulu, urut id
                User::query()->whereKey($players)->orderBy('id')->lockForUpdate()->get();
                $locked = Challenge::query()->whereKey($challenge->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== Challenge::STATUS_PENDING) {
                    throw new DomainException('Tantangan ini sudah tidak berlaku.');
                }

                if ($this->isBusy(...$players)) {
                    throw new DomainException('Salah satu pemain sedang berduel. Tantangan tidak bisa diterima sekarang.');
                }

                $locked->update([
                    'status' => Challenge::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                // Tantangan lain milik keduanya gugur
                Challenge::query()
                    ->pending()
                    ->whereKeyNot($locked->id)
                    ->involving(...$players)
                    ->update(['status' => Challenge::STATUS_EXPIRED, 'responded_at' => now()]);

                return $this->draft->createDuel($locked);
            });
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('duels.show', $duel)
            ->with('success', 'Tantangan diterima. Duel dimulai!');
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

    private function toast(string $kind, Challenge $challenge, ?Collection $courses = null, array $busy = []): array
    {
        $at = $kind === 'incoming' ? $challenge->created_at : $challenge->responded_at;

        return [
            'id' => $kind.'-'.$challenge->id,
            'kind' => $kind,
            'fresh' => $at?->gte(now()->subSeconds(self::TOAST_FRESH_SECONDS)) ?? false,
            'html' => view('challenges.partials.toast', [
                'kind' => $kind,
                'challenge' => $challenge,
                'courses' => $courses,
                'busy' => $busy,
            ])->render(),
        ];
    }

    private function isBusy(int ...$userIds): bool
    {
        return Duel::query()
            ->active()
            ->where(fn ($query) => $query->whereIn('player1_id', $userIds)->orWhereIn('player2_id', $userIds))
            ->exists();
    }

    private function busyUserIds(): array
    {
        return Duel::busyPlayerIds();
    }

    private function pendingPartnerIds(int $userId): array
    {
        return Challenge::query()
            ->pending()
            ->involving($userId)
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
            ->pending()
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('challenger_id', $a)->where('opponent_id', $b))
                ->orWhere(fn ($inner) => $inner->where('challenger_id', $b)->where('opponent_id', $a)))
            ->exists();
    }
}
