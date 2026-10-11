<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-[#fafbfc] px-4 py-8 font-sans selection:bg-blue-600 selection:text-white relative overflow-hidden"
    >
        <!-- Subtle Background Brand Ornaments -->
        <ApplicationLogo
            className="fixed -top-20 -right-20 w-80 h-80 opacity-[0.03] pointer-events-none select-none blur-xs"
            variant="1"
        />
        <ApplicationLogo
            className="fixed -bottom-20 -left-20 w-80 h-80 opacity-[0.03] pointer-events-none select-none blur-xs"
            variant="2"
        />

        <!-- Brand Top Header -->
        <div class="text-center space-y-2 mb-6 relative z-10">
            <Link href="/" class="inline-flex items-center gap-2.5 group">
                <ApplicationLogo className="h-10 w-10 object-contain drop-shadow-xs group-hover:scale-105 transition-transform" variant="1" />
                <div class="flex flex-col text-left leading-none">
                    <span class="text-2xl font-black tracking-tight text-zinc-950">me.myoin.id</span>
                    <span class="text-[10px] font-semibold text-zinc-400 lowercase mt-0.5">part of lunou</span>
                </div>
            </Link>
            <div class="space-y-0.5">
                <p class="text-xs font-semibold text-zinc-600">Your world, your way.</p>
                <p class="text-[11px] text-zinc-400">Mulai dari dirimu. Selebihnya, kita buat sederhana.</p>
            </div>
        </div>

        <!-- Auth Card Container -->
        <div
            class="w-full max-w-md overflow-hidden bg-white p-7 sm:p-8 rounded-3xl border border-zinc-200/90 shadow-xl shadow-zinc-950/5 relative z-10 space-y-5"
        >
            <slot />
        </div>

        <!-- Footer Note -->
        <footer class="mt-8 text-center text-xs text-zinc-400 relative z-10 space-y-2">
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[11px] text-zinc-500 font-medium">
                <Link href="/" class="hover:text-zinc-900 transition-colors">Beranda</Link>
                <span>·</span>
                <Link href="/syarat-ketentuan" class="hover:text-zinc-900 transition-colors">Syarat & Ketentuan</Link>
                <span>·</span>
                <Link href="/syarat-ketentuan" class="hover:text-zinc-900 transition-colors">Kebijakan Privasi</Link>
                <span>·</span>
                <a
                    href="https://saweria.co/Try3"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-amber-600 hover:text-amber-700 font-semibold inline-flex items-center gap-1 transition-colors"
                >
                    <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span>Donasi Saweria</span>
                </a>
            </div>
            <div>&copy; {{ new Date().getFullYear() }} me.myoin.id · part of lunou. Hak Cipta Dilindungi.</div>
        </footer>
    </div>
</template>
