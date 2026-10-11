<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

defineProps({
    title: {
        type: String,
        default: 'Super Admin Console · LUNOU',
    },
    activeTab: {
        type: String,
        default: 'orders',
    },
});

const emit = defineEmits(['change-tab']);

const isMobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-purple-600 selection:text-white antialiased">
        <Head :title="title" />

        <!-- Top Header Navigation -->
        <header class="h-16 border-b border-zinc-200/80 bg-white/95 backdrop-blur-md sticky top-0 z-40 px-3 sm:px-6 flex items-center justify-between gap-3 shadow-2xs">
            <!-- Left: Brand Logo & Title -->
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <Link href="/" class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0 group">
                    <ApplicationLogo className="w-7 h-7 sm:w-8 sm:h-8 flex-shrink-0 transition-transform group-hover:scale-105" variant="1" />
                    <div class="flex flex-col text-left leading-none">
                        <span class="font-extrabold text-zinc-950 text-base sm:text-lg tracking-tight">me.myoin.id</span>
                        <span class="text-[9px] font-semibold text-purple-600 lowercase">part of lunou</span>
                    </div>
                </Link>

                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span class="px-2 sm:px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider whitespace-nowrap">
                        Super Admin
                    </span>
                    <span class="hidden md:inline-flex items-center gap-1 text-[11px] text-emerald-600 font-semibold bg-emerald-50 border border-emerald-200/70 px-2 py-0.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live
                    </span>
                </div>
            </div>

            <!-- Right: Desktop Navigation & Actions -->
            <div class="hidden sm:flex items-center gap-2.5 text-xs flex-shrink-0">
                <Link
                    href="/studio"
                    class="whitespace-nowrap px-3.5 py-2 rounded-xl bg-white hover:bg-zinc-50 text-zinc-800 border border-zinc-300 font-semibold shadow-2xs transition-all flex items-center gap-1.5 hover:border-zinc-400"
                >
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Studio Profil</span>
                </Link>

                <div class="h-4 w-[1px] bg-zinc-200 mx-1"></div>

                <div class="flex items-center gap-2 text-zinc-600">
                    <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-800 font-bold text-xs flex items-center justify-center border border-purple-200">
                        {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                    </div>
                    <span class="font-medium max-w-[120px] truncate hidden md:inline">{{ $page.props.auth?.user?.name }}</span>
                </div>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="whitespace-nowrap px-3 py-2 rounded-xl text-zinc-500 hover:text-rose-600 hover:bg-rose-50/50 transition-colors font-medium"
                >
                    Keluar
                </Link>
            </div>

            <!-- Mobile: Quick Hamburger & Studio Buttons -->
            <div class="flex sm:hidden items-center gap-1.5">
                <Link
                    href="/studio"
                    class="p-2 rounded-xl bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 text-xs font-semibold"
                    title="Buka Studio"
                >
                    <span class="text-[11px] font-bold">&larr; Studio</span>
                </Link>

                <button
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    class="p-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 transition-colors"
                    aria-label="Toggle Admin Menu"
                >
                    <svg v-if="!isMobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </header>

        <!-- Mobile Drawer Navigation (HP Multi-Device Support) -->
        <div
            v-if="isMobileMenuOpen"
            class="sm:hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex flex-col justify-end"
            @click.self="isMobileMenuOpen = false"
        >
            <div class="bg-white rounded-t-3xl border-t border-zinc-200 p-5 space-y-4 shadow-2xl max-h-[85vh] overflow-y-auto animate-in slide-in-from-bottom duration-200">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-purple-100 text-purple-800 font-extrabold text-sm flex items-center justify-center border border-purple-200">
                            {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                        </div>
                        <div>
                            <div class="font-bold text-zinc-950 text-sm">{{ $page.props.auth?.user?.name }}</div>
                            <div class="text-[11px] text-zinc-500">{{ $page.props.auth?.user?.email }}</div>
                        </div>
                    </div>
                    <button @click="isMobileMenuOpen = false" class="text-zinc-400 hover:text-zinc-800 text-2xl font-bold p-1">&times;</button>
                </div>

                <div class="space-y-1.5 text-xs font-semibold text-zinc-700">
                    <button
                        @click="emit('change-tab', 'orders'); isMobileMenuOpen = false;"
                        class="w-full text-left p-3 rounded-xl flex items-center justify-between"
                        :class="activeTab === 'orders' ? 'bg-zinc-900 text-white' : 'hover:bg-zinc-100'"
                    >
                        <span class="flex items-center gap-2">
                            <LunouIcon name="keychain" className="w-4 h-4 text-blue-400" />
                            Pesanan Gantungan Kunci
                        </span>
                        <span v-if="activeTab === 'orders'" class="text-[10px] text-blue-300 font-bold">&bull; Aktif</span>
                    </button>

                    <button
                        @click="emit('change-tab', 'portfolio'); isMobileMenuOpen = false;"
                        class="w-full text-left p-3 rounded-xl flex items-center justify-between"
                        :class="activeTab === 'portfolio' ? 'bg-zinc-900 text-white' : 'hover:bg-zinc-100'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Portfolio Management
                        </span>
                        <span v-if="activeTab === 'portfolio'" class="text-[10px] text-blue-300 font-bold">&bull; Aktif</span>
                    </button>

                    <button
                        @click="emit('change-tab', 'nfc'); isMobileMenuOpen = false;"
                        class="w-full text-left p-3 rounded-xl flex items-center justify-between"
                        :class="activeTab === 'nfc' ? 'bg-zinc-900 text-white' : 'hover:bg-zinc-100'"
                    >
                        <span class="flex items-center gap-2">
                            <LunouIcon name="nfc" className="w-4 h-4" />
                            Katalog NFC / RFID
                        </span>
                        <span v-if="activeTab === 'nfc'" class="text-[10px] text-blue-300 font-bold">&bull; Aktif</span>
                    </button>

                    <button
                        @click="emit('change-tab', 'users'); isMobileMenuOpen = false;"
                        class="w-full text-left p-3 rounded-xl flex items-center justify-between"
                        :class="activeTab === 'users' ? 'bg-zinc-900 text-white' : 'hover:bg-zinc-100'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Pengguna & Moderasi
                        </span>
                        <span v-if="activeTab === 'users'" class="text-[10px] text-blue-300 font-bold">&bull; Aktif</span>
                    </button>

                    <button
                        @click="emit('change-tab', 'logs'); isMobileMenuOpen = false;"
                        class="w-full text-left p-3 rounded-xl flex items-center justify-between"
                        :class="activeTab === 'logs' ? 'bg-zinc-900 text-white' : 'hover:bg-zinc-100'"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Audit Log
                        </span>
                        <span v-if="activeTab === 'logs'" class="text-[10px] text-blue-300 font-bold">&bull; Aktif</span>
                    </button>
                </div>

                <div class="pt-3 border-t border-zinc-100 flex flex-col gap-2">
                    <Link
                        href="/studio"
                        class="w-full text-center py-2.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-900 font-bold text-xs"
                    >
                        &larr; Buka Studio Profil
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full text-center py-2.5 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-100 font-bold text-xs"
                    >
                        Keluar dari Akun
                    </Link>
                </div>
            </div>
        </div>

        <!-- Flash Message Banners -->
        <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="max-w-7xl w-full mx-auto px-3 sm:px-6 pt-4">
            <div
                v-if="$page.props.flash?.success"
                class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-medium flex items-center justify-between gap-3 shadow-2xs"
            >
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>
            <div
                v-if="$page.props.flash?.error"
                class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm font-medium flex items-center justify-between gap-3 shadow-2xs mt-2"
            >
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
            </div>
        </div>

        <!-- Main Workspace Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-3 sm:px-6 py-4 sm:py-6 space-y-5 sm:space-y-6">
            <slot />
        </main>
    </div>
</template>
