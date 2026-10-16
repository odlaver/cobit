@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    <x-card title="Edit Profil" class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <x-avatar :user="$user" size="lg" />
                <div class="flex-1">
                    <x-input name="avatar" label="Foto profil (JPG/PNG/WEBP, maks. 2 MB)" type="file" accept="image/jpeg,image/png,image/webp" />
                </div>
            </div>

            <x-input name="name" label="Nama lengkap" :value="old('name', $user->name)" required />
            <x-input name="email" label="Email" type="email" :value="old('email', $user->email)" required />

            <fieldset class="space-y-4 rounded-lg border border-navy-100 p-4">
                <legend class="px-1 text-sm font-semibold text-navy-700">Ganti kata sandi (opsional)</legend>
                <x-input name="current_password" label="Kata sandi saat ini" type="password" autocomplete="current-password" />
                <x-input name="password" label="Kata sandi baru" type="password" autocomplete="new-password" />
                <x-input name="password_confirmation" label="Ulangi kata sandi baru" type="password" autocomplete="new-password" />
            </fieldset>

            <div class="flex gap-3">
                <x-button>Simpan</x-button>
                <x-button variant="secondary" :href="route('profile.show')">Batal</x-button>
            </div>
        </form>
    </x-card>
@endsection
