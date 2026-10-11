<script setup>
import { useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    card_printed_name: '',
    phone_or_whatsapp: '',
    shipping_address: '',
    custom_image: '/icon/1.png',
    notes: 'Pesanan dibuat oleh Admin (Offline / WhatsApp)',
});

function submit() {
    form.post('/admin/orders/create-offline', {
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
                <div class="flex items-center gap-2.5">
                    <ApplicationLogo className="w-6 h-6 flex-shrink-0" variant="1" />
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Buat Pesanan Offline / WhatsApp</h3>
                        <span class="text-xs text-zinc-500">Hasil kode order dapat diberikan ke pembeli untuk di-redeem</span>
                    </div>
                </div>
                <button
                    @click="emit('close')"
                    class="text-zinc-400 hover:text-zinc-800 text-2xl font-bold p-1 leading-none transition-colors"
                >
                    &times;
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="overflow-y-auto py-4 space-y-3.5 pr-1 text-xs">
                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Nama Pemesan / Dicetak</label>
                    <input
                        type="text"
                        v-model="form.card_printed_name"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        placeholder="Contoh: Tri Febriansah"
                        required
                    />
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Nomor WhatsApp Pelanggan</label>
                    <input
                        type="text"
                        v-model="form.phone_or_whatsapp"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                        placeholder="Contoh: 085862319524"
                        required
                    />
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Alamat Pengiriman (Opsional)</label>
                    <textarea
                        v-model="form.shipping_address"
                        rows="2"
                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        placeholder="Alamat lengkap pengiriman atau isi 'COD / Event'"
                    ></textarea>
                </div>

                <div>
                    <label class="block font-bold text-zinc-700 uppercase tracking-wider mb-1">Pilih Maskot Khas LUNOU</label>
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                        <button
                            v-for="num in [1, 2, 3, 5, 7, 10, 14, 18]"
                            :key="num"
                            type="button"
                            @click="form.custom_image = `/icon/${num}.png`"
                            class="w-10 h-10 rounded-xl p-1 border transition-all flex-shrink-0"
                            :class="form.custom_image === `/icon/${num}.png` ? 'border-zinc-950 bg-zinc-100 ring-2 ring-zinc-950/20' : 'border-zinc-200 bg-white hover:border-zinc-300'"
                        >
                            <img :src="`/icon/${num}.png`" alt="" class="w-full h-full object-contain" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer (Mobile responsive buttons) -->
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
                    :disabled="form.processing || !form.card_printed_name || !form.phone_or_whatsapp"
                    type="button"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50 shadow-sm transition-all text-center"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Terbitkan Kode Pesanan' }}
                </button>
            </div>
        </div>
    </div>
</template>
