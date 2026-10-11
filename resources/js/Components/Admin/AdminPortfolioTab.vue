<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    portfolioPlans: {
        type: Array,
        default: () => [],
    },
    portfolioVouchers: {
        type: Array,
        default: () => [],
    },
    portfolioPaymentMethods: {
        type: Array,
        default: () => [],
    },
    portfolioOrders: {
        type: Array,
        default: () => [],
    },
    portfolioEntitlements: {
        type: Array,
        default: () => [],
    },
    userPortfolios: {
        type: Array,
        default: () => [],
    },
    portfolioSettings: {
        type: Object,
        default: () => ({
            max_pdf_files: 10,
            max_pdf_size_mb: 5,
            terms_and_conditions: '',
        }),
    },
    allUsers: {
        type: Array,
        default: () => [],
    },
});

// Active Subtab inside Portfolio Management
// 'verification', 'pricing', 'vouchers', 'payments', 'entitlements', 'portfolios', 'settings'
const activeSubtab = ref('verification');

// ----------------------------------------------------
// 1. VERIFIKASI TRANSAKSI & BUKTI TRANSFER
// ----------------------------------------------------
const selectedProofImage = ref(null);
const rejectingOrder = ref(null);
const rejectionReason = ref('');

function openProofModal(imageUrl) {
    selectedProofImage.value = imageUrl;
}

function approveOrder(orderId) {
    if (!confirm('Setujui pembayaran ini? Akses premium portofolio pengguna akan langsung aktif secara otomatis.')) return;
    router.post(`/admin/portfolio/orders/${orderId}/verify`, {
        action: 'approve',
    }, {
        preserveScroll: true,
    });
}

function promptRejectOrder(order) {
    rejectingOrder.value = order;
    rejectionReason.value = 'Bukti transfer tidak sesuai atau dana belum masuk rekening.';
}

function confirmRejectOrder() {
    if (!rejectingOrder.value) return;
    router.post(`/admin/portfolio/orders/${rejectingOrder.value.id}/verify`, {
        action: 'reject',
        rejection_reason: rejectionReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rejectingOrder.value = null;
            rejectionReason.value = '';
        },
    });
}

// ----------------------------------------------------
// 2. PENGATURAN HARGA & PROMO
// ----------------------------------------------------
const activePlan = computed(() => props.portfolioPlans[0] || null);
const planForm = useForm({
    price: activePlan.value?.price || 99000,
    promo_price: activePlan.value?.promo_price || 89100,
    is_promo_active: Boolean(activePlan.value?.is_promo),
    duration_days: activePlan.value?.duration_days || 30,
    max_pdf_files: activePlan.value?.max_pdf_files || 10,
    max_pdf_size_mb: activePlan.value?.max_pdf_size_mb || 5,
    promo_ends_at: activePlan.value?.promo_ends_at || '',
});

