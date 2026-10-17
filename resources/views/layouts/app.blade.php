<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-navy-900 antialiased">
    @auth
        @php
            $menu = auth()->user()->isAdmin()
                ? [
                    ['admin.dashboard', 'Dashboard', 'admin.dashboard'],
                    ['admin.courses.index', 'Mata Kuliah', 'admin.courses.*|admin.materials.*|admin.questions.*'],
                ]
                : [
                    ['dashboard', 'Dashboard', 'dashboard'],
                    ['challenges.index', 'Tantangan', 'challenges.*'],
                    ['duels.index', 'Riwayat', 'duels.*'],
                ];
            $menu[] = ['leaderboard', 'Leaderboard', 'leaderboard'];
        @endphp

        <nav class="bg-navy-900 text-white" aria-label="Menu utama">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 px-4 sm:h-16 sm:flex-nowrap sm:gap-x-8">
                <a href="{{ route('home') }}" class="flex min-h-14 items-center text-lg font-extrabold tracking-tight">
                    CO<span class="text-cyan-400">BIT</span>
                </a>

                {{-- Baris kedua penuh di HP --}}
                <div class="order-last -mx-4 grid w-[calc(100%+2rem)] grid-flow-col auto-cols-[minmax(0,1fr)] border-t border-white/10 text-sm font-semibold sm:order-none sm:mx-0 sm:flex sm:h-full sm:w-auto sm:gap-6 sm:border-0">
                    @isset($lockedDuel)
                        <a href="{{ route('duels.show', $lockedDuel) }}" aria-current="page"
                            class="relative flex min-h-11 items-center justify-center gap-2 px-4 text-white after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-cyan-400 sm:h-full sm:justify-start sm:px-0 sm:after:inset-x-0">
                            <span class="size-2 rounded-full bg-cyan-400" aria-hidden="true"></span>
                            Duel sedang berjalan, menu lain dikunci
                        </a>
                    @endisset
                    @foreach (isset($lockedDuel) ? [] : $menu as [$route, $text, $pattern])
                        @php $current = request()->routeIs(...explode('|', $pattern)); @endphp
                        <a href="{{ route($route) }}" @if ($current) aria-current="page" @endif
                            @class([
                                'relative flex min-h-11 items-center justify-center px-1 transition-colors max-[359px]:text-xs sm:h-full sm:px-0',
                                'after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:rounded-full after:bg-cyan-400 sm:after:inset-x-0',
                                'text-white' => $current,
                                'text-navy-200 after:hidden hover:text-white' => ! $current,
                            ])>{{ $text }}</a>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-1">
                    <a @isset($lockedDuel) aria-disabled="true" @else href="{{ route('profile.show') }}" @endisset
                        @if (request()->routeIs('profile.*')) aria-current="page" @endif
                        @class(['flex min-h-11 items-center gap-2 rounded-lg px-2 transition-colors', 'hover:bg-navy-800' => ! isset($lockedDuel)])>
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="hidden max-w-40 truncate text-sm font-semibold md:inline">{{ auth()->user()->name }}</span>
                        @unless (auth()->user()->isAdmin())
                            <x-tier-badge :points="auth()->user()->points" />
                        @endunless
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
        @if (session('success'))
            <div role="status" class="mb-6 rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-medium text-cyan-900">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
        @endif
        @error('throttle')
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $message }}</div>
        @enderror

        @yield('content')
    </main>

    <script>
        function closeMenus(except) {
            document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
                if (m === except) return;
                m.classList.add('hidden');
                var btn = document.querySelector('[data-dropdown-toggle="' + m.id + '"]');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }

        document.addEventListener('click', function (e) {
            var toggleBtn = e.target.closest('[data-dropdown-toggle]');

            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                var targetMenu = document.getElementById(toggleBtn.getAttribute('data-dropdown-toggle'));
                closeMenus(targetMenu);

                if (targetMenu) {
                    var open = targetMenu.classList.toggle('hidden') === false;
                    toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                return;
            }

            if (!e.target.closest('[data-dropdown-menu]')) {
                closeMenus(null);
            }
        });

        // Escape menutup menu, fokus balik
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('[data-dropdown-menu]:not(.hidden)');
            if (!open) return;
            closeMenus(null);
            var btn = document.querySelector('[data-dropdown-toggle="' + open.id + '"]');
            if (btn) btn.focus();
        });
    </script>
</body>
</html>
