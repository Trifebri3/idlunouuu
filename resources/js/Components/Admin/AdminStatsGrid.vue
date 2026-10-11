<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
        <!-- Total Akun -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-zinc-300 transition-all">
            <div class="flex items-center justify-between text-zinc-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Total Akun</span>
                <span class="w-2 h-2 rounded-full bg-zinc-300"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-zinc-950 tracking-tight">{{ stats.total_users }}</div>
        </div>

        <!-- Profil Terbit -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-blue-200 transition-all">
            <div class="flex items-center justify-between text-blue-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Profil Terbit</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-blue-600 tracking-tight">{{ stats.total_profiles }}</div>
        </div>

        <!-- Komponen Sosial -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-indigo-200 transition-all">
            <div class="flex items-center justify-between text-indigo-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Komponen</span>
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-indigo-600 tracking-tight">{{ stats.total_components }}</div>
        </div>

        <!-- Kartu NFC Terhubung -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-emerald-200 transition-all">
            <div class="flex items-center justify-between text-emerald-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Kartu Aktif</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">
                {{ stats.active_nfc }} <span class="text-xs sm:text-sm text-zinc-400 font-medium">/ {{ stats.total_nfc }}</span>
            </div>
        </div>

        <!-- Total Views -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-amber-200 transition-all">
            <div class="flex items-center justify-between text-amber-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Total Views</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight">{{ stats.total_views }}</div>
        </div>

        <!-- Total Klik -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs space-y-1 hover:border-rose-200 transition-all">
            <div class="flex items-center justify-between text-rose-400">
                <span class="text-[10px] sm:text-[11px] text-zinc-500 font-semibold uppercase tracking-wider">Total Klik</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight">{{ stats.total_clicks }}</div>
        </div>
    </div>
</template>
