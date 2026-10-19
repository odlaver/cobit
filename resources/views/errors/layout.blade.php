@php($digits = str_split((string) $code))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <meta name="turbo-visit-control" content="reload">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-navy-950 px-4 font-sans antialiased">
    <main class="flex flex-col items-center gap-10 sm:flex-row sm:gap-12">
        <div aria-hidden="true" class="w-64 shrink-0 rounded-[1.5rem] border-[10px] border-navy-800 px-4 pt-3 pb-8 sm:w-80">
            <div class="flex gap-2">
                <span class="size-3.5 rounded-full bg-navy-800"></span>
                <span class="size-3.5 rounded-full bg-navy-800"></span>
                <span class="size-3.5 rounded-full bg-navy-800"></span>
            </div>
            <div class="mt-3 h-3 rounded-full bg-navy-800"></div>
            <p class="mt-7 text-center text-6xl leading-none font-bold text-ungu-500/70 select-none sm:text-7xl">
                {{ '{' }}{{ $digits[0] }}<span class="inline-block translate-y-3">{{ $digits[1] }}</span>{{ $digits[2] }}{{ '}' }}
            </p>
        </div>

        <div class="max-w-xs text-center sm:text-left">
            <h1 class="text-2xl font-bold text-white">{{ $title }}</h1>
            <p class="mt-2 leading-relaxed text-navy-200">{{ $message }}</p>
            <a href="{{ url('/') }}" class="mt-6 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-cyan-400 underline-offset-4 hover:text-white hover:underline"><x-icon name="back" /> Kembali ke beranda</a>
        </div>
    </main>
</body>
</html>
