<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    nfcDevices: {
        type: Array,
        default: () => [],
    },
    unassignedCards: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['open-create-modal', 'open-batch-modal', 'open-assign-modal']);

const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'active', 'pending'
const copiedCardId = ref(null);

const filteredCards = computed(() => {
    return props.nfcDevices.filter((card) => {
        if (statusFilter.value === 'active' && !card.user_id) return false;
        if (statusFilter.value === 'pending' && card.user_id) return false;

        if (!searchQuery.value.trim()) return true;

        const q = searchQuery.value.toLowerCase();
        const publicId = (card.public_id || '').toLowerCase();
        const label = (card.label || '').toLowerCase();
        const userName = (card.user?.name || '').toLowerCase();
        const username = (card.user?.profile?.username || '').toLowerCase();

        return publicId.includes(q) || label.includes(q) || userName.includes(q) || username.includes(q);
    });
});

function copyTapUrl(publicId) {
    const url = `${window.location.origin}/nfc/${publicId}`;
    navigator.clipboard.writeText(url);
    copiedCardId.value = publicId;
    setTimeout(() => {
        copiedCardId.value = null;
    }, 2000);
}

function deleteCard(card) {
    if (confirm(`Hapus kartu NFC ${card.public_id}?`)) {
        router.delete(`/admin/nfc/${card.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Action Banner -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                    <ApplicationLogo className="w-6 h-6 sm:w-7 sm:h-7" variant="1" />
                    <h2 class="text-base sm:text-lg font-black text-zinc-950 tracking-tight">Katalog Kartu RFID / NFC YOIMO</h2>
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">
                        Hardware Ready
                    </span>
                </div>
                <p class="text-xs text-zinc-500 max-w-xl leading-relaxed">
                    Buat tautan kartu fisik di awal untuk diprogram ke chip NFC. Kartu dapat langsung di-embed ke akun sekarang atau disimpan di stok.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
                <button
                    @click="emit('open-batch-modal')"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 text-xs font-bold flex items-center justify-center gap-1.5 shadow-2xs transition-all"
                >
                    <span>+ Cetak Massal (Batch)</span>
                </button>
                <button
                    @click="emit('open-create-modal')"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-2xs transition-all"
                >
                    <LunouIcon name="card" className="w-4 h-4 text-cyan-400" />
                    <span>+ Buat Kartu Baru</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
            <div class="relative flex-1">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Cari ID publik kartu, label, nama pemilik..."
                    class="w-full bg-zinc-50 border border-zinc-200 rounded-xl pl-9 pr-3 py-2 text-xs text-zinc-900 focus:bg-white focus:outline-none focus:border-zinc-900"
                />
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <button
                    v-for="filter in [
                        { key: 'all', label: 'Semua' },
                        { key: 'active', label: 'Aktif Ter-embed' },
                        { key: 'pending', label: 'Stok Ready' },
                    ]"
                    :key="filter.key"
                    type="button"
                    @click="statusFilter = filter.key"
                    class="px-2.5 py-1.5 rounded-lg font-bold text-[11px] whitespace-nowrap transition-colors"
                    :class="statusFilter === filter.key ? 'bg-zinc-950 text-white' : 'bg-zinc-100 hover:bg-zinc-200 text-zinc-600'"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>

        <!-- NFC Cards Container -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider text-zinc-700">
                    Katalog Seluruh Kartu ({{ filteredCards.length }})
                </h3>
                <span class="text-xs text-zinc-500 font-medium hidden sm:inline">
                    {{ unassignedCards.length }} kartu siap di-embed
                </span>
            </div>

            <!-- Empty State -->
            <div v-if="filteredCards.length === 0" class="p-8 sm:p-12 text-center text-zinc-400 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-zinc-100 flex items-center justify-center mx-auto mb-2 text-zinc-400">
                    <LunouIcon name="card" className="w-6 h-6" />
                </div>
                <p class="font-medium text-zinc-500">Tidak ada kartu yang cocok dengan pencarian.</p>
            </div>

            <div v-else>
                <!-- 1. MOBILE PHONE CARD VIEW (< 768px / HP) -->
                <div class="md:hidden divide-y divide-zinc-100">
                    <div
                        v-for="card in filteredCards"
                        :key="card.id"
                        class="p-4 space-y-3 hover:bg-zinc-50/50 transition-colors"
                    >
                        <!-- Header: Public ID + Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <LunouIcon name="card" className="w-4 h-4 text-blue-600 flex-shrink-0" />
                                <div>
                                    <div class="font-mono font-bold text-zinc-900 text-xs">{{ card.public_id }}</div>
                                    <div class="text-[10px] text-zinc-500 font-medium">{{ card.label || 'YOIMO Smart Card' }}</div>
                                </div>
                            </div>

                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                :class="card.user_id ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            >
                                {{ card.user_id ? 'Aktif' : 'Ready' }}
                            </span>
                        </div>

                        <!-- URL Chip & Copy Button -->
                        <div class="flex items-center justify-between p-2 rounded-xl bg-zinc-50 border border-zinc-100 text-xs">
                            <span class="font-mono text-[11px] text-blue-600 font-semibold truncate">
                                /nfc/{{ card.public_id }}
                            </span>
                            <button
                                @click="copyTapUrl(card.public_id)"
                                class="p-1 rounded bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100 flex items-center gap-1 text-[11px] font-medium"
                                title="Salin URL Lengkap"
                            >
                                <LunouIcon :name="copiedCardId === card.public_id ? 'check' : 'share'" className="w-3 h-3 text-blue-600" />
                                <span>{{ copiedCardId === card.public_id ? 'Tersalin' : 'Salin URL' }}</span>
                            </button>
                        </div>

                        <!-- Owner Account Info -->
                        <div class="text-xs flex items-center justify-between pt-1">
                            <span class="text-zinc-500 text-[11px]">Akun Pemilik:</span>
                            <div v-if="card.user" class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-zinc-200 flex items-center justify-center text-[9px] font-bold text-zinc-800">
                                    {{ card.user.name?.charAt(0) }}
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-zinc-900 text-xs block">{{ card.user.name }}</span>
                                    <span class="text-[10px] font-mono text-blue-600">@{{ card.user.profile?.username || 'user' }}</span>
                                </div>
                            </div>
                            <div v-else class="text-zinc-400 italic text-[11px]">
                                Belum dihubungkan ke akun
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 border-t border-zinc-100 flex items-center gap-2">
                            <button
                                @click="emit('open-assign-modal', card)"
                                type="button"
                                class="flex-1 py-2 px-3 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold text-center transition-colors"
                            >
                                {{ card.user_id ? 'Ganti Akun' : 'Embed ke Akun' }}
                            </button>

                            <a
                                :href="`/nfc/${card.public_id}`"
                                target="_blank"
                                class="p-2 rounded-xl border border-zinc-200 text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100"
                                title="Uji Tap"
                            >
                                <LunouIcon name="eye" className="w-4 h-4" />
                            </a>

                            <button
                                @click="deleteCard(card)"
                                type="button"
                                class="p-2 rounded-xl text-zinc-400 hover:text-rose-600 hover:bg-rose-50/50 transition-colors"
                                title="Hapus Kartu"
                            >
                                <LunouIcon name="trash" className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. TABLET & DESKTOP TABLE VIEW (>= 768px) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-zinc-500 uppercase tracking-wider border-b border-zinc-100 bg-zinc-50/70 text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Public ID Kartu</th>
                                <th class="py-3 px-4">Label / Batch</th>
                                <th class="py-3 px-4">URL Chip (Flashing NFC)</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Akun Ter-embed</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="card in filteredCards" :key="card.id" class="hover:bg-zinc-50/70 transition-colors">
                                <!-- Public ID -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-zinc-900 text-sm flex items-center gap-2">
                                        <LunouIcon name="card" className="w-4 h-4 text-blue-600" />
                                        <span>{{ card.public_id }}</span>
                                    </div>
                                </td>

                                <!-- Label -->
                                <td class="py-3.5 px-4">
                                    <span class="text-zinc-700 font-medium">{{ card.label || 'YOIMO Smart Card' }}</span>
                                </td>

                                <!-- URL Tap Chip -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 font-mono text-[11px] text-blue-600 font-semibold">
                                        <span>/nfc/{{ card.public_id }}</span>
                                        <button
                                            @click="copyTapUrl(card.public_id)"
                                            class="p-1 rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-600 hover:text-zinc-900"
                                            title="Salin URL lengkap untuk di-flash ke kartu fisik"
                                        >
                                            <LunouIcon :name="copiedCardId === card.public_id ? 'check' : 'share'" className="w-3 h-3 text-blue-600" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="card.user_id ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                    >
                                        {{ card.user_id ? 'Aktif (Ter-embed)' : 'Pending (Stok Ready)' }}
                                    </span>
                                </td>

                                <!-- Assigned Account -->
                                <td class="py-3.5 px-4">
                                    <div v-if="card.user" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-zinc-200 flex items-center justify-center text-[10px] font-bold text-zinc-800">
                                            {{ card.user.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-zinc-900">{{ card.user.name }}</div>
                                            <div class="text-[10px] font-mono text-blue-600">@{{ card.user.profile?.username || 'user' }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-zinc-400 italic text-[11px]">
                                        Belum dihubungkan ke akun
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="emit('open-assign-modal', card)"
                                            class="px-3 py-1.5 rounded-lg bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 text-zinc-800 text-xs font-semibold transition-all"
                                            title="Embed atau ubah akun pemilik kartu ini"
                                        >
                                            {{ card.user_id ? 'Ganti Akun' : 'Embed ke Akun' }}
                                        </button>

                                        <a
                                            :href="`/nfc/${card.public_id}`"
                                            target="_blank"
                                            class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100"
                                            title="Uji coba tap kartu fisik"
                                        >
                                            <LunouIcon name="eye" className="w-3.5 h-3.5" />
                                        </a>

                                        <button
                                            @click="deleteCard(card)"
                                            class="p-1.5 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-zinc-100"
                                            title="Hapus kartu"
                                        >
                                            <LunouIcon name="trash" className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
