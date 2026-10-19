@php
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle . ' · ' . config('app.name') : config('app.name');
    $description = trim($__env->yieldContent('description')) ?: 'Duel kuis 1 lawan 1 antar mahasiswa. Draft matkul dengan ban dan pick, jawab 10 soal, kumpulkan poin, dan naik tier dari Noob sampai God.';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @auth
        <meta name="robots" content="noindex, nofollow">
    @endauth
    <meta name="theme-color" content="#141838">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="COBIT, duel kuis 1 lawan 1 antar mahasiswa">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="turbo-prefetch" content="false">
    <meta name="turbo-cache-control" content="no-cache">
    @stack('head')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-navy-900 antialiased"
    @unless (config('cobit.turbo')) data-turbo="false" @endunless
    @hasSection('game') data-game="true" @endif
    @auth
        data-role="{{ auth()->user()->role }}"
        data-user-id="{{ auth()->id() }}"
        data-presence-url="{{ route('presence') }}"
        @if (auth()->user()->isMahasiswa()) data-status-url="{{ route('challenges.status') }}" @endif
        @isset($lockedDuel) data-duel="{{ $lockedDuel->id }}" @endisset
    @endauth>
    @auth
        @php
            $menu = match (auth()->user()->role) {
                \App\Models\User::ROLE_ADMIN => [
                    ['admin.dashboard', 'Dashboard', 'admin.dashboard'],
                    ['admin.courses.index', 'Mata Kuliah', 'admin.courses.*|admin.materials.*|admin.questions.*'],
                    ['admin.users.index', 'Pengguna', 'admin.users.*'],
                ],
                \App\Models\User::ROLE_ASDOS => [
                    ['admin.courses.index', 'Mata Kuliah', 'admin.courses.*|admin.materials.*'],
                    ['admin.questions.index', 'Bank Soal', 'admin.questions.*'],
                ],
                default => [
                    ['dashboard', 'Dashboard', 'dashboard'],
                    ['challenges.index', 'Tantangan', 'challenges.*'],
                    ['duels.index', 'Riwayat', 'duels.*'],
                ],
            };
            $menu[] = ['leaderboard', 'Leaderboard', 'leaderboard'];
            $inbox = auth()->user()->isMahasiswa()
                ? \App\Models\Challenge::query()->pending()->where('opponent_id', auth()->id())->count()
                : 0;
        @endphp

        <nav class="bg-navy-900 text-white" aria-label="Menu utama">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 px-4 lg:h-16 lg:flex-nowrap lg:gap-x-8">
                <a href="{{ route('home') }}" class="flex min-h-14 items-center text-lg font-extrabold tracking-tight">
                    CO<span class="text-cyan-400">BIT</span>
                </a>

                {{-- Baris kedua penuh di HP --}}
                <div class="order-last -mx-4 grid w-[calc(100%+2rem)] grid-flow-col auto-cols-[minmax(0,1fr)] border-t border-white/10 text-sm font-semibold lg:order-none lg:mx-0 lg:flex lg:h-full lg:w-auto lg:gap-6 lg:border-0">
                    @isset($lockedDuel)
                        <a href="{{ route('duels.show', $lockedDuel) }}" aria-current="page"
                            class="relative flex min-h-11 items-center justify-center gap-2 px-4 text-white after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-cyan-400 lg:h-full lg:justify-start lg:px-0 lg:after:inset-x-0">
                            <span class="size-2 rounded-full bg-cyan-400" aria-hidden="true"></span>
                            Duel sedang berjalan, menu lain dikunci
                        </a>
                    @endisset
                    @foreach (isset($lockedDuel) ? [] : $menu as [$route, $text, $pattern])
                        @php $current = request()->routeIs(...explode('|', $pattern)); @endphp
                        <a href="{{ route($route) }}" @if ($current) aria-current="page" @endif
                            @class([
                                'relative flex min-h-11 items-center justify-center px-1 transition-colors max-[359px]:text-xs lg:h-full lg:px-0',
                                'after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-cyan-400 lg:after:inset-x-0',
                                'pr-3.5 lg:pr-0' => $route === 'challenges.index',
                                'text-white' => $current,
                                'text-navy-200 after:hidden hover:text-white' => ! $current,
                            ])>
                            {{ $text }}
                            @if ($route === 'challenges.index')
                                {{-- Jumlah tantangan masuk, diperbarui notifier --}}
                                <span data-inbox-badge @if ($inbox === 0) hidden @endif
                                    class="absolute top-1.5 right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-cyan-400 px-1 text-xs leading-none font-bold text-navy-950 tabular-nums lg:static lg:ml-1.5 lg:h-5 lg:min-w-5 lg:px-1.5"><span data-inbox-count>{{ $inbox }}</span><span class="sr-only"> tantangan masuk</span></span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-1">
                    @if (auth()->user()->isMahasiswa())
                        <button type="button" data-controller="sound" data-action="sound#toggle" aria-pressed="true" aria-label="Suara game" title="Suara game"
                            class="group flex size-11 items-center justify-center rounded-lg text-navy-200 transition-colors hover:bg-navy-800 hover:text-white">
                            <x-icon name="sound" class="size-5 group-aria-[pressed=false]:hidden" />
                            <x-icon name="mute" class="hidden size-5 group-aria-[pressed=false]:block" />
                        </button>
                    @endif
                    <a @isset($lockedDuel) aria-disabled="true" @else href="{{ route('profile.show') }}" @endisset
                        @if (request()->routeIs('profile.*')) aria-current="page" @endif
                        @class(['flex min-h-11 items-center gap-2 rounded-lg px-2 transition-colors', 'hover:bg-navy-800' => ! isset($lockedDuel)])>
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="hidden max-w-40 truncate text-sm font-semibold lg:inline">{{ auth()->user()->name }}</span>
                        @if (auth()->user()->isMahasiswa())
                            <x-tier-badge :points="auth()->user()->points" />
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Keluar" title="Keluar"
                            class="flex size-11 items-center justify-center rounded-lg text-navy-200 transition-colors hover:bg-red-500/15 hover:text-red-400 active:bg-red-500/25">
                            <x-icon name="logout" class="size-5" />
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto px-4 py-6 sm:py-8 @yield('width', 'max-w-6xl')">
        @yield('content')
    </main>

    {{-- Flash dan notifikasi realtime --}}
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col gap-2 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:inset-x-auto sm:right-0 sm:w-[26rem] sm:pr-6 sm:pb-6">
        <div class="flex flex-col gap-2">
            @if (session('success'))
                <x-toast role="status">{{ session('success') }}</x-toast>
            @endif
            @if (session('error'))
                <x-toast role="alert" tone="error">{{ session('error') }}</x-toast>
            @endif
            @error('throttle')
                <x-toast role="alert" tone="error">{{ $message }}</x-toast>
            @enderror
        </div>
        <div id="toast-root" data-turbo-permanent aria-live="polite" aria-label="Notifikasi" class="flex flex-col gap-2"></div>
    </div>
    <div id="sr-live" data-turbo-permanent aria-live="polite" class="sr-only"></div>
</body>
</html>
