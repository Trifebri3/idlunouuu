<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    card: Object,
    allUsers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    user_id: '',
    label: '',
});

watch(() => props.card, (newCard) => {
    if (newCard) {
        form.user_id = newCard.user_id ? String(newCard.user_id) : '';
        form.label = newCard.label || '';
    }
}, { immediate: true });

function submit() {
    if (!props.card) return;
    form.post(`/admin/nfc/${props.card.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
}
</script>

<template>
    <div
        v-if="show && card"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
        @click.self="emit('close')"
    >
        <div class="bg-white border border-zinc-200 rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl text-zinc-900 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3.5 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">Embed Kartu ke Akun Pengguna</h3>
                    <span class="text-xs font-mono text-blue-600 font-bold">{{ card.public_id }}</span>
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
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Pilih Akun Pengguna</label>
                    <select
                        v-model="form.user_id"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                    >
                        <option value="">— Lepas Kartu (Set status kembali ke Pending / Unclaimed) —</option>
                        <option v-for="u in allUsers" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }}) [{{ u.profile ? '@' + u.profile.username : 'belum ada profil' }}]
                        </option>
                    </select>
                    <span class="text-[11px] text-zinc-500 mt-1 block">
                        Saat di-embed, setiap tap fisik pada kartu ini akan langsung diarahkan ke profil pengguna yang dipilih.
                    </span>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Label Kartu</label>
                    <input
                        type="text"
                        v-model="form.label"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        placeholder="Label kartu"
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
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan Embed' }}
                </button>
            </div>
        </div>
    </div>
</template>