function savePlanPricing() {
    if (!activePlan.value) return;
    planForm.patch(`/admin/portfolio/plans/${activePlan.value.id}`, {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 3. VOUCHER MANAGEMENT
// ----------------------------------------------------
const showCreateVoucherModal = ref(false);
const voucherForm = useForm({
    code: '',
    discount_type: 'percent', // 'percent' or 'fixed'
    discount_amount: 10,
    max_discount: null,
    quota: 100,
    expires_at: '',
});

function createVoucher() {
    voucherForm.post('/admin/portfolio/vouchers', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateVoucherModal.value = false;
            voucherForm.reset();
        },
    });
}

function toggleVoucher(voucherId) {
    router.patch(`/admin/portfolio/vouchers/${voucherId}/toggle`, {}, {
        preserveScroll: true,
    });
}

function deleteVoucher(voucherId, code) {
    if (!confirm(`Hapus voucher "${code}"?`)) return;
    router.delete(`/admin/portfolio/vouchers/${voucherId}`, {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 4. REKENING PEMBAYARAN (ENCRYPTED)
// ----------------------------------------------------
const showPaymentMethodModal = ref(false);
const editingPaymentMethod = ref(null);
const paymentMethodForm = useForm({
    bank_name: '',
    account_number: '',
    account_holder: '',
    instructions: '',
    is_active: true,
});

function openCreatePaymentMethod() {
    editingPaymentMethod.value = null;
    paymentMethodForm.reset();
    paymentMethodForm.is_active = true;
    showPaymentMethodModal.value = true;
}

function openEditPaymentMethod(method) {
    editingPaymentMethod.value = method;
    paymentMethodForm.bank_name = method.bank_name;
    paymentMethodForm.account_number = method.account_number;
    paymentMethodForm.account_holder = method.account_holder;
    paymentMethodForm.instructions = method.instructions || '';
    paymentMethodForm.is_active = Boolean(method.is_active);
    showPaymentMethodModal.value = true;
}

function savePaymentMethod() {
    if (editingPaymentMethod.value) {
        paymentMethodForm.put(`/admin/portfolio/payment-methods/${editingPaymentMethod.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showPaymentMethodModal.value = false;
            },
        });
    } else {
        paymentMethodForm.post('/admin/portfolio/payment-methods', {
            preserveScroll: true,
            onSuccess: () => {
                showPaymentMethodModal.value = false;
            },
        });
    }
}

function deletePaymentMethod(methodId) {
    if (!confirm('Hapus rekening pembayaran ini?')) return;
    router.delete(`/admin/portfolio/payment-methods/${methodId}`, {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 5. MANUAL ENTITLEMENT ACCESS
// ----------------------------------------------------
const showGrantAccessModal = ref(false);
const grantForm = useForm({
    user_id: '',
    action: 'grant',
    days: 30,
});

function submitGrantAccess() {
    if (!grantForm.user_id) return;
    grantForm.post(`/admin/portfolio/users/${grantForm.user_id}/entitlement`, {
        preserveScroll: true,
        onSuccess: () => {
            showGrantAccessModal.value = false;
            grantForm.reset();
        },
    });
}

function revokeUserAccess(userId, userName) {
    if (!confirm(`Nonaktifkan akses portofolio untuk ${userName}?`)) return;
    router.post(`/admin/portfolio/users/${userId}/entitlement`, {
        action: 'revoke',
    }, {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 6. GLOBAL SETTINGS, MODE GRATIS, & SAWERIA
// ----------------------------------------------------
const settingsForm = useForm({
    max_pdf_files: props.portfolioSettings.max_pdf_files || 10,
    max_pdf_size_mb: props.portfolioSettings.max_pdf_size_mb || 5,
    terms_and_conditions: props.portfolioSettings.terms_and_conditions || '',
    free_mode_enabled: Boolean(props.portfolioSettings.free_mode_enabled),
    saweria_url: props.portfolioSettings.saweria_url || 'https://saweria.co/Try3',
});

function saveGlobalSettings() {
    settingsForm.post('/admin/portfolio/settings', {
        preserveScroll: true,
    });
}

function toggleFreeModeFast() {
    router.post('/admin/portfolio/toggle-free-mode', {}, {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 7. SLIDE CONTOH KARYA & FOTO (SHOWCASE SLIDES)
// ----------------------------------------------------
const localSlides = ref(
    Array.isArray(props.portfolioSettings.showcase_slides) && props.portfolioSettings.showcase_slides.length > 0
        ? JSON.parse(JSON.stringify(props.portfolioSettings.showcase_slides))
        : [
            {
                title: 'Portofolio Kreatif & Desain Visual',
                subtitle: 'Karya Desain Grafis, UI/UX, & Identitas Visual',
                image_url: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
                description: 'Susun tata letak halaman portofolio layaknya di Canva dengan grid rapi, tipografi presisi, dan warna elegan yang siap dipresentasikan ke klien.',
                tag: 'Visual Branding',
                link: '/p/tri',
            },
            {
                title: 'Dokumen PDF Interaktif & Flipbook',
                subtitle: 'Koleksi CV, Resume, & Buku Karya Digital',
                image_url: 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=80',
                description: 'Sematan berkas PDF langsung di dalam halaman portofolio dengan mode baca buku (flipbook) tanpa perlu mengunduh aplikasi tambahan.',
                tag: 'Digital Flipbook',
                link: '/p/tri',
            },
            {
                title: 'Gantungan Kunci Fisik YOIMO NFC',
                subtitle: 'Kartu & Keychain Pintar Berteknologi Tap Langsung',
                image_url: 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?auto=format&fit=crop&w=1200&q=80',
                description: 'Tingkatkan profesionalisme saat networking. Cukup tap gantungan kunci YOIMO ke ponsel rekan kerja untuk membuka portofolio digital seketika.',
                tag: 'Hardware NFC',
                link: '#cara-pesan',
            },
        ]
);

const slidesForm = useForm({
    slides: localSlides.value,
});

function addSlide() {
    localSlides.value.push({
        title: 'Karya Desain Baru',
        subtitle: 'Kategori Kreatif',
        image_url: 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=1200&q=80',
        description: 'Tuliskan deskripsi ringkas tentang foto atau karya ini.',
        tag: 'Karya Baru',
        link: '/p/tri',
    });
}

function removeSlide(index) {
    localSlides.value.splice(index, 1);
}

function moveSlideUp(index) {
    if (index === 0) return;
    const item = localSlides.value.splice(index, 1)[0];
    localSlides.value.splice(index - 1, 0, item);
}

function moveSlideDown(index) {
    if (index === localSlides.value.length - 1) return;
    const item = localSlides.value.splice(index, 1)[0];
    localSlides.value.splice(index + 1, 0, item);
}

function saveSlides() {
    slidesForm.slides = localSlides.value;
    slidesForm.post('/admin/portfolio/slides', {
        preserveScroll: true,
    });
}

// ----------------------------------------------------
// 8. ALUR CARA PEMESANAN (ORDER STEPS)
// ----------------------------------------------------
const localSteps = ref(
    Array.isArray(props.portfolioSettings.order_steps) && props.portfolioSettings.order_steps.length > 0
        ? JSON.parse(JSON.stringify(props.portfolioSettings.order_steps))
        : [
            {
                step_number: 1,
                title: 'Pilih Paket atau Buat Akun',
                description: 'Daftar akun gratis di me.myoin.id atau pilih paket Portfolio Creator Pro / Bundle YOIMO NFC untuk fitur lengkap.',
                badge: 'Langkah 1',
                icon: 'user',
            },
            {
                step_number: 2,
                title: 'Desain Portofolio & Upload Berkas',
                description: 'Gunakan Canva-style page editor untuk menyusun halaman karya, menambahkan galeri foto, teks profil, dan dokumen PDF resmi.',
                badge: 'Langkah 2',
                icon: 'sparkles',
            },
            {
                step_number: 3,
                title: 'Lakukan Pembayaran & Verifikasi Admin',
                description: 'Transfer ke rekening resmi LUNOU dan unggah bukti transfer (atau gratis langsung aktif saat promo bypass aktif).',
                badge: 'Langkah 3',
                icon: 'check',
            },
            {
                step_number: 4,
                title: 'Portofolio Tayang & Terima Kartu NFC',
                description: 'Website portofolio Anda langsung aktif di link me.myoin.id/p/username dan kartu fisik YOIMO dikirimkan ke alamat Anda.',
                badge: 'Langkah 4',
                icon: 'card',
            },
        ]
);

const stepsForm = useForm({
    steps: localSteps.value,
});

function addStep() {
    const nextNum = localSteps.value.length + 1;
    localSteps.value.push({
        step_number: nextNum,
        title: `Langkah ${nextNum}: Judul Tahapan`,
        description: 'Jelaskan tahapan yang perlu dilakukan pengguna.',
        badge: `Langkah ${nextNum}`,
        icon: 'sparkles',
    });
}

function removeStep(index) {
    localSteps.value.splice(index, 1);
    localSteps.value.forEach((step, idx) => {
        step.step_number = idx + 1;
        step.badge = `Langkah ${idx + 1}`;
    });
}

function moveStepUp(index) {
    if (index === 0) return;
    const item = localSteps.value.splice(index, 1)[0];
    localSteps.value.splice(index - 1, 0, item);
    localSteps.value.forEach((step, idx) => {
        step.step_number = idx + 1;
        step.badge = `Langkah ${idx + 1}`;
    });
}

function moveStepDown(index) {
    if (index === localSteps.value.length - 1) return;
    const item = localSteps.value.splice(index, 1)[0];
    localSteps.value.splice(index + 1, 0, item);
    localSteps.value.forEach((step, idx) => {
        step.step_number = idx + 1;
        step.badge = `Langkah ${idx + 1}`;
    });
}

function saveOrderSteps() {
    stepsForm.steps = localSteps.value;
    stepsForm.post('/admin/portfolio/order-steps', {
        preserveScroll: true,
    });
}

// Helpers
function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num || 0);
}
</script>

<template>
    <div class="space-y-6">
        <!-- ===================================================================== -->
        <!-- TOP STATS CARDS -->
        <!-- ===================================================================== -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs">
                <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">Total Portofolio</span>
                <div class="text-xl sm:text-2xl font-extrabold text-zinc-950 mt-1">
                    {{ stats.total_portfolios || 0 }}
                </div>
                <span class="text-[11px] text-zinc-500 mt-0.5 block">
                    {{ stats.published_portfolios || 0 }} telah dipublikasikan
                </span>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Pelanggan Aktif</span>
                <div class="text-xl sm:text-2xl font-extrabold text-blue-950 mt-1">
                    {{ stats.active_subscribers || 0 }}
                </div>
                <span class="text-[11px] text-blue-600 font-semibold mt-0.5 block">
                    Akses premium valid
                </span>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs">
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Verifikasi Pending</span>
                <div class="text-xl sm:text-2xl font-extrabold text-amber-950 mt-1 flex items-center gap-2">
                    <span>{{ stats.pending_portfolio_orders || 0 }}</span>
                    <span v-if="stats.pending_portfolio_orders > 0" class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                </div>
                <span class="text-[11px] text-amber-700 font-medium mt-0.5 block">
                    Menunggu konfirmasi admin
                </span>
            </div>

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-zinc-200/90 shadow-2xs col-span-2 sm:col-span-1 lg:col-span-2">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Pendapatan Portofolio</span>
                <div class="text-xl sm:text-2xl font-extrabold text-emerald-950 mt-1">
                    {{ formatRupiah(stats.total_portfolio_revenue) }}
                </div>
                <span class="text-[11px] text-emerald-700 font-medium mt-0.5 block">
                    Dari pesanan terverifikasi lunas
                </span>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUB-NAVIGATION BAR (PORTFOLIO MANAGEMENT) -->
        <!-- ===================================================================== -->
        <div class="overflow-x-auto no-scrollbar scroll-smooth -mx-3 px-3 sm:mx-0 sm:px-0">
            <div class="flex items-center gap-1.5 p-1 bg-white border border-zinc-200/90 rounded-2xl w-max text-xs font-bold shadow-2xs">
                <button
                    @click="activeSubtab = 'verification'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'verification' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Verifikasi Transaksi</span>
                    <span v-if="stats.pending_portfolio_orders > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500 text-white">
                        {{ stats.pending_portfolio_orders }}
                    </span>
                </button>

                <button
                    @click="activeSubtab = 'pricing'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'pricing' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Pengaturan Harga & Promo</span>
                </button>

                <button
                    @click="activeSubtab = 'vouchers'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'vouchers' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Voucher Diskon</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-zinc-100 text-zinc-600">
                        {{ portfolioVouchers.length }}
                    </span>
                </button>

                <button
                    @click="activeSubtab = 'payments'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'payments' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Rekening Pembayaran</span>
                </button>

                <button
                    @click="activeSubtab = 'entitlements'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'entitlements' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Aktivasi Premium Pengguna</span>
                </button>

                <button
                    @click="activeSubtab = 'portfolios'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'portfolios' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Portofolio Pengguna</span>
                </button>

                <button
                    @click="activeSubtab = 'settings'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'settings' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <span>Mode Gratis, Saweria & Batasan</span>
                    <span v-if="portfolioSettings.free_mode_enabled" class="px-1.5 py-0.5 rounded text-[9px] bg-emerald-500 text-white font-bold animate-pulse">
                        GRATIS AKTIF
                    </span>
                </button>

                <button
                    @click="activeSubtab = 'slides'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'slides' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <LunouIcon name="layers" className="w-3.5 h-3.5" />
                    <span>Slide Contoh Karya ({{ localSlides.length }})</span>
                </button>

                <button
                    @click="activeSubtab = 'order_steps'"
                    type="button"
                    class="px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                    :class="activeSubtab === 'order_steps' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                >
                    <LunouIcon name="sparkles" className="w-3.5 h-3.5" />
                    <span>Cara Pemesanan ({{ localSteps.length }})</span>
                </button>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 1: VERIFIKASI TRANSAKSI & BUKTI TRANSFER -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'verification'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Antrean Verifikasi Pembayaran Manual</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Periksa kecocokan bukti transfer dengan mutasi bank sebelum menyetujui. Setelah disetujui, akses premium pengguna akan otomatis aktif.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 text-zinc-500 font-bold border-b border-zinc-200">
                        <tr>
                            <th class="py-3 px-4">No. Order</th>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Paket & Voucher</th>
                            <th class="py-3 px-4">Nominal</th>
                            <th class="py-3 px-4">Pengirim</th>
                            <th class="py-3 px-4">Bukti</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800">
                        <tr v-if="portfolioOrders.length === 0">
                            <td colspan="8" class="py-8 text-center text-zinc-400">
                                Belum ada transaksi portofolio.
                            </td>
                        </tr>
                        <tr v-for="order in portfolioOrders" :key="order.id" class="hover:bg-zinc-50/60 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-zinc-950">
                                #{{ order.order_number }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-zinc-900">{{ order.user?.name }}</div>
                                <div class="text-[10px] text-zinc-400">{{ order.user?.email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-zinc-900">{{ order.plan?.name }}</div>
                                <div v-if="order.voucher" class="text-[10px] text-emerald-600 font-mono">
                                    Voucher: {{ order.voucher.code }}
                                </div>
                            </td>
                            <td class="py-3 px-4 font-extrabold text-blue-600">
                                {{ formatRupiah(order.final_amount) }}
                            </td>
                            <td class="py-3 px-4">
                                <div v-if="order.payment">
                                    <span class="font-bold text-zinc-900">{{ order.payment.sender_bank }}</span>
                                    <div class="text-[11px] text-zinc-500">a.n. {{ order.payment.sender_account_name }}</div>
                                </div>
                                <span v-else class="text-zinc-400 italic">Belum diunggah</span>
                            </td>
                            <td class="py-3 px-4">
                                <button
                                    v-if="order.payment?.proof_image_url"
                                    @click="openProofModal(order.payment.proof_image_url)"
                                    class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-[10px] flex items-center gap-1"
                                >
                                    <LunouIcon name="eye" className="w-3 h-3 text-blue-600" />
                                    <span>Lihat Resi</span>
                                </button>
                                <span v-else class="text-zinc-400">-</span>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize"
                                    :class="{
                                        'bg-amber-50 text-amber-700 border border-amber-200': order.payment_status === 'under_review',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': order.payment_status === 'paid',
                                        'bg-rose-50 text-rose-700 border border-rose-200': order.payment_status === 'rejected',
                                        'bg-zinc-100 text-zinc-600': order.payment_status === 'pending',
                                    }"
                                >
                                    {{ order.payment_status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div v-if="order.payment_status === 'under_review'" class="flex items-center justify-end gap-1.5">
                                    <button
                                        @click="approveOrder(order.id)"
                                        class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-2xs"
                                    >
                                        Setujui
                                    </button>
                                    <button
                                        @click="promptRejectOrder(order)"
                                        class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-[11px]"
                                    >
                                        Tolak
                                    </button>
                                </div>
                                <span v-else class="text-[11px] text-zinc-400">Selesai</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 2: PENGATURAN HARGA & PROMO -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'pricing'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs">
            <div>
                <h3 class="text-base font-bold text-zinc-950">Konfigurasi Harga & Periode Promo Paket</h3>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Ubah harga normal, diskon promo, durasi akses, dan batasan tanpa mengubah source code. Seluruh perubahan langsung berlaku saat checkout.
                </p>
            </div>

            <form @submit.prevent="savePlanPricing" class="max-w-2xl space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Harga Normal (Rp)</label>
                        <input
                            v-model.number="planForm.price"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Durasi Akses (Hari)</label>
                        <input
                            v-model.number="planForm.duration_days"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>

                    <div class="sm:col-span-2 p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-bold text-zinc-900 block">Aktifkan Harga Promo</span>
                                <span class="text-[11px] text-zinc-500">Tampilkan diskon khusus berbatas waktu.</span>
                            </div>
                            <input
                                v-model="planForm.is_promo_active"
                                type="checkbox"
                                class="rounded text-blue-600 w-4 h-4"
                            />
                        </div>

                        <div v-if="planForm.is_promo_active" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block font-bold text-zinc-800 mb-1">Harga Promo (Rp)</label>
                                <input
                                    v-model.number="planForm.promo_price"
                                    type="number"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                                />
                            </div>

                            <div>
                                <label class="block font-bold text-zinc-800 mb-1">Batas Waktu Promo (Opsional)</label>
                                <input
                                    v-model="planForm.promo_ends_at"
                                    type="date"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Maksimum Jumlah Berkas PDF</label>
                        <input
                            v-model.number="planForm.max_pdf_files"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Maksimum Ukuran PDF (MB)</label>
                        <input
                            v-model.number="planForm.max_pdf_size_mb"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="planForm.processing"
                    class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all shadow-2xs"
                >
                    {{ planForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Harga' }}
                </button>
            </form>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 3: VOUCHER DISKON -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'vouchers'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Daftar Kode Voucher Diskon</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Kelola voucher persentase (%) atau potongan nominal tetap (Rp).</p>
                </div>
                <button
                    @click="showCreateVoucherModal = true"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all flex items-center gap-1.5"
                >
                    <span>+ Buat Voucher Baru</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 text-zinc-500 font-bold border-b border-zinc-200">
                        <tr>
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Tipe & Diskon</th>
                            <th class="py-3 px-4">Kuota / Terpakai</th>
                            <th class="py-3 px-4">Berlaku Hingga</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800">
                        <tr v-for="voucher in portfolioVouchers" :key="voucher.id" class="hover:bg-zinc-50/60">
                            <td class="py-3 px-4 font-mono font-extrabold text-blue-600 text-sm">
                                {{ voucher.code }}
                            </td>
                            <td class="py-3 px-4 font-bold text-zinc-900">
                                <span v-if="voucher.discount_type === 'percent'">{{ voucher.discount_amount }}%</span>
                                <span v-else>{{ formatRupiah(voucher.discount_amount) }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-zinc-900">{{ voucher.used_count }}</span>
                                <span class="text-zinc-400"> / {{ voucher.quota }}</span>
                            </td>
                            <td class="py-3 px-4 text-zinc-500">
                                {{ voucher.expires_at ? new Date(voucher.expires_at).toLocaleDateString('id-ID') : 'Tidak Terbatas' }}
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="voucher.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-zinc-100 text-zinc-500'"
                                >
                                    {{ voucher.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="toggleVoucher(voucher.id)"
                                        class="text-xs font-semibold hover:underline"
                                        :class="voucher.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                    >
                                        {{ voucher.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                    <button
                                        @click="deleteVoucher(voucher.id, voucher.code)"
                                        class="text-rose-600 hover:text-rose-800 font-semibold text-xs"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 4: REKENING PEMBAYARAN (ENCRYPTED) -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'payments'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Rekening Tujuan Pembayaran Resmi</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Rekening dan nama pemilik disimpan secara terenkripsi menggunakan cipher kriptografi AES-256-CBC.
                    </p>
                </div>
                <button
                    @click="openCreatePaymentMethod"
                    class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all flex items-center gap-1.5"
                >
                    <span>+ Tambah Rekening</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="method in portfolioPaymentMethods"
                    :key="method.id"
                    class="p-5 rounded-2xl border border-zinc-200/90 bg-zinc-50/50 space-y-3 relative group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-zinc-950">{{ method.bank_name }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="method.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-200 text-zinc-600'">
                            {{ method.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="space-y-0.5">
                        <div class="text-base font-mono font-extrabold text-blue-600">{{ method.account_number }}</div>
                        <div class="text-xs text-zinc-600 font-medium">a.n. {{ method.account_holder }}</div>
                    </div>

                    <div class="pt-2 border-t border-zinc-200 flex justify-end gap-2 text-xs">
                        <button @click="openEditPaymentMethod(method)" class="text-blue-600 font-bold hover:underline">Edit</button>
                        <button @click="deletePaymentMethod(method.id)" class="text-rose-600 font-bold hover:underline">Hapus</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 5: AKTIVASI PREMIUM PENGGUNA -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'entitlements'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Hak Akses & Aktivasi Portofolio Pengguna</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Admin dapat mengaktifkan secara manual, memperpanjang masa aktif, atau mencabut hak akses.</p>
                </div>
                <button
                    @click="showGrantAccessModal = true"
                    class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition-all flex items-center gap-1.5"
                >
                    <span>+ Beri / Perpanjang Akses Manual</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 text-zinc-500 font-bold border-b border-zinc-200">
                        <tr>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Mulai Aktif</th>
                            <th class="py-3 px-4">Kedaluwarsa</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800">
                        <tr v-for="ent in portfolioEntitlements" :key="ent.id" class="hover:bg-zinc-50/60">
                            <td class="py-3 px-4">
                                <div class="font-bold text-zinc-950">{{ ent.user?.name }}</div>
                                <div class="text-[10px] text-zinc-400">{{ ent.user?.email }}</div>
                            </td>
                            <td class="py-3 px-4 text-zinc-500">
                                {{ ent.starts_at ? new Date(ent.starts_at).toLocaleDateString('id-ID') : '-' }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-zinc-900">
                                {{ ent.expires_at ? new Date(ent.expires_at).toLocaleDateString('id-ID') : 'Permanen' }}
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="ent.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                >
                                    {{ ent.status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button
                                    v-if="ent.status === 'active'"
                                    @click="revokeUserAccess(ent.user_id, ent.user?.name)"
                                    class="text-rose-600 hover:text-rose-800 font-semibold text-xs"
                                >
                                    Nonaktifkan
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 6: PORTOFOLIO PENGGUNA -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'portfolios'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
            <h3 class="text-base font-bold text-zinc-950">Seluruh Portofolio yang Telah Dibuat Pengguna</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 text-zinc-500 font-bold border-b border-zinc-200">
                        <tr>
                            <th class="py-3 px-4">Pemilik</th>
                            <th class="py-3 px-4">Judul Portofolio</th>
                            <th class="py-3 px-4">Tautan Publik</th>
                            <th class="py-3 px-4">Halaman</th>
                            <th class="py-3 px-4">Views</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800">
                        <tr v-for="p in userPortfolios" :key="p.id" class="hover:bg-zinc-50/60">
                            <td class="py-3 px-4 font-bold text-zinc-950">{{ p.user?.name }}</td>
                            <td class="py-3 px-4 text-zinc-800">{{ p.title }}</td>
                            <td class="py-3 px-4">
                                <a :href="`/p/${p.slug}`" target="_blank" class="font-mono text-blue-600 font-semibold hover:underline">
                                    /p/{{ p.slug }}
                                </a>
                            </td>
                            <td class="py-3 px-4 font-bold">{{ p.pages?.length || 0 }} hal</td>
                            <td class="py-3 px-4 text-zinc-500">{{ p.views_count }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="p.is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                    {{ p.is_published ? 'Publik' : 'Draft' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 7: BATASAN, MODE GRATIS, SAWERIA & SYARAT KETENTUAN -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'settings'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs">
            <div>
                <h3 class="text-base font-bold text-zinc-950">Mode Akses Gratis, Dukungan Saweria, Batasan & Syarat Ketentuan</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Atur bypass harga menjadi gratis, tautan donasi Saweria, batasan berkas, dan syarat layanan.</p>
            </div>

            <!-- 1-Click Fast Free Mode Toggle Banner -->
            <div
                class="p-4 sm:p-5 rounded-2xl border transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                :class="portfolioSettings.free_mode_enabled ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950' : 'bg-zinc-50 border-zinc-200 text-zinc-800'"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                            :class="portfolioSettings.free_mode_enabled ? 'bg-emerald-600 text-white' : 'bg-zinc-300 text-zinc-700'"
                        >
                            {{ portfolioSettings.free_mode_enabled ? 'Mode Bebas Biaya Aktif' : 'Mode Berbayar Normal' }}
                        </span>
                        <h4 class="font-bold text-sm sm:text-base">Bypass Semua Harga Jadi Gratis (Free Mode)</h4>
                    </div>
                    <p class="text-xs opacity-85 leading-relaxed max-w-xl">
                        {{ portfolioSettings.free_mode_enabled
                            ? 'Saat ini semua harga portofolio diatur menjadi Rp 0 (Gratis). Pengguna dapat langsung mengaktifkan fitur pro seketika tanpa perlu transfer bank.'
                            : 'Ketika diaktifkan, semua pengguna baru dan lama dapat membuat serta mempublikasikan portofolio tanpa dipungut biaya langganan.' }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="toggleFreeModeFast"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition-all whitespace-nowrap min-h-[40px]"
                    :class="portfolioSettings.free_mode_enabled
                        ? 'bg-rose-600 hover:bg-rose-700 text-white'
                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20'"
                >
                    {{ portfolioSettings.free_mode_enabled ? 'Matikan Mode Gratis (Normal)' : 'Aktifkan Mode Gratis Sekarang (Bypass)' }}
                </button>
            </div>

            <form @submit.prevent="saveGlobalSettings" class="max-w-2xl space-y-4 text-xs">
                <!-- Checkbox Free Mode in Form -->
                <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-zinc-900 block">Status Mode Gratis</span>
                        <span class="text-[11px] text-zinc-500">Centang untuk membebaskan biaya langganan</span>
                    </div>
                    <input
                        type="checkbox"
                        v-model="settingsForm.free_mode_enabled"
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500"
                    />
                </div>

                <!-- URL Saweria -->
                <div>
                    <label class="block font-bold text-zinc-800 mb-1">
                        URL Donasi & Dukungan Saweria
                        <span class="text-amber-600 font-semibold">(https://saweria.co/Try3)</span>
                    </label>
                    <input
                        v-model="settingsForm.saweria_url"
                        type="url"
                        placeholder="https://saweria.co/Try3"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-medium"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Ditampilkan di footer dan seksi donasi untuk mendukung pengembang & kreator.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Batas Maksimum PDF per Pengguna</label>
                        <input
                            v-model.number="settingsForm.max_pdf_files"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Batas Maksimum Ukuran PDF (MB)</label>
                        <input
                            v-model.number="settingsForm.max_pdf_size_mb"
                            type="number"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-zinc-800 mb-1">Teks Syarat & Ketentuan Tambahan (Markdown/HTML)</label>
                    <textarea
                        v-model="settingsForm.terms_and_conditions"
                        rows="6"
                        class="w-full p-3.5 rounded-2xl border border-zinc-300 font-mono text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    :disabled="settingsForm.processing"
                    class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all shadow-2xs"
                >
                    {{ settingsForm.processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan & Saweria' }}
                </button>
            </form>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 8: SLIDE CONTOH KARYA & FOTO (SHOWCASE SLIDES) -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'slides'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Kelola Slide Contoh Karya & Foto Publik</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Slide ini tampil interaktif di halaman utama (Welcome) untuk memperlihatkan contoh hasil portofolio dan foto produk nyata.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="addSlide"
                        class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
                    >
                        <span>+ Tambah Slide</span>
                    </button>
                    <button
                        type="button"
                        @click="saveSlides"
                        :disabled="slidesForm.processing"
                        class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-2xs transition-all disabled:opacity-50"
                    >
                        {{ slidesForm.processing ? 'Menyimpan...' : 'Simpan Slide' }}
                    </button>
                </div>
            </div>

            <!-- Slide Cards List -->
            <div class="space-y-4">
                <div
                    v-for="(slide, sIdx) in localSlides"
                    :key="sIdx"
                    class="p-4 sm:p-5 rounded-2xl bg-zinc-50/70 border border-zinc-200/80 space-y-3.5"
                >
                    <div class="flex items-center justify-between border-b border-zinc-200/60 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-zinc-900 text-white font-bold text-xs flex items-center justify-center">
                                {{ sIdx + 1 }}
                            </span>
                            <span class="font-bold text-xs text-zinc-900">{{ slide.title || 'Slide Tanpa Judul' }}</span>
                            <span v-if="slide.tag" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                {{ slide.tag }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="moveSlideUp(sIdx)"
                                :disabled="sIdx === 0"
                                class="p-1.5 text-zinc-400 hover:text-zinc-800 disabled:opacity-20"
                                title="Geser ke Atas"
                            >
                                &uarr;
                            </button>
                            <button
                                type="button"
                                @click="moveSlideDown(sIdx)"
                                :disabled="sIdx === localSlides.length - 1"
                                class="p-1.5 text-zinc-400 hover:text-zinc-800 disabled:opacity-20"
                                title="Geser ke Bawah"
                            >
                                &darr;
                            </button>
                            <button
                                type="button"
                                @click="removeSlide(sIdx)"
                                class="p-1.5 text-rose-500 hover:text-rose-700 ml-1"
                                title="Hapus Slide"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start text-xs">
                        <!-- Preview Image Thumbnail -->
                        <div class="md:col-span-4 space-y-1.5">
                            <label class="block font-bold text-zinc-700">URL Gambar / Foto</label>
                            <input
                                v-model="slide.image_url"
                                placeholder="https://..."
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-mono text-[11px]"
                            />
                            <div class="h-32 rounded-xl border border-zinc-200 overflow-hidden bg-zinc-200 relative">
                                <img
                                    v-if="slide.image_url"
                                    :src="slide.image_url"
                                    alt=""
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=600&q=80'"
                                />
                                <div v-else class="h-full flex items-center justify-center text-zinc-400 text-[10px]">
                                    Belum ada foto
                                </div>
                            </div>
                        </div>

                        <!-- Text Inputs -->
                        <div class="md:col-span-8 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-zinc-700 mb-1">Judul Slide</label>
                                    <input
                                        v-model="slide.title"
                                        placeholder="Contoh Portofolio Desain"
                                        class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-semibold text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-zinc-700 mb-1">Subtitle / Subjudul</label>
                                    <input
                                        v-model="slide.subtitle"
                                        placeholder="Karya Grafis & Visual"
                                        class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-zinc-700 mb-1">Tag / Kategori</label>
                                    <input
                                        v-model="slide.tag"
                                        placeholder="Visual Branding"
                                        class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block font-bold text-zinc-700 mb-1">Link Tautan (Opsional)</label>
                                    <input
                                        v-model="slide.link"
                                        placeholder="/p/tri atau https://..."
                                        class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs font-mono text-[11px]"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-zinc-700 mb-1">Deskripsi Lengkap Slide</label>
                                <textarea
                                    v-model="slide.description"
                                    rows="2"
                                    placeholder="Jelaskan keunggulan dan konten dari karya ini..."
                                    class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="localSlides.length === 0" class="p-8 text-center text-zinc-400 bg-zinc-50 rounded-2xl border border-dashed border-zinc-200">
                    Belum ada slide contoh. Klik tombol "+ Tambah Slide" di atas untuk menambahkan contoh karya publik.
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="button"
                        @click="saveSlides"
                        :disabled="slidesForm.processing"
                        class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-2xs transition-all disabled:opacity-50"
                    >
                        {{ slidesForm.processing ? 'Menyimpan...' : 'Simpan Semua Slide Contoh' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SUBTAB 9: CARA PEMESANAN (ORDER STEPS) -->
        <!-- ===================================================================== -->
        <div v-if="activeSubtab === 'order_steps'" class="bg-white border border-zinc-200/90 rounded-3xl p-5 sm:p-7 space-y-6 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-zinc-950">Kelola Alur Cara Pemesanan & Pembelian</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Tahapan ini ditampilkan di seksi "Cara Pesan" pada halaman utama untuk memandu calon pelanggan cara memesan portofolio dan gantungan kunci NFC.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="addStep"
                        class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors"
                    >
                        <span>+ Tambah Langkah</span>
                    </button>
                    <button
                        type="button"
                        @click="saveOrderSteps"
                        :disabled="stepsForm.processing"
                        class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-2xs transition-all disabled:opacity-50"
                    >
                        {{ stepsForm.processing ? 'Menyimpan...' : 'Simpan Alur Pesan' }}
                    </button>
                </div>
            </div>

            <!-- Steps Cards List -->
            <div class="space-y-4">
                <div
                    v-for="(step, stIdx) in localSteps"
                    :key="stIdx"
                    class="p-4 sm:p-5 rounded-2xl bg-zinc-50/70 border border-zinc-200/80 space-y-3"
                >
                    <div class="flex items-center justify-between border-b border-zinc-200/60 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-black text-xs flex items-center justify-center">
                                {{ step.step_number || stIdx + 1 }}
                            </span>
                            <span class="font-bold text-xs text-zinc-900">{{ step.title || 'Langkah Baru' }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-zinc-200 text-zinc-800">
                                {{ step.badge || `Langkah ${stIdx + 1}` }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="moveStepUp(stIdx)"
                                :disabled="stIdx === 0"
                                class="p-1.5 text-zinc-400 hover:text-zinc-800 disabled:opacity-20"
                                title="Geser ke Atas"
                            >
                                &uarr;
                            </button>
                            <button
                                type="button"
                                @click="moveStepDown(stIdx)"
                                :disabled="stIdx === localSteps.length - 1"
                                class="p-1.5 text-zinc-400 hover:text-zinc-800 disabled:opacity-20"
                                title="Geser ke Bawah"
                            >
                                &darr;
                            </button>
                            <button
                                type="button"
                                @click="removeStep(stIdx)"
                                class="p-1.5 text-rose-500 hover:text-rose-700 ml-1"
                                title="Hapus Langkah"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-zinc-700 mb-1">Nomor Urut</label>
                            <input
                                v-model.number="step.step_number"
                                type="number"
                                min="1"
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-bold text-center"
                            />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block font-bold text-zinc-700 mb-1">Teks Badge</label>
                            <input
                                v-model="step.badge"
                                placeholder="Langkah 1"
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-semibold"
                            />
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block font-bold text-zinc-700 mb-1">Judul Langkah</label>
                            <input
                                v-model="step.title"
                                placeholder="Pilih Paket atau Buat Akun"
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-bold"
                            />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block font-bold text-zinc-700 mb-1">Ikon Representatif</label>
                            <select
                                v-model="step.icon"
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 font-medium"
                            >
                                <option value="user">User (Akun)</option>
                                <option value="sparkles">Sparkles (Karya/Kreatif)</option>
                                <option value="check">Check (Verifikasi/Lunas)</option>
                                <option value="card">Card (NFC/Kartu)</option>
                                <option value="layers">Layers (Halaman/Desain)</option>
                                <option value="link">Link (Publikasi)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-12">
                            <label class="block font-bold text-zinc-700 mb-1">Deskripsi Langkah</label>
                            <textarea
                                v-model="step.description"
                                rows="2"
                                placeholder="Jelaskan secara singkat apa yang perlu dilakukan pengguna pada tahap ini..."
                                class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div v-if="localSteps.length === 0" class="p-8 text-center text-zinc-400 bg-zinc-50 rounded-2xl border border-dashed border-zinc-200">
                    Belum ada langkah pemesanan. Klik "+ Tambah Langkah" di atas untuk menambahkan alur cara pesan.
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="button"
                        @click="saveOrderSteps"
                        :disabled="stepsForm.processing"
                        class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-2xs transition-all disabled:opacity-50"
                    >
                        {{ stepsForm.processing ? 'Menyimpan...' : 'Simpan Semua Alur Pemesanan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- MODALS -->
        <!-- ===================================================================== -->
        <!-- 1. Modal Lihat Bukti Transfer -->
        <div v-if="selectedProofImage" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-5 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                    <h4 class="font-bold text-sm text-zinc-950">Pratinjau Bukti Pembayaran</h4>
                    <button @click="selectedProofImage = null" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>
                <div class="max-h-[70vh] overflow-auto rounded-2xl border border-zinc-200">
                    <img :src="selectedProofImage" alt="Bukti Transfer" class="w-full object-contain" />
                </div>
                <div class="flex justify-end">
                    <button @click="selectedProofImage = null" class="px-4 py-2 rounded-xl bg-zinc-900 text-white text-xs font-bold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Modal Tolak Pembayaran -->
        <div v-if="rejectingOrder" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <h4 class="font-bold text-base text-zinc-950">Tolak Pembayaran Order #{{ rejectingOrder.order_number }}</h4>
                <p class="text-xs text-zinc-500">Berikan alasan penolakan agar pengguna dapat mengunggah bukti yang benar.</p>
                <textarea
                    v-model="rejectionReason"
                    rows="3"
                    class="w-full px-3 py-2 rounded-xl border border-zinc-300 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none"
                ></textarea>
                <div class="flex justify-end gap-2">
                    <button @click="rejectingOrder = null" class="px-4 py-2 text-xs font-bold text-zinc-500">Batal</button>
                    <button @click="confirmRejectOrder" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold">
                        Konfirmasi Tolak
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Modal Buat Voucher -->
        <div v-if="showCreateVoucherModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                    <h4 class="font-bold text-base text-zinc-950">Buat Voucher Baru</h4>
                    <button @click="showCreateVoucherModal = false" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>
                <form @submit.prevent="createVoucher" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Kode Voucher</label>
                        <input v-model="voucherForm.code" required uppercase placeholder="LUNOU5" class="w-full px-3 py-2 rounded-xl border font-bold uppercase" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-zinc-800 mb-1">Tipe Diskon</label>
                            <select v-model="voucherForm.discount_type" class="w-full px-3 py-2 rounded-xl border font-medium">
                                <option value="percent">Persentase (%)</option>
                                <option value="fixed">Nominal Tetap (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-zinc-800 mb-1">Nominal Diskon</label>
                            <input v-model.number="voucherForm.discount_amount" type="number" required class="w-full px-3 py-2 rounded-xl border font-semibold" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-zinc-800 mb-1">Kuota Pemakaian</label>
                            <input v-model.number="voucherForm.quota" type="number" required class="w-full px-3 py-2 rounded-xl border font-semibold" />
                        </div>
                        <div>
                            <label class="block font-bold text-zinc-800 mb-1">Berlaku Hingga</label>
                            <input v-model="voucherForm.expires_at" type="date" class="w-full px-3 py-2 rounded-xl border font-medium" />
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showCreateVoucherModal = false" class="px-3 py-2 text-zinc-500 font-bold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Buat Voucher</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. Modal Tambah / Edit Rekening -->
        <div v-if="showPaymentMethodModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                    <h4 class="font-bold text-base text-zinc-950">{{ editingPaymentMethod ? 'Edit Rekening' : 'Tambah Rekening Pembayaran' }}</h4>
                    <button @click="showPaymentMethodModal = false" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>
                <form @submit.prevent="savePaymentMethod" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Nama Bank / Dompet</label>
                        <input v-model="paymentMethodForm.bank_name" placeholder="Bank BCA / QRIS" required class="w-full px-3 py-2 rounded-xl border font-medium" />
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Nomor Rekening</label>
                        <input v-model="paymentMethodForm.account_number" placeholder="1234567890" required class="w-full px-3 py-2 rounded-xl border font-mono font-bold" />
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Nama Pemilik Rekening</label>
                        <input v-model="paymentMethodForm.account_holder" placeholder="PT LUNOU KARYA DIGITAL" required class="w-full px-3 py-2 rounded-xl border font-medium" />
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" v-model="paymentMethodForm.is_active" id="method_active" class="rounded text-blue-600" />
                        <label for="method_active" class="font-bold text-zinc-800">Status Aktif</label>
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showPaymentMethodModal = false" class="px-3 py-2 text-zinc-500 font-bold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-zinc-950 text-white font-bold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. Modal Akses Manual Pengguna -->
        <div v-if="showGrantAccessModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-2">
                    <h4 class="font-bold text-base text-zinc-950">Aktivasi Akses Manual Pengguna</h4>
                    <button @click="showGrantAccessModal = false" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>
                <form @submit.prevent="submitGrantAccess" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Pilih Pengguna</label>
                        <select v-model="grantForm.user_id" required class="w-full px-3 py-2 rounded-xl border font-medium">
                            <option value="" disabled>-- Pilih Pengguna --</option>
                            <option v-for="u in allUsers" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Jumlah Hari Akses</label>
                        <input v-model.number="grantForm.days" type="number" min="1" max="365" required class="w-full px-3 py-2 rounded-xl border font-semibold" />
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showGrantAccessModal = false" class="px-3 py-2 text-zinc-500 font-bold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold">Aktifkan Akses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
