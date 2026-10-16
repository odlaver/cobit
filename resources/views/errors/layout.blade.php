@php($digits = str_split((string) $code))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#1a1a1a] px-4 font-sans antialiased">
    <main class="flex flex-col items-center gap-10 sm:flex-row sm:gap-12">
        <div aria-hidden="true" class="w-64 shrink-0 rounded-[1.5rem] border-[10px] border-neutral-700 px-4 pt-3 pb-8 sm:w-80">
            <div class="flex gap-2">
                <span class="size-3.5 rounded-full bg-neutral-700"></span>
                <span class="size-3.5 rounded-full bg-neutral-700"></span>
                <span class="size-3.5 rounded-full bg-neutral-700"></span>
            </div>
            <div class="mt-3 h-3 rounded-full bg-neutral-700"></div>
            <p class="mt-7 text-center text-6xl leading-none font-bold text-neutral-700 select-none sm:text-7xl">
                {{ '{' }}{{ $digits[0] }}<span class="inline-block translate-y-3">{{ $digits[1] }}</span>{{ $digits[2] }}{{ '}' }}
            </p>
        </div>

        <div class="max-w-xs text-center sm:text-left">
            <h1 class="text-2xl font-bold text-neutral-200">{{ $title }}</h1>
            <p class="mt-2 leading-relaxed text-neutral-400">{{ $message }}</p>
            <a href="{{ url('/') }}" class="mt-6 inline-block text-sm font-medium text-neutral-300 underline-offset-4 hover:text-white hover:underline">&larr; Kembali ke beranda</a>
        </div>
    </main>
</body>
</html>
