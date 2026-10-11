<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    unassignedCards: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['open-offline-modal', 'open-connect-modal', 'switch-tab']);

const searchQuery = ref('');
const statusFilter = ref('all'); // 'all', 'pending', 'processing', 'completed', 'cancelled'
const copiedOrderNumber = ref(null);

const filteredOrders = computed(() => {
    return props.orders.filter((order) => {
        const matchesStatus = statusFilter.value === 'all' || order.status === statusFilter.value;
        if (!matchesStatus) return false;

        if (!searchQuery.value.trim()) return true;

        const q = searchQuery.value.toLowerCase();
        const orderNum = (order.order_number || '').toLowerCase();
        const userName = (order.user?.name || '').toLowerCase();
        const printedName = (order.card_printed_name || '').toLowerCase();
        const phone = (order.phone_or_whatsapp || '').toLowerCase();
        const username = (order.user?.profile?.username || '').toLowerCase();

        return orderNum.includes(q) || userName.includes(q) || printedName.includes(q) || phone.includes(q) || username.includes(q);
    });
});

function copyOrderNumber(num) {
    navigator.clipboard.writeText(num);
    copiedOrderNumber.value = num;
    setTimeout(() => {
        copiedOrderNumber.value = null;
    }, 2000);
}

function verifyPayment(order) {
    if (confirm(`Verifikasi pembayaran untuk pesanan ${order.order_number}?`)) {
        router.patch(`/admin/orders/${order.id}/verify-payment`, {}, {
            preserveScroll: true,
        });
    }
}

