<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
});

const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'active', 'suspended'

const userList = computed(() => {
    return props.users.data || [];
});

const filteredUsers = computed(() => {
    return userList.value.filter((user) => {
        if (statusFilter.value !== 'all' && user.status !== statusFilter.value) {
            return false;
        }

        if (!searchQuery.value.trim()) return true;

        const q = searchQuery.value.toLowerCase();
        const name = (user.name || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const username = (user.profile?.username || '').toLowerCase();

        return name.includes(q) || email.includes(q) || username.includes(q);
    });
});

function toggleStatus(user) {
    if (confirm(`Ubah status akun ${user.name} (${user.email})?`)) {
        router.patch(`/admin/users/${user.id}/status`, {}, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Header Banner & Info -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-zinc-950 tracking-tight">Manajemen Akun & Moderasi</h2>
                </div>
                <p class="text-xs text-zinc-500 max-w-xl leading-relaxed">
                    Daftar seluruh akun pengguna yang terdaftar di platform LUNOU. Anda dapat memoderasi atau menangguhkan akun jika melanggar ketentuan.
                </p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
            <div class="relative flex-1">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Cari nama, email, username..."
                    class="w-full bg-zinc-50 border border-zinc-200 rounded-xl pl-9 pr-3 py-2 text-xs text-zinc-900 focus:bg-white focus:outline-none focus:border-zinc-900"
                />
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <button
                    v-for="filter in [
                        { key: 'all', label: 'Semua Akun' },
                        { key: 'active', label: 'Aktif' },
                        { key: 'suspended', label: 'Ditangguhkan' },
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

        <!-- Users Container -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider text-zinc-700">
                    Daftar Pengguna ({{ filteredUsers.length }})
                </h3>
            </div>

            <!-- Empty State -->
            <div v-if="filteredUsers.length === 0" class="p-8 sm:p-12 text-center text-zinc-400 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-zinc-100 flex items-center justify-center mx-auto mb-2 text-zinc-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <p class="font-medium text-zinc-500">Tidak ada pengguna yang cocok dengan pencarian.</p>
            </div>

            <div v-else>
                <!-- 1. MOBILE PHONE CARD VIEW (< 768px / HP) -->
                <div class="md:hidden divide-y divide-zinc-100">
                    <div
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="p-4 space-y-3 hover:bg-zinc-50/50 transition-colors"
                    >
                        <!-- Top: Avatar + Name + Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-full bg-zinc-100 text-zinc-800 font-bold text-xs flex items-center justify-center border border-zinc-200 flex-shrink-0">
                                    {{ user.name?.charAt(0) || 'U' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-zinc-900 text-xs truncate">{{ user.name }}</div>
                                    <div class="text-[11px] text-zinc-500 font-mono truncate">{{ user.email }}</div>
                                </div>
                            </div>

                            <span
                                class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                            >
                                {{ user.status }}
                            </span>
                        </div>

                        <!-- Info Pills: Profile Link, Role, Cards -->
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <template v-if="user.profile">
                                <a
                                    :href="`/${user.profile.username}`"
                                    target="_blank"
                                    class="text-blue-600 hover:underline font-mono text-[11px] font-semibold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-100"
                                >
                                    @{{ user.profile.username }} &rarr;
                                </a>
                            </template>
                            <span v-else class="text-[11px] text-zinc-400 bg-zinc-50 px-2 py-0.5 rounded-md">
                                Belum ada profil
                            </span>

                            <span
                                class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase"
                                :class="user.role === 'super_admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-zinc-100 text-zinc-600'"
                            >
                                {{ user.role }}
                            </span>

                            <span class="text-zinc-500 font-mono text-[11px] ml-auto">
                                {{ user.nfc_devices?.length || 0 }} kartu NFC
                            </span>
                        </div>

                        <!-- Action Button -->
                        <div v-if="user.id !== $page.props.auth?.user?.id" class="pt-2 border-t border-zinc-100">
                            <button
                                @click="toggleStatus(user)"
                                type="button"
                                class="w-full py-2 rounded-xl text-xs font-semibold border transition-all text-center"
                                :class="user.status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'"
                            >
                                {{ user.status === 'active' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. TABLET & DESKTOP TABLE VIEW (>= 768px) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-zinc-500 uppercase tracking-wider border-b border-zinc-100 bg-zinc-50/70 text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Pengguna</th>
                                <th class="py-3 px-4">Username / Profil</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Kartu NFC</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-zinc-50/70 transition-colors">
                                <!-- User -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-zinc-100 text-zinc-800 font-bold text-xs flex items-center justify-center border border-zinc-200 flex-shrink-0">
                                            {{ user.name?.charAt(0) || 'U' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-zinc-900 text-sm">{{ user.name }}</div>
                                            <div class="text-zinc-500 font-mono text-[11px]">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Profile -->
                                <td class="py-3.5 px-4">
                                    <template v-if="user.profile">
                                        <a :href="`/${user.profile.username}`" target="_blank" class="text-blue-600 hover:underline font-mono font-medium">
                                            @{{ user.profile.username }}
                                        </a>
                                    </template>
                                    <span v-else class="text-zinc-400">-</span>
                                </td>

                                <!-- Role -->
                                <td class="py-3.5 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="user.role === 'super_admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-zinc-100 text-zinc-600'"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>

                                <!-- NFC Count -->
                                <td class="py-3.5 px-4">
                                    <span class="text-zinc-500 font-mono">{{ user.nfc_devices?.length || 0 }} kartu</span>
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        v-if="user.id !== $page.props.auth?.user?.id"
                                        @click="toggleStatus(user)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all"
                                        :class="user.status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'"
                                    >
                                        {{ user.status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination if present -->
                <div v-if="users.links && users.links.length > 3" class="p-4 border-t border-zinc-100 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">
                        Menampilkan {{ users.from || 1 }} - {{ users.to || userList.length }} dari {{ users.total || userList.length }} pengguna
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in users.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1 rounded-lg font-medium transition-colors"
                            :class="link.active ? 'bg-zinc-950 text-white' : 'hover:bg-zinc-100 text-zinc-700'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
