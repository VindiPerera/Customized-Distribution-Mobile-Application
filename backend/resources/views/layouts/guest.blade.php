<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink">
        <div class="min-h-screen grid lg:grid-cols-2">

            <!-- Brand side -->
            <div class="hidden lg:flex flex-col justify-between p-12 bg-ink text-paper relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 28px 28px;"></div>

                <div class="relative flex items-center gap-2.5">
                    <svg class="w-8 h-8 text-accent" viewBox="0 0 24 24" fill="none">
                        <path d="M4 8L12 4L20 8V16L12 20L4 16V8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M4 8L12 12M12 12L20 8M12 12V20" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                    <span class="font-display text-lg font-semibold">{{ config('app.name') }}</span>
                </div>

                <div class="relative max-w-sm">
                    <p class="font-display text-[2rem] leading-[1.15] text-wrap-balance">
                        Every credit sale tracked. Every balance accounted for.
                    </p>
                    <p class="mt-4 text-sm text-paper/60 leading-relaxed">
                        Billing, stock, and customer accounts in one place — built for distributors who run on both cash and credit.
                    </p>
                </div>

                <p class="relative text-xs text-paper/40">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>

            <!-- Form side -->
            <div class="flex items-center justify-center p-6 sm:p-12">
                <div class="w-full max-w-sm">
                    <div class="lg:hidden flex items-center gap-2.5 mb-10">
                        <svg class="w-7 h-7 text-accent" viewBox="0 0 24 24" fill="none">
                            <path d="M4 8L12 4L20 8V16L12 20L4 16V8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                            <path d="M4 8L12 12M12 12L20 8M12 12V20" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                        <span class="font-display text-lg font-semibold text-ink">{{ config('app.name') }}</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
