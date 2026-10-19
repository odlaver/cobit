<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, ?User $user = null): View
    {
        $user ??= $request->user();
        $rank = ! $user->isMahasiswa() ? null : User::mahasiswa()->where('points', '>', $user->points)->count() + 1;

        return view('profile.show', compact('user', 'rank'));
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', Password::min(8), 'confirmed'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        unset($data['current_password']);

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profil berhasil diperbarui.');
    }
}
