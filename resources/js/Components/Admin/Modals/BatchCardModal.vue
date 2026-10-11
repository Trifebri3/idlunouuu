<script setup>
import { useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    count: 10,
    label: 'YOIMO Production Batch',
});

function submit() {
    form.post('/admin/nfc/batch', {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
        @click.self="emit('close')"
    >
        <div class="bg-white border border-zinc-200 rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl text-zinc-900 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3.5 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <ApplicationLogo className="w-5 h-5 flex-shrink-0" variant="1" />
                    <h3 class="text-base font-bold text-zinc-900">Cetak Tautan Kartu Massal (Batch)</h3>
                </div>
                <button
                    @click="emit('close')"
                    class="text-zinc-400 hover:text-zinc-800 text-2xl font-bold p-1 leading-none transition-colors"
                >
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto py-4 space-y-4 pr-1 text-xs">
                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Jumlah Kartu</label>
                    <input
                        type="number"
                        v-model="form.count"
                        min="1"
                        max="50"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        required
                    />
                    <span class="text-[11px] text-zinc-500 mt-1 block">
                        Maksimal 50 kartu per batch. Semua kartu akan berstatus pending dan siap di-burn ke chip RFID.
                    </span>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Label Seri / Batch</label>
                    <input
                        type="text"
                        v-model="form.label"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        placeholder="Contoh: YOIMO Batch Produksi Oktober 2026"
                    />
                </div>
            </div>

            <!-- Footer -->
            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-zinc-100 flex-shrink-0">
                <button
                    @click="emit('close')"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200 transition-colors text-center"
                >
                    Batal
                </button>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    type="button"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50 transition-all text-center"
                >
                    {{ form.processing ? 'Memproses...' : 'Cetak Tautan Massal' }}
                </button>
            </div>
        </div>
    </div>
</template>
