<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    nfcDevices: {
        type: Array,
        default: () => [],
    },
    nfcOrders: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ views: 0, clicks: 0 }),
    },
    themes: {
        type: Array,
        default: () => [],
    },
    publicUrl: {
        type: String,
        required: true,
    },
});

// Active Editor Tab
const activeTab = ref('profile'); // 'profile', 'components', 'themes', 'nfc', 'stats'
const mobileView = ref('editor'); // 'editor' or 'preview'
const copied = ref(false);

// Profile Edit Form
const profileForm = useForm({
    display_name: props.profile.display_name || '',
    bio: props.profile.bio || '',
    avatar: props.profile.avatar || '',
    theme: props.profile.theme || 'clean-light',
    is_published: Boolean(props.profile.is_published),
});

// Username Form
const usernameForm = useForm({
    username: props.profile.username || '',
});
const usernameCheckStatus = ref({ checking: false, available: null, message: '' });
let usernameDebounceTimer = null;

function onUsernameInput() {
    clearTimeout(usernameDebounceTimer);
    const val = usernameForm.username.trim().toLowerCase();
    if (val.length < 3) {
        usernameCheckStatus.value = { checking: false, available: false, message: 'Minimal 3 karakter' };
        return;
    }
    usernameCheckStatus.value = { checking: true, available: null, message: 'Memeriksa ketersediaan...' };
    usernameDebounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/studio/check-username?username=${encodeURIComponent(val)}`);
            const data = await res.json();
            usernameCheckStatus.value = {
                checking: false,
                available: data.available,
                message: data.message,
            };
        } catch {
            usernameCheckStatus.value = { checking: false, available: null, message: '' };
        }
    }, 400);
}

function saveUsername() {
    usernameForm.post('/studio/username', {
        preserveScroll: true,
    });
}

function saveProfile() {
    profileForm.post('/studio/profile', {
        preserveScroll: true,
    });
}

// Select Theme
function selectTheme(themeId) {
    profileForm.theme = themeId;
    saveProfile();
}

// Component Modal & Management
const showAddModal = ref(false);
const editComponentModal = ref(false);
const selectedComponent = ref(null);

const componentForm = useForm({
    type: 'social_link',
    title: '',
    url: '',
    content: '',
    settings: { icon: 'globe' },
});

const showOnboardingWelcome = ref(true);
const socialSearchQuery = ref('');
const selectedMascot = ref(1);

const allPlatforms = [
    { id: 'instagram', name: 'Instagram', icon: 'instagram', prefix: 'https://instagram.com/', placeholder: 'username_kamu', hint: 'Profil atau feed Instagram' },
    { id: 'tiktok', name: 'TikTok', icon: 'tiktok', prefix: 'https://tiktok.com/@', placeholder: 'username_kamu', hint: 'Video & persona TikTok' },
    { id: 'linkedin', name: 'LinkedIn', icon: 'linkedin', prefix: 'https://linkedin.com/in/', placeholder: 'nama-profil', hint: 'Koneksi karir & portofolio kerja' },
    { id: 'youtube', name: 'YouTube', icon: 'youtube', prefix: 'https://youtube.com/@', placeholder: 'channel_kamu', hint: 'Kanal video & kreasi visual' },
    { id: 'threads', name: 'Threads', icon: 'threads', prefix: 'https://threads.net/@', placeholder: 'username_kamu', hint: 'Diskusi & cerita singkat harian' },
    { id: 'x', name: 'X / Twitter', icon: 'x', prefix: 'https://x.com/', placeholder: 'username_kamu', hint: 'Opini, kurasi ide & obrolan' },
    { id: 'whatsapp', name: 'WhatsApp', icon: 'whatsapp', prefix: 'https://wa.me/', placeholder: '628123456789', hint: 'Obrolan personal & kontak langsung' },
    { id: 'spotify', name: 'Spotify', icon: 'spotify', prefix: 'https://open.spotify.com/', placeholder: 'user/playlist/track', hint: 'Playlist, lagu, atau podcast favorit' },
    { id: 'github', name: 'GitHub', icon: 'github', prefix: 'https://github.com/', placeholder: 'username_kamu', hint: 'Proyek open-source & repository' },
    { id: 'behance', name: 'Behance', icon: 'behance', prefix: 'https://behance.net/', placeholder: 'portofolio_kamu', hint: 'Showcase desain & visual art' },
    { id: 'dribbble', name: 'Dribbble', icon: 'dribbble', prefix: 'https://dribbble.com/', placeholder: 'profil_kamu', hint: 'Karya ilustrasi & produk desain' },
    { id: 'pinterest', name: 'Pinterest', icon: 'pinterest', prefix: 'https://pinterest.com/', placeholder: 'papan_kamu', hint: 'Koleksi inspirasi & moodboard' },
    { id: 'facebook', name: 'Facebook', icon: 'facebook', prefix: 'https://facebook.com/', placeholder: 'profil_kamu', hint: 'Komunitas & jejaring teman' },
    { id: 'website', name: 'Website Pribadi', icon: 'globe', prefix: 'https://', placeholder: 'situs-kamu.com', hint: 'Situs web atau blog pribadi' },
    { id: 'other', name: 'Tautan Lainnya', icon: 'globe', prefix: 'https://', placeholder: 'https://...', hint: 'Platform lain sesuai kebutuhanmu' },
];

const filteredSocialPlatforms = computed(() => {
    if (!socialSearchQuery.value.trim()) return allPlatforms;
    const q = socialSearchQuery.value.toLowerCase().trim();
    return allPlatforms.filter(p => p.name.toLowerCase().includes(q) || p.id.toLowerCase().includes(q) || p.hint.toLowerCase().includes(q));
});

function openAddModal(preset = null) {
    if (preset) {
        componentForm.type = 'social_link';
        componentForm.title = preset.name === 'Tautan Lainnya' ? '' : preset.name;
        componentForm.url = preset.prefix;
        componentForm.settings = { icon: preset.icon };
    } else {
        componentForm.reset();
        componentForm.type = 'social_link';
        componentForm.settings = { icon: 'globe' };
    }
    showAddModal.value = true;
}

function submitNewComponent() {
    componentForm.post('/studio/components', {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            componentForm.reset();
        },
    });
}

function openEditModal(comp) {
    selectedComponent.value = comp;
    componentForm.title = comp.title || '';
    componentForm.url = comp.url || '';
    componentForm.content = comp.content || '';
    componentForm.settings = comp.settings || { icon: 'globe' };
    editComponentModal.value = true;
}

function submitEditComponent() {
    if (!selectedComponent.value) return;
    componentForm.put(`/studio/components/${selectedComponent.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editComponentModal.value = false;
            selectedComponent.value = null;
        },
    });
}

function deleteComponent(comp) {
    if (confirm(`Hapus komponen "${comp.title}"?`)) {
        router.delete(`/studio/components/${comp.id}`, {
            preserveScroll: true,
        });
    }
}

function toggleVisibility(comp) {
    router.patch(`/studio/components/${comp.id}/toggle`, {}, {
        preserveScroll: true,
    });
}

// Move Component Order
function moveComponent(index, direction) {
    const list = [...props.profile.components];
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= list.length) return;

    const temp = list[index];
    list[index] = list[targetIndex];
    list[targetIndex] = temp;

    const order = list.map(c => c.id);

    router.post('/studio/components/reorder', { order }, {
        preserveScroll: true,
    });
}

// Gantungan Kunci LUNOU (NFC Keychain) Order & Claim
const showOrderModal = ref(false);
const showManualClaim = ref(false);
const orderSuccessModal = ref(false);
const confirmedOrderCode = ref('');
const orderImagePreview = ref('/icon/1.png');
const orderImageFile = ref(null);

const bankPayment = {
    bankName: 'blu by BCA Digital',
    accountNumber: '007377724613',
    accountHolder: 'Tri febriansah',
    normalPrice: '100.000',
    promoPrice: '49.000',
    amount: '49.000',
    whatsappAdmin: '085862319524',
};

const orderForm = useForm({
    card_variant: 'custom_keychain',
    card_printed_name: props.profile.display_name || '',
    custom_image: '/icon/1.png',
    image: null,
    phone_or_whatsapp: '',
    shipping_address: '',
    notes: 'Karakter Maskot: #1',
    payment_method: 'bank_transfer',
});

function onImageFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    orderImageFile.value = file;
    orderForm.image = file;
    orderForm.custom_image = '';
    const reader = new FileReader();
    reader.onload = (ev) => {
        orderImagePreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
}

function selectPresetIcon(num) {
    selectedMascot.value = num;
    orderImageFile.value = null;
    orderForm.image = null;
    orderImagePreview.value = `/icon/${num}.png`;
    orderForm.custom_image = `/icon/${num}.png`;
    orderForm.notes = `Karakter Maskot: #${num}`;
}

