<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    show: Boolean,
    order: Object,
    unassignedCards: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    nfc_device_id: '',
    auto_generate: false,
});

watch(() => props.order, (newOrder) => {
    if (newOrder) {
        form.nfc_device_id = props.unassignedCards[0] ? String(props.unassignedCards[0].id) : '';
        form.auto_generate = props.unassignedCards.length === 0;
    }
}, { immediate: true });

function submit() {
    if (!props.order) return;
    form.post(`/admin/orders/${props.order.id}/connect`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
}
</script>

<template>
    <div
        v-if="show && order"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
        @click.self="emit('close')"
    >
        <div class="bg-white border border-zinc-200 rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl text-zinc-900 flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3.5 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <ApplicationLogo className="w-6 h-6 flex-shrink-0" variant="1" />
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Hubungkan Pesanan ke Kartu Fisik</h3>
                        <span class="text-xs font-mono text-blue-600 font-bold">{{ order.order_number }}</span>
                    </div>
                </div>
                <button
                    @click="emit('close')"
                    class="text-zinc-400 hover:text-zinc-800 text-2xl font-bold p-1 leading-none transition-colors"
                >
                    &times;
                </button>
            </div>

            <!-- Scrollable Modal Body -->
            <div class="overflow-y-auto py-4 space-y-4 pr-1">
                <!-- Order Detail Summary Card -->
                <div class="p-3.5 sm:p-4 rounded-2xl bg-zinc-50 border border-zinc-200 text-xs space-y-2">
                    <div v-if="order.custom_image" class="flex items-center gap-3 pb-2 border-b border-zinc-200/60">
                        <span class="text-zinc-500">Desain Gambar:</span>
                        <div class="w-12 h-12 rounded-xl bg-white border border-zinc-200 p-1 flex items-center justify-center shadow-xs flex-shrink-0">
                            <img :src="order.custom_image" alt="Desain Gantungan Kunci" class="w-full h-full object-contain" />
                        </div>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-zinc-500">Pemesan:</span>
                        <strong class="text-zinc-900 text-right truncate">{{ order.user?.name }} ({{ order.user?.email }})</strong>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-zinc-500">Nama Cetak:</span>
                        <strong class="text-zinc-900 text-right">{{ order.card_printed_name }}</strong>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-zinc-500">Varian:</span>
                        <span class="font-bold text-zinc-900 capitalize">{{ order.card_variant?.replace('_', ' ') }}</span>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-zinc-500">WhatsApp:</span>
                        <span class="font-mono font-bold text-emerald-600">{{ order.phone_or_whatsapp }}</span>
                    </div>
                </div>

                <!-- Connection Options -->
                <div class="space-y-3">
                    <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Metode Sambungan Kartu Fisik</label>
                    <div class="space-y-2">
                        <label
                            class="p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-all"
                            :class="!form.auto_generate ? 'border-zinc-950 bg-zinc-50/80 ring-1 ring-zinc-950/10' : 'border-zinc-200'"
                        >
                            <input
                                type="radio"
                                :value="false"
                                v-model="form.auto_generate"
                                class="mt-0.5 text-zinc-950 focus:ring-0"
                            />
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-zinc-900">Pilih dari Stok Kartu Fisik yang Tersedia</span>
                                <span class="block text-[11px] text-zinc-500 mt-0.5">
                                    Pilih nomor ID kartu fisik acak yang sudah dicetak/diprovisioning admin sebelumnya.
                                </span>
                            </div>
                        </label>

                        <label
                            class="p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-all"
                            :class="form.auto_generate ? 'border-zinc-950 bg-zinc-50/80 ring-1 ring-zinc-950/10' : 'border-zinc-200'"
                        >
                            <input
                                type="radio"
                                :value="true"
                                v-model="form.auto_generate"
                                class="mt-0.5 text-zinc-950 focus:ring-0"
                            />
                            <div class="min-w-0">
                                <span class="block text-xs font-bold text-zinc-900">Buat Tautan/Kartu Fisik Acak Baru Secara Otomatis</span>
                                <span class="block text-[11px] text-zinc-500 mt-0.5">
                                    Sistem akan membuat ID acak baru (contoh: YOIMO-XXXXXXX) dan langsung mengaktifkannya untuk pemesan.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Dropdown for selecting available stock card -->
                    <div v-if="!form.auto_generate" class="pt-1">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">
                            Pilih Kartu Fisik dari Stok ({{ unassignedCards.length }} Tersedia)
                        </label>
                        <select
                            v-model="form.nfc_device_id"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                        >
                            <option v-if="unassignedCards.length === 0" value="">
                                — Tidak ada stok kartu yang belum di-embed. Silakan pilih 'Buat Baru Secara Otomatis' —
                            </option>
                            <option v-for="c in unassignedCards" :key="c.id" :value="String(c.id)">
                                {{ c.public_id }} · {{ c.label || 'YOIMO Batch Card' }}
                            </option>
                        </select>
                        <div v-if="form.errors.nfc_device_id" class="text-xs text-rose-600 mt-1">
                            {{ form.errors.nfc_device_id }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Mobile thumb-friendly buttons) -->
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
                    :disabled="form.processing || (!form.auto_generate && !form.nfc_device_id)"
                    type="button"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold disabled:opacity-50 shadow-sm flex items-center justify-center gap-1.5 transition-all"
                >
                    <span>{{ form.processing ? 'Menyambungkan...' : 'Sambungkan & Selesaikan Order' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
