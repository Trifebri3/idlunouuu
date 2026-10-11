<script setup>
import LunouIcon from '@/Components/LunouIcon.vue';

defineProps({
    currentTab: {
        type: String,
        required: true,
    },
    ordersCount: {
        type: Number,
        default: 0,
    },
    nfcCount: {
        type: Number,
        default: 0,
    },
    usersCount: {
        type: Number,
        default: 0,
    },
    pendingPortfolioOrdersCount: {
        type: Number,
        default: 0,
    },
});

defineEmits(['update:currentTab']);
</script>

<template>
    <div class="overflow-x-auto no-scrollbar scroll-smooth -mx-3 px-3 sm:mx-0 sm:px-0">
        <div class="flex items-center gap-1.5 p-1 bg-white border border-zinc-200/90 rounded-2xl w-max text-xs font-bold shadow-2xs">
            <!-- Orders Tab -->
            <button
                @click="$emit('update:currentTab', 'orders')"
                type="button"
                class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                :class="currentTab === 'orders' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
            >
                <LunouIcon name="keychain" className="w-4 h-4 text-blue-400 flex-shrink-0" />
                <span>Pesanan Gantungan Kunci</span>
                <span
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="currentTab === 'orders' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700'"
                >
                    {{ ordersCount }}
                </span>
            </button>

            <!-- Portfolio Management Tab -->
            <button
                @click="$emit('update:currentTab', 'portfolio')"
                type="button"
                class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                :class="currentTab === 'portfolio' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
            >
                <svg class="w-4 h-4 flex-shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Portfolio Management</span>
                <span
                    v-if="pendingPortfolioOrdersCount > 0"
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white animate-pulse"
                >
                    {{ pendingPortfolioOrdersCount }}
                </span>
            </button>

            <!-- NFC Tab -->
            <button
                @click="$emit('update:currentTab', 'nfc')"
                type="button"
                class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                :class="currentTab === 'nfc' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
            >
                <LunouIcon name="nfc" className="w-4 h-4 flex-shrink-0" />
                <span>Manajemen NFC / RFID</span>
                <span
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="currentTab === 'nfc' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-600'"
                >
                    {{ nfcCount }}
                </span>
            </button>

            <!-- Users Tab -->
            <button
                @click="$emit('update:currentTab', 'users')"
                type="button"
                class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                :class="currentTab === 'users' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
            >
                <svg class="w-4 h-4 flex-shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Pengguna & Moderasi</span>
                <span
                    class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="currentTab === 'users' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-600'"
                >
                    {{ usersCount }}
                </span>
            </button>

            <!-- Audit Logs Tab -->
            <button
                @click="$emit('update:currentTab', 'logs')"
                type="button"
                class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                :class="currentTab === 'logs' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
            >
                <svg class="w-4 h-4 flex-shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Audit Log</span>
            </button>
        </div>
    </div>
</template>
