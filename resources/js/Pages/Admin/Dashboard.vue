<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
    allUsers: {
        type: Array,
        default: () => [],
    },
    nfcDevices: {
        type: Array,
        default: () => [],
    },
    nfcOrders: {
        type: Array,
        default: () => [],
    },
    unassignedCards: {
        type: Array,
        default: () => [],
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
});

const currentTab = ref('orders'); // 'orders', 'nfc', 'users', 'logs'
const copiedCardId = ref(null);

// Modal states
const showCreateModal = ref(false);
const showBatchModal = ref(false);
const showAssignModal = ref(false);
const showConnectOrderModal = ref(false);
const showOfflineOrderModal = ref(false);
const activeEditingCard = ref(null);
const activeConnectingOrder = ref(null);

// Connect Order to NFC Form
const connectForm = useForm({
    nfc_device_id: '',
    auto_generate: false,
});

// Offline Order Form
const offlineOrderForm = useForm({
    card_printed_name: '',
    phone_or_whatsapp: '',
    shipping_address: '',
    custom_image: '/icon/1.png',
    notes: 'Pesanan dibuat oleh Admin (Offline / WhatsApp)',
});

function submitOfflineOrder() {
    offlineOrderForm.post('/admin/orders/create-offline', {
        preserveScroll: true,
        onSuccess: () => {
            showOfflineOrderModal.value = false;
            offlineOrderForm.reset();
        },
    });
}

function verifyPayment(order) {
    if (confirm(`Verifikasi pembayaran untuk pesanan ${order.order_number}?`)) {
        router.patch(`/admin/orders/${order.id}/verify-payment`, {}, {
            preserveScroll: true,
        });
    }
}

function openConnectModal(order) {
    activeConnectingOrder.value = order;
    connectForm.nfc_device_id = props.unassignedCards[0] ? String(props.unassignedCards[0].id) : '';
    connectForm.auto_generate = props.unassignedCards.length === 0;
    showConnectOrderModal.value = true;
}

function submitConnectOrder() {
    if (!activeConnectingOrder.value) return;
    connectForm.post(`/admin/orders/${activeConnectingOrder.value.id}/connect`, {
        preserveScroll: true,
        onSuccess: () => {
            showConnectOrderModal.value = false;
            activeConnectingOrder.value = null;
        },
    });
}

function updateOrderStatus(order, status, paymentStatus) {
    router.patch(`/admin/orders/${order.id}/status`, {
        status,
        payment_status: paymentStatus,
    }, {
        preserveScroll: true,
    });
}

// Single Create Form
const createCardForm = useForm({
    public_id: '',
    label: '',
    user_id: '',
});

function submitCreateCard() {
    createCardForm.post('/admin/nfc', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createCardForm.reset();
        },
    });
}

// Batch Create Form
const batchCardForm = useForm({
    count: 10,
    label: 'YOIMO Production Batch',
});

function submitBatchCards() {
    batchCardForm.post('/admin/nfc/batch', {
        preserveScroll: true,
        onSuccess: () => {
            showBatchModal.value = false;
            batchCardForm.reset();
        },
    });
}

// Assign Form
const assignForm = useForm({
    user_id: '',
    label: '',
});

function openAssignModal(card) {
    activeEditingCard.value = card;
    assignForm.user_id = card.user_id ? String(card.user_id) : '';
    assignForm.label = card.label || '';
    showAssignModal.value = true;
}