function copyText(text, label = 'Teks') {
    navigator.clipboard.writeText(text);
    alert(`${label} "${text}" berhasil disalin!`);
}

function copyOrderCode(code) {
    copyText(code, 'Kode Order');
}

function getWhatsAppUrl(orderNumber) {
    const code = orderNumber || confirmedOrderCode.value || props.nfcOrders?.[0]?.order_number || 'LUNOU-ORDER';
    const text = `hallo saya sudah pesan dengan nomor order ${code} dan bukti TF Rp 49.000 (Promo Lifetime)`;
    return `https://wa.me/6285862319524?text=${encodeURIComponent(text)}`;
}

function submitNfcOrder() {
    orderForm.post('/studio/nfc/order', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            showOrderModal.value = false;
            const flashCode = page?.props?.flash?.latest_order_number;
            const latest = props.nfcOrders && props.nfcOrders.length > 0 ? props.nfcOrders[0] : null;
            confirmedOrderCode.value = flashCode || latest?.order_number || 'LUNOU-' + Math.random().toString(36).substring(2, 8).toUpperCase();
            orderSuccessModal.value = true;
            orderForm.reset('shipping_address');
        },
    });
}

// Redeem Kode Pesanan (Untuk pesanan dari web maupun luar web / offline)
const redeemForm = useForm({
    code: '',
});

function submitRedeemCode() {
    redeemForm.post('/studio/nfc/redeem', {
        preserveScroll: true,
        onSuccess: () => {
            redeemForm.reset();
        },
    });
}

// NFC Claim Form (Opsional)
const nfcForm = useForm({
    public_id: '',
    label: '',
});

function claimNfcCard() {
    nfcForm.post('/studio/nfc/claim', {
        preserveScroll: true,
        onSuccess: () => {
            nfcForm.reset();
        },
    });
}

function generateDemoNfc() {
    router.post('/studio/nfc/demo', {}, {
        preserveScroll: true,
    });
}

function unbindNfcCard(device) {
    if (confirm(`Putuskan kartu ${device.public_id} dari akun Anda?`)) {
        router.delete(`/studio/nfc/${device.id}`, {
            preserveScroll: true,
        });
    }
}

// Copy URL
function copyPublicUrl() {
    navigator.clipboard.writeText(props.publicUrl);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
}

