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
        <nav class="bg-navy-900 text-white shadow">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-wide">
                    CO<span class="text-cyan-400">BIT</span>
                </a>

                <div class="flex flex-wrap gap-4 text-sm font-medium text-navy-100">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-white">Dashboard Admin</a>
                        <a href="{{ route('admin.courses.index') }}" class="hover:text-white">Mata Kuliah</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="hover:text-white">Dashboard</a>
                    @endif
                    <a href="{{ route('leaderboard') }}" class="hover:text-white">Leaderboard</a>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <a href="{{ route('profile.show') }}" class="flex items-center gap-2 hover:opacity-90">
                        <x-avatar :user="auth()->user()" size="sm" />
                        <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                        @unless (auth()->user()->isAdmin())
                            <x-tier-badge :points="auth()->user()->points" />
                        @endunless
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md px-2 py-1 text-sm text-navy-100 hover:bg-navy-800 hover:text-white">Keluar</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @error('throttle')
            <div class="mx-auto mb-6 max-w-md rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        @yield('content')
    </main>

    <script>
        document.addEventListener('click', function (e) {
            var toggleBtn = e.target.closest('[data-dropdown-toggle]');
            var allMenus = document.querySelectorAll('[data-dropdown-menu]');

            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                var targetId = toggleBtn.getAttribute('data-dropdown-toggle');
                var targetMenu = document.getElementById(targetId);

                allMenus.forEach(function (m) {
                    if (m !== targetMenu) {
                        m.classList.add('hidden');
                    }
                });

                if (targetMenu) {
                    targetMenu.classList.toggle('hidden');
                }
                return;
            }

            if (!e.target.closest('[data-dropdown-menu]')) {
                allMenus.forEach(function (m) {
                    m.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