function updateOrderStatus(order, event) {
    const newStatus = event.target.value;
    router.patch(`/admin/orders/${order.id}/status`, {
        status: newStatus,
        payment_status: order.payment_status,
    }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Action Banner -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                    <ApplicationLogo className="w-6 h-6 sm:w-7 sm:h-7" variant="1" />
                    <h2 class="text-base sm:text-lg font-black text-zinc-950 tracking-tight">Pesanan Gantungan Kunci LUNOU</h2>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                        {{ orders.filter(o => o.status === 'pending').length }} Perlu Diproses
                    </span>
                </div>
                <p class="text-xs text-zinc-500 max-w-xl leading-relaxed">
                    Pengguna mengajukan pesanan gantungan kunci NFC custom. Admin dapat memverifikasi pembayaran lalu menyambungkan chip RFID fisik ke akun pengguna.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
                <button
                    @click="emit('open-offline-modal')"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-2xs"
                >
                    <LunouIcon name="plus" className="w-3.5 h-3.5 text-blue-400" />
                    <span>+ Buat Pesanan Offline / WA</span>
                </button>
                <button
                    @click="emit('switch-tab', 'nfc')"
                    type="button"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-bold transition-all text-center"
                >
                    Lihat Stok Kartu ({{ unassignedCards.length }} Tersedia)
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
            <!-- Search Input -->
            <div class="relative flex-1">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Cari no. order, pemesan, nama cetak, no. WA..."
                    class="w-full bg-zinc-50 border border-zinc-200 rounded-xl pl-9 pr-3 py-2 text-xs text-zinc-900 focus:bg-white focus:outline-none focus:border-zinc-900"
                />
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                <button
                    v-for="filter in [
                        { key: 'all', label: 'Semua' },
                        { key: 'pending', label: 'Menunggu' },
                        { key: 'processing', label: 'Diproses' },
                        { key: 'completed', label: 'Selesai' },
                        { key: 'cancelled', label: 'Batal' },
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

        <!-- Orders Container -->
        <div class="bg-white border border-zinc-200/90 rounded-2xl sm:rounded-3xl shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-bold text-xs uppercase tracking-wider text-zinc-700">
                    Daftar Pengajuan Pesanan ({{ filteredOrders.length }})
                </h3>
            </div>

            <!-- Empty State -->
            <div v-if="filteredOrders.length === 0" class="p-8 sm:p-12 text-center text-zinc-400 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-zinc-100 flex items-center justify-center mx-auto mb-2 text-zinc-400">
                    <LunouIcon name="keychain" className="w-6 h-6" />
                </div>
                <p class="font-medium text-zinc-500">Tidak ada pesanan yang sesuai filter.</p>
            </div>

            <div v-else>
                <!-- 1. MOBILE PHONE CARD VIEW (< 768px / HP) -->
                <div class="md:hidden divide-y divide-zinc-100">
                    <div
                        v-for="order in filteredOrders"
                        :key="order.id"
                        class="p-4 space-y-3 hover:bg-zinc-50/50 transition-colors"
                    >
                        <!-- Card Header: Order No, Date, Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-zinc-950 text-xs">{{ order.order_number }}</span>
                                    <button
                                        @click="copyOrderNumber(order.order_number)"
                                        class="p-1 rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100"
                                        title="Salin No. Order"
                                    >
                                        <LunouIcon :name="copiedOrderNumber === order.order_number ? 'check' : 'share'" className="w-3 h-3 text-blue-600" />
                                    </button>
                                </div>
                                <div class="text-[10px] text-zinc-400">
                                    {{ new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-col items-end gap-1">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': order.status === 'completed',
                                        'bg-blue-50 text-blue-700 border border-blue-200': order.status === 'processing',
                                        'bg-amber-50 text-amber-700 border border-amber-200': order.status === 'pending',
                                        'bg-rose-50 text-rose-700 border border-rose-200': order.status === 'cancelled'
                                    }"
                                >
                                    {{ order.status }}
                                </span>
                                <span class="text-[9px] text-zinc-500 uppercase font-mono">
                                    {{ order.payment_method }} ({{ order.payment_status }})
                                </span>
                            </div>
                        </div>

                        <!-- Card Detail: Mascot, Printed Name, Variant, Price -->
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-50/80 border border-zinc-100">
                            <div v-if="order.custom_image" class="w-12 h-12 rounded-xl bg-white border border-zinc-200 p-1 flex-shrink-0 flex items-center justify-center shadow-2xs">
                                <a :href="order.custom_image" target="_blank" title="Buka gambar">
                                    <img :src="order.custom_image" alt="Desain" class="w-full h-full object-contain" />
                                </a>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-zinc-950 text-xs truncate">{{ order.card_printed_name }}</div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-white border border-zinc-200 text-zinc-700">
                                        {{ order.card_variant ? order.card_variant.replace('_', ' ') : 'Gantungan Kunci' }}
                                    </span>
                                    <span class="text-[10px] font-mono text-zinc-600 font-bold">
                                        Rp {{ Number(order.amount).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Info & Contact -->
                        <div class="text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 text-[11px]">Pemesan:</span>
                                <div class="text-right">
                                    <span class="font-bold text-zinc-900">{{ order.user?.name || 'Customer' }}</span>
                                    <a
                                        v-if="order.user?.profile"
                                        :href="`/${order.user.profile.username}`"
                                        target="_blank"
                                        class="block text-[10px] text-blue-600 font-mono hover:underline"
                                    >
                                        @{{ order.user.profile.username }}
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 text-[11px]">WhatsApp:</span>
                                <a
                                    :href="`https://wa.me/${order.phone_or_whatsapp.replace(/[^0-9]/g, '')}`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 font-mono font-bold text-emerald-600 hover:underline"
                                >
                                    <LunouIcon name="whatsapp" className="w-3.5 h-3.5" />
                                    <span>{{ order.phone_or_whatsapp }}</span>
                                </a>
                            </div>

                            <div v-if="order.shipping_address" class="text-[10px] text-zinc-500 bg-zinc-50 p-2 rounded-lg">
                                {{ order.shipping_address }}
                            </div>
                        </div>

                        <!-- Physical NFC Link Status -->
                        <div class="pt-2 border-t border-zinc-100 flex items-center justify-between">
                            <span class="text-[11px] text-zinc-500">Kartu Fisik:</span>
                            <div v-if="order.device" class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono font-bold text-[11px]">
                                    #{{ order.device.public_id }}
                                </span>
                                <span class="text-[9px] text-emerald-600 font-bold">ACTIVE</span>
                            </div>
                            <div v-else class="text-xs text-amber-600 font-semibold flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                <span>Belum Terhubung</span>
                            </div>
                        </div>

                        <!-- Action Buttons (Mobile full-width buttons) -->
                        <div class="pt-2 flex flex-col sm:flex-row gap-2">
                            <button
                                v-if="order.payment_status === 'pending'"
                                @click="verifyPayment(order)"
                                type="button"
                                class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs flex items-center justify-center gap-1.5"
                            >
                                <LunouIcon name="check" className="w-3.5 h-3.5" />
                                <span>Verifikasi Bayar</span>
                            </button>

                            <button
                                v-if="!order.device"
                                @click="emit('open-connect-modal', order)"
                                type="button"
                                class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs flex items-center justify-center gap-1.5"
                            >
                                <LunouIcon name="keychain" className="w-3.5 h-3.5" />
                                <span>Hubungkan Kartu</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <button
                                    v-if="order.device"
                                    @click="emit('open-connect-modal', order)"
                                    type="button"
                                    class="flex-1 py-2 rounded-xl border border-zinc-200 hover:bg-zinc-100 text-zinc-700 text-xs font-semibold text-center"
                                >
                                    Ganti Kartu
                                </button>

                                <select
                                    :value="order.status"
                                    @change="updateOrderStatus(order, $event)"
                                    class="flex-1 py-2 px-2.5 bg-zinc-50 border border-zinc-200 rounded-xl text-xs font-semibold text-zinc-800 focus:outline-none"
                                >
                                    <option value="pending">Set: Pending</option>
                                    <option value="processing">Set: Diproses</option>
                                    <option value="completed">Set: Selesai</option>
                                    <option value="cancelled">Set: Dibatalkan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TABLET & DESKTOP TABLE VIEW (>= 768px) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 text-zinc-500 font-bold uppercase tracking-wider border-b border-zinc-200 text-[10px]">
                            <tr>
                                <th class="py-3 px-4">No. Order / Tanggal</th>
                                <th class="py-3 px-4">Pemesan</th>
                                <th class="py-3 px-4">Detail Kartu & Nama Cetak</th>
                                <th class="py-3 px-4">Kontak & Alamat</th>
                                <th class="py-3 px-4">Status & Bayar</th>
                                <th class="py-3 px-4">Kartu Fisik Terhubung</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-zinc-50/70 transition-colors">
                                <!-- Order Number -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-zinc-950 flex items-center gap-1.5">
                                        <span>{{ order.order_number }}</span>
                                        <button
                                            @click="copyOrderNumber(order.order_number)"
                                            class="p-1 rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100"
                                            title="Salin No. Order"
                                        >
                                            <LunouIcon :name="copiedOrderNumber === order.order_number ? 'check' : 'share'" className="w-3 h-3 text-blue-600" />
                                        </button>
                                    </div>
                                    <div class="text-[10px] text-zinc-400 mt-0.5">
                                        {{ new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-zinc-900">{{ order.user?.name || 'Customer' }}</div>
                                    <div class="text-[11px] text-zinc-500">{{ order.user?.email }}</div>
                                    <a
                                        v-if="order.user?.profile"
                                        :href="`/${order.user.profile.username}`"
                                        target="_blank"
                                        class="text-[10px] text-blue-600 font-mono hover:underline"
                                    >
                                        @{{ order.user.profile.username }} &rarr;
                                    </a>
                                </td>

                                <!-- Card Details -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div v-if="order.custom_image" class="w-12 h-12 rounded-xl bg-zinc-50 border border-zinc-200 p-1 flex-shrink-0 flex items-center justify-center shadow-inner">
                                            <a :href="order.custom_image" target="_blank" title="Klik untuk lihat gambar penuh">
                                                <img :src="order.custom_image" alt="Desain Gantungan Kunci" class="w-full h-full object-contain hover:scale-110 transition-transform" />
                                            </a>
                                        </div>
                                        <div>
                                            <div class="font-bold text-zinc-950 text-xs">{{ order.card_printed_name }}</div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-zinc-100 text-zinc-700">
                                                    {{ order.card_variant ? order.card_variant.replace('_', ' ') : 'Gantungan Kunci' }}
                                                </span>
                                                <span class="text-[10px] font-mono text-zinc-500">Rp {{ Number(order.amount).toLocaleString('id-ID') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact & Address -->
                                <td class="py-3.5 px-4">
                                    <a
                                        :href="`https://wa.me/${order.phone_or_whatsapp.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 font-mono font-bold text-emerald-600 hover:underline"
                                    >
                                        <LunouIcon name="whatsapp" className="w-3.5 h-3.5" />
                                        <span>{{ order.phone_or_whatsapp }}</span>
                                    </a>
                                    <div class="text-[10px] text-zinc-500 max-w-xs truncate mt-0.5" :title="order.shipping_address">
                                        {{ order.shipping_address }}
                                    </div>
                                </td>

                                <!-- Status & Payment -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase w-fit"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200': order.status === 'completed',
                                                'bg-blue-50 text-blue-700 border border-blue-200': order.status === 'processing',
                                                'bg-amber-50 text-amber-700 border border-amber-200': order.status === 'pending',
                                                'bg-rose-50 text-rose-700 border border-rose-200': order.status === 'cancelled'
                                            }"
                                        >
                                            {{ order.status }}
                                        </span>
                                        <span class="text-[10px] text-zinc-500 uppercase font-mono">
                                            {{ order.payment_method }} ({{ order.payment_status }})
                                        </span>
                                    </div>
                                </td>

                                <!-- Connected NFC -->
                                <td class="py-3.5 px-4">
                                    <div v-if="order.device" class="flex items-center gap-1.5">
                                        <span class="px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono font-bold text-xs">
                                            #{{ order.device.public_id }}
                                        </span>
                                        <span class="text-[10px] text-emerald-600 font-bold">ACTIVE</span>
                                    </div>
                                    <div v-else class="text-xs text-amber-600 font-semibold flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                        <span>Belum Terhubung</span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="order.payment_status === 'pending'"
                                            @click="verifyPayment(order)"
                                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition-all flex items-center gap-1"
                                            title="Verifikasi Bukti Transfer"
                                        >
                                            <LunouIcon name="check" className="w-3 h-3" />
                                            <span>Verifikasi Bayar</span>
                                        </button>
                                        <button
                                            v-if="!order.device"
                                            @click="emit('open-connect-modal', order)"
                                            class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition-all flex items-center gap-1.5"
                                        >
                                            <LunouIcon name="keychain" className="w-3.5 h-3.5 text-white" />
                                            <span>Hubungkan Kartu</span>
                                        </button>
                                        <button
                                            v-else
                                            @click="emit('open-connect-modal', order)"
                                            class="px-2.5 py-1.5 rounded-xl border border-zinc-200 hover:bg-zinc-100 text-zinc-600 text-xs font-medium"
                                            title="Ganti Kartu"
                                        >
                                            Ganti Kartu
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