function submitAssignCard() {
    if (!activeEditingCard.value) return;
    assignForm.post(`/admin/nfc/${activeEditingCard.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
            activeEditingCard.value = null;
        },
    });
}

function deleteCard(card) {
    if (confirm(`Hapus kartu NFC ${card.public_id}?`)) {
        router.delete(`/admin/nfc/${card.id}`, {
            preserveScroll: true,
        });
    }
}

function copyTapUrl(publicId) {
    const url = `${window.location.origin}/nfc/${publicId}`;
    navigator.clipboard.writeText(url);
    copiedCardId.value = publicId;
    setTimeout(() => {
        copiedCardId.value = null;
    }, 2000);
}

function toggleStatus(user) {
    if (confirm(`Ubah status akun ${user.name} (${user.email})?`)) {
        router.patch(`/admin/users/${user.id}/status`, {}, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-purple-600 selection:text-white">
        <Head title="Super Admin · Manajemen Platform & RFID LUNOU" />

        <!-- Header (Clean White Style) -->
        <header class="h-16 border-b border-zinc-200/80 bg-white/90 backdrop-blur-md sticky top-0 z-40 px-4 sm:px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-shrink-0">
                <Link href="/" class="flex items-center gap-2.5 flex-shrink-0">
                    <ApplicationLogo className="w-8 h-8 flex-shrink-0" variant="1" />
                    <span class="font-extrabold text-zinc-950 text-base tracking-tight">LUNOU</span>
                </Link>
                <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold uppercase tracking-wider whitespace-nowrap hidden sm:inline-block">
                    Super Admin Console
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs flex-shrink-0">
                <Link href="/studio" class="whitespace-nowrap px-3.5 py-1.5 rounded-xl bg-white hover:bg-zinc-50 text-zinc-800 border border-zinc-300 font-semibold shadow-xs transition-all flex-shrink-0">
                    &larr; Buka Studio Profil
                </Link>
                <Link href="/logout" method="post" as="button" class="whitespace-nowrap text-zinc-500 hover:text-zinc-900 transition-colors flex-shrink-0">
                    Keluar
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-6 space-y-6">
            <!-- Global Stats Overview (Nuansa Putih) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Total Akun</span>
                    <div class="text-2xl font-black text-zinc-950">{{ stats.total_users }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Profil Terbit</span>
                    <div class="text-2xl font-black text-blue-600">{{ stats.total_profiles }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Komponen Sosial</span>
                    <div class="text-2xl font-black text-indigo-600">{{ stats.total_components }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Gantungan Kunci Terhubung</span>
                    <div class="text-2xl font-black text-emerald-600">{{ stats.active_nfc }} / {{ stats.total_nfc }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Total Views</span>
                    <div class="text-2xl font-black text-amber-600">{{ stats.total_views }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-zinc-200/90 shadow-xs space-y-1">
                    <span class="text-[11px] text-zinc-500 font-medium">Total Klik</span>
                    <div class="text-2xl font-black text-rose-600">{{ stats.total_clicks }}</div>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-1.5 p-1 bg-white border border-zinc-200/90 rounded-2xl w-fit text-xs font-bold shadow-xs">
                <button
                    @click="currentTab = 'orders'"
                    class="px-4 py-2 rounded-xl transition-all flex items-center gap-2"
                    :class="currentTab === 'orders' ? 'bg-zinc-950 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <LunouIcon name="keychain" className="w-4 h-4 text-blue-400" />
                    <span>Pesanan Gantungan Kunci</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentTab === 'orders' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700'">
                        {{ nfcOrders.length }}
                    </span>
                </button>
                <button
                    @click="currentTab = 'nfc'"
                    class="px-4 py-2 rounded-xl transition-all flex items-center gap-2"
                    :class="currentTab === 'nfc' ? 'bg-zinc-950 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <LunouIcon name="nfc" className="w-4 h-4" />
                    <span>Manajemen NFC / RFID</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentTab === 'nfc' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-600'">
                        {{ nfcDevices.length }}
                    </span>
                </button>
                <button
                    @click="currentTab = 'users'"
                    class="px-4 py-2 rounded-xl transition-all flex items-center gap-2"
                    :class="currentTab === 'users' ? 'bg-zinc-950 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Pengguna & Moderasi</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="currentTab === 'users' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-600'">
                        {{ users.total || users.data?.length || 0 }}
                    </span>
                </button>
                <button
                    @click="currentTab = 'logs'"
                    class="px-4 py-2 rounded-xl transition-all flex items-center gap-2"
                    :class="currentTab === 'logs' ? 'bg-zinc-950 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Audit Log</span>
                </button>
            </div>

            <!-- TAB 0: ORDERS & PENGAJUAN (Nuansa Putih) -->
            <div v-if="currentTab === 'orders'" class="space-y-6">
                <!-- Action Bar -->
                <div class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <ApplicationLogo className="w-7 h-7" variant="1" />
                            <h2 class="text-lg font-black text-zinc-950 tracking-tight">Pesanan Gantungan Kunci LUNOU</h2>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                {{ nfcOrders.filter(o => o.status === 'pending').length }} Perlu Diproses
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500 mt-1 max-w-xl">
                            Pengguna mengajukan pesanan gantungan kunci NFC custom. Admin dapat memverifikasi pembayaran lalu menyambungkan gantungan kunci ke akun profil pengguna secara instan.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="showOfflineOrderModal = true"
                            class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm"
                        >
                            <LunouIcon name="plus" className="w-3.5 h-3.5 text-blue-400" />
                            <span>+ Buat Pesanan Offline / WA</span>
                        </button>
                        <button
                            @click="currentTab = 'nfc'"
                            class="px-4 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-bold transition-all"
                        >
                            Lihat Stok Kartu RFID ({{ unassignedCards.length }} Tersedia)
                        </button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white border border-zinc-200/90 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-zinc-100 flex items-center justify-between">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-zinc-700">Daftar Pengajuan Pesanan ({{ nfcOrders.length }})</h3>
                    </div>

                    <div v-if="nfcOrders.length === 0" class="p-12 text-center text-zinc-400 text-xs">
                        Belum ada pesanan kartu yang masuk dari pengguna.
                    </div>

                    <div v-else class="overflow-x-auto">
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
                                <tr v-for="order in nfcOrders" :key="order.id" class="hover:bg-zinc-50/70 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-bold text-zinc-950">{{ order.order_number }}</div>
                                        <div class="text-[10px] text-zinc-400 mt-0.5">
                                            {{ new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-zinc-900">{{ order.user?.name }}</div>
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
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                v-if="order.payment_status === 'pending'"
                                                @click="verifyPayment(order)"
                                                class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1"
                                                title="Verifikasi Bukti Transfer"
                                            >
                                                <LunouIcon name="check" className="w-3 h-3" />
                                                <span>Verifikasi Bayar</span>
                                            </button>
                                            <button
                                                v-if="!order.device"
                                                @click="openConnectModal(order)"
                                                class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1.5"
                                            >
                                                <LunouIcon name="keychain" className="w-3.5 h-3.5 text-white" />
                                                <span>Hubungkan Kartu</span>
                                            </button>
                                            <button
                                                v-else
                                                @click="openConnectModal(order)"
                                                class="px-2.5 py-1 rounded-lg border border-zinc-200 hover:bg-zinc-100 text-zinc-600 text-[11px] font-medium"
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

            <!-- TAB 1: RFID/NFC MANAGEMENT (Nuansa Putih) -->
            <div v-if="currentTab === 'nfc'" class="space-y-6">
                <!-- Action Bar -->
                <div class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <ApplicationLogo className="w-7 h-7" variant="1" />
                            <h2 class="text-lg font-black text-zinc-950 tracking-tight">Katalog Kartu RFID / NFC YOIMO</h2>
                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">
                                Hardware Ready
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500 mt-1 max-w-xl">
                            Buat tautan kartu fisik di awal untuk diprogram ke chip NFC. Kartu dapat langsung di-embed ke pengguna sekarang, atau dibiarkan kosong agar diaktivasi saat pengguna menerima kartu fisik.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            @click="showBatchModal = true"
                            class="px-4 py-2.5 rounded-xl bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 text-xs font-bold flex items-center gap-1.5 shadow-xs transition-all"
                        >
                            <span>+ Cetak Massal (Batch)</span>
                        </button>
                        <button
                            @click="showCreateModal = true"
                            class="px-4 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all"
                        >
                            <LunouIcon name="card" className="w-4 h-4 text-cyan-400" />
                            <span>+ Buat Kartu Baru</span>
                        </button>
                    </div>
                </div>

                <!-- NFC Devices Table (Clean White) -->
                <div class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
                        <div class="text-xs text-zinc-500">
                            Menampilkan seluruh kartu terdaftar di database
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-zinc-500 uppercase tracking-wider border-b border-zinc-100 text-[10px]">
                                <tr>
                                    <th class="py-3 px-3">Public ID Kartu</th>
                                    <th class="py-3 px-3">Label / Batch</th>
                                    <th class="py-3 px-3">URL Chip (Flashing NFC)</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-3">Akun Ter-embed</th>
                                    <th class="py-3 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <tr v-if="nfcDevices.length === 0">
                                    <td colspan="6" class="py-8 text-center text-zinc-400">
                                        Belum ada kartu RFID/NFC yang dibuat. Klik "+ Buat Kartu Baru" di atas.
                                    </td>
                                </tr>
                                <tr v-for="card in nfcDevices" :key="card.id" class="hover:bg-zinc-50/70 transition-colors">
                                    <!-- Public ID -->
                                    <td class="py-3 px-3">
                                        <div class="font-mono font-bold text-zinc-900 text-sm flex items-center gap-2">
                                            <LunouIcon name="card" className="w-4 h-4 text-blue-600" />
                                            <span>{{ card.public_id }}</span>
                                        </div>
                                    </td>

                                    <!-- Label -->
                                    <td class="py-3 px-3">
                                        <span class="text-zinc-700 font-medium">{{ card.label || 'YOIMO Smart Card' }}</span>
                                    </td>

                                    <!-- URL Tap Chip -->
                                    <td class="py-3 px-3">
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
                                    <td class="py-3 px-3">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                            :class="card.user_id ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                        >
                                            {{ card.user_id ? 'Aktif (Ter-embed)' : 'Pending (Belum Di-embed)' }}
                                        </span>
                                    </td>

                                    <!-- Assigned Account -->
                                    <td class="py-3 px-3">
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
                                    <td class="py-3 px-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Embed / Reassign Modal Button -->
                                            <button
                                                @click="openAssignModal(card)"
                                                class="px-3 py-1 rounded-lg bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 text-zinc-800 text-xs font-semibold transition-all"
                                                title="Embed atau ubah akun pemilik kartu ini"
                                            >
                                                {{ card.user_id ? 'Ganti Akun' : 'Embed ke Akun' }}
                                            </button>

                                            <!-- Test Tap Link -->
                                            <a
                                                :href="`/nfc/${card.public_id}`"
                                                target="_blank"
                                                class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 hover:bg-zinc-100"
                                                title="Uji coba tap kartu fisik"
                                            >
                                                <LunouIcon name="eye" className="w-3.5 h-3.5" />
                                            </a>

                                            <!-- Delete Card -->
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

            <!-- TAB 2: USER MANAGEMENT (Clean White) -->
            <div v-else-if="currentTab === 'users'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-zinc-900">Manajemen Akun & Moderasi</h2>
                        <p class="text-xs text-zinc-500">Daftar seluruh akun pengguna yang terdaftar di platform LUNOU.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-zinc-500 uppercase tracking-wider border-b border-zinc-100 text-[10px]">
                            <tr>
                                <th class="py-3 px-3">Pengguna</th>
                                <th class="py-3 px-3">Username / Profil</th>
                                <th class="py-3 px-3">Role</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3">Kartu NFC</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-zinc-50/70 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-zinc-900 text-sm">{{ user.name }}</div>
                                    <div class="text-zinc-500 font-mono text-[11px]">{{ user.email }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <template v-if="user.profile">
                                        <a :href="`/${user.profile.username}`" target="_blank" class="text-blue-600 hover:underline font-mono font-medium">
                                            @{{ user.profile.username }}
                                        </a>
                                    </template>
                                    <span v-else class="text-zinc-400">-</span>
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="user.role === 'super_admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-zinc-100 text-zinc-600'"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="text-zinc-500 font-mono">{{ user.nfc_devices?.length || 0 }} kartu</span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button
                                        v-if="user.id !== $page.props.auth?.user?.id"
                                        @click="toggleStatus(user)"
                                        class="px-3 py-1 rounded-lg text-xs font-semibold border transition-all"
                                        :class="user.status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200'"
                                    >
                                        {{ user.status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: AUDIT LOGS (Clean White) -->
            <div v-else-if="currentTab === 'logs'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-zinc-900">Log Aktivitas & Audit Keamanan Platform</h3>
                <div class="space-y-2 max-h-96 overflow-y-auto text-xs">
                    <div v-if="auditLogs.length === 0" class="text-xs text-zinc-400 py-4 text-center">
                        Belum ada catatan log aktivitas.
                    </div>
                    <div
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="p-3 rounded-xl bg-zinc-50 border border-zinc-200 flex items-center justify-between"
                    >
                        <div>
                            <span class="font-mono text-purple-700 font-semibold">{{ log.action }}</span>
                            <p class="text-[11px] text-zinc-500 mt-0.5">Pelaku: {{ log.actor?.name || 'Sistem' }} · Target: {{ log.target_type }} #{{ log.target_id || '-' }}</p>
                        </div>
                        <span class="text-[10px] font-mono text-zinc-400">{{ new Date(log.created_at).toLocaleString() }}</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL: BUAT KARTU RFID BARU (Nuansa Putih) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-5 h-5" variant="1" />
                        <h3 class="text-base font-bold">Buat Tautan Kartu RFID / NFC Baru</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">ID Publik Kartu (Opsional)</label>
                        <input
                            type="text"
                            v-model="createCardForm.public_id"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono uppercase"
                            placeholder="Biarkan kosong untuk otomatis (misal: YOIMO-ABC123)"
                        />
                        <span class="text-[11px] text-zinc-500">ID unik ini akan menjadi URL tap: lunou.id/nfc/ID</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Label / Nama Kartu</label>
                        <input
                            type="text"
                            v-model="createCardForm.label"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Contoh: YOIMO Premium Matte White"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Langsung Embed ke Akun Pengguna (Opsional)</label>
                        <select
                            v-model="createCardForm.user_id"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        >
                            <option value="">— Simpan Kosong (Belum Di-embed / Siap Diberikan) —</option>
                            <option v-for="u in allUsers" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }}) [{{ u.profile ? '@' + u.profile.username : 'belum ada profil' }}]
                            </option>
                        </select>
                        <span class="text-[11px] text-zinc-500">Anda dapat memilih akun sekarang atau membiarkannya kosong untuk di-embed nanti.</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showCreateModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitCreateCard"
                        :disabled="createCardForm.processing"
                        class="px-5 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50"
                    >
                        {{ createCardForm.processing ? 'Menyimpan...' : 'Simpan & Buat Tautan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: CETAK MASSAL (BATCH) (Nuansa Putih) -->
        <div v-if="showBatchModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-5 h-5" variant="1" />
                        <h3 class="text-base font-bold">Cetak Tautan Kartu Massal (Batch)</h3>
                    </div>
                    <button @click="showBatchModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Jumlah Kartu</label>
                        <input
                            type="number"
                            v-model="batchCardForm.count"
                            min="1"
                            max="50"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        />
                        <span class="text-[11px] text-zinc-500">Maksimal 50 kartu per batch. Semua kartu akan berstatus pending dan siap di-burn ke chip RFID.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Label Seri / Batch</label>
                        <input
                            type="text"
                            v-model="batchCardForm.label"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Contoh: YOIMO Batch Produksi Oktober 2026"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showBatchModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitBatchCards"
                        :disabled="batchCardForm.processing"
                        class="px-5 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50"
                    >
                        {{ batchCardForm.processing ? 'Memproses...' : 'Cetak Tautan Massal' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: EMBED / GANTI AKUN PENGGUNA (Nuansa Putih) -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold">Embed Kartu ke Akun Pengguna</h3>
                        <span class="text-xs font-mono text-blue-600 font-bold">{{ activeEditingCard?.public_id }}</span>
                    </div>
                    <button @click="showAssignModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Pilih Akun Pengguna</label>
                        <select
                            v-model="assignForm.user_id"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        >
                            <option value="">— Lepas Kartu (Set status kembali ke Pending / Unclaimed) —</option>
                            <option v-for="u in allUsers" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }}) [{{ u.profile ? '@' + u.profile.username : 'belum ada profil' }}]
                            </option>
                        </select>
                        <span class="text-[11px] text-zinc-500">
                            Saat di-embed, setiap tap fisik pada kartu ini akan langsung diarahkan ke profil pengguna yang dipilih.
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Label Kartu</label>
                        <input
                            type="text"
                            v-model="assignForm.label"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Label kartu"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showAssignModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitAssignCard"
                        :disabled="assignForm.processing"
                        class="px-5 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50"
                    >
                        {{ assignForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Embed' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: HUBUNGKAN PESANAN KE KARTU FISIK RFID/NFC -->
        <div v-if="showConnectOrderModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-6 h-6" variant="1" />
                        <div>
                            <h3 class="text-base font-bold text-zinc-900">Hubungkan Pesanan ke Kartu Fisik</h3>
                            <span class="text-xs font-mono text-blue-600 font-bold">{{ activeConnectingOrder?.order_number }}</span>
                        </div>
                    </div>
                    <button @click="showConnectOrderModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <!-- Order Detail Summary Card -->
                <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 text-xs space-y-2">
                    <div v-if="activeConnectingOrder?.custom_image" class="flex items-center gap-3 pb-2 border-b border-zinc-200/60">
                        <span class="text-zinc-500">Desain Gambar:</span>
                        <div class="w-12 h-12 rounded-xl bg-white border border-zinc-200 p-1 flex items-center justify-center shadow-xs">
                            <img :src="activeConnectingOrder.custom_image" alt="Desain Gantungan Kunci" class="w-full h-full object-contain" />
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Pemesan:</span>
                        <strong class="text-zinc-900">{{ activeConnectingOrder?.user?.name }} ({{ activeConnectingOrder?.user?.email }})</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Nama Cetak:</span>
                        <strong class="text-zinc-900">{{ activeConnectingOrder?.card_printed_name }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Varian:</span>
                        <span class="font-bold text-zinc-900 capitalize">{{ activeConnectingOrder?.card_variant?.replace('_', ' ') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Kontak WhatsApp:</span>
                        <span class="font-mono font-bold text-emerald-600">{{ activeConnectingOrder?.phone_or_whatsapp }}</span>
                    </div>
                </div>

                <!-- Connection Options -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-2">Metode Sambungan Kartu Fisik</label>
                        <div class="space-y-2">
                            <label
                                class="p-3.5 rounded-xl border flex items-start gap-3 cursor-pointer transition-all"
                                :class="!connectForm.auto_generate ? 'border-zinc-950 bg-zinc-50/80 ring-1 ring-zinc-950/10' : 'border-zinc-200'"
                            >
                                <input
                                    type="radio"
                                    :value="false"
                                    v-model="connectForm.auto_generate"
                                    class="mt-0.5 text-zinc-950 focus:ring-0"
                                />
                                <div class="min-w-0">
                                    <span class="block text-xs font-bold text-zinc-900">Pilih dari Stok Kartu Fisik yang Tersedia</span>
                                    <span class="block text-[11px] text-zinc-500 mt-0.5">
                                        Pilih salah satu nomor ID kartu fisik acak yang sudah dicetak/diprovisioning admin sebelumnya.
                                    </span>
                                </div>
                            </label>

                            <label
                                class="p-3.5 rounded-xl border flex items-start gap-3 cursor-pointer transition-all"
                                :class="connectForm.auto_generate ? 'border-zinc-950 bg-zinc-50/80 ring-1 ring-zinc-950/10' : 'border-zinc-200'"
                            >
                                <input
                                    type="radio"
                                    :value="true"
                                    v-model="connectForm.auto_generate"
                                    class="mt-0.5 text-zinc-950 focus:ring-0"
                                />
                                <div class="min-w-0">
                                    <span class="block text-xs font-bold text-zinc-900">Buat Tautan/Kartu Fisik Acak Baru Secara Otomatis</span>
                                    <span class="block text-[11px] text-zinc-500 mt-0.5">
                                        Sistem akan membuat ID acak baru (contoh: YOIMO-XXXXXXX) dan langsung mengaktifkannya untuk pengguna ini.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dropdown for selecting available stock card -->
                    <div v-if="!connectForm.auto_generate">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">
                            Pilih Kartu Fisik dari Stok ({{ unassignedCards.length }} Tersedia)
                        </label>
                        <select
                            v-model="connectForm.nfc_device_id"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                        >
                            <option v-if="unassignedCards.length === 0" value="">
                                — Tidak ada stok kartu yang belum di-embed. Silakan pilih 'Buat Baru Secara Otomatis' —
                            </option>
                            <option v-for="c in unassignedCards" :key="c.id" :value="String(c.id)">
                                {{ c.public_id }} · {{ c.label || 'YOIMO Batch Card' }}
                            </option>
                        </select>
                        <div v-if="connectForm.errors.nfc_device_id" class="text-xs text-rose-600 mt-1">
                            {{ connectForm.errors.nfc_device_id }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100">
                    <button @click="showConnectOrderModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitConnectOrder"
                        :disabled="connectForm.processing || (!connectForm.auto_generate && !connectForm.nfc_device_id)"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold disabled:opacity-50 shadow-sm flex items-center gap-1.5"
                    >
                        <span>{{ connectForm.processing ? 'Menyambungkan...' : 'Sambungkan & Selesaikan Order' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: BUAT PESANAN OFFLINE / WHATSAPP / LUAR WEB -->
        <div v-if="showOfflineOrderModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-3xl max-w-md w-full p-6 md:p-8 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-6 h-6" variant="1" />
                        <div>
                            <h3 class="text-base font-bold text-zinc-900">Buat Pesanan Offline / WhatsApp</h3>
                            <span class="text-xs text-zinc-500">Hasil kode order dapat diberikan ke pembeli untuk diredeem</span>
                        </div>
                    </div>
                    <button @click="showOfflineOrderModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Nama Pemesan / Dicetak</label>
                        <input
                            type="text"
                            v-model="offlineOrderForm.card_printed_name"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Contoh: Tri Febriansah"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Nomor WhatsApp Pelanggan</label>
                        <input
                            type="text"
                            v-model="offlineOrderForm.phone_or_whatsapp"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                            placeholder="Contoh: 085862319524"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Alamat Pengiriman (Opsional)</label>
                        <textarea
                            v-model="offlineOrderForm.shipping_address"
                            rows="2"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Alamat lengkap atau isi 'COD / Event'"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Pilih Maskot Khas LUNOU</label>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                            <button
                                v-for="num in [1, 2, 3, 5, 7, 10, 14, 18]"
                                :key="num"
                                type="button"
                                @click="offlineOrderForm.custom_image = `/icon/${num}.png`"
                                class="w-10 h-10 rounded-xl p-1 border transition-all flex-shrink-0"
                                :class="offlineOrderForm.custom_image === `/icon/${num}.png` ? 'border-zinc-950 bg-zinc-100 ring-2 ring-zinc-950/20' : 'border-zinc-200 bg-white'"
                            >
                                <img :src="`/icon/${num}.png`" alt="" class="w-full h-full object-contain" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100">
                    <button @click="showOfflineOrderModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitOfflineOrder"
                        :disabled="offlineOrderForm.processing || !offlineOrderForm.card_printed_name || !offlineOrderForm.phone_or_whatsapp"
                        class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50 shadow-sm"
                    >
                        {{ offlineOrderForm.processing ? 'Menyimpan...' : 'Terbitkan Kode Pesanan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
