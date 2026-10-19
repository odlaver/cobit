<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Duel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q) => $q->whereAny(['name', 'email', 'npm'], 'ilike', '%'.$request->string('search').'%'))
            ->when(in_array($request->input('role'), User::ROLES, true), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->input('status') === 'online', fn ($q) => $q->online())
            ->orderByRaw("case role when 'admin' then 0 when 'asdos' then 1 else 2 end")
            ->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'busy' => Duel::busyPlayerIds()]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_MAHASISWA, User::ROLE_ASDOS])],
        ]);

        if ($user->isAdmin()) {
            return back()->with('error', 'Role admin tidak bisa diubah dari halaman ini.');
        }

        if ($data['role'] === $user->role) {
            return back();
        }

        if ($user->duels()->where('status', '!=', Duel::STATUS_FINISHED)->exists()) {
            return back()->with('error', "{$user->name} masih punya duel yang berjalan. Ubah role setelah duelnya selesai.");
        }

        // Asdos tidak ikut duel
        if ($data['role'] === User::ROLE_ASDOS) {
            Challenge::where('status', Challenge::STATUS_PENDING)
                ->where(fn ($q) => $q->where('challenger_id', $user->id)->orWhere('opponent_id', $user->id))
                ->update(['status' => Challenge::STATUS_CANCELLED]);
        }

        $user->forceFill(['role' => $data['role']])->save();

        return back()->with('success', "{$user->name} sekarang ".($data['role'] === User::ROLE_ASDOS ? 'asdos' : 'mahasiswa').'.');
    }
}
