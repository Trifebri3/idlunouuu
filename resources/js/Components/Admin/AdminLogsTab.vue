<script setup>
defineProps({
    auditLogs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Banner & Info -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xs flex items-center justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-zinc-950 tracking-tight">Log Aktivitas & Audit Keamanan</h2>
                </div>
                <p class="text-xs text-zinc-500 max-w-xl leading-relaxed">
                    Catatan seluruh tindakan penting administratif untuk kepatuhan, pemantauan status, dan audit integritas data.
                </p>
            </div>
            <span class="px-3 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold hidden sm:inline">
                {{ auditLogs.length }} Aktivitas Terakhir
            </span>
        </div>

        <!-- Logs Container -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xs space-y-3">
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                <h3 class="font-bold text-xs uppercase tracking-wider text-zinc-700">Linimasa Aktivitas Terkini</h3>
                <span class="text-[11px] text-zinc-400 font-mono">Real-time Platform Logs</span>
            </div>

            <div v-if="auditLogs.length === 0" class="p-8 sm:p-12 text-center text-zinc-400 text-xs">
                Belum ada catatan log aktivitas yang tersimpan.
            </div>

            <div v-else class="space-y-2.5 max-h-[600px] overflow-y-auto pr-1 text-xs">
                <div
                    v-for="log in auditLogs"
                    :key="log.id"
                    class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:border-zinc-300 transition-colors"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-purple-700 font-bold bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200 text-[11px]">
                                {{ log.action }}
                            </span>
                            <span class="text-[11px] text-zinc-500 font-medium hidden sm:inline">&bull;</span>
                            <span class="text-[11px] text-zinc-600 font-semibold">
                                Pelaku: <span class="text-zinc-900">{{ log.actor?.name || 'Sistem' }}</span>
                            </span>
                        </div>
                        <div class="text-[11px] text-zinc-500">
                            Target: <span class="font-semibold text-zinc-700">{{ log.target_type }}</span> #{{ log.target_id || '-' }}
                            <template v-if="log.metadata">
                                <span class="text-zinc-400 mx-1">|</span>
                                <span class="font-mono text-[10px] text-zinc-500">
                                    {{ typeof log.metadata === 'object' ? JSON.stringify(log.metadata) : log.metadata }}
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="text-[10px] font-mono text-zinc-400 sm:text-right flex-shrink-0">
                        {{ new Date(log.created_at).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