// Preview Theme Classes
const currentThemeClass = computed(() => {
    switch (profileForm.theme) {
        case 'sunset':
            return {
                bg: 'bg-gradient-to-br from-orange-950 via-rose-950 to-neutral-950 text-white',
                card: 'bg-rose-950/40 border border-rose-800/40 text-white hover:border-rose-500/60 shadow-md',
                accent: 'bg-gradient-to-r from-amber-500 to-rose-500 text-white',
                badge: 'bg-rose-900/60 text-rose-300 border border-rose-700/50',
            };
        case 'cyber':
            return {
                bg: 'bg-gradient-to-br from-emerald-950 via-neutral-950 to-black text-white',
                card: 'bg-emerald-950/30 border border-emerald-800/40 text-white hover:border-emerald-400/60 shadow-md',
                accent: 'bg-emerald-400 text-black hover:bg-emerald-300 font-semibold',
                badge: 'bg-emerald-900/60 text-emerald-300 border border-emerald-700/50',
            };
        case 'midnight':
            return {
                bg: 'bg-gradient-to-br from-slate-950 via-zinc-900 to-black text-white',
                card: 'bg-zinc-900/80 border border-zinc-800 text-white hover:border-cyan-500/50 shadow-md',
                accent: 'bg-cyan-500 text-black hover:bg-cyan-400 font-semibold',
                badge: 'bg-cyan-950/60 text-cyan-300 border border-cyan-800/50',
            };
        case 'clean-light':
        default:
            return {
                bg: 'bg-gradient-to-b from-slate-50 via-white to-zinc-50 text-zinc-900',
                card: 'bg-white border border-zinc-200/90 text-zinc-800 hover:border-zinc-400 shadow-sm',
                accent: 'bg-zinc-950 text-white hover:bg-black font-semibold',
                badge: 'bg-zinc-100 text-zinc-800 border border-zinc-200',
            };
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-blue-600 selection:text-white relative">
        <Head title="LUNOU Studio · Profile & Social Card Editor" />

        <!-- Distinctive Background Brand Ornaments -->
        <img
            src="/icon/1.png"
            alt=""
            class="fixed -bottom-16 -right-16 w-80 h-80 opacity-[0.03] pointer-events-none select-none"
        />

        <!-- Top Header Navigation (Nuansa Putih) -->
        <header class="h-16 border-b border-zinc-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-40 px-4 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/" class="flex items-center gap-2.5 group">
                    <ApplicationLogo className="w-8 h-8" variant="1" />
                    <div class="flex flex-col">
                        <span class="font-extrabold tracking-tight text-zinc-950 text-base leading-none">LUNOU</span>
                        <span class="text-[10px] font-bold text-blue-600 tracking-widest uppercase">Studio</span>
                    </div>
                </Link>

                <div class="hidden md:flex items-center gap-2 pl-4 border-l border-zinc-200 text-xs">
                    <span class="text-zinc-500">Tautan Publik:</span>
                    <a :href="publicUrl" target="_blank" class="font-mono text-blue-600 hover:underline flex items-center gap-1 font-semibold">
                        /{{ profile.username }}
                        <LunouIcon name="eye" className="w-3.5 h-3.5 text-blue-600" />
                    </a>
                </div>
            </div>

            <!-- Header Right Actions -->
            <div class="flex items-center gap-2 md:gap-3 flex-shrink-0">
                <button
                    @click="copyPublicUrl"
                    class="whitespace-nowrap px-3 py-1.5 rounded-xl bg-white hover:bg-zinc-50 border border-zinc-300 text-xs font-semibold text-zinc-700 flex items-center gap-1.5 shadow-sm transition-all flex-shrink-0"
                >
                    <LunouIcon :name="copied ? 'check' : 'share'" className="w-3.5 h-3.5 text-blue-600" />
                    <span>{{ copied ? 'Tersalin' : 'Bagikan' }}</span>
                </button>

                <a
                    :href="publicUrl"
                    target="_blank"
                    class="whitespace-nowrap px-3.5 py-1.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all flex-shrink-0"
                >
                    <span>Buka Profil</span>
                    <LunouIcon name="eye" className="w-3.5 h-3.5 text-zinc-300" />
                </a>

                <Link
                    v-if="$page.props.auth?.user?.role === 'super_admin'"
                    href="/admin"
                    class="whitespace-nowrap px-3 py-1.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 hover:bg-purple-100 text-xs font-bold flex-shrink-0"
                >
                    Super Admin
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 hover:bg-zinc-100 transition-colors flex-shrink-0"
                    title="Keluar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </Link>
            </div>
        </header>

        <!-- Mobile Switcher (Editor vs Live Preview) -->
        <div class="lg:hidden flex border-b border-zinc-200 bg-white text-xs font-medium sticky top-16 z-30">
            <button
                @click="mobileView = 'editor'"
                class="flex-1 py-3 text-center transition-colors"
                :class="mobileView === 'editor' ? 'text-blue-600 border-b-2 border-blue-600 font-bold bg-blue-50/40' : 'text-zinc-500'"
            >
                Studio Editor
            </button>
            <button
                @click="mobileView = 'preview'"
                class="flex-1 py-3 text-center transition-colors flex items-center justify-center gap-1.5"
                :class="mobileView === 'preview' ? 'text-blue-600 border-b-2 border-blue-600 font-bold bg-blue-50/40' : 'text-zinc-500'"
            >
                <LunouIcon name="eye" className="w-3.5 h-3.5" />
                Live Preview
            </button>
        </div>

        <!-- Main Studio Body -->
        <div class="flex-1 max-w-7xl w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-0 lg:gap-8 p-4 lg:p-8">

            <!-- LEFT COLUMN: Editor Studio Panel (7 cols on Desktop) -->
            <div
                class="lg:col-span-7 flex flex-col gap-6"
                :class="{ 'hidden lg:flex': mobileView === 'preview' }"
            >
                <!-- Welcoming Space Onboarding Card: Ruang LUNOU-ku (Section 4) -->
                <div v-if="showOnboardingWelcome" class="bg-gradient-to-br from-white via-blue-50/20 to-zinc-50 border border-blue-100/80 rounded-3xl p-6 md:p-7 shadow-xs relative overflow-hidden">
                    <img
                        src="/icon/1.png"
                        alt=""
                        class="absolute -right-6 -bottom-6 w-36 h-36 opacity-10 pointer-events-none select-none"
                    />

                    <div class="relative z-10 space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-zinc-950 text-white flex items-center justify-center shadow-sm flex-shrink-0">
                                    <ApplicationLogo className="w-6 h-6 object-contain" variant="1" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200/60">
                                            Ruang LUNOU-ku
                                        </span>
                                        <span class="text-xs text-zinc-400 font-medium">Semua tentangmu, dalam satu tempat.</span>
                                    </div>
                                    <h2 class="text-lg md:text-xl font-black text-zinc-950 tracking-tight mt-1">
                                        Ceritakan tentang dirimu.
                                    </h2>
                                    <p class="text-xs text-zinc-600 mt-1 max-w-xl leading-relaxed">
                                        Tambahkan foto, tulis bio, dan pilih hal-hal yang ingin kamu bagikan. Tidak perlu mengisi semuanya sekaligus—mulai dari apa yang kamu punya.
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="showOnboardingWelcome = false"
                                class="text-zinc-400 hover:text-zinc-700 text-xs font-semibold px-2.5 py-1 rounded-lg hover:bg-white/80 transition-colors flex-shrink-0"
                                title="Tutup sapaan ini"
                            >
                                Sembunyikan &times;
                            </button>
                        </div>

                        <!-- Quick Jump Starting Cards: Mulai dari yang kamu punya -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2">
                            <button
                                @click="activeTab = 'profile'"
                                class="p-3.5 rounded-2xl bg-white border border-zinc-200/80 hover:border-blue-500 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 group-hover:text-blue-600">01</span>
                                    <span class="w-2 h-2 rounded-full" :class="profileForm.avatar ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                </div>
                                <div class="text-xs font-bold text-zinc-900 mt-2">Foto Profil</div>
                                <div class="text-[11px] text-zinc-500 mt-0.5">Tampilkan dirimu.</div>
                            </button>

                            <button
                                @click="activeTab = 'profile'"
                                class="p-3.5 rounded-2xl bg-white border border-zinc-200/80 hover:border-blue-500 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 group-hover:text-blue-600">02</span>
                                    <span class="w-2 h-2 rounded-full" :class="profileForm.bio ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                </div>
                                <div class="text-xs font-bold text-zinc-900 mt-2">Bio Personal</div>
                                <div class="text-[11px] text-zinc-500 mt-0.5">Ceritakan kisahmu.</div>
                            </button>

                            <button
                                @click="activeTab = 'components'"
                                class="p-3.5 rounded-2xl bg-white border border-zinc-200/80 hover:border-blue-500 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 group-hover:text-blue-600">03</span>
                                    <span class="w-2 h-2 rounded-full" :class="(profile.components?.length || 0) > 0 ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                                </div>
                                <div class="text-xs font-bold text-zinc-900 mt-2">Sosial Media</div>
                                <div class="text-[11px] text-zinc-500 mt-0.5">Hubungkan akunmu.</div>
                            </button>

                            <button
                                @click="activeTab = 'themes'"
                                class="p-3.5 rounded-2xl bg-white border border-zinc-200/80 hover:border-blue-500 hover:shadow-xs transition-all text-left group"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 group-hover:text-blue-600">04</span>
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                </div>
                                <div class="text-xs font-bold text-zinc-900 mt-2">Gaya Personal</div>
                                <div class="text-[11px] text-zinc-500 mt-0.5">Pilih tampilanmu.</div>
                            </button>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-zinc-500 pt-1">
                            <span class="italic">“Silakan mulai dari mana saja. Belum siap membagikannya? Kamu bisa kembali nanti.”</span>
                            <button @click="activeTab = 'profile'" class="text-blue-600 font-bold hover:underline">
                                Lengkapi Profilku &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Studio Navigation Tabs (Nuansa Putih) -->
                <div class="flex items-center gap-1 p-1 bg-white border border-zinc-200/90 rounded-2xl overflow-x-auto text-xs font-semibold shadow-sm">
                    <button
                        @click="activeTab = 'profile'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                        :class="activeTab === 'profile' ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                    >
                        <span>Ruang Personal</span>
                    </button>
                    <button
                        @click="activeTab = 'components'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                        :class="activeTab === 'components' ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                    >
                        <span>Sosial Mediamu</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeTab === 'components' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-600'">
                            {{ profile.components?.length || 0 }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'themes'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                        :class="activeTab === 'themes' ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                    >
                        <span>Gaya Tampilan</span>
                    </button>
                    <button
                        @click="activeTab = 'nfc'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                        :class="activeTab === 'nfc' ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                    >
                        <LunouIcon name="keychain" className="w-3.5 h-3.5 text-blue-400" />
                        <span>Gantungan Kunci</span>
                    </button>
                    <button
                        @click="activeTab = 'stats'"
                        class="px-4 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap"
                        :class="activeTab === 'stats' ? 'bg-zinc-900 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'"
                    >
                        <span>Interaksi Profil</span>
                    </button>
                </div>

                <!-- TAB 1: Profil & Bio (Clean White) -->
                <div v-if="activeTab === 'profile'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 md:p-8 space-y-6 shadow-sm">
                    <div class="flex items-start justify-between border-b border-zinc-100 pb-5">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo className="w-8 h-8" variant="1" />
                            <div>
                                <h2 class="text-lg font-bold text-zinc-900 tracking-tight">Identitas Profil</h2>
                                <p class="text-xs text-zinc-500 mt-0.5">Informasi utama saat pengunjung membuka profil atau men-tap kartu NFC.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-zinc-700">Publikasi Profil:</label>
                            <input
                                type="checkbox"
                                v-model="profileForm.is_published"
                                @change="saveProfile"
                                class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 bg-white border-zinc-300 cursor-pointer"
                            />
                        </div>
                    </div>

                    <!-- Username Config -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Username LUNOU</label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <span class="absolute left-3.5 top-2.5 text-zinc-400 text-sm font-mono">lunou.id/</span>
                                <input
                                    type="text"
                                    v-model="usernameForm.username"
                                    @input="onUsernameInput"
                                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl pl-24 pr-4 py-2.5 text-sm font-mono text-zinc-900 focus:outline-none transition-all"
                                    placeholder="username-anda"
                                />
                            </div>
                            <button
                                @click="saveUsername"
                                :disabled="usernameForm.processing || usernameCheckStatus.available === false"
                                class="px-5 py-2.5 bg-zinc-900 hover:bg-black text-white font-bold text-xs rounded-xl disabled:opacity-50 transition-all whitespace-nowrap shadow-sm"
                            >
                                Simpan
                            </button>
                        </div>
                        <div v-if="usernameCheckStatus.message" class="text-xs flex items-center gap-1.5" :class="usernameCheckStatus.available ? 'text-emerald-600' : 'text-rose-600'">
                            <LunouIcon :name="usernameCheckStatus.available ? 'check' : 'trash'" className="w-3.5 h-3.5" />
                            <span>{{ usernameCheckStatus.message }}</span>
                        </div>
                        <div v-if="usernameForm.errors.username" class="text-xs text-rose-600">
                            {{ usernameForm.errors.username }}
                        </div>
                    </div>

                    <!-- Display Name & Avatar -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Nama Tampilan</label>
                            <input
                                type="text"
                                v-model="profileForm.display_name"
                                @change="saveProfile"
                                class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl px-4 py-2.5 text-sm text-zinc-900 focus:outline-none transition-all"
                                placeholder="Contoh: Tri Wicaksono"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Foto Profil (URL Gambar)</label>
                            <input
                                type="text"
                                v-model="profileForm.avatar"
                                @change="saveProfile"
                                class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl px-4 py-2.5 text-sm text-zinc-900 focus:outline-none transition-all"
                                placeholder="https://..."
                            />
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Bio Singkat</label>
                            <span class="text-[11px] text-zinc-400">{{ (profileForm.bio || '').length }}/500</span>
                        </div>
                        <textarea
                            v-model="profileForm.bio"
                            @change="saveProfile"
                            rows="3"
                            maxlength="500"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl p-3 text-sm text-zinc-900 focus:outline-none transition-all resize-none"
                            placeholder="Deskripsikan peran, minat, atau pesan utama..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            @click="saveProfile"
                            :disabled="profileForm.processing"
                            class="px-6 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold shadow-sm transition-all"
                        >
                            {{ profileForm.processing ? 'Menyimpan...' : 'Perbarui Profil' }}
                        </button>
                    </div>
                </div>

                <!-- TAB 2: Komponen Sosial & Media (Clean White) -->
                <div v-else-if="activeTab === 'components'" class="space-y-6">
                    <!-- Quick Add Toolbar & Searchable Platform Catalog (Section 5) -->
                    <div class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 tracking-tight">Katalog Media Sosial & Tautan</h3>
                                <p class="text-xs text-zinc-500">Cari platform atau logo yang ingin kamu hubungkan ke ruang profilmu</p>
                            </div>
                            <button
                                @click="openAddModal()"
                                class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all w-fit"
                            >
                                <LunouIcon name="plus" className="w-3.5 h-3.5" />
                                <span>Tautan Kustom Lainnya</span>
                            </button>
                        </div>

                        <!-- Real-time Platform Search Bar -->
                        <div class="relative">
                            <input
                                type="text"
                                v-model="socialSearchQuery"
                                class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl pl-9 pr-4 py-2.5 text-xs text-zinc-900 focus:outline-none transition-all placeholder:text-zinc-400"
                                placeholder="Cari logo platform (misal: LinkedIn, Spotify, Threads, GitHub, Behance...)"
                            />
                            <LunouIcon name="search" className="w-4 h-4 text-zinc-400 absolute left-3 top-3 pointer-events-none" />
                        </div>

                        <!-- Presets Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 max-h-60 overflow-y-auto pr-1">
                            <button
                                v-for="platform in filteredSocialPlatforms"
                                :key="platform.id"
                                @click="openAddModal(platform)"
                                class="flex flex-col items-center justify-center p-3 rounded-2xl bg-zinc-50 hover:bg-zinc-100/90 border border-zinc-200/80 hover:border-zinc-400 transition-all group text-center"
                            >
                                <div class="w-8 h-8 rounded-xl bg-white border border-zinc-200 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                    <LunouIcon :name="platform.icon" className="w-4 h-4 text-zinc-800 group-hover:text-blue-600 transition-colors" />
                                </div>
                                <span class="text-xs font-bold text-zinc-800 group-hover:text-zinc-950 mt-1.5">{{ platform.name }}</span>
                                <span class="text-[9px] text-zinc-400 truncate max-w-full px-1">{{ platform.hint }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Active Components List -->
                    <div class="bg-white border border-zinc-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
                            <h3 class="text-sm font-bold text-zinc-900">Komponen Aktif ({{ profile.components?.length || 0 }})</h3>
                            <span class="text-xs text-zinc-400">Gunakan panah untuk mengatur susunan urutan</span>
                        </div>

                        <div v-if="!profile.components || profile.components.length === 0" class="py-12 text-center text-zinc-400 space-y-3">
                            <ApplicationLogo className="w-12 h-12 mx-auto opacity-30" variant="2" />
                            <p class="text-sm">Belum ada komponen di profil Anda.</p>
                            <button @click="openAddModal()" class="text-xs text-blue-600 hover:underline font-semibold">
                                + Tambahkan komponen pertama Anda sekarang
                            </button>
                        </div>

                        <div v-else class="space-y-2.5">
                            <div
                                v-for="(comp, index) in profile.components"
                                :key="comp.id"
                                class="flex items-center justify-between p-3.5 rounded-xl bg-zinc-50/80 border border-zinc-200/90 hover:border-zinc-300 transition-all"
                                :class="{ 'opacity-50': !comp.is_visible }"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-white border border-zinc-200 flex items-center justify-center text-zinc-800 flex-shrink-0 shadow-xs">
                                        <LunouIcon :name="comp.settings?.icon || 'globe'" className="w-4 h-4 text-blue-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-semibold text-zinc-900 truncate">{{ comp.title }}</h4>
                                        <p class="text-xs text-zinc-500 truncate font-mono">{{ comp.url || comp.content || comp.type }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <!-- Move Order -->
                                    <button
                                        @click="moveComponent(index, -1)"
                                        :disabled="index === 0"
                                        class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 hover:bg-zinc-200/70 disabled:opacity-20"
                                        title="Pindah ke Atas"
                                    >
                                        <LunouIcon name="chevron-up" className="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="moveComponent(index, 1)"
                                        :disabled="index === profile.components.length - 1"
                                        class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-900 hover:bg-zinc-200/70 disabled:opacity-20"
                                        title="Pindah ke Bawah"
                                    >
                                        <LunouIcon name="chevron-down" className="w-4 h-4" />
                                    </button>

                                    <!-- Visibility Toggle -->
                                    <button
                                        @click="toggleVisibility(comp)"
                                        class="p-1.5 rounded-lg hover:bg-zinc-200/70"
                                        :class="comp.is_visible ? 'text-emerald-600' : 'text-zinc-300'"
                                        title="Tampilkan / Sembunyikan"
                                    >
                                        <LunouIcon name="eye" className="w-4 h-4" />
                                    </button>

                                    <!-- Edit -->
                                    <button
                                        @click="openEditModal(comp)"
                                        class="p-1.5 rounded-lg text-zinc-500 hover:text-blue-600 hover:bg-zinc-200/70"
                                        title="Edit Komponen"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        @click="deleteComponent(comp)"
                                        class="p-1.5 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-zinc-200/70"
                                        title="Hapus Komponen"
                                    >
                                        <LunouIcon name="trash" className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Tema & Tampilan -->
                <div v-else-if="activeTab === 'themes'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 md:p-8 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 tracking-tight">Katalog Tema Profil</h2>
                        <p class="text-xs text-zinc-500 mt-0.5">Pilih gaya visual untuk profil Anda. Tampilan di samping akan langsung terbarui secara instan.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div
                            v-for="th in themes"
                            :key="th.id"
                            @click="selectTheme(th.id)"
                            class="p-4 rounded-2xl border cursor-pointer transition-all relative overflow-hidden group"
                            :class="profileForm.theme === th.id ? 'border-zinc-950 bg-zinc-50 ring-1 ring-zinc-950' : 'border-zinc-200 bg-white hover:border-zinc-300'"
                        >
                            <div class="h-20 rounded-xl mb-3 flex items-center justify-center p-3 relative overflow-hidden border border-zinc-200/60 shadow-xs" :style="{ backgroundColor: th.previewColor }">
                                <div class="w-full flex items-center justify-between">
                                    <div class="w-8 h-8 rounded-full bg-zinc-400/20"></div>
                                    <div class="h-3 w-20 rounded-full bg-zinc-400/30"></div>
                                </div>
                                <div v-if="profileForm.theme === th.id" class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-zinc-950 text-white text-[10px] font-black uppercase tracking-wider">
                                    Aktif
                                </div>
                            </div>

                            <h3 class="font-bold text-sm text-zinc-900 group-hover:text-blue-600 transition-colors">{{ th.name }}</h3>
                            <p class="text-xs text-zinc-500 mt-1">{{ th.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Gantungan Kunci Custom LUNOU (NFC Charm) -->
                <div v-else-if="activeTab === 'nfc'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 md:p-8 shadow-sm space-y-6">
                    <div class="border-b border-zinc-100 pb-5">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold tracking-widest uppercase">
                                Produk Fisik Personal
                            </span>
                            <span class="text-xs text-zinc-400">Sentuhan NFC Instan</span>
                        </div>
                        <h2 class="text-lg font-bold text-zinc-900 tracking-tight mt-1">Gantungan Kunci Custom LUNOU</h2>
                        <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
                            Bukan sekadar kartu nama biasa. Sebuah gantungan kunci custom berkarakter dengan sentuhan chip NFC yang menghubungkan siapa pun langsung ke profil personalmu. Cukup dekatkan ke ponsel yang kompatibel tanpa perlu aplikasi tambahan.
                        </p>
                    </div>

                    <!-- HERO: Keychain Showcase & Order Trigger -->
                    <div class="p-6 rounded-2xl bg-gradient-to-br from-zinc-50 via-white to-zinc-50 border border-zinc-200 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <!-- Visual Keychain Charm Mockup -->
                            <div class="flex flex-col items-center flex-shrink-0 select-none">
                                <!-- Metal Ring Loop -->
                                <div class="w-8 h-8 rounded-full border-[3px] border-zinc-400 bg-zinc-200/50 shadow-inner flex items-center justify-center">
                                    <div class="w-3.5 h-3.5 rounded-full border border-zinc-400"></div>
                                </div>
                                <div class="w-1.5 h-4 bg-zinc-400 rounded-xs -mt-1 shadow-xs"></div>

                                <!-- Charm Body with Mascot/Initial -->
                                <div class="w-24 h-24 rounded-3xl bg-zinc-950 border border-zinc-800 text-white p-2.5 flex flex-col items-center justify-between shadow-xl -mt-0.5 relative overflow-hidden group">
                                    <div class="w-full flex items-center justify-between">
                                        <span class="text-[7px] font-mono tracking-widest text-zinc-400">NFC</span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                    </div>
                                    <img
                                        :src="`/icon/${selectedMascot || 1}.png`"
                                        alt=""
                                        class="w-10 h-10 object-contain drop-shadow-md group-hover:scale-110 transition-transform"
                                    />
                                    <div class="w-full text-center truncate">
                                        <div class="text-[8px] font-black truncate leading-tight">{{ profile.display_name }}</div>
                                        <div class="text-[6px] font-mono text-zinc-400 truncate">@{{ profile.username }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="text-[10px] font-mono uppercase tracking-widest text-blue-600 font-bold">Small object. Personal meaning. Easy connection.</div>
                                <h3 class="text-sm font-bold text-zinc-900">Bawa Identitasmu ke Dunia Nyata</h3>
                                <p class="text-xs text-zinc-500 leading-relaxed max-w-md">
                                    Pilih karakter maskot favoritmu atau ukir inisial namamu. Saat bertemu orang baru, cukup sentuhkan gantungan kunci ke ponsel mereka untuk membuka semua ceritamu.
                                </p>
                                <div class="flex items-center gap-3 pt-1 text-[11px] text-zinc-600">
                                    <span class="flex items-center gap-1">
                                        <LunouIcon name="check" className="w-3.5 h-3.5 text-emerald-600" />
                                        Chip NTAG216 Tahan Air
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <LunouIcon name="check" className="w-3.5 h-3.5 text-emerald-600" />
                                        Tanpa Baterai
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="showOrderModal = true"
                            class="w-full md:w-auto px-6 py-3 rounded-2xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2 flex-shrink-0"
                        >
                            <LunouIcon name="keychain" className="w-4 h-4 text-blue-400" />
                            <span>Pesan Gantungan Kunci Custom</span>
                        </button>
                    </div>

                    <!-- STATUS PESANAN / KODE ORDER (Tinggal Pantau) -->
                    <div v-if="nfcOrders.length > 0" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-zinc-700 uppercase tracking-wider">
                                Pesanan Gantungan Kunci & Kode Order ({{ nfcOrders.length }})
                            </h4>
                            <span class="text-xs text-zinc-500">Tinggal pantau status pesananmu</span>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="order in nfcOrders"
                                :key="order.id"
                                class="p-5 rounded-2xl bg-white border border-zinc-200/90 shadow-sm space-y-4"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 pb-3">
                                    <div class="flex items-center gap-3.5">
                                        <!-- Mini Keychain Graphic -->
                                        <div class="w-14 h-14 rounded-2xl bg-zinc-50 border border-zinc-200 p-1.5 flex items-center justify-center flex-shrink-0 shadow-inner relative">
                                            <img
                                                :src="order.custom_image || '/icon/1.png'"
                                                alt="Desain Gambar Gantungan Kunci"
                                                class="w-full h-full object-contain"
                                            />
                                        </div>

                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-[11px] font-bold text-zinc-500">Nomor Order / Kode Booking:</span>
                                                <span class="font-mono font-black text-xs text-zinc-950 bg-zinc-100 px-2.5 py-0.5 rounded-lg border border-zinc-200">
                                                    {{ order.order_number }}
                                                </span>
                                                <button
                                                    @click="copyOrderCode(order.order_number)"
                                                    class="text-[10px] text-blue-600 font-bold hover:underline"
                                                    title="Salin Kode Order"
                                                >
                                                    Salin Kode
                                                </button>
                                            </div>
                                            <div class="text-xs text-zinc-700 mt-1">
                                                Nama Cetak: <strong class="text-zinc-950 font-bold">{{ order.card_printed_name }}</strong>
                                            </div>
                                            <div class="text-[10px] text-zinc-400 font-mono mt-0.5">
                                                Dipesan: {{ new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:items-end">
                                        <span
                                            v-if="order.status === 'completed'"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5 w-fit"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Selesai & Gantungan Kunci Aktif
                                        </span>
                                        <span
                                            v-else-if="order.status === 'processing'"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1.5 w-fit"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            Diverifikasi Admin & Sedang Dibuat
                                        </span>
                                        <span
                                            v-else
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1.5 w-fit"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Menunggu Bukti TF & Verifikasi Admin
                                        </span>
                                        <span class="text-[11px] font-mono font-bold text-zinc-600 mt-1">
                                            Rp {{ Number(order.amount).toLocaleString('id-ID') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Detail Transfer & Action WA (Jika Belum Terverifikasi) -->
                                <div v-if="order.status === 'pending'" class="bg-amber-50/50 rounded-xl p-3.5 border border-amber-200/80 space-y-2.5 text-xs">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-200/50 pb-2">
                                        <div>
                                            <span class="text-[10px] font-bold uppercase text-amber-800 tracking-wider block">Rekening Pembayaran:</span>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <strong class="font-mono text-zinc-950 text-xs">{{ bankPayment.bankName }} · {{ bankPayment.accountNumber }}</strong>
                                                <span class="text-zinc-600">a/n {{ bankPayment.accountHolder }}</span>
                                                <button
                                                    @click="copyText(bankPayment.accountNumber, 'Nomor Rekening')"
                                                    class="text-[10px] text-blue-700 font-bold hover:underline"
                                                >
                                                    Salin Rekening
                                                </button>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[10px] text-zinc-500 block">Total Nominal:</span>
                                            <strong class="font-mono text-zinc-950 font-bold">Rp {{ Number(order.amount).toLocaleString('id-ID') }}</strong>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <span class="text-[11px] text-amber-900 leading-relaxed">
                                            Sudah transfer? Kirimkan bukti transfer ke WhatsApp admin. Admin akan memverifikasi pesananmu agar kode aktif.
                                        </span>
                                        <a
                                            :href="getWhatsAppUrl(order.order_number)"
                                            target="_blank"
                                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] flex items-center justify-center gap-2 w-full sm:w-auto flex-shrink-0 shadow-xs transition-all"
                                        >
                                            <LunouIcon name="whatsapp" className="w-4 h-4" />
                                            <span>Kirim Bukti TF ke WA Admin</span>
                                        </a>
                                    </div>
                                </div>

                                <div v-else-if="order.status === 'processing'" class="bg-blue-50/60 rounded-xl p-3 border border-blue-200/70 flex items-center gap-2.5 text-xs text-blue-900">
                                    <div class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[9px] flex-shrink-0">✓</div>
                                    <span class="text-[11px] leading-relaxed">
                                        Pembayaran telah diverifikasi! Admin sedang menyiapkan fisik gantungan kunci dan memprogram chip NFC untuk profilmu. Tinggal pantau hingga selesai.
                                    </span>
                                </div>

                                <div v-else class="bg-emerald-50/60 rounded-xl p-3 border border-emerald-200/70 flex items-center gap-2.5 text-xs text-emerald-900">
                                    <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-[9px] flex-shrink-0">✓</div>
                                    <span class="text-[11px] leading-relaxed">
                                        Gantungan kunci telah selesai dibuat dan aktif terhubung ke profilmu.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD: REDEEM KODE PESANAN (UNTUK PESANAN LUAR WEB / DARI WA / OFFLINE) -->
                    <div class="p-6 rounded-2xl bg-white border border-zinc-200/90 shadow-sm space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-zinc-950 text-white flex items-center justify-center flex-shrink-0">
                                <LunouIcon name="keychain" className="w-5 h-5 text-blue-400" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-zinc-950 tracking-tight">Redeem Kode Pesanan / Aktivasi Gantungan Kunci</h4>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Pesan gantungan kunci di luar web (melalui WhatsApp, offline event, atau marketplace)? Masukkan Kode Order / Kode Booking yang diberikan Admin di sini untuk langsung menghubungkannya ke profilmu.
                                </p>
                            </div>
                        </div>

                        <form @submit.prevent="submitRedeemCode" class="space-y-2">
                            <div class="flex flex-col sm:flex-row items-center gap-2.5">
                                <div class="relative flex-1 w-full">
                                    <input
                                        type="text"
                                        v-model="redeemForm.code"
                                        placeholder="Masukkan Kode Pesanan (Contoh: LUNOU-261010-XXXX atau YOIMO-ABC123)"
                                        class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-900 rounded-xl px-4 py-3 text-xs font-mono font-bold text-zinc-900 uppercase focus:outline-none transition-all"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="redeemForm.processing || !redeemForm.code"
                                    class="w-full sm:w-auto px-6 py-3 bg-zinc-950 hover:bg-black text-white font-bold text-xs rounded-xl disabled:opacity-50 transition-all shadow-sm whitespace-nowrap flex items-center justify-center gap-2"
                                >
                                    <span>{{ redeemForm.processing ? 'Memproses...' : 'Aktivasi Gantungan Kunci' }}</span>
                                </button>
                            </div>
                            <div v-if="redeemForm.errors.code" class="text-xs text-rose-600 font-medium pt-1">
                                {{ redeemForm.errors.code }}
                            </div>
                        </form>
                    </div>

                    <!-- GANTUNGAN KUNCI TERHUBUNG -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-zinc-700 uppercase tracking-wider">Gantungan Kunci Terhubung ke Akunmu ({{ nfcDevices.length }})</h4>
                        </div>
                        <div v-if="nfcDevices.length === 0" class="p-6 text-center text-zinc-400 text-xs border border-dashed border-zinc-200 rounded-xl space-y-2">
                            <p>Belum ada gantungan kunci NFC aktif yang tersambung ke profil ini.</p>
                            <p class="text-[11px] text-zinc-400">Pesan sekarang atau masukkan kode aktivasi manual jika kamu memilikinya dari acara khusus.</p>
                        </div>
                        <div
                            v-for="dev in nfcDevices"
                            :key="dev.id"
                            class="flex items-center justify-between p-4 rounded-xl bg-white border border-zinc-200/90 shadow-xs"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-zinc-100 flex items-center justify-center text-zinc-800">
                                    <LunouIcon name="keychain" className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-zinc-900 text-sm">{{ dev.public_id }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ dev.status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-zinc-500">{{ dev.label || 'Gantungan Kunci LUNOU' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="unbindNfcCard(dev)"
                                    class="text-xs text-zinc-400 hover:text-rose-600 p-2"
                                    title="Lepas gantungan kunci"
                                >
                                    <LunouIcon name="trash" className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- OPSIONAL: Hubungkan Manual (Khusus Kartu Fisik dari Event / Kode Voucher) -->
                    <div class="border-t border-zinc-100 pt-4">
                        <button
                            @click="showManualClaim = !showManualClaim"
                            class="text-xs text-zinc-500 hover:text-zinc-900 font-medium flex items-center gap-1.5"
                        >
                            <span>{{ showManualClaim ? 'Tutup Opsi Manual' : 'Punya kartu fisik dari event / acara? Masukkan kode aktivasi manual' }}</span>
                            <svg class="w-3.5 h-3.5" :class="{ 'rotate-180': showManualClaim }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"></path></svg>
                        </button>

                        <div v-if="showManualClaim" class="mt-3 bg-zinc-50 border border-zinc-200 rounded-xl p-4 space-y-3">
                            <h5 class="text-xs font-bold text-zinc-700 uppercase tracking-wider">Aktivasi Kode Kartu Manual</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input
                                    type="text"
                                    v-model="nfcForm.public_id"
                                    class="bg-white border border-zinc-200 focus:border-zinc-900 rounded-xl px-3.5 py-2.5 text-sm text-zinc-900 font-mono uppercase focus:outline-none"
                                    placeholder="Contoh: YOIMO-ABC123"
                                />
                                <input
                                    type="text"
                                    v-model="nfcForm.label"
                                    class="bg-white border border-zinc-200 focus:border-zinc-900 rounded-xl px-3.5 py-2.5 text-sm text-zinc-900 focus:outline-none"
                                    placeholder="Label kartu (misal: Kartu Bisnis)"
                                />
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <button
                                    @click="generateDemoNfc"
                                    class="text-xs text-blue-600 hover:underline font-semibold"
                                >
                                    + Buat Kartu Demo untuk Uji Coba
                                </button>
                                <button
                                    @click="claimNfcCard"
                                    :disabled="nfcForm.processing || !nfcForm.public_id"
                                    class="px-5 py-2.5 bg-zinc-950 hover:bg-black text-white font-bold text-xs rounded-xl disabled:opacity-50 transition-all shadow-sm"
                                >
                                    Hubungkan Kartu
                                </button>
                            </div>
                            <div v-if="nfcForm.errors.public_id" class="text-xs text-rose-600">
                                {{ nfcForm.errors.public_id }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Statistik Profil -->
                <div v-else-if="activeTab === 'stats'" class="bg-white border border-zinc-200/90 rounded-2xl p-6 md:p-8 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 tracking-tight">Statistik & Kunjungan</h2>
                        <p class="text-xs text-zinc-500 mt-0.5">Pantau interaksi pada profil dan kartu NFC pintar Anda.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-5 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-1">
                            <span class="text-xs text-zinc-500 font-semibold">Total Kunjungan Profil</span>
                            <div class="text-3xl font-extrabold text-zinc-950 tracking-tight">{{ stats.views || 0 }}</div>
                            <span class="text-[11px] text-zinc-400">Tayangan halaman publik</span>
                        </div>
                        <div class="p-5 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-1">
                            <span class="text-xs text-zinc-500 font-semibold">Total Klik Komponen</span>
                            <div class="text-3xl font-extrabold text-blue-600 tracking-tight">{{ stats.clicks || 0 }}</div>
                            <span class="text-[11px] text-zinc-400">Interaksi tautan media sosial</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Interactive Smartphone Live Preview (5 cols on Desktop) -->
            <div
                class="lg:col-span-5 flex flex-col items-center justify-start sticky top-24"
                :class="{ 'hidden lg:flex': mobileView === 'editor' }"
            >
                <!-- Phone Frame Container in Clean White / Light Titanium Style -->
                <div class="w-full max-w-[340px] rounded-[44px] p-3 bg-white border-4 border-zinc-200 shadow-xl relative">
                    <!-- Speaker & Dynamic Notch -->
                    <div class="absolute top-6 left-1/2 -translate-x-1/2 w-28 h-4 bg-zinc-900 rounded-full z-20 flex items-center justify-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-zinc-800"></div>
                    </div>

                    <!-- Inner Phone Screen -->
                    <div
                        class="w-full h-[600px] rounded-[36px] overflow-y-auto p-5 pt-12 flex flex-col items-center text-center transition-all duration-300 scrollbar-none relative"
                        :class="currentThemeClass.bg"
                    >
                        <!-- Floating Brand Watermark Ornament -->
                        <ApplicationLogo className="absolute top-12 right-4 w-6 h-6 opacity-30 pointer-events-none" variant="1" />

                        <!-- Avatar -->
                        <div class="relative group mt-2 mb-3">
                            <img
                                :src="profileForm.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80'"
                                alt="Avatar"
                                class="w-20 h-20 rounded-full object-cover border-2 border-white/60 shadow-md"
                            />
                            <div v-if="profileForm.is_published" class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white"></div>
                        </div>

                        <!-- Name & Username -->
                        <h3 class="font-bold text-base tracking-tight leading-snug">{{ profileForm.display_name || 'Nama Anda' }}</h3>
                        <p class="text-xs opacity-70 font-mono mt-0.5">@{{ usernameForm.username || profile.username }}</p>

                        <!-- Bio -->
                        <p class="text-xs mt-2.5 opacity-80 leading-relaxed px-2 line-clamp-3">
                            {{ profileForm.bio || 'Tulis bio singkat di editor untuk memperkenalkan diri Anda.' }}
                        </p>

                        <!-- Components Stack Preview -->
                        <div class="w-full mt-6 space-y-2.5 pb-6">
                            <template v-for="comp in profile.components" :key="comp.id">
                                <div
                                    v-if="comp.is_visible"
                                    class="w-full py-2.5 px-3.5 rounded-xl transition-all flex items-center justify-between text-left text-xs font-semibold cursor-pointer"
                                    :class="comp.type === 'featured_link' ? currentThemeClass.accent : currentThemeClass.card"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <LunouIcon :name="comp.settings?.icon || 'globe'" className="w-4 h-4 flex-shrink-0" />
                                        <span class="truncate">{{ comp.title }}</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 opacity-40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </div>
                            </template>

                            <div v-if="!profile.components || profile.components.length === 0" class="py-8 text-center text-xs opacity-40">
                                Komponen akan muncul di sini secara real-time.
                            </div>
                        </div>

                        <!-- Bottom Brand Watermark -->
                        <div class="mt-auto pt-4 pb-2 flex items-center gap-1.5 opacity-50">
                            <ApplicationLogo className="w-3.5 h-3.5" variant="1" />
                            <span class="text-[10px] font-bold tracking-widest uppercase font-mono">LUNOU · NFC READY</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 text-xs text-zinc-500">
                    <span>Live Responsive Preview</span>
                    <span>·</span>
                    <a :href="publicUrl" target="_blank" class="text-blue-600 hover:underline font-semibold">Buka Tab Penuh &rarr;</a>
                </div>
            </div>

        </div>

        <!-- MODAL: Tambah Komponen (Nuansa Putih) -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-5 h-5" variant="1" />
                        <h3 class="text-base font-bold text-zinc-900">Tambah Komponen Profil</h3>
                    </div>
                    <button @click="showAddModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Tipe Komponen</label>
                        <select
                            v-model="componentForm.type"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        >
                            <option value="social_link">Tautan Media Sosial</option>
                            <option value="featured_link">Tautan Unggulan (Card Highlight)</option>
                            <option value="contact">Kontak Langsung</option>
                            <option value="text">Catatan / Teks</option>
                            <option value="heading">Heading / Pemisah</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Judul / Label</label>
                        <input
                            type="text"
                            v-model="componentForm.title"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Contoh: Instagram Official"
                        />
                    </div>

                    <div v-if="componentForm.type !== 'heading' && componentForm.type !== 'text'">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">URL / Link Tujuan</label>
                        <input
                            type="text"
                            v-model="componentForm.url"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                            placeholder="https://..."
                        />
                    </div>

                    <div v-if="componentForm.type === 'text'">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Isi Pesan / Konten</label>
                        <textarea
                            v-model="componentForm.content"
                            rows="3"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none resize-none"
                            placeholder="Tuliskan catatan di sini..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Ikon Representasi</label>
                        <select
                            v-model="componentForm.settings.icon"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        >
                            <option value="globe">Tautan Umum (Globe)</option>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="youtube">YouTube</option>
                            <option value="threads">Threads</option>
                            <option value="x">X / Twitter</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="spotify">Spotify</option>
                            <option value="github">GitHub</option>
                            <option value="behance">Behance</option>
                            <option value="dribbble">Dribbble</option>
                            <option value="pinterest">Pinterest</option>
                            <option value="facebook">Facebook</option>
                            <option value="keychain">Gantungan Kunci LUNOU</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showAddModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold hover:bg-zinc-200">
                        Batal
                    </button>
                    <button
                        @click="submitNewComponent"
                        :disabled="componentForm.processing || !componentForm.title"
                        class="px-5 py-2 rounded-xl bg-zinc-900 hover:bg-black text-white text-xs font-bold disabled:opacity-50"
                    >
                        {{ componentForm.processing ? 'Menyimpan...' : 'Simpan Komponen' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: Edit Komponen (Nuansa Putih) -->
        <div v-if="editComponentModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <h3 class="text-base font-bold text-zinc-900">Edit Komponen</h3>
                    <button @click="editComponentModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Judul / Label</label>
                        <input
                            type="text"
                            v-model="componentForm.title"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        />
                    </div>

                    <div v-if="selectedComponent?.type !== 'heading' && selectedComponent?.type !== 'text'">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">URL / Link Tujuan</label>
                        <input
                            type="text"
                            v-model="componentForm.url"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                        />
                    </div>

                    <div v-if="selectedComponent?.type === 'text'">
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Isi Konten</label>
                        <textarea
                            v-model="componentForm.content"
                            rows="3"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase mb-1">Ikon</label>
                        <select
                            v-model="componentForm.settings.icon"
                            class="w-full bg-zinc-50 border border-zinc-200 rounded-xl p-2.5 text-xs text-zinc-900 focus:outline-none"
                        >
                            <option value="globe">Tautan Umum (Globe)</option>
                            <option value="instagram">Instagram</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="youtube">YouTube</option>
                            <option value="threads">Threads</option>
                            <option value="x">X / Twitter</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="spotify">Spotify</option>
                            <option value="github">GitHub</option>
                            <option value="behance">Behance</option>
                            <option value="dribbble">Dribbble</option>
                            <option value="pinterest">Pinterest</option>
                            <option value="facebook">Facebook</option>
                            <option value="keychain">Gantungan Kunci LUNOU</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="editComponentModal = false" class="px-4 py-2 rounded-xl bg-zinc-100 text-zinc-600 text-xs font-semibold">
                        Batal
                    </button>
                    <button
                        @click="submitEditComponent"
                        :disabled="componentForm.processing || !componentForm.title"
                        class="px-5 py-2 rounded-xl bg-zinc-900 hover:bg-black text-white text-xs font-bold disabled:opacity-50"
                    >
                        {{ componentForm.processing ? 'Menyimpan...' : 'Perbarui' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: Pesan Gantungan Kunci Custom LUNOU (Upload Image & Live Mockup) -->
        <div v-if="showOrderModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white border border-zinc-200 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-6 shadow-2xl text-zinc-900 my-8">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-zinc-950 text-white flex items-center justify-center">
                            <LunouIcon name="keychain" className="w-5 h-5 text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 leading-tight">Pesan Gantungan Kunci Custom LUNOU</h3>
                            <p class="text-[11px] text-zinc-500">Upload desainmu & bawa identitas digitalmu ke dunia nyata</p>
                        </div>
                    </div>
                    <button @click="showOrderModal = false" class="text-zinc-400 hover:text-zinc-700 text-2xl font-bold">&times;</button>
                </div>

                <!-- REALISTIC GANTUNGAN KUNCI PREVIEW -->
                <div class="flex flex-col items-center justify-center py-2 select-none bg-zinc-50/70 border border-zinc-200/80 rounded-2xl p-4">
                    <!-- Metal Ring Loop -->
                    <div class="w-12 h-12 rounded-full border-[5px] border-zinc-400 bg-zinc-200/60 shadow-inner flex items-center justify-center relative">
                        <div class="w-5 h-5 rounded-full border-2 border-zinc-400"></div>
                        <div class="absolute -bottom-1 w-2.5 h-2.5 rounded-full bg-zinc-400"></div>
                    </div>
                    <!-- Chain Links -->
                    <div class="flex flex-col items-center -space-y-1 my-0.5">
                        <div class="w-2.5 h-3.5 border-2 border-zinc-400 rounded-full bg-zinc-300"></div>
                        <div class="w-2.5 h-3.5 border-2 border-zinc-400 rounded-full bg-zinc-300"></div>
                    </div>

                    <!-- Keychain Charm Body -->
                    <div class="w-48 h-56 rounded-[32px] p-4 bg-white border-2 border-zinc-200/90 shadow-2xl shadow-zinc-900/10 flex flex-col items-center justify-between relative overflow-hidden group">
                        <!-- Top Charm Hole & NFC Indicator -->
                        <div class="w-full flex items-center justify-between px-1">
                            <div class="w-3.5 h-3.5 rounded-full border-2 border-zinc-300 bg-zinc-100 mx-auto -mt-1 shadow-inner"></div>
                        </div>
                        <div class="absolute top-3 right-3 flex items-center gap-1 bg-zinc-100/90 border border-zinc-200/80 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                            <span class="text-[8px] font-mono font-bold text-zinc-600">NFC</span>
                        </div>

                        <!-- Custom Uploaded Image / Mascot Graphic -->
                        <div class="w-28 h-28 rounded-2xl bg-zinc-50 border border-zinc-200/80 flex items-center justify-center p-2 overflow-hidden shadow-inner relative group-hover:scale-105 transition-transform">
                            <img
                                :src="orderImagePreview || '/icon/1.png'"
                                alt="Desain Gambar Gantungan Kunci"
                                class="w-full h-full object-contain"
                            />
                        </div>

                        <!-- Printed Engraved Name & Username -->
                        <div class="w-full text-center pb-1">
                            <div class="text-xs font-black text-zinc-950 truncate tracking-tight">
                                {{ orderForm.card_printed_name || profile.display_name }}
                            </div>
                            <div class="text-[10px] font-mono text-zinc-400 truncate mt-0.5">
                                @{{ profile.username }} · LUNOU CHARM
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] text-zinc-500 mt-2 font-mono">Pratinjau Gantungan Kunci Custom-mu</span>
                </div>

                <!-- ORDER FORM FIELDS -->
                <div class="space-y-4">
                    <!-- 1. UPLOAD IMAGE UNTUK GAMBAR GANTUNGAN KUNCI -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-zinc-800 uppercase tracking-wider">
                                1. Upload Gambar / Desain Gantungan Kunci
                            </label>
                            <span v-if="orderImageFile" class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                <LunouIcon name="check" className="w-3 h-3" />
                                File Dipilih
                            </span>
                        </div>

                        <label
                            for="keychain-image-file-input"
                            class="border-2 border-dashed border-zinc-300 hover:border-blue-500 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 cursor-pointer bg-zinc-50 hover:bg-blue-50/30 transition-all text-center group"
                        >
                            <input
                                id="keychain-image-file-input"
                                type="file"
                                accept="image/*"
                                @change="onImageFileChange"
                                class="hidden"
                            />
                            <div class="w-10 h-10 rounded-xl bg-white border border-zinc-200 flex items-center justify-center shadow-xs text-blue-600 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-zinc-900 block">
                                    {{ orderImageFile ? orderImageFile.name : 'Klik untuk upload gambar / foto dari perangkat' }}
                                </span>
                                <span class="text-[10px] text-zinc-500 block mt-0.5">Mendukung format JPG, PNG, WebP (maks. 5MB)</span>
                            </div>
                        </label>

                        <!-- Preset Mascot Icons as Alternative -->
                        <div class="pt-1">
                            <span class="text-[11px] text-zinc-500 block mb-1.5">Atau gunakan karakter maskot resmi LUNOU:</span>
                            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                                <button
                                    v-for="num in [1, 2, 3, 5, 7, 10, 14, 18]"
                                    :key="num"
                                    type="button"
                                    @click="selectPresetIcon(num)"
                                    class="w-10 h-10 rounded-xl p-1 border transition-all flex-shrink-0"
                                    :class="selectedMascot === num && !orderImageFile ? 'border-zinc-950 bg-zinc-100 ring-2 ring-zinc-950/20 scale-105' : 'border-zinc-200 bg-white hover:border-zinc-300'"
                                    :title="`Maskot #${num}`"
                                >
                                    <img :src="`/icon/${num}.png`" alt="" class="w-full h-full object-contain" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Custom Printed Name -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">
                            2. Nama yang Dicetak pada Gantungan Kunci
                        </label>
                        <input
                            type="text"
                            v-model="orderForm.card_printed_name"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:border-zinc-900 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Contoh: Tri Wicaksono"
                        />
                        <div v-if="orderForm.errors.card_printed_name" class="text-xs text-rose-600 mt-1">{{ orderForm.errors.card_printed_name }}</div>
                    </div>

                    <!-- 3. WhatsApp / Contact -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">
                            3. Nomor WhatsApp / Kontak (Konfirmasi & Tracking)
                        </label>
                        <input
                            type="tel"
                            v-model="orderForm.phone_or_whatsapp"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:border-zinc-900 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none font-mono"
                            placeholder="Contoh: 08123456789"
                        />
                        <div v-if="orderForm.errors.phone_or_whatsapp" class="text-xs text-rose-600 mt-1">{{ orderForm.errors.phone_or_whatsapp }}</div>
                    </div>

                    <!-- 4. Shipping Address -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">
                            4. Alamat Lengkap Pengiriman
                        </label>
                        <textarea
                            v-model="orderForm.shipping_address"
                            rows="2"
                            class="w-full bg-zinc-50 border border-zinc-200 focus:border-zinc-900 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none"
                            placeholder="Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan, Kota & Kode Pos"
                        ></textarea>
                        <div v-if="orderForm.errors.shipping_address" class="text-xs text-rose-600 mt-1">{{ orderForm.errors.shipping_address }}</div>
                    </div>

                    <!-- 5. Payment Method & Transfer Instruction -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            5. Metode Pembayaran & Rekening Transfer
                        </label>

                        <!-- Box Rekening Bank Resmi -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/90 space-y-2.5">
                            <div class="flex items-center justify-between border-b border-zinc-200/60 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <span class="text-xs font-bold text-zinc-950">{{ bankPayment.bankName }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] text-zinc-400 line-through font-mono">Rp {{ bankPayment.normalPrice }}</span>
                                    <span class="text-[11px] font-mono font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        Rp {{ bankPayment.amount }} (Promo)
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="text-[10px] text-zinc-500 uppercase tracking-wider block">Nomor Rekening:</span>
                                    <span class="font-mono font-black text-sm text-zinc-950 tracking-wider">{{ bankPayment.accountNumber }}</span>
                                    <span class="text-[11px] text-zinc-600 block">a/n {{ bankPayment.accountHolder }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click="copyText(bankPayment.accountNumber, 'Nomor Rekening')"
                                    class="px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-black text-white text-[11px] font-bold transition-all shadow-xs"
                                >
                                    Salin Rekening
                                </button>
                            </div>

                            <p class="text-[11px] text-zinc-500 leading-relaxed pt-1 border-t border-zinc-200/60">
                                Setelah order dibuat, kirimkan bukti transfer ke WhatsApp admin <strong>085862319524</strong> agar pesananmu segera diverifikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PRICE & SUBMIT -->
                <div class="border-t border-zinc-100 pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-zinc-500 block">Total Investasi Gantungan Kunci NFC:</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold">
                                PROMO LIFETIME
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <span class="text-lg font-black text-zinc-950 font-mono">Rp {{ bankPayment.amount }}</span>
                            <span class="text-xs text-zinc-400 line-through font-mono">Rp {{ bankPayment.normalPrice }}</span>
                            <span class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">Sekali Bayar Selamanya</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="showOrderModal = false"
                            class="px-4 py-2.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 text-xs font-semibold"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitNfcOrder"
                            :disabled="orderForm.processing || !orderForm.card_printed_name || !orderForm.phone_or_whatsapp || !orderForm.shipping_address"
                            class="px-6 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold disabled:opacity-50 transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <span>{{ orderForm.processing ? 'Mengirim...' : 'Kirim Pesanan Gantungan Kunci' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: KODE ORDER BERHASIL DIBUAT (PENGALIHAN WA & TINGGAL PANTAU) -->
        <div v-if="orderSuccessModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white border border-zinc-200 rounded-3xl max-w-md w-full p-6 md:p-8 space-y-5 shadow-2xl text-zinc-900 text-center animate-in fade-in duration-300 my-8">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto">
                    <LunouIcon name="check" className="w-7 h-7" />
                </div>

                <div class="space-y-1">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/80">
                        Pesanan Berhasil Tercatat
                    </span>
                    <h3 class="text-lg font-black text-zinc-950 tracking-tight pt-1">
                        Selesaikan Transfer & Kirim Bukti
                    </h3>
                    <p class="text-xs text-zinc-500 leading-relaxed max-w-sm mx-auto">
                        Silakan transfer sesuai nominal, lalu kirim bukti transfer ke WhatsApp admin. Setelah itu kamu tinggal memantau status pesanan hingga aktif!
                    </p>
                </div>

                <!-- Highlight Box Kode Order / Booking -->
                <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-1 text-left">
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block">Nomor Order / Kode Booking:</span>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-black text-base text-zinc-950 tracking-tight">
                            {{ confirmedOrderCode || (nfcOrders[0]?.order_number || 'LUNOU-ORDER') }}
                        </span>
                        <button
                            @click="copyOrderCode(confirmedOrderCode || (nfcOrders[0]?.order_number || 'LUNOU-ORDER'))"
                            class="px-3 py-1 rounded-lg bg-zinc-950 text-white text-[11px] font-bold hover:bg-black transition-all flex items-center gap-1 shadow-xs"
                        >
                            <span>Salin Kode</span>
                        </button>
                    </div>
                </div>

                <!-- Highlight Box Rekening Pembayaran (blu BCA Digital) -->
                <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-200 text-left space-y-2">
                    <div class="flex items-center justify-between border-b border-blue-200/60 pb-1.5">
                        <span class="text-xs font-bold text-blue-950">{{ bankPayment.bankName }}</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-zinc-400 line-through font-mono">Rp {{ bankPayment.normalPrice }}</span>
                            <span class="font-mono font-black text-xs text-emerald-700">Rp {{ bankPayment.amount }} (Promo Lifetime)</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <span class="text-[10px] text-blue-700 font-bold uppercase tracking-wider block">No. Rekening:</span>
                            <span class="font-mono font-black text-sm text-zinc-950 tracking-wider">{{ bankPayment.accountNumber }}</span>
                            <span class="text-[11px] text-zinc-600 block">a/n {{ bankPayment.accountHolder }}</span>
                        </div>
                        <button
                            type="button"
                            @click="copyText(bankPayment.accountNumber, 'Nomor Rekening')"
                            class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition-all shadow-xs"
                        >
                            Salin Rekening
                        </button>
                    </div>

                    <div class="pt-1.5 border-t border-blue-200/60 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        <span class="text-[11px] font-bold text-amber-900">
                            Status: Menunggu Pembayaran & Verifikasi Admin (Tinggal Pantau)
                        </span>
                    </div>
                </div>

                <div class="space-y-2 pt-1">
                    <!-- Tombol Utama Kirim Bukti TF ke WhatsApp Admin -->
                    <a
                        :href="getWhatsAppUrl(confirmedOrderCode)"
                        target="_blank"
                        class="w-full py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-md"
                    >
                        <LunouIcon name="whatsapp" className="w-4 h-4" />
                        <span>Kirim Bukti TF ke WhatsApp Admin (085862319524)</span>
                    </a>

                    <button
                        @click="orderSuccessModal = false; activeTab = 'nfc'"
                        class="w-full py-2.5 rounded-2xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-bold text-xs transition-all"
                    >
                        Tutup & Pantau di Tab Gantungan Kunci
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
