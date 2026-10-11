<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    hasAccess: {
        type: Boolean,
        default: false,
    },
    accessStatus: {
        type: String,
        default: 'not_subscribed', // 'not_subscribed', 'pending_payment', 'under_review', 'active', 'expired'
    },
    entitlement: {
        type: Object,
        default: null,
    },
    latestOrder: {
        type: Object,
        default: null,
    },
    plans: {
        type: Array,
        default: () => [],
    },
    activePlan: {
        type: Object,
        default: null,
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    termsAndConditions: {
        type: String,
        default: '',
    },
    portfolio: {
        type: Object,
        default: null,
    },
    publicUrl: {
        type: String,
        default: null,
    },
    freeModeEnabled: {
        type: Boolean,
        default: false,
    },
    saweriaUrl: {
        type: String,
        default: 'https://saweria.co/Try3',
    },
});

// ==========================================
// 1. STATE & MODALS
// ==========================================
const activeSidebarTab = ref('elements'); // 'elements', 'icons', 'templates', 'projects', 'pdf'
const iconSearchQuery = ref('');
const selectedPageIndex = ref(0);
const previewDevice = ref('desktop'); // 'desktop', 'tablet', 'mobile'
const isSettingsModalOpen = ref(false);
const isAddPageModalOpen = ref(false);
const isUploadPdfModalOpen = ref(false);
const isUploadImageModalOpen = ref(false);
const isHelpGuideModalOpen = ref(false);
const helpGuideTab = ref('hp'); // 'hp', 'pc', 'prestasi', 'rekomendasi'
const dismissHelperBanner = ref(false);
const isMobileQuickAddOpen = ref(false);
const saveStatus = ref('saved'); // 'saved', 'saving', 'unsaved'
const copiedText = ref(null);

// Local mutable portfolio & pages copy
const localPortfolio = ref(props.portfolio ? JSON.parse(JSON.stringify(props.portfolio)) : {
    title: 'Portofolio Kreatif Saya',
    slug: 'user',
    bio: '',
    theme: 'minimal-clean',
    is_published: false,
    settings: {
        allow_download: true,
        flipbook_default: true,
        accent_color: '#2563eb',
        font_family: 'sans',
    },
    pages: [],
    assets: [],
});

// Watch props.portfolio update
watch(() => props.portfolio, (newVal) => {
    if (newVal) {
        localPortfolio.value = JSON.parse(JSON.stringify(newVal));
        if (selectedPageIndex.value >= (localPortfolio.value.pages?.length || 0)) {
            selectedPageIndex.value = 0;
        }
    }
}, { deep: true });

const activePage = computed(() => {
    if (!localPortfolio.value.pages || localPortfolio.value.pages.length === 0) return null;
    return localPortfolio.value.pages[selectedPageIndex.value] || localPortfolio.value.pages[0];
});

const mobileEditorMode = ref('canvas'); // 'canvas' or 'toolbox'

// ==========================================
// 2. CHECKOUT & PAYMENT FLOW (UNSUBSCRIBED / PENDING)
// ==========================================
const selectedPlan = ref(props.activePlan || (props.plans.length > 0 ? props.plans[0] : null));
const voucherInput = ref('');
const voucherStatus = ref({ checking: false, valid: null, message: '', discount: 0, code: '' });
const isAgreeTerms = ref(true);

function selectPlan(plan) {
    if (!plan) return;
    selectedPlan.value = plan;
    checkoutForm.plan_id = plan.id;
    if (voucherInput.value.trim()) {
        applyVoucher();
    }
}

async function applyVoucher() {
    if (!voucherInput.value.trim() || !selectedPlan.value) return;
    voucherStatus.value.checking = true;
    voucherStatus.value.message = '';

    try {
        const response = await fetch('/studio/portfolio/validate-voucher', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code: voucherInput.value.trim(),
                plan_id: selectedPlan.value.id,
            }),
        });

        const data = await response.json();
        if (response.ok && data.valid) {
            voucherStatus.value = {
                checking: false,
                valid: true,
                message: `Voucher ${data.code} berhasil diterapkan! Hemat ${formatRupiah(data.discount_amount)}`,
                discount: data.discount_amount,
                code: data.code,
            };
        } else {
            voucherStatus.value = {
                checking: false,
                valid: false,
                message: data.message || 'Kode voucher tidak valid atau telah kedaluwarsa.',
                discount: 0,
                code: '',
            };
        }
    } catch (err) {
        voucherStatus.value = {
            checking: false,
            valid: false,
            message: 'Gagal memvalidasi voucher. Periksa koneksi internet Anda.',
            discount: 0,
            code: '',
        };
    }
}

const computedOrderTotal = computed(() => {
    if (props.freeModeEnabled) return 0;
    if (!selectedPlan.value) return 0;
    const isPromo = selectedPlan.value.is_promo || selectedPlan.value.is_promo_active;
    const base = isPromo && selectedPlan.value.promo_price
        ? selectedPlan.value.promo_price
        : selectedPlan.value.price;
    const finalAmount = Math.max(0, base - (voucherStatus.value.discount || 0));
    return finalAmount;
});

const checkoutForm = useForm({
    plan_id: selectedPlan.value?.id || null,
    voucher_code: '',
});

function handleCheckout() {
    if (!selectedPlan.value) return;
    checkoutForm.plan_id = selectedPlan.value.id;
    checkoutForm.voucher_code = voucherStatus.value.valid ? voucherStatus.value.code : '';
    checkoutForm.post('/studio/portfolio/checkout', {
        preserveScroll: true,
    });
}

// Payment Proof Form
const paymentProofForm = useForm({
    sender_bank: '',
    sender_account_name: '',
    notes: '',
    proof_image: null,
});
const proofImagePreview = ref(null);

function onProofImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        paymentProofForm.proof_image = file;
        proofImagePreview.value = URL.createObjectURL(file);
    }
}

function submitPaymentProof() {
    if (!props.latestOrder) return;
    paymentProofForm.post(`/studio/portfolio/orders/${props.latestOrder.id}/payment-proof`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentProofForm.reset();
            proofImagePreview.value = null;
        },
    });
}

// ==========================================
// 3. CANVA-STYLE PAGE CANVAS ACTIONS
// ==========================================
let autoSaveTimer = null;

function markUnsaved() {
    saveStatus.value = 'unsaved';
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveActivePageContent();
        saveEntirePortfolio();
    }, 1500); // 1.5 second autosave debounce
}

function saveEntirePortfolio() {
    saveStatus.value = 'saving';
    router.post('/studio/portfolio/save', {
        title: localPortfolio.value.title,
        slug: localPortfolio.value.slug,
        bio: localPortfolio.value.bio,
        theme: localPortfolio.value.theme,
        settings: localPortfolio.value.settings,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            saveStatus.value = 'saved';
        },
        onError: () => {
            saveStatus.value = 'unsaved';
        },
    });
}

function togglePublish() {
    router.post('/studio/portfolio/publish', {}, {
        preserveScroll: true,
    });
}

// Page Management
function selectPage(index) {
    selectedPageIndex.value = index;
}

// Page Reordering & Drag-and-Drop (Mudah Digeser)
const draggedPageIndex = ref(null);
const dragOverPageIndex = ref(null);

function shiftPage(index, direction) {
    if (!localPortfolio.value.pages || localPortfolio.value.pages.length <= 1) return;
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= localPortfolio.value.pages.length) return;

    const pages = [...localPortfolio.value.pages];
    const moved = pages.splice(index, 1)[0];
    pages.splice(targetIndex, 0, moved);

    localPortfolio.value.pages = pages;
    selectedPageIndex.value = targetIndex;

    persistPageOrder(pages);
}

function onPageDragStart(index, event) {
    draggedPageIndex.value = index;
    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', index.toString());
    }
}

function onPageDragOver(index, event) {
    if (draggedPageIndex.value === null || draggedPageIndex.value === index) return;
    dragOverPageIndex.value = index;
}

function onPageDragLeave(index, event) {
    if (dragOverPageIndex.value === index) {
        dragOverPageIndex.value = null;
    }
}

function onPageDrop(targetIndex) {
    const sourceIndex = draggedPageIndex.value;
    draggedPageIndex.value = null;
    dragOverPageIndex.value = null;

    if (sourceIndex === null || sourceIndex === undefined || sourceIndex === targetIndex) return;

    const pages = [...localPortfolio.value.pages];
    const moved = pages.splice(sourceIndex, 1)[0];
    pages.splice(targetIndex, 0, moved);

    localPortfolio.value.pages = pages;
    selectedPageIndex.value = targetIndex;

    persistPageOrder(pages);
}

function onPageDragEnd() {
    draggedPageIndex.value = null;
    dragOverPageIndex.value = null;
}

function persistPageOrder(pages) {
    const page_ids = pages.map(p => p.id).filter(Boolean);
    if (page_ids.length > 0) {
        saveStatus.value = 'saving';
        router.post('/studio/portfolio/pages/reorder', { page_ids }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                saveStatus.value = 'saved';
            },
            onError: () => {
                saveStatus.value = 'unsaved';
            },
        });
    }
}

function addNewPage(pageType = 'custom') {
    const nextOrder = (localPortfolio.value.pages?.length || 0) + 1;
    let title = `Halaman ${nextOrder}`;
    let defaultContent = {};

    switch (pageType) {
        case 'cover':
            title = 'Cover Depan';
            defaultContent = {
                headline: 'Koleksi Portofolio & Karya Saya',
                subheadline: 'Selamat datang di ruang karya digital resmi.',
                author_name: props.portfolio?.title || 'Kreator',
                author_title: 'Desainer & Kreator',
                cover_image: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
                cta_text: 'Jelajahi Karya',
            };
            break;
        case 'about':
            title = 'Tentang Saya';
            defaultContent = {
                heading: 'Kisah & Perjalanan Saya',
                bio_paragraph: 'Menjelaskan visi, latar belakang, dan spesialisasi profesional secara ringkas dan menarik.',
                skills: ['Kreativitas', 'Desain Visual', 'Manajemen Waktu', 'Kolaborasi'],
            };
            break;
        case 'projects':
            title = 'Proyek Pilihan';
            defaultContent = {
                heading: 'Karya & Proyek Terpilih',
                description: 'Kumpulan studi kasus dan proyek yang telah diselesaikan dengan standar tinggi.',
                items: [
                    {
                        title: 'Proyek Kreatif Terkini',
                        category: 'Branding & Inovasi',
                        description: 'Konsep visual dan eksekusi komprehensif untuk klien korporat.',
                        image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80',
                        link: '#',
                        link_label: 'Lihat Detail Proyek',
                    },
                ],
            };
            break;
        case 'gallery':
            title = 'Galeri Visual';
            defaultContent = {
                heading: 'Galeri Karya & Momen',
                description: 'Eksplorasi visual dan dokumentasi kegiatan terbaik.',
                images: [
                    { url: 'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=800&q=80', caption: 'Karya Fotografi 01' },
                    { url: 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80', caption: 'Karya Desain 02' },
                ],
            };
            break;
        case 'pdf_book':
            title = 'Dokumen & Buku Digital';
            defaultContent = {
                heading: 'Buku Karya / E-Book Portofolio',
                description: 'Baca dokumen resmi atau buku profil dalam mode flipbook digital.',
                asset_id: null,
                flipbook_mode: true,
                allow_download: true,
            };
            break;
        case 'contact':
            title = 'Hubungi Saya';
            defaultContent = {
                heading: 'Mari Berkolaborasi',
                description: 'Terbuka untuk diskusi proyek baru, kolaborasi kreatif, atau peluang kerja sama.',
                email: 'halo@lunou.tech',
                phone: '+62 812-3456-7890',
                location: 'Indonesia',
            };
            break;
        case 'custom':
            title = `Kanvas Bebas ${nextOrder}`;
            defaultContent = {
                heading: '',
                description: '',
                blocks: [],
            };
            break;
        default:
            title = `Halaman Kosong ${nextOrder}`;
            defaultContent = {
                heading: '',
                description: '',
                blocks: [],
            };
    }

    router.post('/studio/portfolio/pages', {
        title,
        page_type: pageType,
        sort_order: nextOrder,
        content: defaultContent,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            isAddPageModalOpen.value = false;
            selectedPageIndex.value = (localPortfolio.value.pages?.length || 1) - 1;
        },
    });
}

function addPrestasiPage() {
    isAddPageModalOpen.value = false;
    const nextOrder = (localPortfolio.value.pages?.length || 0) + 1;
    router.post('/studio/portfolio/pages', {
        title: 'Prestasi & Rekam Jejak',
        page_type: 'custom',
        sort_order: nextOrder,
        content: {
            heading: '',
            description: '',
            blocks: [
                {
                    id: 'blk_' + Date.now() + '_1',
                    type: 'subheading',
                    tag: 'PRESTASI & REKAM JEJAK',
                    title: 'Prestasi & Penghargaan',
                    subtitle: 'Koleksi pencapaian kompetisi, penghargaan industri, dan sertifikasi profesional.',
                    align: 'left',
                },
                {
                    id: 'blk_' + Date.now() + '_2',
                    type: 'photo_card',
                    url: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80',
                    title: 'Juara 1 Desain & Inovasi UI/UX Nasional 2025',
                    description: 'Diberikan oleh Kementerian atas keunggulan riset pengguna dan perancangan antarmuka yang berdampak positif.',
                    meta: 'Tahun 2025 • Tingkat Nasional',
                    align: 'left',
                    width: 'full',
                },
            ],
        },
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedPageIndex.value = (localPortfolio.value.pages?.length || 1) - 1;
        },
    });
}

function duplicateCurrentPage() {
    if (!activePage.value) return;
    const nextOrder = (localPortfolio.value.pages?.length || 0) + 1;
    router.post('/studio/portfolio/pages', {
        title: `${activePage.value.title} (Salinan)`,
        page_type: activePage.value.page_type,
        sort_order: nextOrder,
        content: activePage.value.content,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedPageIndex.value = (localPortfolio.value.pages?.length || 1) - 1;
        },
    });
}

function deleteCurrentPage() {
    if (!activePage.value) return;
    if (localPortfolio.value.pages.length <= 1) {
        alert('Portofolio harus memiliki minimal satu halaman.');
        return;
    }
    if (!confirm(`Hapus halaman "${activePage.value.title}"?`)) return;

    router.delete(`/studio/portfolio/pages/${activePage.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedPageIndex.value >= localPortfolio.value.pages.length) {
                selectedPageIndex.value = Math.max(0, localPortfolio.value.pages.length - 1);
            }
        },
    });
}

function saveActivePageContent() {
    if (!activePage.value) return;
    saveStatus.value = 'saving';
    router.post('/studio/portfolio/pages', {
        id: activePage.value.id,
        page_id: activePage.value.id,
        title: activePage.value.title,
        page_type: activePage.value.page_type,
        content: activePage.value.content,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            saveStatus.value = 'saved';
        },
        onError: () => {
            saveStatus.value = 'unsaved';
        },
    });
}

// ==========================================
// CANVA-STYLE PLUG & PLAY BLOCKS ENGINE
// ==========================================
const draggedBlockIndex = ref(null);
const activeBlockIconPickerIndex = ref(null);
const blockImageUploadingIndex = ref(null);

const availableIcons = [
    { name: 'whatsapp', label: 'WhatsApp', category: 'social' },
    { name: 'instagram', label: 'Instagram', category: 'social' },
    { name: 'linkedin', label: 'LinkedIn', category: 'social' },
    { name: 'github', label: 'GitHub', category: 'social' },
    { name: 'behance', label: 'Behance', category: 'social' },
    { name: 'dribbble', label: 'Dribbble', category: 'social' },
    { name: 'youtube', label: 'YouTube', category: 'social' },
    { name: 'tiktok', label: 'TikTok', category: 'social' },
    { name: 'twitter', label: 'X / Twitter', category: 'social' },
    { name: 'threads', label: 'Threads', category: 'social' },
    { name: 'spotify', label: 'Spotify', category: 'social' },
    { name: 'sparkles', label: 'Sparkles', category: 'creative' },
    { name: 'star', label: 'Bintang', category: 'creative' },
    { name: 'heart', label: 'Love', category: 'creative' },
    { name: 'briefcase', label: 'Karier', category: 'general' },
    { name: 'mail', label: 'Email', category: 'contact' },
    { name: 'phone', label: 'Telepon', category: 'contact' },
    { name: 'card', label: 'Kartu', category: 'general' },
    { name: 'keychain', label: 'Keychain', category: 'general' },
    { name: 'camera', label: 'Foto', category: 'media' },
    { name: 'download', label: 'Unduh CV', category: 'general' },
    { name: 'external-link', label: 'Tautan', category: 'general' },
    { name: 'globe', label: 'Website', category: 'general' },
    { name: 'check', label: 'Centang', category: 'creative' },
    { name: 'quote', label: 'Kutipan', category: 'creative' },
    { name: 'share', label: 'Bagikan', category: 'general' },
    { name: 'qrcode', label: 'QR Code', category: 'general' },
];

const filteredIcons = computed(() => {
    if (!iconSearchQuery.value.trim()) return availableIcons;
    const q = iconSearchQuery.value.toLowerCase();
    return availableIcons.filter(ic => ic.name.toLowerCase().includes(q) || ic.label.toLowerCase().includes(q));
});

function ensureBlocksArray() {
    if (!activePage.value) return [];
    if (!activePage.value.content) activePage.value.content = {};
    if (!Array.isArray(activePage.value.content.blocks)) {
        activePage.value.content.blocks = [];
    }
    return activePage.value.content.blocks;
}

function addBlock(type, customProps = {}) {
    const blocks = ensureBlocksArray();
    let newBlock = {
        id: 'blk_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
        type,
        align: 'left',
        ...customProps,
    };

    switch (type) {
        case 'heading':
            newBlock = {
                ...newBlock,
                text: customProps.text || 'Tuliskan Judul Menarik di Sini',
                level: customProps.level || 'h2',
                align: customProps.align || 'left',
            };
            break;
        case 'text':
            newBlock = {
                ...newBlock,
                text: customProps.text || 'Tuliskan narasi profil, deskripsi keahlian, atau pesan karya di sini. Teks ini bebas diatur posisinya dan otomatis super responsif di ponsel.',
                align: customProps.align || 'left',
                size: customProps.size || 'base',
            };
            break;
        case 'badge':
            newBlock = {
                ...newBlock,
                text: customProps.text || 'Tersedia untuk Proyek Baru',
                icon: customProps.icon || 'sparkles',
                variant: customProps.variant || 'blue',
                align: customProps.align || 'left',
            };
            break;
        case 'icon':
            newBlock = {
                ...newBlock,
                name: customProps.name || 'sparkles',
                size: customProps.size || 'lg',
                color: customProps.color || 'blue',
                bg_style: customProps.bg_style || 'circle',
                align: customProps.align || 'center',
                label: customProps.label || '',
                link: customProps.link || '',
            };
            break;
        case 'image':
            newBlock = {
                ...newBlock,
                url: customProps.url || 'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=1200&q=80',
                caption: customProps.caption || '',
                width: customProps.width || 'full',
                rounded: customProps.rounded || 'rounded-2xl',
                align: customProps.align || 'center',
            };
            break;
        case 'button':
            newBlock = {
                ...newBlock,
                label: customProps.label || 'Hubungi Saya via WhatsApp',
                url: customProps.url || 'https://wa.me/',
                icon: customProps.icon || 'whatsapp',
                variant: customProps.variant || 'emerald',
                align: customProps.align || 'left',
            };
            break;
        case 'link_card':
            newBlock = {
                ...newBlock,
                title: customProps.title || 'Portofolio Desain di Behance',
                subtitle: customProps.subtitle || 'Kumpulan studi kasus identitas merek & visual grafis.',
                url: customProps.url || 'https://behance.net',
                icon: customProps.icon || 'behance',
                badge: customProps.badge || 'Tautan Eksternal',
            };
            break;
        case 'subheading':
        case 'section_header':
            newBlock = {
                ...newBlock,
                tag: customProps.tag || 'PRESTASI & REKAM JEJAK',
                title: customProps.title || 'Prestasi & Penghargaan',
                subtitle: customProps.subtitle || 'Daftar penghargaan, sertifikasi resmi, dan pengakuan profesional selama berkarier.',
                align: customProps.align || 'left',
            };
            break;
        case 'photo_card':
            newBlock = {
                ...newBlock,
                url: customProps.url || 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80',
                title: customProps.title || 'Juara 1 Desain & Inovasi UI/UX Nasional',
                description: customProps.description || 'Penghargaan tingkat nasional atas perancangan antarmuka pengguna yang inklusif dan inovatif.',
                meta: customProps.meta || 'Tahun 2025 • Tingkat Nasional',
                aspect_ratio: customProps.aspect_ratio || 'video',
                width: customProps.width || 'full',
                align: customProps.align || 'left',
            };
            break;
        case 'photo_grid':
            newBlock = {
                ...newBlock,
                items: customProps.items || [
                    {
                        url: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=800&q=80',
                        title: 'Juara 1 UI/UX Design Challenge',
                        description: 'Penghargaan tingkat nasional atas inovasi desain produk ramah inklusi.',
                        meta: 'Tahun 2025',
                    },
                    {
                        url: 'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=800&q=80',
                        title: 'Sertifikasi Keahlian Desain Visual',
                        description: 'Lisensi resmi kompetensi strategi visual dan identitas merek.',
                        meta: 'Tahun 2024',
                    },
                ],
            };
            break;
        case 'quote':
            newBlock = {
                ...newBlock,
                text: customProps.text || '"Desain yang baik bukan hanya tentang estetika, tetapi bagaimana karya tersebut memecahkan masalah nyata."',
                author: customProps.author || 'Prinsip Desain Saya',
                role: customProps.role || '',
                align: customProps.align || 'left',
            };
            break;
        case 'divider':
            newBlock = {
                ...newBlock,
                style: customProps.style || 'line',
            };
            break;
    }

    blocks.push(newBlock);
    markUnsaved();
    saveActivePageContent();
}

function addPrestasiTemplate() {
    if (activePage.value?.content) {
        if (activePage.value.content.heading === 'Halaman Baru') {
            activePage.value.content.heading = '';
        }
        if (typeof activePage.value.content.description === 'string' && activePage.value.content.description.startsWith('Tambahkan elemen')) {
            activePage.value.content.description = '';
        }
    }
    addBlock('subheading', {
        tag: 'PRESTASI & REKAM JEJAK',
        title: 'Prestasi & Penghargaan',
        subtitle: 'Koleksi pencapaian kompetisi, penghargaan industri, dan sertifikasi profesional.',
        align: 'left',
    });
    addBlock('photo_card', {
        url: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80',
        title: 'Juara 1 Desain & Inovasi UI/UX Nasional 2025',
        description: 'Diberikan oleh Kementerian atas keunggulan riset pengguna dan perancangan antarmuka yang berdampak positif.',
        meta: 'Tahun 2025 • Tingkat Nasional',
        align: 'left',
        width: 'full',
    });
}

function addPhotoGridItem(blockIndex) {
    const blocks = ensureBlocksArray();
    if (blocks[blockIndex] && Array.isArray(blocks[blockIndex].items)) {
        blocks[blockIndex].items.push({
            url: 'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=800&q=80',
            title: 'Prestasi / Karya Baru',
            description: 'Tuliskan deskripsi pencapaian atau keterangan foto di sini.',
            meta: 'Tahun ' + new Date().getFullYear(),
        });
        markUnsaved();
        saveActivePageContent();
    }
}

function removePhotoGridItem(blockIndex, itemIndex) {
    const blocks = ensureBlocksArray();
    if (blocks[blockIndex] && Array.isArray(blocks[blockIndex].items)) {
        blocks[blockIndex].items.splice(itemIndex, 1);
        markUnsaved();
        saveActivePageContent();
    }
}

function moveBlock(index, direction) {
    const blocks = ensureBlocksArray();
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= blocks.length) return;
    const temp = blocks[index];
    blocks[index] = blocks[targetIndex];
    blocks[targetIndex] = temp;
    markUnsaved();
    saveActivePageContent();
}

function duplicateBlock(index) {
    const blocks = ensureBlocksArray();
    const cloned = JSON.parse(JSON.stringify(blocks[index]));
    cloned.id = 'blk_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
    blocks.splice(index + 1, 0, cloned);
    markUnsaved();
    saveActivePageContent();
}

function deleteBlock(index) {
    const blocks = ensureBlocksArray();
    blocks.splice(index, 1);
    markUnsaved();
    saveActivePageContent();
}

function onBlockDragStart(index, event) {
    draggedBlockIndex.value = index;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', index.toString());
    }
}

function onBlockDragOver(index, event) {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
}

function onBlockDrop(targetIndex, event) {
    event.preventDefault();
    if (draggedBlockIndex.value === null) return;
    const fromIndex = draggedBlockIndex.value;
    draggedBlockIndex.value = null;
    if (fromIndex === targetIndex) return;

    const blocks = ensureBlocksArray();
    const [movedItem] = blocks.splice(fromIndex, 1);
    blocks.splice(targetIndex, 0, movedItem);
    markUnsaved();
    saveActivePageContent();
}

async function uploadBlockImageFile(index, file) {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Berkas harus berupa gambar (JPG, PNG, WebP).');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('Ukuran gambar maksimal 10 MB.');
        return;
    }

    blockImageUploadingIndex.value = index;
    const blocks = ensureBlocksArray();
    const prevUrl = blocks[index]?.url;
    blocks[index].url = URL.createObjectURL(file);

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', 'Gambar Blok ' + (index + 1));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const res = await fetch('/studio/portfolio/assets', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: formData,
        });

        const data = await res.json();
        if (res.ok && data.success) {
            blocks[index].url = data.optimized?.url || data.asset?.file_path;
            markUnsaved();
            saveActivePageContent();
        } else {
            blocks[index].url = prevUrl;
            alert(data.message || 'Gagal mengunggah gambar.');
        }
    } catch {
        blocks[index].url = prevUrl;
        alert('Terjadi kesalahan saat mengunggah gambar.');
    } finally {
        blockImageUploadingIndex.value = null;
    }
}

function onBlockImageFileChange(index, event) {
    const file = event.target.files?.[0];
    if (file) {
        uploadBlockImageFile(index, file);
    }
}

async function uploadPhotoGridImageFile(blockIndex, itemIndex, file) {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Berkas harus berupa gambar (JPG, PNG, WebP).');
        return;
    }
    const blocks = ensureBlocksArray();
    if (!blocks[blockIndex]?.items?.[itemIndex]) return;

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', 'Foto Galeri ' + (itemIndex + 1));
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const res = await fetch('/studio/portfolio/assets', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: formData,
        });

        const data = await res.json();
        if (res.ok && data.success) {
            blocks[blockIndex].items[itemIndex].url = data.optimized?.url || data.asset?.file_path;
            markUnsaved();
            saveActivePageContent();
        } else {
            alert(data.message || 'Gagal mengunggah gambar.');
        }
    } catch {
        alert('Terjadi kesalahan saat mengunggah gambar.');
    }
}

function selectIconForBlock(index, iconName) {
    const blocks = ensureBlocksArray();
    if (blocks[index]) {
        blocks[index].name = iconName;
        if (blocks[index].type === 'button' || blocks[index].type === 'link_card' || blocks[index].type === 'badge') {
            blocks[index].icon = iconName;
        }
        markUnsaved();
        saveActivePageContent();
    }
    activeBlockIconPickerIndex.value = null;
}

// Backward-compat alias for addTextSnippet
function addTextSnippet(type) {
    if (type === 'heading') {
        addBlock('heading', { text: 'Judul Halaman Baru' });
    } else if (type === 'subheading') {
        addBlock('text', { text: 'Subjudul atau ringkasan pembuka karya ini.', size: 'sm' });
    } else if (type === 'bio') {
        addBlock('text', { text: 'Tuliskan deskripsi lengkap, pengalaman profesional, atau narasi karya Anda di sini secara menarik.' });
    }
}

// Project Editor Modal State
const isEditProjectModalOpen = ref(false);
const editingProjectIndex = ref(-1);
const editingProject = ref({
    title: '',
    category: '',
    description: '',
    image: '',
    role: '',
    client: '',
    year: '',
    toolsText: '',
    case_study: '',
    challenge: '',
    solution: '',
    result: '',
    link: '',
    link_label: '',
});

// Project Image Uploader State
const projectImageFileInput = ref(null);
const projectImageUploading = ref(false);
const projectImageUploadError = ref('');
const projectImageUploadMessage = ref('');
const showManualProjectImageUrl = ref(false);
const isProjectImageDragging = ref(false);

async function uploadProjectImageFile(file) {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        projectImageUploadError.value = 'Berkas harus berupa gambar (JPG, PNG, WebP).';
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        projectImageUploadError.value = 'Ukuran berkas maksimal 10 MB.';
        return;
    }

    projectImageUploadError.value = '';
    projectImageUploading.value = true;
    projectImageUploadMessage.value = '';

    const previousImage = editingProject.value.image;
    editingProject.value.image = URL.createObjectURL(file);

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', editingProject.value.title || file.name.replace(/\.[^/.]+$/, ''));

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch('/studio/portfolio/assets', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            const finalUrl = data.optimized?.url || data.asset?.file_path;
            editingProject.value.image = finalUrl;
            projectImageUploadMessage.value = data.message || 'Gambar berhasil diunggah.';
            setTimeout(() => {
                projectImageUploadMessage.value = '';
            }, 5000);
        } else {
            editingProject.value.image = previousImage;
            projectImageUploadError.value = data.message || 'Gagal mengunggah gambar proyek.';
        }
    } catch {
        editingProject.value.image = previousImage;
        projectImageUploadError.value = 'Terjadi kesalahan saat mengunggah. Silakan coba lagi.';
    } finally {
        projectImageUploading.value = false;
        if (projectImageFileInput.value) {
            projectImageFileInput.value.value = '';
        }
    }
}

function onProjectImageFileChange(e) {
    const file = e.target.files?.[0];
    if (file) {
        uploadProjectImageFile(file);
    }
}

function onProjectImageDrop(e) {
    isProjectImageDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        uploadProjectImageFile(file);
    }
}

// Cover Image Uploader State for Page 1
const coverFileInput = ref(null);
const coverUploading = ref(false);

async function uploadCoverImageFile(file) {
    if (!file || !activePage.value) return;
    if (!file.type.startsWith('image/')) {
        alert('Berkas harus berupa gambar (JPG, PNG, WebP).');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('Ukuran berkas maksimal 10 MB.');
        return;
    }

    coverUploading.value = true;
    const prevCover = activePage.value.content?.cover_image;
    if (!activePage.value.content) activePage.value.content = {};
    activePage.value.content.cover_image = URL.createObjectURL(file);

    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', 'Cover ' + (localPortfolio.value?.title || 'Portofolio'));

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch('/studio/portfolio/assets', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            activePage.value.content.cover_image = data.optimized?.url || data.asset?.file_path;
            markUnsaved();
            saveActivePageContent();
        } else {
            activePage.value.content.cover_image = prevCover;
            alert(data.message || 'Gagal mengunggah cover.');
        }
    } catch {
        activePage.value.content.cover_image = prevCover;
        alert('Terjadi kesalahan saat mengunggah cover.');
    } finally {
        coverUploading.value = false;
        if (coverFileInput.value) {
            coverFileInput.value.value = '';
        }
    }
}

function onCoverFileChange(e) {
    const file = e.target.files?.[0];
    if (file) {
        uploadCoverImageFile(file);
    }
}

// Project Preview Modal in Builder
const isPreviewProjectModalOpen = ref(false);
const previewProjectItem = ref(null);

function openEditProjectModal(index) {
    if (!activePage.value?.content?.items?.[index]) return;
    const item = activePage.value.content.items[index];
    editingProjectIndex.value = index;
    editingProject.value = {
        title: item.title || '',
        category: item.category || '',
        description: item.description || '',
        image: item.image || '',
        role: item.role || '',
        client: item.client || '',
        year: item.year || '',
        toolsText: Array.isArray(item.tools) ? item.tools.join(', ') : (item.tools || ''),
        case_study: item.case_study || '',
        challenge: item.challenge || '',
        solution: item.solution || '',
        result: item.result || '',
        link: item.link && item.link !== '#' ? item.link : '',
        link_label: item.link_label || 'Buka di Pihak Ketiga',
    };
    projectImageUploadError.value = '';
    projectImageUploadMessage.value = '';
    showManualProjectImageUrl.value = false;
    isEditProjectModalOpen.value = true;
}

function saveEditedProject() {
    if (editingProjectIndex.value < 0 || !activePage.value?.content?.items) return;
    const toolsArray = editingProject.value.toolsText
        ? editingProject.value.toolsText.split(',').map(t => t.trim()).filter(Boolean)
        : [];

    activePage.value.content.items[editingProjectIndex.value] = {
        title: editingProject.value.title || 'Proyek Tanpa Judul',
        category: editingProject.value.category || 'Portfolio',
        description: editingProject.value.description || '',
        image: editingProject.value.image || '',
        role: editingProject.value.role || '',
        client: editingProject.value.client || '',
        year: editingProject.value.year || '',
        tools: toolsArray,
        case_study: editingProject.value.case_study || '',
        challenge: editingProject.value.challenge || '',
        solution: editingProject.value.solution || '',
        result: editingProject.value.result || '',
        link: editingProject.value.link || '',
        link_label: editingProject.value.link_label || 'Buka di Pihak Ketiga',
    };

    isEditProjectModalOpen.value = false;
    markUnsaved();
    saveActivePageContent();
}

function previewProjectDetailInEditor(item) {
    previewProjectItem.value = item;
    isPreviewProjectModalOpen.value = true;
}

function addProjectItem() {
    if (!activePage.value) return;
    if (!activePage.value.content) activePage.value.content = {};
    if (!activePage.value.content.items) activePage.value.content.items = [];
    const nextIdx = activePage.value.content.items.length + 1;
    activePage.value.content.items.push({
        title: `Proyek Unggulan #${nextIdx}`,
        category: 'Kategori Desain',
        description: 'Ringkasan singkat karya ini yang menarik minat pengunjung di halaman depan.',
        image: 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
        role: 'Desainer & Pengembang',
        client: 'Klien Mandiri',
        year: '2026',
        tools: ['Figma', 'Vue 3', 'Tailwind CSS'],
        case_study: 'Tuliskan kisah lengkap perancangan proyek ini. Semua informasi ini akan langsung dibaca oleh pengunjung tanpa harus keluar ke website pihak ketiga.',
        challenge: 'Tantangan atau rumusan masalah yang dihadapi sebelum proyek dimulai.',
        solution: 'Solusi terukur dan metodologi yang diterapkan.',
        result: 'Hasil nyata, feedback pengguna, atau dampak positif yang diraih.',
        link: '',
        link_label: 'Kunjungi Tautan Luar (Opsional)',
    });
    markUnsaved();
    saveActivePageContent();
}

function removeProjectItem(index) {
    if (!activePage.value?.content?.items) return;
    activePage.value.content.items.splice(index, 1);
    markUnsaved();
    saveActivePageContent();
}

// Upload Media Asset (PDF or Image)
const assetUploadForm = useForm({
    file: null,
    file_type: 'image',
    title: '',
});

function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    assetUploadForm.file = file;
    assetUploadForm.file_type = 'image';
    assetUploadForm.title = file.name.replace(/\.[^/.]+$/, '');

    assetUploadForm.post('/studio/portfolio/assets', {
        preserveScroll: true,
        onSuccess: () => {
            isUploadImageModalOpen.value = false;
            assetUploadForm.reset();
        },
    });
}

function handlePdfUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file PDF maksimal 5 MB.');
        return;
    }

    assetUploadForm.file = file;
    assetUploadForm.file_type = 'pdf';
    assetUploadForm.title = file.name.replace(/\.[^/.]+$/, '');

    assetUploadForm.post('/studio/portfolio/assets', {
        preserveScroll: true,
        onSuccess: () => {
            isUploadPdfModalOpen.value = false;
            assetUploadForm.reset();
        },
    });
}

function attachPdfToCurrentPage(assetId) {
    if (!activePage.value) return;

    // 1. If currently on a pdf_book page or a blank custom page, attach directly here
    const isBlankCustom = activePage.value.page_type === 'custom' && (!activePage.value.content?.blocks || activePage.value.content.blocks.length === 0);
    if (activePage.value.page_type === 'pdf_book' || isBlankCustom) {
        if (!activePage.value.content) activePage.value.content = {};
        activePage.value.content.asset_id = assetId;
        activePage.value.content.flipbook_mode = true;
        activePage.value.page_type = 'pdf_book';
        markUnsaved();
        saveActivePageContent();
        return;
    }

    // 2. If current page is Cover, About, Projects, etc., look for an existing pdf_book page
    const existingPdfIdx = localPortfolio.value?.pages?.findIndex(p => p.page_type === 'pdf_book') ?? -1;
    if (existingPdfIdx !== -1) {
        selectedPageIndex.value = existingPdfIdx;
        const targetPage = localPortfolio.value.pages[existingPdfIdx];
        if (!targetPage.content) targetPage.content = {};
        targetPage.content.asset_id = assetId;
        targetPage.content.flipbook_mode = true;
        markUnsaved();
        saveActivePageContent();
        return;
    }

    // 3. Otherwise, create a dedicated new pdf_book page so current page is never overwritten
    const nextOrder = (localPortfolio.value.pages?.length || 0) + 1;
    router.post('/studio/portfolio/pages', {
        title: 'Buku Portofolio & Dokumen',
        page_type: 'pdf_book',
        sort_order: nextOrder,
        content: {
            heading: 'Buku Portofolio & Dokumen Resmi',
            description: 'Buka dokumen resmi atau buku profil dalam mode flipbook digital.',
            asset_id: assetId,
            flipbook_mode: true,
            allow_download: true,
        },
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedPageIndex.value = (localPortfolio.value.pages?.length || 1) - 1;
        },
    });
}

function deleteAsset(assetId) {
    if (!confirm('Hapus berkas ini dari portofolio?')) return;
    router.delete(`/studio/portfolio/assets/${assetId}`, {
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

function copyToClipboard(text, key) {
    navigator.clipboard.writeText(text);
    copiedText.value = key;
    setTimeout(() => {
        copiedText.value = null;
    }, 2000);
}
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-blue-600 selection:text-white relative">
        <Head :title="hasAccess ? `${localPortfolio.title} · me.myoin.id (part of lunou)` : 'me.myoin.id · part of lunou · Web Portfolio Builder'" />

        <!-- ========================================================================= -->
        <!-- HEADER TOPBAR -->
        <!-- ========================================================================= -->
        <header class="h-16 border-b border-zinc-200/90 bg-white/95 backdrop-blur-md sticky top-0 z-40 px-3 sm:px-6 flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                <Link href="/studio" class="p-2 rounded-xl text-zinc-500 hover:text-zinc-950 hover:bg-zinc-100 transition-colors flex-shrink-0" title="Kembali ke Studio">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>

                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <ApplicationLogo className="w-6 h-6 sm:w-7 sm:h-7 flex-shrink-0" variant="1" />
                    <div class="flex flex-col min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="font-extrabold text-xs sm:text-base tracking-tight text-zinc-950 truncate block">
                                {{ hasAccess ? (localPortfolio.title || 'Portofolio') : 'me.myoin.id Portfolio' }}
                            </span>
                            <span v-if="hasAccess" class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase flex-shrink-0" :class="localPortfolio.is_published ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                {{ localPortfolio.is_published ? 'Publik' : 'Draft' }}
                            </span>
                        </div>
                        <span class="text-[9px] text-zinc-400 font-medium hidden sm:inline-block">
                            part of lunou · {{ hasAccess ? 'Visual Canva-Style Page Editor' : 'Paket Langganan & Aktivasi' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Top Right Action Controls -->
            <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                <template v-if="hasAccess">
                    <!-- Auto-save status (UCD: Perubahan Tersimpan) - Desktop Only -->
                    <div class="hidden md:flex items-center gap-1.5 text-xs text-zinc-500 font-medium mr-1">
                        <span class="w-2 h-2 rounded-full" :class="saveStatus === 'saving' ? 'bg-amber-400 animate-ping' : (saveStatus === 'unsaved' ? 'bg-zinc-300' : 'bg-emerald-500')"></span>
                        <span>{{ saveStatus === 'saving' ? 'Menyimpan...' : (saveStatus === 'unsaved' ? 'Perlu disimpan' : 'Perubahan tersimpan') }}</span>
                    </div>

                    <!-- Device Preview Mode Switcher (Canva Style) -->
                    <div class="hidden lg:flex items-center bg-zinc-100 p-0.5 rounded-xl border border-zinc-200/80 text-xs font-semibold">
                        <button
                            @click="previewDevice = 'desktop'"
                            class="p-1.5 rounded-lg transition-all"
                            :class="previewDevice === 'desktop' ? 'bg-white text-blue-600 shadow-2xs font-bold' : 'text-zinc-500 hover:text-zinc-900'"
                            title="Tampilan Desktop / Laptop"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </button>
                        <button
                            @click="previewDevice = 'tablet'"
                            class="p-1.5 rounded-lg transition-all"
                            :class="previewDevice === 'tablet' ? 'bg-white text-blue-600 shadow-2xs font-bold' : 'text-zinc-500 hover:text-zinc-900'"
                            title="Tampilan Tablet"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </button>
                        <button
                            @click="previewDevice = 'mobile'"
                            class="p-1.5 rounded-lg transition-all"
                            :class="previewDevice === 'mobile' ? 'bg-white text-blue-600 shadow-2xs font-bold' : 'text-zinc-500 hover:text-zinc-900'"
                            title="Tampilan Ponsel (HP)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>

                    <!-- Tombol Panduan Penggunaan & Bantuan (UCD: Ramah Pemula) -->
                    <button
                        type="button"
                        @click="isHelpGuideModalOpen = true"
                        class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-all shadow-2xs flex-shrink-0"
                        title="Panduan Lengkap Cara Penggunaan di HP & Komputer"
                    >
                        <LunouIcon name="help" className="w-4 h-4 text-blue-600 flex-shrink-0" />
                        <span class="hidden sm:inline">Panduan</span>
                    </button>

                    <!-- Settings Button -->
                    <button
                        @click="isSettingsModalOpen = true"
                        class="w-9 h-9 sm:w-auto sm:px-3 sm:py-2 rounded-xl border border-zinc-200 hover:bg-zinc-100 text-xs font-semibold text-zinc-700 flex items-center justify-center gap-1.5 transition-all shadow-2xs flex-shrink-0"
                        title="Pengaturan Portofolio"
                    >
                        <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="hidden sm:inline">Pengaturan</span>
                    </button>

                    <!-- Preview / Buka Website Link (UCD: Lihat Tampilan) -->
                    <a
                        :href="publicUrl"
                        target="_blank"
                        class="w-9 h-9 sm:w-auto sm:px-3 sm:py-2 rounded-xl border border-zinc-200 hover:bg-zinc-100 text-xs font-semibold text-zinc-700 flex items-center justify-center gap-1.5 transition-all shadow-2xs flex-shrink-0"
                        title="Buka Website Publik"
                    >
                        <LunouIcon name="eye" className="w-4 h-4 text-zinc-500" />
                        <span class="hidden sm:inline">Lihat Tampilan</span>
                    </a>

                    <!-- Terbitkan Portofolio (UCD: Terbitkan) -->
                    <button
                        @click="togglePublish"
                        class="px-3 sm:px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-2xs flex-shrink-0"
                        :class="localPortfolio.is_published ? 'bg-zinc-950 hover:bg-black text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="localPortfolio.is_published ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                        <span class="hidden sm:inline">{{ localPortfolio.is_published ? 'Terbitkan Ulang' : 'Terbitkan Portofolio' }}</span>
                        <span class="sm:hidden">{{ localPortfolio.is_published ? 'Terbit' : 'Terbitkan' }}</span>
                    </button>
                </template>

                <template v-else>
                    <Link
                        href="/studio"
                        class="px-3 sm:px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>&larr;</span>
                        <span>Profil</span>
                    </Link>
                </template>
            </div>
        </header>

        <!-- ========================================================================= -->
        <!-- STATE A: BELUM BERLANGGANAN / KEDALUWARSA (PAYWALL & CHECKOUT) -->
        <!-- ========================================================================= -->
        <main v-if="!hasAccess && (accessStatus === 'not_subscribed' || accessStatus === 'expired')" class="flex-1 max-w-5xl w-full mx-auto p-4 sm:p-8 space-y-8">
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-zinc-950 via-zinc-900 to-blue-950 text-white p-6 sm:p-12 shadow-xl border border-zinc-800">
                <img src="/icon/1.png" alt="" class="absolute -right-12 -bottom-12 w-80 h-80 opacity-10 pointer-events-none select-none" />

                <div class="relative z-10 max-w-2xl space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold border border-blue-400/30">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                        Fitur Baru · me.myoin.id Web Portfolio (part of lunou)
                    </div>

                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                        Tampilkan Seluruh Perjalanan Karyamu dalam Satu Halaman.
                    </h1>

                    <p class="text-sm sm:text-base text-zinc-300 leading-relaxed">
                        Atur halaman dengan mudah, tampilkan studi kasus proyek, dan bagikan buku digital flipbook melalui alamat khusus <code class="bg-white/10 px-2 py-0.5 rounded text-blue-200">me.myoin.id/p/namakamu</code>.
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-4 text-xs">
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Tanpa Paham Koding
                        </div>
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Mode Buku Digital Flipbook
                        </div>
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Dukung Dokumen PDF Hingga 5MB
                        </div>
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Editor Visual Siap Pakai
                        </div>
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Alamat Khusus & Responsif
                        </div>
                        <div class="flex items-center gap-2 text-zinc-200">
                            <span class="text-emerald-400 font-bold">&check;</span> Terhubung Otomatis ke Profil
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing & Checkout Section -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <!-- Left: Paket Card & Selector (UCD/HCD: Transparansi Pilihan Produk) -->
                <div class="md:col-span-7 bg-white border border-zinc-200/90 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <!-- Pilihan Paket Tabs / Grid jika terdapat pilihan paket -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900 uppercase tracking-widest">Pilih Ruang yang Sesuai</span>
                            <span class="text-[11px] text-zinc-400">Pilih paket sesuai kebutuhanmu</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" v-if="plans && plans.length > 1">
                            <div
                                v-for="plan in plans"
                                :key="plan.id"
                                @click="selectPlan(plan)"
                                class="p-4 rounded-2xl border-2 cursor-pointer transition-all relative flex flex-col justify-between"
                                :class="selectedPlan?.id === plan.id ? 'border-blue-600 bg-blue-50/40 ring-1 ring-blue-600' : 'border-zinc-200 bg-zinc-50/50 hover:border-zinc-300'"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1.5">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                                            :class="plan.slug === 'bundle-pro-nfc' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                        >
                                            {{ plan.slug === 'bundle-pro-nfc' ? 'Bundle Fisik + Digital' : 'Akses Digital Saja' }}
                                        </span>
                                        <span v-if="selectedPlan?.id === plan.id" class="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold">
                                            <LunouIcon name="check" className="w-2.5 h-2.5 text-white" />
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-sm text-zinc-950 mt-1">{{ plan.name }}</h3>
                                    <p class="text-[11px] text-zinc-500 mt-0.5 line-clamp-2 leading-relaxed">{{ plan.description }}</p>
                                </div>

                                <div class="pt-3 mt-2 border-t border-zinc-200/60 flex items-baseline gap-2">
                                    <span class="text-base font-black text-zinc-950 tracking-tight">
                                        {{ formatRupiah((plan.is_promo || plan.is_promo_active) && plan.promo_price ? plan.promo_price : plan.price) }}
                                    </span>
                                    <span v-if="(plan.is_promo || plan.is_promo_active) && plan.promo_price" class="text-xs text-zinc-400 line-through">
                                        {{ formatRupiah(plan.price) }}
                                    </span>
                                    <span class="text-[10px] text-zinc-500 font-semibold">/ {{ plan.duration_days || 30 }} Hari</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback jika hanya 1 plan terkonfigurasi -->
                        <div v-else class="p-4 rounded-2xl border-2 border-blue-600 bg-blue-50/30">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-sm text-zinc-950">{{ selectedPlan?.name || 'Creator Pro' }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Akses Digital</span>
                            </div>
                            <p class="text-xs text-zinc-500 mt-1">{{ selectedPlan?.description }}</p>
                        </div>
                    </div>

                    <!-- UCD/HCD: Transparansi Produk & Kepercayaan Pelanggan -->
                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/70 flex items-start gap-3 text-xs text-blue-950 leading-relaxed">
                        <LunouIcon name="sparkles" className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <strong class="font-bold block text-blue-900">Perhatian & Transparansi Produk Lunou:</strong>
                            <p>
                                • Pembelian <strong>Gantungan Kunci NFC fisik</strong> di menu Studio Profil adalah pembelian hardware seumur hidup dan <strong>terpisah</strong> dari paket Web Portofolio digital (tidak otomatis mencakup langganan builder ini).
                            </p>
                            <p>
                                • Jika kamu menginginkan website portofolio sekaligus 1 gantungan kunci fisik NFC siap pakai, silakan pilih opsi <strong>Bundle All-in-One</strong> di atas.
                            </p>
                        </div>
                    </div>

                    <!-- Voucher Input -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-zinc-800">Punya Kode Voucher?</label>
                        <div class="flex gap-2">
                            <input
                                v-model="voucherInput"
                                type="text"
                                placeholder="Contoh: LUNOU5"
                                uppercase
                                class="flex-1 px-4 py-2.5 rounded-xl border border-zinc-300 text-xs font-bold uppercase tracking-wider focus:ring-2 focus:ring-blue-500 focus:outline-none min-h-[44px]"
                            />
                            <button
                                @click="applyVoucher"
                                :disabled="voucherStatus.checking || !voucherInput.trim()"
                                class="px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-black text-white text-xs font-bold transition-all disabled:opacity-50 min-h-[44px]"
                            >
                                {{ voucherStatus.checking ? 'Memeriksa...' : 'Terapkan' }}
                            </button>
                        </div>
                        <p v-if="voucherStatus.message" class="text-xs font-medium" :class="voucherStatus.valid ? 'text-emerald-600' : 'text-rose-600'">
                            {{ voucherStatus.message }}
                        </p>
                    </div>

                    <!-- Syarat & Ketentuan Agreement (UCD: Ceklis Wajib Transparan) -->
                    <div class="pt-2 border-t border-zinc-100 flex items-start gap-2.5">
                        <input
                            v-model="isAgreeTerms"
                            type="checkbox"
                            id="terms_agree"
                            class="mt-1 rounded text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer"
                        />
                        <label for="terms_agree" class="text-xs text-zinc-600 leading-relaxed cursor-pointer">
                            Saya menyetujui <Link href="/syarat-ketentuan" target="_blank" class="text-blue-600 font-bold underline">Syarat dan Ketentuan</Link>, kebijakan privasi, serta ketentuan verifikasi transaksi manual Lunou.
                        </label>
                    </div>
                </div>

                <!-- Right: Summary & Order Button -->
                <div class="md:col-span-5 bg-white border border-zinc-200/90 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                    <div class="border-b border-zinc-100 pb-3 space-y-1">
                        <h3 class="text-base font-bold text-zinc-950">Sedikit lagi, profilmu siap dikembangkan.</h3>
                        <p class="text-xs text-zinc-500">Periksa kembali paket dan jumlah pembayaran sebelum melanjutkan.</p>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between text-zinc-600">
                            <span>Paket Terpilih</span>
                            <span class="font-bold text-zinc-900">{{ selectedPlan?.name }}</span>
                        </div>
                        <div class="flex justify-between text-zinc-600">
                            <span>Masa Akses Aktif</span>
                            <span>{{ selectedPlan?.duration_days || 30 }} Hari Kalender</span>
                        </div>
                        <div class="flex justify-between text-zinc-600">
                            <span>Harga Paket Normal</span>
                            <span>{{ formatRupiah(selectedPlan?.price) }}</span>
                        </div>
                        <div v-if="(selectedPlan?.is_promo || selectedPlan?.is_promo_active) && selectedPlan?.promo_price" class="flex justify-between text-rose-600 font-semibold">
                            <span>Diskon Promo Spesial</span>
                            <span>-{{ formatRupiah((selectedPlan?.price || 0) - (selectedPlan?.promo_price || 0)) }}</span>
                        </div>
                        <div v-if="voucherStatus.valid && voucherStatus.discount > 0" class="flex justify-between text-emerald-600 font-semibold">
                            <span>Potongan Voucher ({{ voucherStatus.code }})</span>
                            <span>-{{ formatRupiah(voucherStatus.discount) }}</span>
                        </div>

                        <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline font-bold text-sm text-zinc-950">
                            <span>Total Pembayaran</span>
                            <span v-if="freeModeEnabled" class="text-xl text-emerald-600 font-extrabold flex items-center gap-1.5">
                                <span>GRATIS</span>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full uppercase">Rp 0</span>
                            </span>
                            <span v-else class="text-xl text-blue-600 font-extrabold">{{ formatRupiah(computedOrderTotal) }}</span>
                        </div>
                    </div>

                    <!-- Free Mode Banner if active -->
                    <div v-if="freeModeEnabled" class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 flex items-start gap-2.5">
                        <span class="text-base">✨</span>
                        <div class="space-y-0.5">
                            <strong class="font-bold block">Mode Bebas Biaya (Gratis) Aktif!</strong>
                            <p class="text-[11px] text-emerald-800">
                                Anda dapat langsung mengaktifkan fitur Portofolio Pro tanpa transfer bank sepeser pun.
                            </p>
                        </div>
                    </div>

                    <button
                        @click="handleCheckout"
                        :disabled="!isAgreeTerms || checkoutForm.processing"
                        class="w-full min-h-[48px] py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-md transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span v-if="freeModeEnabled">
                            {{ checkoutForm.processing ? 'Mengaktifkan Akses Gratis...' : 'Aktifkan Portofolio Pro Sekarang (Gratis)' }}
                        </span>
                        <span v-else>
                            {{ checkoutForm.processing ? 'Menyiapkan Pembayaran...' : 'Lanjutkan ke Pembayaran' }}
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>

                    <p class="text-[11px] text-zinc-400 text-center leading-relaxed">
                        Data nomor rekening dan transaksi diverifikasi aman secara internal oleh sistem Lunou.
                    </p>
                </div>
            </div>
        </main>

        <!-- ========================================================================= -->
        <!-- STATE B: MENUNGGU PEMBAYARAN (UPLOAD BUKTI TRANSFER) -->
        <!-- ========================================================================= -->
        <main v-else-if="!hasAccess && accessStatus === 'pending_payment'" class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-8 space-y-6">
            <div class="bg-amber-50 border border-amber-200/90 rounded-2xl p-5 flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-amber-900">Sedikit lagi, profilmu siap dikembangkan.</h2>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Pesananmu <strong>#{{ latestOrder?.order_number }}</strong> berhasil dibuat. Silakan transfer tepat sesuai nominal akhir dan unggah bukti pembayaran di bawah ini agar tim kami dapat memverifikasinya.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <!-- Left: Rekening Tujuan -->
                <div class="md:col-span-6 bg-white border border-zinc-200/90 rounded-3xl p-6 space-y-5 shadow-sm">
                    <h3 class="text-sm font-bold text-zinc-950 uppercase tracking-wider">Rekening Tujuan Pembayaran</h3>

                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-semibold text-blue-700">Total Nominal Transfer:</span>
                            <div class="text-2xl font-extrabold text-blue-950">{{ formatRupiah(latestOrder?.final_amount) }}</div>
                        </div>
                        <button
                            @click="copyToClipboard(latestOrder?.final_amount, 'amount')"
                            class="px-3 py-1.5 rounded-xl bg-white border border-blue-200 text-xs font-bold text-blue-700 hover:bg-blue-50 transition-all"
                        >
                            {{ copiedText === 'amount' ? 'Tersalin!' : 'Salin Nominal' }}
                        </button>
                    </div>

                    <!-- Payment methods loop -->
                    <div class="space-y-3">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            class="p-4 rounded-2xl border border-zinc-200 hover:border-zinc-300 bg-zinc-50/50 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-900">{{ method.bank_name }}</span>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-zinc-200 text-zinc-700">Resmi Lunou</span>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div>
                                    <div class="text-base font-mono font-bold text-zinc-950">{{ method.account_number }}</div>
                                    <div class="text-xs text-zinc-500">a.n. {{ method.account_holder }}</div>
                                </div>
                                <button
                                    @click="copyToClipboard(method.account_number, method.id)"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-zinc-300 text-xs font-bold text-zinc-800 hover:bg-zinc-100 transition-all"
                                >
                                    {{ copiedText === method.id ? 'Tersalin!' : 'Salin Rekening' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Formulir Unggah Bukti Transfer -->
                <div class="md:col-span-6 bg-white border border-zinc-200/90 rounded-3xl p-6 space-y-5 shadow-sm">
                    <h3 class="text-sm font-bold text-zinc-950 uppercase tracking-wider">Unggah Bukti Transfer</h3>

                    <form @submit.prevent="submitPaymentProof" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-800 mb-1">Bank / Dompet Pengirim</label>
                            <input
                                v-model="paymentProofForm.sender_bank"
                                type="text"
                                placeholder="Contoh: BCA / Mandiri / GoPay"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-800 mb-1">Nama Pemilik Rekening Pengirim</label>
                            <input
                                v-model="paymentProofForm.sender_account_name"
                                type="text"
                                placeholder="Nama yang tertera pada mutasi"
                                required
                                class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-800 mb-1">Foto Bukti Transfer (Resi / Screenshot)</label>
                            <input
                                @change="onProofImageChange"
                                type="file"
                                accept="image/*"
                                required
                                class="w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <div v-if="proofImagePreview" class="mt-3">
                                <img :src="proofImagePreview" alt="Pratinjau Bukti" class="w-full h-40 object-cover rounded-xl border border-zinc-200" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-800 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea
                                v-model="paymentProofForm.notes"
                                rows="2"
                                placeholder="Nomor referensi atau catatan..."
                                class="w-full px-4 py-2 rounded-xl border border-zinc-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="paymentProofForm.processing"
                            class="w-full min-h-[48px] py-3.5 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-md transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ paymentProofForm.processing ? 'Mengunggah Bukti...' : 'Kirim Bukti Pembayaran' }}</span>
                        </button>

                        <!-- WhatsApp Admin Button for fast verification (UCD Bab 10 & 12) -->
                        <a
                            :href="`https://wa.me/6285862319524?text=${encodeURIComponent('Halo Admin LUNOU, saya ingin konfirmasi transfer pembayaran Web Portfolio #' + (latestOrder?.order_number || ''))}`"
                            target="_blank"
                            class="w-full min-h-[48px] py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer mt-2"
                        >
                            <LunouIcon name="whatsapp" className="w-4 h-4" />
                            <span>Konfirmasi Cepat via WhatsApp Admin</span>
                        </a>
                    </form>
                </div>
            </div>
        </main>

        <!-- ========================================================================= -->
        <!-- STATE C: MENUNGGU VERIFIKASI ADMIN (UNDER REVIEW) -->
        <!-- ========================================================================= -->
        <main v-else-if="!hasAccess && accessStatus === 'under_review'" class="flex-1 max-w-2xl w-full mx-auto p-4 sm:p-12 text-center space-y-6">
            <div class="bg-white border border-zinc-200/90 rounded-3xl p-8 sm:p-12 shadow-sm space-y-6">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <div class="space-y-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Status: Pembayaranmu sedang diperiksa
                    </span>
                    <h2 class="text-2xl font-extrabold text-zinc-950">Pembayaranmu sedang diperiksa.</h2>
                    <p class="text-xs sm:text-sm text-zinc-600 max-w-md mx-auto leading-relaxed">
                        Terima kasih! Bukti transfer untuk pesanan <strong>#{{ latestOrder?.order_number }}</strong> telah diterima. Tim kami sedang mencocokkan mutasi rekeningmu (maks. 1x24 jam). Fitur portofolio akan langsung aktif setelah terverifikasi.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 text-left text-xs space-y-1.5 max-w-sm mx-auto">
                    <div class="flex justify-between text-zinc-500">
                        <span>Nominal:</span>
                        <strong class="text-zinc-900">{{ formatRupiah(latestOrder?.final_amount) }}</strong>
                    </div>
                    <div class="flex justify-between text-zinc-500">
                        <span>Paket:</span>
                        <strong class="text-zinc-900">{{ latestOrder?.plan?.name || 'Creator Pro' }}</strong>
                    </div>
                </div>

                <p class="text-[11px] text-zinc-400">
                    Fitur Web Portfolio Builder akan otomatis aktif segera setelah disetujui admin tanpa perlu melakukan refresh manual berulang kali.
                </p>

                <div class="pt-4 flex flex-col sm:flex-row justify-center gap-3">
                    <a
                        :href="`https://wa.me/6285862319524?text=${encodeURIComponent('Halo Admin LUNOU, mengecek status pesanan portofolio #' + (latestOrder?.order_number || ''))}`"
                        target="_blank"
                        class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-2 min-h-[44px]"
                    >
                        <LunouIcon name="whatsapp" className="w-4 h-4" />
                        <span>Tanya Admin via WhatsApp</span>
                    </a>
                    <Link href="/studio" class="px-5 py-3 rounded-xl border border-zinc-300 text-xs font-bold text-zinc-700 hover:bg-zinc-50 min-h-[44px] flex items-center justify-center">
                        Kembali ke Ruang Profil
                    </Link>
                </div>
            </div>
        </main>

        <!-- ========================================================================= -->
        <!-- STATE D: AKTIF (CANVA-STYLE VISUAL PAGE BUILDER) -->
        <!-- ========================================================================= -->
        <main v-else class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">
            <!-- Mobile Mode Switcher Ribbon (Canvas vs Toolbox) - UCD Bab 6: Ramah Jempol Ponsel -->
            <div class="lg:hidden flex border-b border-zinc-200 bg-white text-xs font-semibold sticky top-0 z-30">
                <button
                    type="button"
                    @click="mobileEditorMode = 'canvas'"
                    class="flex-1 py-3 text-center transition-colors min-h-[48px] flex items-center justify-center gap-1.5"
                    :class="mobileEditorMode === 'canvas' ? 'text-blue-600 border-b-2 border-blue-600 font-bold bg-blue-50/40' : 'text-zinc-500 hover:text-zinc-900'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Kanvas Portofolio</span>
                </button>
                <button
                    type="button"
                    @click="mobileEditorMode = 'toolbox'"
                    class="flex-1 py-3 text-center transition-colors min-h-[48px] flex items-center justify-center gap-1.5"
                    :class="mobileEditorMode === 'toolbox' ? 'text-blue-600 border-b-2 border-blue-600 font-bold bg-blue-50/40' : 'text-zinc-500 hover:text-zinc-900'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    <span>Alat & Template</span>
                </button>
            </div>

            <!-- --------------------------------------------------------------------- -->
            <!-- 1. LEFT TOOLBOX SIDEBAR (CANVA STYLE) -->
            <!-- --------------------------------------------------------------------- -->
            <aside
                class="w-full lg:w-80 bg-white border-r border-zinc-200/90 flex flex-col flex-shrink-0 z-20 shadow-2xs"
                :class="{ 'hidden lg:flex': mobileEditorMode === 'canvas' }"
            >
                <!-- Sidebar Category Tabs -->
                <div class="flex items-center justify-around border-b border-zinc-200 p-1.5 bg-zinc-50/60 text-xs font-semibold">
                    <button
                        @click="activeSidebarTab = 'elements'"
                        class="px-2 py-1.5 rounded-lg transition-all flex flex-col items-center gap-1"
                        :class="activeSidebarTab === 'elements' ? 'bg-white text-blue-600 font-bold shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                    >
                        <LunouIcon name="sparkles" className="w-4 h-4" />
                        <span class="text-[10px]">Elemen</span>
                    </button>

                    <button
                        @click="activeSidebarTab = 'icons'"
                        class="px-2 py-1.5 rounded-lg transition-all flex flex-col items-center gap-1"
                        :class="activeSidebarTab === 'icons' ? 'bg-white text-blue-600 font-bold shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                    >
                        <LunouIcon name="star" className="w-4 h-4" />
                        <span class="text-[10px]">Ikon</span>
                    </button>

                    <button
                        @click="activeSidebarTab = 'templates'"
                        class="px-2 py-1.5 rounded-lg transition-all flex flex-col items-center gap-1"
                        :class="activeSidebarTab === 'templates' ? 'bg-white text-blue-600 font-bold shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                        <span class="text-[10px]">Tata Letak</span>
                    </button>

                    <button
                        @click="activeSidebarTab = 'projects'"
                        class="px-2 py-1.5 rounded-lg transition-all flex flex-col items-center gap-1"
                        :class="activeSidebarTab === 'projects' ? 'bg-white text-blue-600 font-bold shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                    >
                        <LunouIcon name="briefcase" className="w-4 h-4" />
                        <span class="text-[10px]">Proyek</span>
                    </button>

                    <button
                        @click="activeSidebarTab = 'pdf'"
                        class="px-2 py-1.5 rounded-lg transition-all flex flex-col items-center gap-1"
                        :class="activeSidebarTab === 'pdf' ? 'bg-white text-blue-600 font-bold shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                    >
                        <LunouIcon name="card" className="w-4 h-4" />
                        <span class="text-[10px]">PDF Book</span>
                    </button>
                </div>

                <!-- Tab Content Body -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- 1. PLUG & PLAY ELEMENTS TAB (CANVA STYLE) -->
                    <div v-if="activeSidebarTab === 'elements'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-zinc-900">Komponen Plug & Play</span>
                            <span class="text-[10px] text-zinc-400">Klik untuk tempel</span>
                        </div>

                        <!-- Text Group -->
                        <div class="space-y-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Teks & Tipografi</span>
                            <div class="space-y-1.5">
                                <button
                                    type="button"
                                    @click="addBlock('subheading')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div>
                                        <div class="text-xs font-black text-zinc-900 group-hover:text-blue-600">+ Sub-judul / Seksi Baru</div>
                                        <div class="text-[10px] text-zinc-400">Misal: Prestasi, Penghargaan, Sertifikasi</div>
                                    </div>
                                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">H2/Tag</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('heading')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-zinc-900 group-hover:text-blue-600">+ Judul Utama (Heading)</div>
                                        <div class="text-[10px] text-zinc-400">Teks tebal untuk tajuk karya / section</div>
                                    </div>
                                    <span class="text-[11px] font-bold text-zinc-400 group-hover:text-blue-600 font-mono">H1/H2</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('text')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-zinc-800 group-hover:text-blue-600">+ Paragraf Narasi</div>
                                        <div class="text-[10px] text-zinc-400">Deskripsi diri, keahlian, atau pesan</div>
                                    </div>
                                    <span class="text-[11px] text-zinc-400 group-hover:text-blue-600">¶</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('badge')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-zinc-800 group-hover:text-blue-600">+ Badge / Pill Tag</div>
                                        <div class="text-[10px] text-zinc-400">Pill sorotan status atau spesialisasi</div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100">Badge</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('quote')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div>
                                        <div class="text-xs font-bold text-zinc-800 group-hover:text-blue-600">+ Kutipan Inspiratif (Quote)</div>
                                        <div class="text-[10px] text-zinc-400">Filosofi kerja atau testimoni</div>
                                    </div>
                                    <LunouIcon name="quote" className="w-3.5 h-3.5 text-zinc-400 group-hover:text-blue-600" />
                                </button>
                            </div>
                        </div>

                        <!-- Icons & Visual Group -->
                        <div class="space-y-1.5 pt-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Foto, Prestasi & Media</span>
                            <div class="space-y-2">
                                <button
                                    type="button"
                                    @click="addBlock('photo_card')"
                                    class="w-full p-2.5 rounded-xl border border-blue-200 hover:border-blue-500 bg-blue-50/30 hover:bg-blue-50/60 text-left transition-all flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
                                            <LunouIcon name="camera" className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-blue-950 group-hover:text-blue-700">+ Foto & Keterangan (Prestasi)</div>
                                            <div class="text-[10px] text-blue-800/70">Foto bebas + judul + keterangan di bawahnya</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-blue-600 font-bold">&rarr;</span>
                                </button>

                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="addBlock('icon', { name: 'sparkles', label: '' })"
                                        class="p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                                    >
                                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                                            <LunouIcon name="sparkles" className="w-4 h-4" />
                                        </div>
                                        <div class="text-xs font-bold text-zinc-900 group-hover:text-blue-600">+ Stiker Ikon</div>
                                        <div class="text-[10px] text-zinc-400">27+ Ikon vector</div>
                                    </button>

                                    <button
                                        type="button"
                                        @click="addBlock('photo_grid')"
                                        class="p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                                    >
                                        <div class="w-7 h-7 rounded-lg bg-zinc-100 text-zinc-700 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        </div>
                                        <div class="text-xs font-bold text-zinc-900 group-hover:text-blue-600">+ Grid 2 Foto</div>
                                        <div class="text-[10px] text-zinc-400">Foto berjejer</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons & Links Group -->
                        <div class="space-y-1.5 pt-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Tombol & Tautan (Plug & Play)</span>
                            <div class="space-y-1.5">
                                <button
                                    type="button"
                                    @click="addBlock('button', { label: 'Chat WhatsApp Sekarang', url: 'https://wa.me/', icon: 'whatsapp', variant: 'emerald' })"
                                    class="w-full p-2.5 rounded-xl border border-emerald-200 hover:border-emerald-500 bg-emerald-50/40 hover:bg-emerald-50 text-left transition-all flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-emerald-600 text-white flex items-center justify-center">
                                            <LunouIcon name="whatsapp" className="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-emerald-950">+ Tombol WhatsApp</div>
                                            <div class="text-[10px] text-emerald-700">Langsung klik hubungi WhatsApp</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-emerald-600 font-bold">&rarr;</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('button', { label: 'Lihat Karya Lengkap', url: '#', icon: 'sparkles', variant: 'black' })"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-zinc-900 bg-white hover:bg-zinc-50 text-left transition-all flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-zinc-900 text-white flex items-center justify-center">
                                            <LunouIcon name="sparkles" className="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-zinc-900">+ Tombol Aksi (CTA)</div>
                                            <div class="text-[10px] text-zinc-400">Desain modern hitam / biru</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-zinc-400 font-bold">&rarr;</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('link_card')"
                                    class="w-full p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center">
                                            <LunouIcon name="card" className="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-zinc-900 group-hover:text-blue-600">+ Kartu Tautan (Link Card)</div>
                                            <div class="text-[10px] text-zinc-400">Kartu profil dengan icon & arrow</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-zinc-400 group-hover:text-blue-600 font-bold">&rarr;</span>
                                </button>

                                <button
                                    type="button"
                                    @click="addBlock('divider')"
                                    class="w-full p-2 rounded-xl border border-zinc-200 hover:border-zinc-400 bg-white text-left transition-all text-xs font-medium text-zinc-600 flex items-center justify-between"
                                >
                                    <span>+ Garis Pemisah (Divider)</span>
                                    <span class="text-zinc-300">————</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ICONS & STICKERS TAB (VISUAL GRID) -->
                    <div v-if="activeSidebarTab === 'icons'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-zinc-900">Koleksi Ikon & Stiker</span>
                            <span class="text-[10px] text-zinc-400">Klik untuk pasang</span>
                        </div>

                        <!-- Search icon box -->
                        <div class="relative">
                            <input
                                v-model="iconSearchQuery"
                                placeholder="Cari ikon (cth: whatsapp, ig, bintang)..."
                                class="w-full px-3 py-1.5 pl-8 rounded-xl border border-zinc-200 text-xs bg-zinc-50 focus:bg-white focus:outline-none focus:border-blue-500"
                            />
                            <div class="absolute left-2.5 top-2 text-zinc-400">
                                <LunouIcon name="search" className="w-3.5 h-3.5" />
                            </div>
                            <button
                                v-if="iconSearchQuery"
                                @click="iconSearchQuery = ''"
                                class="absolute right-2.5 top-2 text-zinc-400 hover:text-zinc-700 text-xs"
                            >
                                &times;
                            </button>
                        </div>

                        <!-- Grid of Icons -->
                        <div class="grid grid-cols-3 gap-2 pt-1 max-h-[500px] overflow-y-auto pr-1">
                            <button
                                v-for="icon in filteredIcons"
                                :key="icon.name"
                                type="button"
                                @click="addBlock('icon', { name: icon.name, label: icon.label })"
                                class="p-2.5 rounded-xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 flex flex-col items-center justify-center gap-1.5 transition-all group"
                                :title="`Tambah stiker ${icon.label}`"
                            >
                                <div class="w-8 h-8 rounded-lg bg-zinc-50 group-hover:bg-blue-50 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center transition-all group-hover:scale-110">
                                    <LunouIcon :name="icon.name" className="w-4 h-4" />
                                </div>
                                <span class="text-[10px] font-semibold text-zinc-700 group-hover:text-blue-600 truncate max-w-full text-center">
                                    {{ icon.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- 3. TEMPLATES TAB -->
                    <div v-if="activeSidebarTab === 'templates'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900">Tata Letak Siap Pakai</span>
                            <span class="text-[10px] text-zinc-400">Klik untuk tambah</span>
                        </div>

                        <!-- Blank Canvas Option (Highlighted) -->
                        <button
                            @click="addNewPage('custom')"
                            class="w-full p-3 rounded-2xl border-2 border-blue-500 bg-blue-50/40 hover:bg-blue-50 text-left transition-all flex items-center justify-between group"
                        >
                            <div>
                                <span class="text-[10px] font-black text-blue-600 uppercase tracking-wider">Desain Bebas Gaya Canva</span>
                                <div class="text-xs font-extrabold text-zinc-950 mt-0.5">+ Halaman Kosong (Kanvas Bebas)</div>
                                <p class="text-[10px] text-zinc-600 mt-0.5">Mulai dari lembar putih, tempel elemen sesukamu.</p>
                            </div>
                            <span class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm group-hover:scale-110 transition-transform">+</span>
                        </button>

                        <!-- Prestasi & Rekam Jejak Template Button -->
                        <button
                            @click="addPrestasiTemplate"
                            class="w-full p-3 rounded-2xl border border-amber-300 bg-amber-50/50 hover:bg-amber-50 text-left transition-all flex items-center justify-between group"
                        >
                            <div>
                                <span class="text-[10px] font-black text-amber-700 uppercase tracking-wider">Template Populer</span>
                                <div class="text-xs font-extrabold text-amber-950 mt-0.5">+ Seksi Prestasi & Penghargaan</div>
                                <p class="text-[10px] text-amber-800/80 mt-0.5">Subjudul prestasi + foto & keterangan di bawahnya.</p>
                            </div>
                            <span class="w-7 h-7 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-xs group-hover:scale-110 transition-transform">
                                <LunouIcon name="star" className="w-3.5 h-3.5" />
                            </span>
                        </button>

                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <button
                                @click="addNewPage('cover')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Cover Depan</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Hero & Judul</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Foto pembuka, nama, dan tajuk.</p>
                            </button>

                            <button
                                @click="addNewPage('about')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Profil</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Tentang Saya</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Bio narasi & daftar keahlian.</p>
                            </button>

                            <button
                                @click="addNewPage('projects')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Karya</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Proyek Terpilih</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Kartu proyek & studi kasus.</p>
                            </button>

                            <button
                                @click="addNewPage('gallery')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Visual</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Galeri Foto</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Grid gambar & dokumentasi.</p>
                            </button>

                            <button
                                @click="addNewPage('pdf_book')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Dokumen</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Buku Digital</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Flipbook interaktif dari PDF.</p>
                            </button>

                            <button
                                @click="addNewPage('contact')"
                                class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/20 text-left transition-all group"
                            >
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Kontak</span>
                                <div class="text-xs font-bold text-zinc-900 mt-1">Hubungi Saya</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Email, telepon, & lokasi.</p>
                            </button>
                        </div>
                    </div>

                    <!-- 4. PROJECTS TAB -->
                    <div v-if="activeSidebarTab === 'projects'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900">Kartu Proyek & Studi Kasus</span>
                            <button
                                v-if="activePage?.page_type === 'projects'"
                                @click="addProjectItem"
                                class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-[11px] font-bold hover:bg-blue-100 transition-colors"
                            >
                                + Tambah Proyek
                            </button>
                        </div>

                        <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100/80 text-[11px] text-blue-900 leading-snug space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <LunouIcon name="sparkles" className="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                                <span>Mandiri & Lengkap di Sini</span>
                            </div>
                            <p class="text-zinc-600">
                                Pengunjung dapat membaca seluruh studi kasus, tantangan, solusi, dan hasil karya langsung di sini tanpa harus keluar ke website lain. Tautan eksternal (pihak ke-3) bersifat opsional.
                            </p>
                        </div>

                        <div v-if="activePage?.page_type !== 'projects'" class="p-4 rounded-2xl border border-blue-200 bg-blue-50/40 text-center space-y-3">
                            <div class="text-xs text-zinc-700">
                                <span class="font-bold text-zinc-900 block">Halaman Aktif: {{ activePage?.title || 'Bukan Proyek' }}</span>
                                <span class="text-[11px] text-zinc-500">Pengaturan kartu studi kasus dikhususkan untuk halaman bertipe Proyek.</span>
                            </div>

                            <div v-if="localPortfolio.pages && localPortfolio.pages.findIndex(p => p.page_type === 'projects') !== -1" class="space-y-1.5 pt-1">
                                <button
                                    type="button"
                                    @click="selectPage(localPortfolio.pages.findIndex(p => p.page_type === 'projects'))"
                                    class="w-full py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <span>Buka Halaman Proyek (Hal. {{ localPortfolio.pages.findIndex(p => p.page_type === 'projects') + 1 }})</span>
                                    <span>&rarr;</span>
                                </button>
                                <p class="text-[10px] text-zinc-500">Beralih langsung ke halaman proyek tanpa membuat halaman duplikat.</p>
                            </div>

                            <div v-else class="space-y-1.5 pt-1">
                                <button
                                    type="button"
                                    @click="addNewPage('projects')"
                                    class="w-full py-2 px-3 rounded-xl bg-zinc-950 text-white text-xs font-bold hover:bg-black transition-all cursor-pointer"
                                >
                                    + Buat Halaman Proyek Baru
                                </button>
                                <p class="text-[10px] text-zinc-500">Tambahkan halaman khusus untuk menampilkan kartu studi kasus karya Anda.</p>
                            </div>
                        </div>

                        <div v-else-if="!activePage.content?.items || activePage.content.items.length === 0" class="text-center py-6 border-2 border-dashed border-zinc-200 rounded-2xl text-zinc-400 text-xs">
                            Belum ada kartu proyek. Klik tombol '+ Tambah Proyek' di atas.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(item, idx) in activePage.content.items"
                                :key="idx"
                                class="p-3 rounded-xl border border-zinc-200 bg-white hover:border-zinc-300 transition-all space-y-2"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-zinc-900 truncate">{{ item.title || `Proyek #${idx+1}` }}</span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 font-semibold uppercase">{{ item.category || 'Proyek' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 pt-1">
                                    <button
                                        type="button"
                                        @click="openEditProjectModal(idx)"
                                        class="flex-1 py-1.5 rounded-lg bg-zinc-900 hover:bg-black text-white text-[10px] font-bold text-center transition-colors"
                                    >
                                        Edit Detail Lengkap
                                    </button>
                                    <button
                                        type="button"
                                        @click="previewProjectDetailInEditor(item)"
                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 hover:bg-zinc-100 text-zinc-700 text-[10px] font-semibold transition-colors"
                                        title="Pratinjau Detail"
                                    >
                                        Lihat
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. PDF TAB -->
                    <div v-if="activeSidebarTab === 'pdf'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900">Dokumen PDF (Buku Digital)</span>
                            <button
                                @click="isUploadPdfModalOpen = true"
                                class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-[11px] font-bold hover:bg-blue-100"
                            >
                                + Unggah PDF
                            </button>
                        </div>

                        <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 text-[11px] text-blue-900 leading-snug space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <LunouIcon name="book-open" className="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                                <span>Flipbook Digital Interaktif</span>
                            </div>
                            <p class="text-zinc-600">Maks. 10 berkas PDF (maks. 5 MB/berkas). Saat dipasang, dokumen otomatis ditempatkan pada halaman Buku Digital tanpa menimpa Cover atau Profil Anda.</p>
                        </div>

                        <div v-if="!localPortfolio.assets || localPortfolio.assets.filter(a => a.file_type === 'pdf').length === 0" class="text-center py-6 border-2 border-dashed border-zinc-200 rounded-2xl text-zinc-400 text-xs">
                            Belum ada dokumen PDF diunggah.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="asset in localPortfolio.assets.filter(a => a.file_type === 'pdf')"
                                :key="asset.id"
                                class="p-3 rounded-xl border border-zinc-200 bg-white hover:border-zinc-300 flex items-center justify-between gap-2"
                            >
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-zinc-900 truncate">{{ asset.title }}</div>
                                    <div class="text-[10px] text-zinc-400 font-mono">{{ asset.formatted_size }}</div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button
                                        @click="attachPdfToCurrentPage(asset.id)"
                                        class="px-2.5 py-1.5 rounded-lg bg-zinc-900 text-white text-[10px] font-bold hover:bg-black transition-colors cursor-pointer"
                                        title="Pasang ke halaman Buku Digital"
                                    >
                                        Pasang PDF
                                    </button>
                                    <button
                                        @click="deleteAsset(asset.id)"
                                        class="p-1 rounded text-zinc-400 hover:text-rose-600"
                                        title="Hapus berkas"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- --------------------------------------------------------------------- -->
            <!-- 2. CENTER WYSIWYG CANVAS WORKSPACE -->
            <!-- --------------------------------------------------------------------- -->
            <section
                class="flex-1 flex flex-col bg-zinc-100/70 overflow-hidden relative"
                :class="{ 'hidden lg:flex': mobileEditorMode === 'toolbox' }"
            >
                <!-- Canvas Top Context Ribbon -->
                <div class="border-b border-zinc-200/80 bg-white/80 backdrop-blur-xs px-3 sm:px-4 py-2 flex items-center justify-between gap-2 text-xs text-zinc-500 min-h-10">
                    <div class="flex items-center gap-1.5 min-w-0 flex-1">
                        <span class="font-bold text-zinc-800 whitespace-nowrap text-[11px] sm:text-xs flex-shrink-0">
                            Hal. {{ selectedPageIndex + 1 }}/{{ localPortfolio.pages?.length || 1 }}:
                        </span>
                        <input
                            v-if="activePage"
                            v-model="activePage.title"
                            @input="markUnsaved"
                            class="bg-transparent border-b border-dashed border-zinc-300 font-medium text-zinc-800 px-1 py-0.5 text-xs focus:border-blue-500 focus:outline-none min-w-[80px] max-w-[140px] sm:max-w-xs truncate"
                        />
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0 text-[11px]">
                        <!-- Quick Move Page in Canvas Bar -->
                        <div class="flex items-center bg-zinc-100 rounded-lg p-0.5 border border-zinc-200/80" title="Atur Posisi Halaman Ini">
                            <button
                                type="button"
                                @click="shiftPage(selectedPageIndex, -1)"
                                :disabled="selectedPageIndex === 0"
                                class="px-2 py-0.5 rounded text-[10px] font-bold text-zinc-600 hover:text-blue-600 hover:bg-white disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-400 transition-all flex items-center gap-0.5 cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                                title="Geser ke kiri / halaman sebelumnya"
                            >
                                &larr; Geser Kiri
                            </button>
                            <button
                                type="button"
                                @click="shiftPage(selectedPageIndex, 1)"
                                :disabled="selectedPageIndex === (localPortfolio.pages?.length || 1) - 1"
                                class="px-2 py-0.5 rounded text-[10px] font-bold text-zinc-600 hover:text-blue-600 hover:bg-white disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-400 transition-all flex items-center gap-0.5 cursor-pointer disabled:cursor-not-allowed shadow-2xs"
                                title="Geser ke kanan / halaman berikutnya"
                            >
                                Geser Kanan &rarr;
                            </button>
                        </div>
                        <span class="text-zinc-300">|</span>
                        <button
                            @click="duplicateCurrentPage"
                            class="hover:text-blue-600 font-semibold px-1.5 py-0.5 rounded-md hover:bg-zinc-100"
                            title="Duplikasi halaman ini"
                        >
                            <span>Duplikasi</span>
                        </button>
                        <span class="text-zinc-300">|</span>
                        <button
                            @click="deleteCurrentPage"
                            class="hover:text-rose-600 font-semibold px-1.5 py-0.5 rounded-md hover:bg-zinc-100"
                            title="Hapus halaman ini"
                        >
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>

                <!-- Helpful Tips Banner for Beginners (HP & Komputer) -->
                <div
                    v-if="!dismissHelperBanner"
                    class="mx-3 sm:mx-6 mt-3 p-3.5 rounded-2xl bg-gradient-to-r from-blue-50 via-indigo-50/60 to-blue-50 border border-blue-200/90 flex items-start justify-between gap-3 shadow-2xs transition-all"
                >
                    <div class="flex items-start gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">
                            <LunouIcon name="help" className="w-4 h-4" />
                        </div>
                        <div class="space-y-1 min-w-0 text-xs">
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <span class="font-extrabold text-blue-950 text-xs">Tips Mudah Pakai di HP & Komputer:</span>
                                <span class="px-2 py-0.5 rounded-full bg-blue-200/80 text-blue-950 text-[9px] font-bold uppercase">Panduan Cepat</span>
                            </div>
                            <p class="text-[11px] text-blue-950/80 leading-relaxed">
                                <strong>1. Ketik Langsung:</strong> Klik/ketuk teks di layar untuk mengedit. &bull;
                                <strong>2. Unggah Foto:</strong> Tekan tombol kamera / Unggah untuk pasang foto (otomatis WebP). &bull;
                                <strong>3. Tambah Elemen:</strong> Tekan tombol <span class="font-bold underline text-blue-700 cursor-pointer" @click="isMobileQuickAddOpen = true">+ Tambah Elemen</span> di bawah untuk tambah Sub-judul, Prestasi, atau Tombol WhatsApp.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button
                            type="button"
                            @click="isHelpGuideModalOpen = true"
                            class="px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] transition-colors shadow-2xs whitespace-nowrap"
                        >
                            Panduan Lengkap
                        </button>
                        <button
                            type="button"
                            @click="dismissHelperBanner = true"
                            class="w-7 h-7 rounded-lg text-blue-400 hover:text-blue-800 hover:bg-blue-100 flex items-center justify-center text-base font-bold transition-colors"
                            title="Tutup tips ini"
                        >
                            &times;
                        </button>
                    </div>
                </div>

                <!-- Canvas Scroll Area -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-8 flex items-center justify-center">
                    <!-- Dynamic Responsive Canvas Card -->
                    <div
                        class="bg-white rounded-3xl shadow-lg border border-zinc-200/90 transition-all duration-300 p-6 sm:p-10 relative overflow-hidden"
                        :class="{
                            'w-full max-w-4xl min-h-[580px]': previewDevice === 'desktop',
                            'w-full max-w-2xl min-h-[500px]': previewDevice === 'tablet',
                            'w-full max-w-sm min-h-[520px]': previewDevice === 'mobile',
                        }"
                    >
                        <!-- Active Page Template Renderer with Inline Editing -->
                        <div v-if="activePage" class="space-y-6">
                            <!-- A. COVER PAGE -->
                            <div v-if="activePage.page_type === 'cover'" class="space-y-6">
                                <div class="relative h-48 sm:h-64 rounded-2xl overflow-hidden bg-zinc-100 group">
                                    <img
                                        :src="activePage.content?.cover_image || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80'"
                                        alt="Cover"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute bottom-2 right-2 sm:bottom-3 sm:right-3 bg-black/70 backdrop-blur-md px-2.5 py-1.5 rounded-xl text-white text-[10px] sm:text-[11px] font-medium flex items-center gap-1.5 max-w-[95%]">
                                        <input
                                            ref="coverFileInput"
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            class="hidden"
                                            @change="onCoverFileChange"
                                        />
                                        <button
                                            type="button"
                                            @click="coverFileInput?.click()"
                                            :disabled="coverUploading"
                                            class="px-2.5 py-1 bg-white hover:bg-zinc-100 text-zinc-950 font-bold rounded-lg text-[10px] inline-flex items-center gap-1 transition-all shadow-xs"
                                        >
                                            <LunouIcon name="camera" className="w-3 h-3 text-zinc-700" />
                                            <span>{{ coverUploading ? 'Mengunggah...' : 'Unggah Cover' }}</span>
                                        </button>
                                        <span class="text-white/40 hidden sm:inline">|</span>
                                        <span class="hidden sm:inline">URL:</span>
                                        <input
                                            v-model="activePage.content.cover_image"
                                            @input="markUnsaved"
                                            placeholder="https://..."
                                            class="bg-white/20 text-white rounded px-2 py-0.5 text-[10px] w-24 sm:w-32 focus:outline-none"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <input
                                        v-model="activePage.content.headline"
                                        @input="markUnsaved"
                                        placeholder="Judul Utama Portofolio..."
                                        class="w-full text-2xl sm:text-4xl font-extrabold tracking-tight text-zinc-950 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                    <textarea
                                        v-model="activePage.content.subheadline"
                                        @input="markUnsaved"
                                        placeholder="Subjudul atau penjelasan singkat..."
                                        rows="2"
                                        class="w-full text-sm sm:text-base text-zinc-600 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- B. ABOUT PAGE -->
                            <div v-else-if="activePage.page_type === 'about'" class="space-y-6">
                                <input
                                    v-model="activePage.content.heading"
                                    @input="markUnsaved"
                                    placeholder="Judul Tentang Saya..."
                                    class="w-full text-2xl sm:text-3xl font-extrabold text-zinc-950 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                />

                                <textarea
                                    v-model="activePage.content.bio_paragraph"
                                    @input="markUnsaved"
                                    rows="5"
                                    placeholder="Ceritakan latar belakang, visi, dan pencapaian Anda..."
                                    class="w-full text-sm text-zinc-700 leading-relaxed border rounded-xl p-3 border-zinc-200 hover:border-zinc-300 focus:border-blue-500 focus:outline-none"
                                ></textarea>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-zinc-700">Daftar Keahlian (Pisahkan dengan koma):</label>
                                    <input
                                        :value="activePage.content.skills ? activePage.content.skills.join(', ') : ''"
                                        @input="(e) => { activePage.content.skills = e.target.value.split(',').map(s => s.trim()); markUnsaved(); }"
                                        placeholder="Contoh: Desain Grafis, UI/UX, Storytelling"
                                        class="w-full px-3 py-2 rounded-xl border border-zinc-200 text-xs font-medium"
                                    />
                                </div>
                            </div>

                            <!-- C. PROJECTS PAGE -->
                            <div v-else-if="activePage.page_type === 'projects'" class="space-y-6">
                                <div>
                                    <input
                                        v-model="activePage.content.heading"
                                        @input="markUnsaved"
                                        placeholder="Judul Proyek..."
                                        class="w-full text-2xl sm:text-3xl font-extrabold text-zinc-950 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                    <input
                                        v-model="activePage.content.description"
                                        @input="markUnsaved"
                                        placeholder="Deskripsi singkat galeri proyek..."
                                        class="w-full text-xs text-zinc-500 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div
                                        v-for="(item, idx) in activePage.content?.items || []"
                                        :key="idx"
                                        class="p-4 rounded-2xl border border-zinc-200 bg-zinc-50/70 space-y-3 relative group hover:border-zinc-300 transition-all shadow-2xs"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-600 font-bold text-[9px] uppercase tracking-wider border border-blue-100">
                                                {{ item.category || 'Kategori' }}
                                            </span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    @click="openEditProjectModal(idx)"
                                                    class="p-1 rounded-md text-zinc-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                    title="Edit studi kasus & detail lengkap"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="removeProjectItem(idx)"
                                                    class="p-1 rounded-md text-zinc-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                    title="Hapus proyek ini"
                                                >
                                                    &times;
                                                </button>
                                            </div>
                                        </div>

                                        <div class="h-28 rounded-xl overflow-hidden bg-zinc-200 relative group/img">
                                            <img
                                                :src="item.image || 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80'"
                                                :alt="item.title"
                                                class="w-full h-full object-cover"
                                            />
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center p-2">
                                                <input
                                                    v-model="item.image"
                                                    @input="markUnsaved"
                                                    placeholder="URL Gambar Thumbnail..."
                                                    class="w-full bg-white text-zinc-900 rounded-lg px-2 py-1 text-[10px] focus:outline-none"
                                                />
                                            </div>
                                        </div>

                                        <input
                                            v-model="item.title"
                                            @input="markUnsaved"
                                            placeholder="Nama Proyek..."
                                            class="w-full font-bold text-sm text-zinc-950 bg-transparent border-b border-transparent hover:border-zinc-300 focus:outline-none"
                                        />

                                        <textarea
                                            v-model="item.description"
                                            @input="markUnsaved"
                                            rows="2"
                                            placeholder="Deskripsi ringkas untuk kartu depan..."
                                            class="w-full text-xs text-zinc-600 bg-transparent border-b border-transparent hover:border-zinc-300 focus:outline-none"
                                        ></textarea>

                                        <div class="pt-2 border-t border-zinc-200/80 flex items-center justify-between gap-2">
                                            <button
                                                type="button"
                                                @click="openEditProjectModal(idx)"
                                                class="px-2.5 py-1.5 rounded-lg bg-zinc-900 hover:bg-black text-white text-[11px] font-bold inline-flex items-center gap-1 transition-all"
                                            >
                                                <span>Edit Detail Lengkap</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>

                                            <button
                                                type="button"
                                                @click="previewProjectDetailInEditor(item)"
                                                class="px-2.5 py-1.5 rounded-lg border border-zinc-200 hover:bg-zinc-100 text-zinc-700 text-[11px] font-semibold inline-flex items-center gap-1 transition-all"
                                                title="Lihat modal detail studi kasus"
                                            >
                                                <span>Pratinjau</span>
                                                <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Project Button in Canvas -->
                                <div class="pt-2 flex justify-center">
                                    <button
                                        type="button"
                                        @click="addProjectItem"
                                        class="px-4 py-2 rounded-xl border border-dashed border-zinc-300 hover:border-blue-500 hover:bg-blue-50/30 text-xs font-bold text-zinc-700 hover:text-blue-600 transition-all inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Tambah Kartu Proyek Baru</span>
                                    </button>
                                </div>
                            </div>

                            <!-- D. PDF FLIPBOOK PAGE -->
                            <div v-else-if="activePage.page_type === 'pdf_book'" class="space-y-6 text-center">
                                <div>
                                    <input
                                        v-model="activePage.content.heading"
                                        @input="markUnsaved"
                                        placeholder="Judul Buku Portofolio..."
                                        class="w-full text-2xl font-extrabold text-zinc-950 text-center border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                    <input
                                        v-model="activePage.content.description"
                                        @input="markUnsaved"
                                        placeholder="Deskripsi e-book atau dokumen..."
                                        class="w-full text-xs text-zinc-500 text-center border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                </div>

                                <div class="p-8 rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/30 flex flex-col items-center justify-center space-y-3">
                                    <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-xs">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                    <div class="text-sm font-bold text-zinc-900">Mode Flipbook 3D Aktif</div>
                                    <p class="text-xs text-zinc-500 max-w-sm">
                                        Dokumen PDF yang dipasang di halaman ini akan otomatis ditampilkan dengan efek membuka halaman (flipbook) serta opsi pembaca biasa di website publik.
                                    </p>
                                    <button
                                        @click="activeSidebarTab = 'pdf'"
                                        class="px-4 py-2 rounded-xl bg-zinc-900 hover:bg-black text-white text-xs font-bold transition-all"
                                    >
                                        Pilih Berkas PDF dari Media
                                    </button>
                                </div>
                            </div>

                            <!-- E. CONTACT PAGE -->
                            <div v-else-if="activePage.page_type === 'contact'" class="space-y-6">
                                <div>
                                    <input
                                        v-model="activePage.content.heading"
                                        @input="markUnsaved"
                                        placeholder="Judul Kontak..."
                                        class="w-full text-2xl font-extrabold text-zinc-950 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                    <input
                                        v-model="activePage.content.description"
                                        @input="markUnsaved"
                                        placeholder="Ajakan kerja sama atau kolaborasi..."
                                        class="w-full text-xs text-zinc-500 border-b border-transparent hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                    />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-bold text-zinc-700">Email:</label>
                                        <input
                                            v-model="activePage.content.email"
                                            @input="markUnsaved"
                                            placeholder="email@anda.com"
                                            class="w-full px-3 py-2 rounded-xl border border-zinc-200 text-xs mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-zinc-700">Nomor Telepon / WhatsApp:</label>
                                        <input
                                            v-model="activePage.content.phone"
                                            @input="markUnsaved"
                                            placeholder="+62 8..."
                                            class="w-full px-3 py-2 rounded-xl border border-zinc-200 text-xs mt-1"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- F. CUSTOM / GENERIC -->
                            <div v-else class="space-y-4">
                                <input
                                    v-model="activePage.content.heading"
                                    @input="markUnsaved"
                                    placeholder="Judul Halaman..."
                                    class="w-full text-2xl font-extrabold text-zinc-950 border-b border-transparent hover:border-zinc-300 focus:outline-none py-1"
                                />
                                <textarea
                                    v-model="activePage.content.description"
                                    @input="markUnsaved"
                                    rows="4"
                                    placeholder="Tuliskan konten atau susunan materi Anda di sini..."
                                    class="w-full text-xs text-zinc-700 border rounded-xl p-3 border-zinc-200 focus:outline-none"
                                ></textarea>
                            </div>

                            <!-- G. CANVA DYNAMIC BLOCKS SECTION (PLUG & PLAY FREE POSITIONING) -->
                            <div class="mt-8 pt-6 border-t border-dashed border-zinc-200/90 space-y-4">
                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <div class="flex items-center gap-1.5 font-bold text-zinc-800">
                                        <LunouIcon name="sparkles" className="w-4 h-4 text-blue-600 flex-shrink-0" />
                                        <span>Elemen Bebas Halaman Ini ({{ activePage.content?.blocks?.length || 0 }})</span>
                                    </div>
                                    <span class="text-[11px] text-zinc-400 hidden sm:inline">Bisa digeser, ubah urutan, tempel icon & tautan</span>
                                </div>

                                <!-- Render Blocks List -->
                                <div v-if="activePage.content?.blocks && activePage.content.blocks.length > 0" class="space-y-3">
                                    <div
                                        v-for="(block, bIdx) in activePage.content.blocks"
                                        :key="block.id || bIdx"
                                        draggable="true"
                                        @dragstart="onBlockDragStart(bIdx, $event)"
                                        @dragover.prevent="onBlockDragOver(bIdx, $event)"
                                        @drop="onBlockDrop(bIdx, $event)"
                                        class="group/block relative p-3 sm:p-4 rounded-2xl border transition-all"
                                        :class="[
                                            draggedBlockIndex === bIdx
                                                ? 'opacity-40 border-blue-400 border-dashed bg-blue-50/20 shadow-none'
                                                : 'border-zinc-200/90 hover:border-blue-400 bg-zinc-50/40 hover:bg-white hover:shadow-xs'
                                        ]"
                                    >
                                        <!-- Block Control Bar (Canva Style) -->
                                        <div class="flex items-center justify-between pb-2 mb-3 border-b border-zinc-200/60 text-xs">
                                            <!-- Drag Handle & Reorder -->
                                            <div class="flex items-center gap-1.5">
                                                <span class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-blue-600 font-mono text-[11px] select-none flex items-center gap-1 px-1.5 py-0.5 rounded bg-zinc-100 hover:bg-blue-50" title="Klik dan tahan untuk menggeser urutan">
                                                    <span>⠿</span>
                                                    <span class="text-[9px] font-bold uppercase tracking-wider hidden sm:inline">Geser</span>
                                                </span>
                                                <button
                                                    type="button"
                                                    @click="moveBlock(bIdx, -1)"
                                                    :disabled="bIdx === 0"
                                                    class="p-1 rounded text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 disabled:opacity-20 disabled:hover:bg-transparent"
                                                    title="Pindah ke Atas"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="moveBlock(bIdx, 1)"
                                                    :disabled="bIdx === activePage.content.blocks.length - 1"
                                                    class="p-1 rounded text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 disabled:opacity-20 disabled:hover:bg-transparent"
                                                    title="Pindah ke Bawah"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <span class="text-[9px] font-bold uppercase tracking-wider text-blue-600 px-1.5 py-0.5 rounded bg-blue-50 border border-blue-100 font-mono">
                                                    {{ block.type }}
                                                </span>
                                            </div>

                                            <!-- Alignment & Actions -->
                                            <div class="flex items-center gap-1">
                                                <!-- Alignment Selector -->
                                                <div v-if="['heading', 'text', 'badge', 'icon', 'image', 'button', 'quote'].includes(block.type)" class="flex items-center rounded-lg bg-zinc-100 p-0.5">
                                                    <button
                                                        type="button"
                                                        @click="block.align = 'left'; markUnsaved()"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-all"
                                                        :class="block.align === 'left' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                                                        title="Rata Kiri"
                                                    >
                                                        Kiri
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.align = 'center'; markUnsaved()"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-all"
                                                        :class="block.align === 'center' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                                                        title="Rata Tengah"
                                                    >
                                                        Tengah
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.align = 'right'; markUnsaved()"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-all"
                                                        :class="block.align === 'right' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500 hover:text-zinc-900'"
                                                        title="Rata Kanan"
                                                    >
                                                        Kanan
                                                    </button>
                                                </div>

                                                <!-- Duplicate -->
                                                <button
                                                    type="button"
                                                    @click="duplicateBlock(bIdx)"
                                                    class="p-1 rounded text-zinc-400 hover:text-blue-600 hover:bg-blue-50"
                                                    title="Duplikasi elemen ini"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                                </button>

                                                <!-- Delete -->
                                                <button
                                                    type="button"
                                                    @click="deleteBlock(bIdx)"
                                                    class="p-1 rounded text-zinc-400 hover:text-rose-600 hover:bg-rose-50"
                                                    title="Hapus elemen ini"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 1. HEADING BLOCK -->
                                        <div v-if="block.type === 'heading'" class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                    <button
                                                        type="button"
                                                        @click="block.level = 'h1'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.level === 'h1' ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                    >
                                                        H1
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.level = 'h2'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.level === 'h2' ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                    >
                                                        H2
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.level = 'h3'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.level === 'h3' ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                    >
                                                        H3
                                                    </button>
                                                </div>
                                                <span class="text-[10px] text-zinc-400">Ukuran Judul</span>
                                            </div>
                                            <input
                                                v-model="block.text"
                                                @input="markUnsaved"
                                                placeholder="Tuliskan Judul..."
                                                class="w-full font-black text-zinc-950 bg-transparent border-b border-dashed border-zinc-200 hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                                :class="{
                                                    'text-2xl sm:text-4xl': block.level === 'h1',
                                                    'text-xl sm:text-2xl': block.level === 'h2',
                                                    'text-lg sm:text-xl': block.level === 'h3',
                                                    'text-left': block.align === 'left',
                                                    'text-center': block.align === 'center',
                                                    'text-right': block.align === 'right',
                                                }"
                                            />
                                        </div>

                                        <!-- 1B. SUBHEADING / SECTION HEADER BLOCK (PRESTASI / SEKSI BEBAS) -->
                                        <div v-else-if="block.type === 'subheading' || block.type === 'section_header'" class="space-y-3">
                                            <div class="flex items-center gap-2">
                                                <input
                                                    v-model="block.tag"
                                                    @input="markUnsaved"
                                                    placeholder="TAG / KATEGORI (cth: PRESTASI & PENGHARGAAN)..."
                                                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 focus:outline-none focus:ring-1 focus:ring-blue-500 w-auto max-w-xs"
                                                />
                                                <span class="text-[10px] text-zinc-400">Pill Label</span>
                                            </div>
                                            <input
                                                v-model="block.title"
                                                @input="markUnsaved"
                                                placeholder="Judul Seksi / Subjudul (cth: Prestasi & Pencapaian)..."
                                                class="w-full text-xl sm:text-3xl font-black text-zinc-950 bg-transparent border-b border-dashed border-zinc-200 hover:border-zinc-300 focus:border-blue-500 focus:outline-none py-1"
                                                :class="{
                                                    'text-left': block.align === 'left',
                                                    'text-center': block.align === 'center',
                                                    'text-right': block.align === 'right',
                                                }"
                                            />
                                            <textarea
                                                v-model="block.subtitle"
                                                @input="markUnsaved"
                                                rows="2"
                                                placeholder="Keterangan singkat seksi ini (cth: Koleksi penghargaan dan rekognisi industri)..."
                                                class="w-full text-xs sm:text-sm text-zinc-600 bg-transparent border rounded-xl p-2.5 border-zinc-200 hover:border-zinc-300 focus:border-blue-500 focus:outline-none leading-relaxed"
                                                :class="{
                                                    'text-left': block.align === 'left',
                                                    'text-center': block.align === 'center',
                                                    'text-right': block.align === 'right',
                                                }"
                                            ></textarea>
                                        </div>

                                        <!-- 2. TEXT BLOCK -->
                                        <div v-else-if="block.type === 'text'" class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                    <button
                                                        type="button"
                                                        @click="block.size = 'sm'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.size === 'sm' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500'"
                                                    >
                                                        Kecil
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.size = 'base'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.size === 'base' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500'"
                                                    >
                                                        Normal
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="block.size = 'lg'; markUnsaved()"
                                                        class="px-2 py-0.5 rounded transition-all"
                                                        :class="block.size === 'lg' ? 'bg-white text-blue-600 shadow-2xs' : 'text-zinc-500'"
                                                    >
                                                        Besar
                                                    </button>
                                                </div>
                                                <span class="text-[10px] text-zinc-400">Ukuran Teks</span>
                                            </div>
                                            <textarea
                                                v-model="block.text"
                                                @input="markUnsaved"
                                                rows="3"
                                                placeholder="Tuliskan narasi atau penjelasan..."
                                                class="w-full text-zinc-700 bg-transparent border rounded-xl p-2.5 border-zinc-200 hover:border-zinc-300 focus:border-blue-500 focus:outline-none leading-relaxed"
                                                :class="{
                                                    'text-xs': block.size === 'sm',
                                                    'text-sm': block.size === 'base',
                                                    'text-base': block.size === 'lg',
                                                    'text-left': block.align === 'left',
                                                    'text-center': block.align === 'center',
                                                    'text-right': block.align === 'right',
                                                }"
                                            ></textarea>
                                        </div>

                                        <!-- 3. BADGE BLOCK -->
                                        <div v-else-if="block.type === 'badge'" class="space-y-2">
                                            <div
                                                class="flex"
                                                :class="{
                                                    'justify-start': block.align === 'left',
                                                    'justify-center': block.align === 'center',
                                                    'justify-end': block.align === 'right',
                                                }"
                                            >
                                                <span
                                                    class="px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs border transition-all"
                                                    :class="{
                                                        'bg-blue-50 text-blue-700 border-blue-200': block.variant === 'blue',
                                                        'bg-emerald-50 text-emerald-700 border-emerald-200': block.variant === 'emerald',
                                                        'bg-amber-50 text-amber-700 border-amber-200': block.variant === 'amber',
                                                        'bg-zinc-100 text-zinc-800 border-zinc-200': block.variant === 'zinc',
                                                        'bg-purple-50 text-purple-700 border-purple-200': block.variant === 'purple',
                                                    }"
                                                >
                                                    <LunouIcon :name="block.icon || 'sparkles'" className="w-3.5 h-3.5 flex-shrink-0" />
                                                    <span>{{ block.text || 'Teks Badge' }}</span>
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                                <input
                                                    v-model="block.text"
                                                    @input="markUnsaved"
                                                    placeholder="Teks Badge..."
                                                    class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                />
                                                <div class="flex items-center gap-1">
                                                    <button
                                                        v-for="v in ['blue', 'emerald', 'amber', 'zinc', 'purple']"
                                                        :key="v"
                                                        type="button"
                                                        @click="block.variant = v; markUnsaved()"
                                                        class="w-6 h-6 rounded-full border-2 transition-transform"
                                                        :class="[
                                                            block.variant === v ? 'scale-110 border-blue-600' : 'border-transparent',
                                                            v === 'blue' ? 'bg-blue-500' :
                                                            v === 'emerald' ? 'bg-emerald-500' :
                                                            v === 'amber' ? 'bg-amber-500' :
                                                            v === 'zinc' ? 'bg-zinc-600' : 'bg-purple-500'
                                                        ]"
                                                        :title="v"
                                                    ></button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 4. ICON BLOCK -->
                                        <div v-else-if="block.type === 'icon'" class="space-y-3">
                                            <div
                                                class="flex flex-col"
                                                :class="{
                                                    'items-start text-left': block.align === 'left',
                                                    'items-center text-center': block.align === 'center',
                                                    'items-end text-right': block.align === 'right',
                                                }"
                                            >
                                                <div
                                                    class="transition-all flex items-center justify-center cursor-pointer group/ic"
                                                    @click="activeBlockIconPickerIndex = activeBlockIconPickerIndex === bIdx ? null : bIdx"
                                                    :class="[
                                                        block.bg_style === 'circle' ? 'rounded-full shadow-2xs' :
                                                        block.bg_style === 'rounded' ? 'rounded-2xl shadow-2xs' : '',
                                                        block.size === 'sm' ? 'w-8 h-8 p-1.5' :
                                                        block.size === 'md' ? 'w-12 h-12 p-2.5' :
                                                        block.size === 'lg' ? 'w-16 h-16 p-3.5' : 'w-24 h-24 p-5',
                                                        block.bg_style !== 'none' && block.color === 'blue' ? 'bg-blue-50 text-blue-600' :
                                                        block.bg_style !== 'none' && block.color === 'emerald' ? 'bg-emerald-50 text-emerald-600' :
                                                        block.bg_style !== 'none' && block.color === 'zinc' ? 'bg-zinc-100 text-zinc-900' :
                                                        block.bg_style !== 'none' && block.color === 'amber' ? 'bg-amber-50 text-amber-600' :
                                                        block.bg_style !== 'none' && block.color === 'rose' ? 'bg-rose-50 text-rose-600' :
                                                        block.bg_style !== 'none' && block.color === 'purple' ? 'bg-purple-50 text-purple-600' : '',
                                                        block.bg_style === 'none' && block.color === 'blue' ? 'text-blue-600' :
                                                        block.bg_style === 'none' && block.color === 'emerald' ? 'text-emerald-600' :
                                                        block.bg_style === 'none' && block.color === 'zinc' ? 'text-zinc-900' :
                                                        block.bg_style === 'none' && block.color === 'amber' ? 'text-amber-600' :
                                                        block.bg_style === 'none' && block.color === 'rose' ? 'text-rose-600' :
                                                        block.bg_style === 'none' && block.color === 'purple' ? 'text-purple-600' : 'text-zinc-800'
                                                    ]"
                                                >
                                                    <LunouIcon
                                                        :name="block.name || 'sparkles'"
                                                        :className="[
                                                            block.size === 'sm' ? 'w-5 h-5' :
                                                            block.size === 'md' ? 'w-7 h-7' :
                                                            block.size === 'lg' ? 'w-10 h-10' : 'w-14 h-14'
                                                        ].join(' ')"
                                                    />
                                                </div>
                                                <span v-if="block.label" class="text-xs font-bold text-zinc-700 mt-1">{{ block.label }}</span>
                                            </div>

                                            <!-- Controls -->
                                            <div class="p-2.5 rounded-xl bg-white border border-zinc-200/90 space-y-2 text-xs">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <button
                                                        type="button"
                                                        @click="activeBlockIconPickerIndex = activeBlockIconPickerIndex === bIdx ? null : bIdx"
                                                        class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold text-[11px] inline-flex items-center gap-1 transition-colors"
                                                    >
                                                        <LunouIcon :name="block.name" className="w-3.5 h-3.5" />
                                                        <span>Ganti Ikon ({{ block.name }})</span>
                                                    </button>

                                                    <!-- Size Options -->
                                                    <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                        <button
                                                            v-for="sz in [{ id: 'sm', label: 'S' }, { id: 'md', label: 'M' }, { id: 'lg', label: 'L' }, { id: 'xl', label: 'XL' }]"
                                                            :key="sz.id"
                                                            type="button"
                                                            @click="block.size = sz.id; markUnsaved()"
                                                            class="px-2 py-0.5 rounded transition-all"
                                                            :class="block.size === sz.id ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                        >
                                                            {{ sz.label }}
                                                        </button>
                                                    </div>

                                                    <!-- Shape Options -->
                                                    <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                        <button
                                                            v-for="sh in [{ id: 'circle', label: 'Bulat' }, { id: 'rounded', label: 'Kotak' }, { id: 'none', label: 'Polos' }]"
                                                            :key="sh.id"
                                                            type="button"
                                                            @click="block.bg_style = sh.id; markUnsaved()"
                                                            class="px-2 py-0.5 rounded transition-all"
                                                            :class="block.bg_style === sh.id ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                        >
                                                            {{ sh.label }}
                                                        </button>
                                                    </div>

                                                    <!-- Color Options -->
                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            v-for="cl in ['blue', 'emerald', 'zinc', 'amber', 'rose', 'purple']"
                                                            :key="cl"
                                                            type="button"
                                                            @click="block.color = cl; markUnsaved()"
                                                            class="w-5 h-5 rounded-full border-2 transition-transform"
                                                            :class="[
                                                                block.color === cl ? 'scale-110 border-blue-600' : 'border-transparent',
                                                                cl === 'blue' ? 'bg-blue-500' :
                                                                cl === 'emerald' ? 'bg-emerald-500' :
                                                                cl === 'zinc' ? 'bg-zinc-800' :
                                                                cl === 'amber' ? 'bg-amber-500' :
                                                                cl === 'rose' ? 'bg-rose-500' : 'bg-purple-500'
                                                            ]"
                                                        ></button>
                                                    </div>
                                                </div>

                                                <!-- Popover Grid of Icons -->
                                                <div v-if="activeBlockIconPickerIndex === bIdx" class="p-2 rounded-xl bg-zinc-50 border border-zinc-200 space-y-2 pt-2">
                                                    <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Pilih Ikon Baru:</span>
                                                    <div class="grid grid-cols-6 sm:grid-cols-9 gap-1.5 max-h-40 overflow-y-auto p-1">
                                                        <button
                                                            v-for="ic in availableIcons"
                                                            :key="ic.name"
                                                            type="button"
                                                            @click="selectIconForBlock(bIdx, ic.name)"
                                                            class="p-2 rounded-lg border bg-white hover:bg-blue-50 hover:border-blue-500 flex items-center justify-center transition-all group"
                                                            :class="block.name === ic.name ? 'border-blue-600 bg-blue-50 text-blue-600 font-bold' : 'border-zinc-200 text-zinc-700'"
                                                            :title="ic.label"
                                                        >
                                                            <LunouIcon :name="ic.name" className="w-4 h-4 group-hover:scale-110 transition-transform" />
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Label & Link Inputs -->
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-zinc-100">
                                                    <input
                                                        v-model="block.label"
                                                        @input="markUnsaved"
                                                        placeholder="Keterangan teks di bawah ikon (opsional)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                    <input
                                                        v-model="block.link"
                                                        @input="markUnsaved"
                                                        placeholder="Tautan tujuan jika diklik (https://...)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 5. IMAGE BLOCK -->
                                        <div v-else-if="block.type === 'image'" class="space-y-3">
                                            <div
                                                class="flex"
                                                :class="{
                                                    'justify-start': block.align === 'left',
                                                    'justify-center': block.align === 'center',
                                                    'justify-end': block.align === 'right',
                                                }"
                                            >
                                                <div
                                                    class="overflow-hidden border border-zinc-200/90 shadow-2xs relative group/img"
                                                    :class="{
                                                        'w-full max-h-96': block.width === 'full',
                                                        'w-full max-w-xl max-h-80': block.width === 'large',
                                                        'w-full max-w-md max-h-64': block.width === 'medium',
                                                        'w-full max-w-xs max-h-48': block.width === 'small',
                                                        'w-28 h-28 sm:w-36 sm:h-36 rounded-full': block.width === 'avatar',
                                                        'rounded-2xl': block.width !== 'avatar' && block.rounded === 'rounded-2xl',
                                                        'rounded-3xl': block.width !== 'avatar' && block.rounded === 'rounded-3xl',
                                                        'rounded-none': block.width !== 'avatar' && block.rounded === 'rounded-none',
                                                    }"
                                                >
                                                    <img
                                                        :src="block.url || 'https://images.unsplash.com/photo-1542744094-3a31f272c490?auto=format&fit=crop&w=1200&q=80'"
                                                        :alt="block.caption || 'Gambar'"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <div v-if="blockImageUploadingIndex === bIdx" class="absolute inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold">
                                                        Mengunggah & Mengompres WebP...
                                                    </div>
                                                </div>
                                            </div>

                                            <p v-if="block.caption" class="text-center text-xs text-zinc-500 italic">{{ block.caption }}</p>

                                            <!-- Controls -->
                                            <div class="p-2.5 rounded-xl bg-white border border-zinc-200/90 space-y-2 text-xs">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <label class="px-2.5 py-1.5 rounded-lg bg-zinc-900 hover:bg-black text-white font-bold text-[11px] cursor-pointer inline-flex items-center gap-1 shadow-2xs transition-colors">
                                                        <LunouIcon name="camera" className="w-3.5 h-3.5" />
                                                        <span>Unggah Foto (JPG/PNG/WebP)</span>
                                                        <input
                                                            type="file"
                                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                                            class="hidden"
                                                            @change="onBlockImageFileChange(bIdx, $event)"
                                                        />
                                                    </label>

                                                    <!-- Width Selector -->
                                                    <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                        <button
                                                            v-for="w in [{ id: 'full', label: '100%' }, { id: 'large', label: '75%' }, { id: 'medium', label: '50%' }, { id: 'small', label: '25%' }, { id: 'avatar', label: 'Bulat' }]"
                                                            :key="w.id"
                                                            type="button"
                                                            @click="block.width = w.id; markUnsaved()"
                                                            class="px-1.5 py-0.5 rounded transition-all"
                                                            :class="block.width === w.id ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                        >
                                                            {{ w.label }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-zinc-100">
                                                    <input
                                                        v-model="block.url"
                                                        @input="markUnsaved"
                                                        placeholder="Atau tempel URL gambar (https://...)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                    <input
                                                        v-model="block.caption"
                                                        @input="markUnsaved"
                                                        placeholder="Keterangan foto / caption (opsional)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 5B. PHOTO CARD BLOCK (FOTO + JUDUL PRESTASI + KETERANGAN DI BAWAHNYA) -->
                                        <div v-else-if="block.type === 'photo_card'" class="space-y-3">
                                            <div
                                                class="flex"
                                                :class="{
                                                    'justify-start': block.align === 'left',
                                                    'justify-center': block.align === 'center',
                                                    'justify-end': block.align === 'right',
                                                }"
                                            >
                                                <div
                                                    class="overflow-hidden rounded-2xl border border-zinc-200/90 shadow-2xs relative group/img bg-zinc-100"
                                                    :class="{
                                                        'w-full': block.width === 'full',
                                                        'w-full max-w-xl': block.width === 'large',
                                                        'w-full max-w-md': block.width === 'medium',
                                                        'aspect-video': block.aspect_ratio === 'video',
                                                        'aspect-square': block.aspect_ratio === 'square',
                                                        'aspect-[21/9]': block.aspect_ratio === 'wide',
                                                        'max-h-96': block.aspect_ratio === 'auto',
                                                    }"
                                                >
                                                    <img
                                                        :src="block.url || 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80'"
                                                        :alt="block.title || 'Foto Prestasi'"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <div v-if="blockImageUploadingIndex === bIdx" class="absolute inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold">
                                                        Mengunggah & Mengompres WebP...
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Controls & Keterangan di bawah foto -->
                                            <div class="space-y-2.5 p-3.5 rounded-2xl bg-white border border-zinc-200 text-xs shadow-2xs">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tahun / Tingkat / Penyelenggara:</label>
                                                        <input
                                                            v-model="block.meta"
                                                            @input="markUnsaved"
                                                            placeholder="Contoh: Tahun 2025 • Tingkat Nasional"
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs mt-0.5"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Judul Prestasi / Foto:</label>
                                                        <input
                                                            v-model="block.title"
                                                            @input="markUnsaved"
                                                            placeholder="Contoh: Juara 1 UI/UX Design Challenge"
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs font-extrabold text-zinc-950 mt-0.5"
                                                        />
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Keterangan / Deskripsi di Bawah Foto:</label>
                                                    <textarea
                                                        v-model="block.description"
                                                        @input="markUnsaved"
                                                        rows="2"
                                                        placeholder="Tuliskan keterangan detail prestasi, tantangan karya, atau cerita di balik foto ini..."
                                                        class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs mt-0.5 leading-relaxed"
                                                    ></textarea>
                                                </div>

                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-zinc-100">
                                                    <label class="px-2.5 py-1.5 rounded-lg bg-zinc-900 hover:bg-black text-white font-bold text-[11px] cursor-pointer inline-flex items-center gap-1 shadow-2xs transition-colors">
                                                        <LunouIcon name="camera" className="w-3.5 h-3.5" />
                                                        <span>Ganti Foto (WebP Kompres)</span>
                                                        <input
                                                            type="file"
                                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                                            class="hidden"
                                                            @change="onBlockImageFileChange(bIdx, $event)"
                                                        />
                                                    </label>

                                                    <!-- Aspect ratio selector -->
                                                    <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                        <button
                                                            v-for="ar in [{ id: 'video', label: '16:9' }, { id: 'square', label: '1:1' }, { id: 'wide', label: '21:9' }, { id: 'auto', label: 'Auto' }]"
                                                            :key="ar.id"
                                                            type="button"
                                                            @click="block.aspect_ratio = ar.id; markUnsaved()"
                                                            class="px-2 py-0.5 rounded transition-all"
                                                            :class="block.aspect_ratio === ar.id ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                        >
                                                            {{ ar.label }}
                                                        </button>
                                                    </div>

                                                    <!-- Width Selector -->
                                                    <div class="flex items-center rounded-lg bg-zinc-100 p-0.5 text-[10px] font-bold">
                                                        <button
                                                            v-for="w in [{ id: 'full', label: '100%' }, { id: 'large', label: '75%' }, { id: 'medium', label: '50%' }]"
                                                            :key="w.id"
                                                            type="button"
                                                            @click="block.width = w.id; markUnsaved()"
                                                            class="px-2 py-0.5 rounded transition-all"
                                                            :class="block.width === w.id ? 'bg-white text-blue-600 shadow-2xs font-extrabold' : 'text-zinc-500'"
                                                        >
                                                            {{ w.label }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <input
                                                    v-model="block.url"
                                                    @input="markUnsaved"
                                                    placeholder="Atau tempel URL gambar langsung (https://...)..."
                                                    class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-zinc-50/50"
                                                />
                                            </div>
                                        </div>

                                        <!-- 5C. PHOTO GRID BLOCK (GALERI FOTO PRESTASI 2 KOLOM) -->
                                        <div v-else-if="block.type === 'photo_grid'" class="space-y-3">
                                            <div class="flex items-center justify-between text-xs font-bold text-zinc-800">
                                                <span>Galeri Foto Prestasi (Grid 2 Kolom)</span>
                                                <button
                                                    type="button"
                                                    @click="addPhotoGridItem(bIdx)"
                                                    class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-[11px] font-bold hover:bg-blue-100 transition-colors"
                                                >
                                                    + Tambah Foto
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div
                                                    v-for="(item, itmIdx) in (block.items || [])"
                                                    :key="itmIdx"
                                                    class="p-3 rounded-2xl border border-zinc-200 bg-white space-y-2 relative group shadow-2xs"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="removePhotoGridItem(bIdx, itmIdx)"
                                                        class="absolute top-2 right-2 w-5 h-5 rounded-full bg-black/60 hover:bg-rose-600 text-white flex items-center justify-center text-xs z-10 transition-colors"
                                                        title="Hapus foto ini"
                                                    >
                                                        &times;
                                                    </button>

                                                    <div class="aspect-video w-full rounded-xl overflow-hidden bg-zinc-100 border border-zinc-200/80">
                                                        <img :src="item.url" :alt="item.title" class="w-full h-full object-cover" />
                                                    </div>

                                                    <input
                                                        v-model="item.meta"
                                                        @input="markUnsaved"
                                                        placeholder="Tahun / Label..."
                                                        class="w-full text-[10px] font-bold uppercase text-blue-600 bg-transparent border-b border-transparent hover:border-zinc-200 focus:outline-none"
                                                    />
                                                    <input
                                                        v-model="item.title"
                                                        @input="markUnsaved"
                                                        placeholder="Judul Prestasi / Foto..."
                                                        class="w-full text-xs font-extrabold text-zinc-950 bg-transparent border-b border-transparent hover:border-zinc-200 focus:outline-none"
                                                    />
                                                    <textarea
                                                        v-model="item.description"
                                                        @input="markUnsaved"
                                                        rows="2"
                                                        placeholder="Keterangan di bawah foto..."
                                                        class="w-full text-[11px] text-zinc-600 bg-transparent border-b border-transparent hover:border-zinc-200 focus:outline-none leading-snug"
                                                    ></textarea>
                                                    <input
                                                        v-model="item.url"
                                                        @input="markUnsaved"
                                                        placeholder="URL Gambar..."
                                                        class="w-full text-[10px] text-zinc-400 bg-zinc-50 rounded px-2 py-1 focus:outline-none"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 6. BUTTON BLOCK -->
                                        <div v-else-if="block.type === 'button'" class="space-y-3">
                                            <div
                                                class="flex"
                                                :class="{
                                                    'justify-start': block.align === 'left',
                                                    'justify-center': block.align === 'center',
                                                    'justify-end': block.align === 'right',
                                                    'w-full': block.align === 'full',
                                                }"
                                            >
                                                <a
                                                    :href="block.url || '#'"
                                                    target="_blank"
                                                    @click.prevent
                                                    class="px-5 py-3 rounded-2xl font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2 shadow-2xs hover:shadow-md transition-all duration-200"
                                                    :class="[
                                                        block.align === 'full' ? 'w-full' : '',
                                                        block.variant === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' :
                                                        block.variant === 'black' ? 'bg-zinc-950 hover:bg-black text-white' :
                                                        block.variant === 'blue' ? 'bg-blue-600 hover:bg-blue-700 text-white' :
                                                        'border-2 border-zinc-900 text-zinc-900 hover:bg-zinc-100 bg-white'
                                                    ]"
                                                >
                                                    <LunouIcon :name="block.icon || 'whatsapp'" className="w-4 h-4 flex-shrink-0" />
                                                    <span>{{ block.label || 'Tombol Tindakan' }}</span>
                                                    <span class="text-xs">&rarr;</span>
                                                </a>
                                            </div>

                                            <!-- Controls -->
                                            <div class="p-2.5 rounded-xl bg-white border border-zinc-200/90 space-y-2 text-xs">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="text-[10px] font-bold text-zinc-500 uppercase">Teks Tombol:</label>
                                                        <input
                                                            v-model="block.label"
                                                            @input="markUnsaved"
                                                            placeholder="Contoh: Chat WhatsApp Saya..."
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs mt-0.5"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] font-bold text-zinc-500 uppercase">Tautan Tujuan (URL):</label>
                                                        <input
                                                            v-model="block.url"
                                                            @input="markUnsaved"
                                                            placeholder="https://wa.me/628123... atau https://..."
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs mt-0.5"
                                                        />
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-zinc-100">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[10px] font-bold text-zinc-400">Warna:</span>
                                                        <button
                                                            type="button"
                                                            @click="block.variant = 'emerald'; markUnsaved()"
                                                            class="px-2 py-1 rounded-md text-[10px] font-bold transition-all"
                                                            :class="block.variant === 'emerald' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-800'"
                                                        >
                                                            WhatsApp
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="block.variant = 'black'; markUnsaved()"
                                                            class="px-2 py-1 rounded-md text-[10px] font-bold transition-all"
                                                            :class="block.variant === 'black' ? 'bg-zinc-950 text-white shadow-2xs' : 'bg-zinc-100 text-zinc-800'"
                                                        >
                                                            Hitam
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="block.variant = 'blue'; markUnsaved()"
                                                            class="px-2 py-1 rounded-md text-[10px] font-bold transition-all"
                                                            :class="block.variant === 'blue' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-blue-50 text-blue-800'"
                                                        >
                                                            Biru
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="block.variant = 'outline'; markUnsaved()"
                                                            class="px-2 py-1 rounded-md text-[10px] font-bold transition-all"
                                                            :class="block.variant === 'outline' ? 'bg-zinc-800 text-white shadow-2xs' : 'border border-zinc-300 text-zinc-700'"
                                                        >
                                                            Garis
                                                        </button>
                                                    </div>

                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[10px] font-bold text-zinc-400">Ikon:</span>
                                                        <select
                                                            v-model="block.icon"
                                                            @change="markUnsaved"
                                                            class="px-2 py-1 rounded-lg border border-zinc-200 text-xs bg-white font-semibold"
                                                        >
                                                            <option v-for="ic in availableIcons" :key="ic.name" :value="ic.name">
                                                                {{ ic.label }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 7. LINK CARD BLOCK -->
                                        <div v-else-if="block.type === 'link_card'" class="space-y-3">
                                            <div class="p-4 rounded-2xl border border-zinc-200/90 bg-white hover:border-blue-500 hover:shadow-md transition-all flex items-center justify-between gap-3 group">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                                        <LunouIcon :name="block.icon || 'behance'" className="w-5 h-5" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-extrabold text-sm text-zinc-950 truncate">{{ block.title || 'Judul Tautan' }}</span>
                                                            <span v-if="block.badge" class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 border border-zinc-200">
                                                                {{ block.badge }}
                                                            </span>
                                                        </div>
                                                        <p class="text-xs text-zinc-500 truncate mt-0.5">{{ block.subtitle || 'Deskripsi singkat profil atau tujuan tautan' }}</p>
                                                    </div>
                                                </div>
                                                <span class="w-8 h-8 rounded-full bg-zinc-50 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center text-zinc-400 text-sm font-bold transition-all flex-shrink-0">
                                                    &rarr;
                                                </span>
                                            </div>

                                            <div class="p-2.5 rounded-xl bg-white border border-zinc-200/90 space-y-2 text-xs">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <input
                                                        v-model="block.title"
                                                        @input="markUnsaved"
                                                        placeholder="Judul Kartu..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white font-bold"
                                                    />
                                                    <input
                                                        v-model="block.subtitle"
                                                        @input="markUnsaved"
                                                        placeholder="Deskripsi singkat..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 border-t border-zinc-100">
                                                    <input
                                                        v-model="block.url"
                                                        @input="markUnsaved"
                                                        placeholder="URL Tautan (https://...)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white sm:col-span-2"
                                                    />
                                                    <select
                                                        v-model="block.icon"
                                                        @change="markUnsaved"
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white font-semibold"
                                                    >
                                                        <option v-for="ic in availableIcons" :key="ic.name" :value="ic.name">
                                                            {{ ic.label }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 8. QUOTE BLOCK -->
                                        <div v-else-if="block.type === 'quote'" class="space-y-3">
                                            <div class="p-5 rounded-2xl bg-zinc-50 border-l-4 border-blue-600 space-y-2">
                                                <div class="text-blue-600 text-2xl font-serif leading-none">“</div>
                                                <p class="text-sm sm:text-base font-medium text-zinc-800 italic leading-relaxed">
                                                    {{ block.text || 'Tuliskan kutipan atau prinsip berkarya Anda di sini...' }}
                                                </p>
                                                <div v-if="block.author" class="text-xs font-bold text-zinc-900 pt-1">
                                                    — {{ block.author }} <span v-if="block.role" class="text-zinc-400 font-normal">({{ block.role }})</span>
                                                </div>
                                            </div>

                                            <div class="p-2.5 rounded-xl bg-white border border-zinc-200/90 space-y-2 text-xs">
                                                <textarea
                                                    v-model="block.text"
                                                    @input="markUnsaved"
                                                    rows="2"
                                                    placeholder="Teks kutipan..."
                                                    class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                ></textarea>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <input
                                                        v-model="block.author"
                                                        @input="markUnsaved"
                                                        placeholder="Penulis / Sumber Kutipan..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white font-bold"
                                                    />
                                                    <input
                                                        v-model="block.role"
                                                        @input="markUnsaved"
                                                        placeholder="Peran / Jabatan (opsional)..."
                                                        class="px-2.5 py-1.5 rounded-lg border border-zinc-200 text-xs bg-white"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 9. DIVIDER BLOCK -->
                                        <div v-else-if="block.type === 'divider'" class="py-2">
                                            <div v-if="block.style === 'line'" class="border-t border-zinc-200"></div>
                                            <div v-else-if="block.style === 'dots'" class="flex justify-center gap-2 text-zinc-300 font-bold">&bull; &bull; &bull;</div>
                                            <div v-else class="h-8"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State when no blocks -->
                                <div v-else class="p-6 rounded-2xl border-2 border-dashed border-zinc-200/90 text-center space-y-2 bg-zinc-50/40">
                                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto shadow-2xs">
                                        <LunouIcon name="sparkles" className="w-5 h-5" />
                                    </div>
                                    <div class="text-xs font-extrabold text-zinc-900">Kanvas Elemen Bebas (Gaya Canva)</div>
                                    <p class="text-[11px] text-zinc-500 max-w-md mx-auto">
                                        Tempel teks, stiker icon, foto, tombol WhatsApp, atau kartu tautan. Semua elemen bisa digeser urutannya dan otomatis 100% responsif di ponsel.
                                    </p>
                                </div>

                                <!-- Quick Add Action Bar (Sangat Mudah Digunakan) -->
                                <div class="pt-4 border-t border-zinc-200/90 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Tambah Elemen Baru:</span>
                                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[9px] font-bold">Plug & Play</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="isHelpGuideModalOpen = true"
                                            class="text-[10px] font-semibold text-blue-600 hover:underline flex items-center gap-1"
                                        >
                                            <LunouIcon name="help" className="w-3 h-3" />
                                            <span>Butuh Bantuan?</span>
                                        </button>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                        <!-- Primary Big Button -->
                                        <button
                                            type="button"
                                            @click="isMobileQuickAddOpen = true"
                                            class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-[11px] shadow-2xs transition-all flex items-center gap-1.5"
                                        >
                                            <LunouIcon name="plus" className="w-3.5 h-3.5 text-white" />
                                            <span>+ Buka Menu Elemen</span>
                                        </button>

                                        <!-- Prestasi & Subheading Shortcuts -->
                                        <button
                                            @click="addBlock('subheading')"
                                            type="button"
                                            class="px-3 py-2 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100 text-blue-900 font-bold text-[11px] transition-all inline-flex items-center gap-1"
                                            title="Buat Sub-judul (misal: Prestasi & Rekam Jejak)"
                                        >
                                            <LunouIcon name="sparkles" className="w-3 h-3 text-blue-600" />
                                            <span>+ Sub-judul / Prestasi</span>
                                        </button>

                                        <button
                                            @click="addBlock('photo_card')"
                                            type="button"
                                            class="px-3 py-2 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100 text-amber-950 font-bold text-[11px] transition-all inline-flex items-center gap-1"
                                            title="Tambah Foto dengan Keterangan Lengkap di Bawahnya"
                                        >
                                            <LunouIcon name="camera" className="w-3 h-3 text-amber-600" />
                                            <span>+ Foto & Keterangan</span>
                                        </button>

                                        <button
                                            @click="addBlock('photo_grid')"
                                            type="button"
                                            class="px-3 py-2 rounded-xl border border-zinc-200 bg-white hover:border-zinc-400 font-bold text-[11px] transition-all inline-flex items-center gap-1"
                                        >
                                            <span>+ Grid 2 Foto</span>
                                        </button>

                                        <button
                                            @click="addBlock('button')"
                                            type="button"
                                            class="px-3 py-2 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-[11px] transition-all inline-flex items-center gap-1"
                                        >
                                            <LunouIcon name="whatsapp" className="w-3 h-3 text-emerald-600" />
                                            <span>+ Tombol WhatsApp</span>
                                        </button>

                                        <button
                                            @click="addBlock('text')"
                                            type="button"
                                            class="px-2.5 py-2 rounded-xl border border-zinc-200 bg-white hover:border-blue-500 hover:text-blue-600 font-bold text-[11px] transition-all"
                                        >
                                            + Teks
                                        </button>

                                        <button
                                            @click="addBlock('heading')"
                                            type="button"
                                            class="px-2.5 py-2 rounded-xl border border-zinc-200 bg-white hover:border-blue-500 hover:text-blue-600 font-bold text-[11px] transition-all"
                                        >
                                            + Judul
                                        </button>

                                        <button
                                            @click="addBlock('icon')"
                                            type="button"
                                            class="px-2.5 py-2 rounded-xl border border-zinc-200 bg-white hover:border-blue-500 hover:text-blue-600 font-bold text-[11px] transition-all inline-flex items-center gap-1"
                                        >
                                            <LunouIcon name="star" className="w-3 h-3 text-amber-500" />
                                            <span>+ Ikon</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-20 text-zinc-400 text-xs">
                            Tidak ada halaman terpilih. Klik Tambah Halaman di bagian bawah untuk memulai.
                        </div>
                    </div>
                </div>

                <!-- ----------------------------------------------------------------- -->
                <!-- 3. BOTTOM FILMSTRIP PAGE NAVIGATION (CANVA STYLE) -->
                <!-- ----------------------------------------------------------------- -->
                <footer class="h-20 border-t border-zinc-200/90 bg-white px-4 flex items-center justify-between gap-3 overflow-x-auto shadow-2xs z-30">
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Thumbnail page items (Bisa Di-Drag & Digeser dengan Panah) -->
                        <div
                            v-for="(page, idx) in localPortfolio.pages"
                            :key="page.id || idx"
                            draggable="true"
                            @dragstart="onPageDragStart(idx, $event)"
                            @dragover.prevent="onPageDragOver(idx, $event)"
                            @dragleave="onPageDragLeave(idx, $event)"
                            @drop.prevent="onPageDrop(idx)"
                            @dragend="onPageDragEnd"
                            @click="selectPage(idx)"
                            class="h-14 min-w-[105px] max-w-[135px] px-2.5 py-1.5 rounded-xl border transition-all cursor-grab active:cursor-grabbing flex flex-col justify-between select-none relative group"
                            :class="[
                                selectedPageIndex === idx ? 'border-blue-600 bg-blue-50/60 shadow-2xs ring-1 ring-blue-500' : 'border-zinc-200 hover:border-zinc-300 bg-zinc-50/80',
                                draggedPageIndex === idx ? 'opacity-40 scale-95 border-dashed border-blue-400' : '',
                                dragOverPageIndex === idx ? 'ring-2 ring-blue-500 scale-105 bg-blue-100/80 border-blue-500' : ''
                            ]"
                            :title="'Halaman ' + (idx + 1) + ': ' + page.title + ' (Tahan & drag untuk geser posisi)'"
                        >
                            <!-- Top row: Page number & Grip + Shift Arrows -->
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1 min-w-0">
                                    <span class="text-zinc-300 group-hover:text-zinc-500 text-[10px] leading-none select-none font-mono" title="Tahan & geser untuk pindah posisi">⋮⋮</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 group-hover:text-blue-600 truncate">
                                        Page {{ idx + 1 }}
                                    </span>
                                </div>

                                <!-- Quick Shift Arrows (◀ / ▶) -->
                                <div class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-opacity" :class="{ '!opacity-100': selectedPageIndex === idx }">
                                    <button
                                        type="button"
                                        @click.stop="shiftPage(idx, -1)"
                                        :disabled="idx === 0"
                                        class="w-4 h-4 rounded flex items-center justify-center text-[10px] font-bold text-zinc-600 hover:text-white hover:bg-blue-600 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-300 cursor-pointer disabled:cursor-not-allowed transition-all"
                                        title="Geser halaman ke kiri"
                                    >
                                        &larr;
                                    </button>
                                    <button
                                        type="button"
                                        @click.stop="shiftPage(idx, 1)"
                                        :disabled="idx === localPortfolio.pages.length - 1"
                                        class="w-4 h-4 rounded flex items-center justify-center text-[10px] font-bold text-zinc-600 hover:text-white hover:bg-blue-600 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-zinc-300 cursor-pointer disabled:cursor-not-allowed transition-all"
                                        title="Geser halaman ke kanan"
                                    >
                                        &rarr;
                                    </button>
                                </div>
                            </div>

                            <!-- Bottom row: Page Title -->
                            <span class="text-[11px] font-bold text-zinc-900 truncate">
                                {{ page.title }}
                            </span>
                        </div>

                        <!-- Add Page Button (+) -->
                        <button
                            @click="isAddPageModalOpen = true"
                            class="h-14 min-w-[75px] rounded-xl border border-dashed border-zinc-300 hover:border-blue-500 hover:bg-blue-50/30 text-zinc-600 hover:text-blue-600 flex flex-col items-center justify-center gap-0.5 text-xs font-bold transition-all flex-shrink-0 cursor-pointer"
                            title="Tambah Halaman Baru"
                        >
                            <span class="text-base leading-none">+</span>
                            <span class="text-[10px]">Halaman</span>
                        </button>
                    </div>

                    <!-- Helpful Reorder Tooltip Hint (Desktop) -->
                    <div class="hidden xl:flex items-center gap-1.5 text-[11px] text-zinc-500 bg-zinc-50 px-3 py-1.5 rounded-xl border border-zinc-200/80 flex-shrink-0">
                        <span class="text-blue-600 font-bold">💡 Tips:</span>
                        <span>Drag kartu atau klik panah &larr; &rarr; untuk mengubah susunan halaman.</span>
                    </div>
                </footer>
                <!-- Mobile Floating "+ Tambah Elemen" Button (Ramah Jempol Ponsel) -->
                <div v-if="mobileEditorMode === 'canvas'" class="lg:hidden fixed bottom-24 right-4 z-40">
                    <button
                        type="button"
                        @click="isMobileQuickAddOpen = true"
                        class="px-4 py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-xl flex items-center gap-2 border-2 border-white/90 active:scale-95 transition-all cursor-pointer"
                    >
                        <LunouIcon name="plus" className="w-4 h-4 text-white" />
                        <span>+ Tambah Elemen</span>
                    </button>
                </div>
            </section>
        </main>

        <!-- ========================================================================= -->
        <!-- MODALS -->
        <!-- ========================================================================= -->

        <!-- 0. Quick Add Elements Drawer / Modal (Ramah Ponsel & Desktop) -->
        <div v-if="isMobileQuickAddOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="isMobileQuickAddOpen = false">
            <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full max-h-[85vh] flex flex-col shadow-2xl border border-zinc-200 overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-black text-blue-600 uppercase tracking-wider">Plug & Play</span>
                            <span class="text-xs text-zinc-300">&bull;</span>
                            <span class="text-xs text-zinc-500 font-medium">Kanvas Bebas</span>
                        </div>
                        <h3 class="font-extrabold text-base text-zinc-950 mt-0.5">Tambah Elemen ke Halaman</h3>
                    </div>
                    <button @click="isMobileQuickAddOpen = false" class="w-8 h-8 rounded-full bg-zinc-100 hover:bg-zinc-200 text-zinc-600 flex items-center justify-center font-bold text-lg">&times;</button>
                </div>

                <div class="p-4 sm:p-5 space-y-3 overflow-y-auto flex-1">
                    <p class="text-xs text-zinc-500">Pilih komponen yang ingin kamu sisipkan. Semua elemen dapat diedit dan diatur urutannya secara fleksibel.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Sub-judul / Prestasi -->
                        <button
                            type="button"
                            @click="addBlock('subheading'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border-2 border-blue-200 hover:border-blue-600 bg-blue-50/50 hover:bg-blue-50 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">
                                <LunouIcon name="sparkles" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-blue-950 group-hover:text-blue-700">Sub-judul / Seksi Baru</div>
                                <div class="text-[10px] text-zinc-600 mt-0.5 leading-snug">Tag pill & judul seksi baru (misal: Prestasi & Rekam Jejak).</div>
                            </div>
                        </button>

                        <!-- Foto & Keterangan (Prestasi) -->
                        <button
                            type="button"
                            @click="addBlock('photo_card'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border-2 border-amber-200 hover:border-amber-500 bg-amber-50/50 hover:bg-amber-50 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">
                                <LunouIcon name="camera" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-amber-950 group-hover:text-amber-800">Foto & Keterangan</div>
                                <div class="text-[10px] text-zinc-600 mt-0.5 leading-snug">Foto karya/prestasi dengan judul & deskripsi di bawahnya.</div>
                            </div>
                        </button>

                        <!-- Grid 2 Foto -->
                        <button
                            type="button"
                            @click="addBlock('photo_grid'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="card" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Grid 2 Foto Berjejer</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Dua foto berdampingan lengkap dengan keterangan masing-masing.</div>
                            </div>
                        </button>

                        <!-- Tombol WhatsApp -->
                        <button
                            type="button"
                            @click="addBlock('button'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-emerald-200 hover:border-emerald-500 bg-emerald-50/40 hover:bg-emerald-50 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-2xs">
                                <LunouIcon name="whatsapp" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-emerald-950 group-hover:text-emerald-700">Tombol WhatsApp</div>
                                <div class="text-[10px] text-zinc-600 mt-0.5 leading-snug">Tombol chat langsung ke nomor WhatsApp milikmu.</div>
                            </div>
                        </button>

                        <!-- Foto Tunggal -->
                        <button
                            type="button"
                            @click="addBlock('image'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="camera" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Foto Tunggal</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Unggah satu foto dengan pilihan lebar dan sudut melengkung.</div>
                            </div>
                        </button>

                        <!-- Paragraf Teks -->
                        <button
                            type="button"
                            @click="addBlock('text'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="pencil" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Paragraf Teks</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Tambahkan tulisan narasi, cerita, atau keterangan umum.</div>
                            </div>
                        </button>

                        <!-- Judul Baru -->
                        <button
                            type="button"
                            @click="addBlock('heading'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5 font-black text-sm">
                                H
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Judul Besar</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Tajuk utama tebal untuk memisahkan topik pembahasan.</div>
                            </div>
                        </button>

                        <!-- Stiker Ikon -->
                        <button
                            type="button"
                            @click="addBlock('icon'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="star" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Tempel Ikon / Stiker</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Hiasan visual vektor resmi Lunou (bisa dipilih sesukamu).</div>
                            </div>
                        </button>

                        <!-- Kartu Tautan -->
                        <button
                            type="button"
                            @click="addBlock('link_card'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="external-link" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Kartu Tautan Luar</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Tautan ke Behance, LinkedIn, Instagram, atau website eksternal.</div>
                            </div>
                        </button>

                        <!-- Kutipan -->
                        <button
                            type="button"
                            @click="addBlock('quote'); isMobileQuickAddOpen = false"
                            class="p-3.5 rounded-2xl border border-zinc-200 hover:border-blue-500 bg-white hover:bg-blue-50/30 text-left transition-all group flex items-start gap-3 cursor-pointer"
                        >
                            <div class="w-9 h-9 rounded-xl bg-zinc-100 group-hover:bg-blue-100 text-zinc-700 group-hover:text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <LunouIcon name="quote" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-zinc-950 group-hover:text-blue-600">Kutipan / Prinsip</div>
                                <div class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Kata mutiara, filosofi, atau testimoni berkesan.</div>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="p-4 bg-zinc-50 border-t border-zinc-100 flex items-center justify-between">
                    <button
                        type="button"
                        @click="isHelpGuideModalOpen = true; isMobileQuickAddOpen = false"
                        class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1.5"
                    >
                        <LunouIcon name="help" className="w-4 h-4" />
                        <span>Buka Panduan Lengkap</span>
                    </button>
                    <button
                        type="button"
                        @click="isMobileQuickAddOpen = false"
                        class="px-4 py-2 rounded-xl bg-zinc-900 text-white font-bold text-xs hover:bg-black"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- 0.1 Comprehensive Interactive Help & Guide Modal (Panduan Lengkap Ramah Pemula) -->
        <div v-if="isHelpGuideModalOpen" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6 overflow-y-auto" @click.self="isHelpGuideModalOpen = false">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-zinc-200 overflow-hidden my-auto text-zinc-900">
                <!-- Modal Sticky Header -->
                <div class="px-5 py-4 border-b border-zinc-100 bg-white/95 backdrop-blur-md sticky top-0 z-10 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 border border-blue-100">
                            <LunouIcon name="help" className="w-5 h-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-black text-sm sm:text-base text-zinc-950 tracking-tight">Panduan Mudah Portofolio</h3>
                                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[9px] font-extrabold uppercase">Ramah Pemula</span>
                            </div>
                            <p class="text-[11px] text-zinc-500 mt-0.5">Pelajari cara praktis mengedit di ponsel dan komputer tanpa pusing.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="isHelpGuideModalOpen = false"
                        class="w-8 h-8 rounded-full bg-zinc-100 hover:bg-zinc-200 text-zinc-600 flex items-center justify-center font-bold text-lg transition-colors flex-shrink-0"
                    >
                        &times;
                    </button>
                </div>

                <!-- Tab Navigation Pills -->
                <div class="px-5 pt-3 pb-2 bg-zinc-50/70 border-b border-zinc-200/80 flex items-center gap-1.5 overflow-x-auto text-xs">
                    <button
                        type="button"
                        @click="helpGuideTab = 'hp'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                        :class="helpGuideTab === 'hp' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100'"
                    >
                        <LunouIcon name="smartphone" className="w-3.5 h-3.5" />
                        <span>Cara Pakai di HP</span>
                    </button>

                    <button
                        type="button"
                        @click="helpGuideTab = 'pc'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                        :class="helpGuideTab === 'pc' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100'"
                    >
                        <LunouIcon name="laptop" className="w-3.5 h-3.5" />
                        <span>Cara Pakai di Laptop / PC</span>
                    </button>

                    <button
                        type="button"
                        @click="helpGuideTab = 'prestasi'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                        :class="helpGuideTab === 'prestasi' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100'"
                    >
                        <LunouIcon name="trophy" className="w-3.5 h-3.5" />
                        <span>Buat Seksi Prestasi & Foto</span>
                    </button>

                    <button
                        type="button"
                        @click="helpGuideTab = 'rekomendasi'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                        :class="helpGuideTab === 'rekomendasi' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100'"
                    >
                        <LunouIcon name="sparkles" className="w-3.5 h-3.5" />
                        <span>Susunan Portofolio Terbaik</span>
                    </button>
                </div>

                <!-- Modal Scrollable Content -->
                <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 text-xs">
                    <!-- TAB 1: CARA PAKAI DI HP -->
                    <div v-if="helpGuideTab === 'hp'" class="space-y-4">
                        <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-200 text-blue-950 space-y-1">
                            <span class="font-extrabold text-blue-900 block">Kemudahan Edit dari Genggaman Ponsel</span>
                            <p class="text-[11px] text-blue-950/80 leading-relaxed">
                                Anda tidak memerlukan komputer atau keahlian koding. Cukup gunakan ponsel Anda untuk mengunggah foto langsung dari galeri HP dan menuliskan deskripsi karya.
                            </p>
                        </div>

                        <!-- Step 1 -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">1</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Pilih Halaman yang Mau Diedit</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Di bagian paling bawah layar ponsel, terdapat deretan halaman (Page 1, Page 2, Page 3). Ketuk halaman yang ingin Anda ubah, atau ketuk tombol <strong>"+ Halaman"</strong> untuk membuat lembaran halaman baru.
                            </p>
                        </div>

                        <!-- Step 2 -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">2</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Langsung Ketuk & Ketik di Layar</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Semua teks di kanvas (Judul, Subjudul, Narasi) bisa langsung diketuk dan diketik seperti mengetik pesan di WhatsApp. Sistem menyimpan ketikan Anda secara otomatis.
                            </p>
                        </div>

                        <!-- Step 3 -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">3</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Unggah Foto Langsung dari Galeri HP</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Tekan tombol <strong>"Unggah Cover"</strong> atau <strong>"Unggah Foto"</strong>. Anda bisa memilih foto dari kamera atau album foto di ponsel Anda. Server Lunou otomatis mengompresi foto menjadi format WebP berukuran sangat ringan tanpa mengurangi ketajaman gambar.
                            </p>
                        </div>

                        <!-- Step 4 -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">4</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Gunakan Tombol Biru "+ Tambah Elemen"</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Di pojok kanan bawah kanvas terdapat tombol melayang <strong>"+ Tambah Elemen"</strong>. Tekan tombol ini kapan saja untuk menyisipkan Sub-judul Prestasi, Foto & Keterangan, Tombol Chat WhatsApp, atau Kartu Tautan.
                            </p>
                        </div>

                        <!-- Step 5 -->
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">5</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Terbitkan ke Internet</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Setelah Anda selesai, tekan tombol hijau <strong>"Terbitkan"</strong> di pojok kanan atas. Portofolio Anda seketika live dan bisa dibagikan ke klien, rekan kerja, atau HRD melalui tautan resmi <strong>me.myoin.id/p/namakamu</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- TAB 2: CARA PAKAI DI LAPTOP / KOMPUTER -->
                    <div v-else-if="helpGuideTab === 'pc'" class="space-y-4">
                        <div class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-indigo-950 space-y-1">
                            <span class="font-extrabold text-indigo-900 block">Navigasi Visual Gaya Canva di Komputer</span>
                            <p class="text-[11px] text-indigo-950/80 leading-relaxed">
                                Di layar komputer atau laptop, Anda memiliki ruang kerja luas: panel alat (Toolbox) di sebelah kiri dan kanvas interaktif di tengah.
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">1</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Sidebar di Sebelah Kiri</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Ada 5 tab utama di sebelah kiri:
                                <br />&bull; <strong>Elemen:</strong> Komponen plug & play (Sub-judul, Foto, Tombol, Teks, Kutipan).
                                <br />&bull; <strong>Ikon:</strong> Stiker vektor resmi Lunou (stiker bintang, spark, piala, dll).
                                <br />&bull; <strong>Tata Letak:</strong> Template seksi instan (Prestasi, Galeri, Proyek).
                                <br />&bull; <strong>Proyek:</strong> Pengaturan studi kasus mandiri.
                                <br />&bull; <strong>PDF Book:</strong> Pemasang dokumen buku digital flipbook.
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">2</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Atur Posisi dengan Tombol Panah (▲ / ▼)</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Setiap kartu elemen di kanvas memiliki tombol panah atas (▲) dan panah bawah (▼). Klik panah tersebut untuk memindahkan urutan elemen ke atas atau ke bawah sesuai selera Anda.
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0">3</span>
                                <span class="font-extrabold text-zinc-950 text-sm">Simulasi Tampilan Multi-Perangkat</span>
                            </div>
                            <p class="text-zinc-600 text-[11px] leading-relaxed pl-8">
                                Di bar atas, gunakan tombol <strong>Desktop</strong>, <strong>Tablet</strong>, atau <strong>Ponsel</strong> untuk melihat bagaimana portofolio Anda beradaptasi di berbagai ukuran layar pengunjung.
                            </p>
                        </div>
                    </div>

                    <!-- TAB 3: CARA BUAT SEKSI PRESTASI & FOTO -->
                    <div v-else-if="helpGuideTab === 'prestasi'" class="space-y-4">
                        <div class="p-3 rounded-2xl bg-amber-50/70 border border-amber-200 text-amber-950 space-y-1">
                            <span class="font-extrabold text-amber-900 block">Langkah Menambahkan Prestasi & Rekam Jejak</span>
                            <p class="text-[11px] text-amber-950/80 leading-relaxed">
                                Tampilkan penghargaan, piagam, piala, atau sertifikasi dengan foto yang jelas dan penjelasan cerita di bawahnya.
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-amber-50/40 border border-amber-200/90 space-y-2">
                            <span class="font-bold text-amber-900 uppercase text-[10px] tracking-wider">Cara 1 (Paling Cepat - 1 Kali Klik):</span>
                            <p class="text-zinc-700 text-[11px] leading-relaxed">
                                Buka tab <strong>"Tata Letak"</strong> di sidebar kiri, lalu klik tombol kuning <strong>"+ Seksi Prestasi & Penghargaan"</strong>. Seksi lengkap dengan format sub-judul dan kartu foto prestasi langsung otomatis tersusun di kanvas!
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200 space-y-3">
                            <span class="font-bold text-zinc-900 uppercase text-[10px] tracking-wider">Cara 2 (Manual Fleksibel):</span>
                            <div class="space-y-2 text-[11px] text-zinc-600 leading-relaxed pl-2">
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-blue-600">A.</span>
                                    <span>Klik <strong>"+ Sub-judul / Seksi Baru"</strong>. Isi label tag (misal: <code>PRESTASI & REKAM JEJAK</code>), judul besar, dan kalimat pengantar singkat.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-amber-600">B.</span>
                                    <span>Klik <strong>"+ Foto & Keterangan (Prestasi)"</strong>.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-emerald-600">C.</span>
                                    <span>Tekan <strong>"Unggah Foto"</strong> untuk memasukkan foto piagam/piala atau foto saat menerima penghargaan.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-purple-600">D.</span>
                                    <span>Tulis tahun & tingkat (misal: <code>Tahun 2025 · Juara 1 Tingkat Nasional</code>), lalu tuliskan deskripsi lengkap di kolom keterangan tepat di bawah fotonya.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: REKOMENDASI SUSUNAN PORTOFOLIO -->
                    <div v-else-if="helpGuideTab === 'rekomendasi'" class="space-y-4">
                        <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-emerald-950 space-y-1">
                            <span class="font-extrabold text-emerald-900 block">Formula Susunan Portofolio yang Disukai Klien & HRD</span>
                            <p class="text-[11px] text-emerald-950/80 leading-relaxed">
                                Urutan halaman yang runut akan memandu pengunjung memahami siapa Anda dan mengapa Anda adalah pilihan terbaik untuk diajak bekerja sama.
                            </p>
                        </div>

                        <div class="space-y-2.5">
                            <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">1</span>
                                <div>
                                    <div class="font-bold text-xs text-zinc-950">Cover & Hero (Halaman Pembuka)</div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5">Sapa pengunjung dengan nama, bidang profesi (contoh: Senior Designer / Software Engineer), dan 1 kalimat pengantar yang kuat.</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">2</span>
                                <div>
                                    <div class="font-bold text-xs text-zinc-950">Tentang Saya & Keahlian Utama</div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5">Jelaskan dedikasi berkarya Anda dan daftarkan keahlian inti (skills) serta pengalaman kerja.</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-lg bg-amber-500 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">3</span>
                                <div>
                                    <div class="font-bold text-xs text-zinc-950">Prestasi & Penghargaan</div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5">Bukti nyata pencapaian kompetisi, penghargaan industri, atau sertifikasi yang memperkuat kredibilitas Anda.</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">4</span>
                                <div>
                                    <div class="font-bold text-xs text-zinc-950">Proyek Terpilih & Studi Kasus</div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5">Koleksi karya terbaik lengkap dengan narasi tantangan, solusi yang Anda ciptakan, dan hasil akhirnya.</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">5</span>
                                <div>
                                    <div class="font-bold text-xs text-zinc-950">Kontak & Tombol WhatsApp</div>
                                    <p class="text-[11px] text-zinc-500 mt-0.5">Permudah klien menghubungi Anda secara langsung dengan tombol WhatsApp dan email yang responsif.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-4 bg-zinc-50 border-t border-zinc-200 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-zinc-400">
                        me.myoin.id · part of lunou
                    </span>
                    <button
                        type="button"
                        @click="isHelpGuideModalOpen = false"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition-all cursor-pointer"
                    >
                        Paham, Mulai Buat Portofolio! &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. Settings Modal -->
        <div v-if="isSettingsModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-zinc-100">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <h3 class="font-extrabold text-base text-zinc-950">Pengaturan Portofolio</h3>
                    <button @click="isSettingsModalOpen = false" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Judul Portofolio</label>
                        <input
                            v-model="localPortfolio.title"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-300 font-semibold"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">URL Portofolio Khusus</label>
                        <div class="flex items-center rounded-xl border border-zinc-300 px-3 py-2 bg-zinc-50 font-mono">
                            <span class="text-zinc-400">/p/</span>
                            <input
                                v-model="localPortfolio.slug"
                                class="bg-transparent font-bold text-zinc-900 w-full focus:outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-800 mb-1">Tema Tampilan</label>
                        <select v-model="localPortfolio.theme" class="w-full px-3 py-2.5 rounded-xl border border-zinc-300 font-semibold">
                            <option value="minimal-clean">Clean White (Minimalist)</option>
                            <option value="creative-dark">Creative Dark (Zinc Studio)</option>
                            <option value="obsidian">Obsidian Gold (Premium)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="font-bold text-zinc-800">Izinkan Unduh Berkas PDF</span>
                        <input type="checkbox" v-model="localPortfolio.settings.allow_download" class="rounded text-blue-600" />
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="font-bold text-zinc-800">Mode Default Flipbook 3D</span>
                        <input type="checkbox" v-model="localPortfolio.settings.flipbook_default" class="rounded text-blue-600" />
                    </div>
                </div>

                <div class="pt-3 border-t border-zinc-100 flex justify-end gap-2">
                    <button
                        @click="isSettingsModalOpen = false; saveEntirePortfolio();"
                        class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs"
                    >
                        Simpan Pengaturan
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Add Page Layout Modal -->
        <div v-if="isAddPageModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-zinc-100">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <h3 class="font-extrabold text-base text-zinc-950">Pilih Layout Halaman Baru</h3>
                    <button @click="isAddPageModalOpen = false" class="text-zinc-400 hover:text-zinc-800 text-lg">&times;</button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <!-- Recommended Custom Canvas & Prestasi Options -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="addNewPage('custom')"
                            class="p-3.5 rounded-2xl border-2 border-blue-500 bg-blue-50/40 hover:bg-blue-50 text-left transition-all flex items-start gap-2.5 group cursor-pointer"
                        >
                            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                +
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Gaya Canva</span>
                                <div class="font-extrabold text-zinc-950 text-xs">Kanvas Bebas</div>
                                <p class="text-[10px] text-zinc-500 mt-0.5 leading-snug">Halaman kosong, susun elemen bebas.</p>
                            </div>
                        </button>

                        <button
                            type="button"
                            @click="addPrestasiPage()"
                            class="p-3.5 rounded-2xl border-2 border-amber-300 bg-amber-50/50 hover:bg-amber-50 text-left transition-all flex items-start gap-2.5 group cursor-pointer"
                        >
                            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm flex-shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                <LunouIcon name="trophy" className="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold text-amber-700 text-[10px] uppercase">Template Siap Pakai</span>
                                <div class="font-extrabold text-amber-950 text-xs">Prestasi & Rekam Jejak</div>
                                <p class="text-[10px] text-amber-900/80 mt-0.5">Subjudul + kartu foto penghargaan.</p>
                            </div>
                        </button>
                    </div>

                    <div class="pt-1">
                        <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider block mb-2">Preset Halaman Terstruktur:</span>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button @click="addNewPage('cover')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Cover & Hero</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Halaman Pembuka</div>
                            </button>

                            <button @click="addNewPage('about')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Tentang Saya</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Bio & Keahlian</div>
                            </button>

                            <button @click="addNewPage('projects')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Proyek & Portofolio</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Studi Kasus Karya</div>
                            </button>

                            <button @click="addNewPage('gallery')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Galeri Foto</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Grid Dokumentasi</div>
                            </button>

                            <button @click="addNewPage('pdf_book')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Flipbook PDF</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Buku Digital</div>
                            </button>

                            <button @click="addNewPage('contact')" class="p-3 rounded-2xl border border-zinc-200 hover:border-blue-500 hover:bg-blue-50/20 text-left transition-all cursor-pointer">
                                <span class="font-bold text-blue-600 text-[10px] uppercase">Kontak</span>
                                <div class="font-bold text-zinc-900 mt-0.5 text-xs">Koneksi & Media</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Upload Image Modal -->
        <div v-if="isUploadImageModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
                <div>
                    <h3 class="font-extrabold text-base text-zinc-950">Unggah Gambar / Foto</h3>
                    <p class="text-xs text-zinc-500 mt-1">Otomatis dikompres & dikonversi ke WebP di server untuk loading cepat.</p>
                </div>
                <input
                    @change="handleImageUpload"
                    type="file"
                    accept="image/*"
                    class="w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 cursor-pointer"
                />
                <div class="flex justify-end">
                    <button @click="isUploadImageModalOpen = false" class="text-xs font-bold text-zinc-500 hover:text-zinc-800">Batal</button>
                </div>
            </div>
        </div>

        <!-- 4. Upload PDF Modal -->
        <div v-if="isUploadPdfModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
                <h3 class="font-extrabold text-base text-zinc-950">Unggah Berkas PDF</h3>
                <p class="text-xs text-zinc-500">Maksimal 5 MB per file PDF.</p>
                <input
                    @change="handlePdfUpload"
                    type="file"
                    accept="application/pdf"
                    class="w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700"
                />
                <div class="flex justify-end">
                    <button @click="isUploadPdfModalOpen = false" class="text-xs font-bold text-zinc-500 hover:text-zinc-800">Batal</button>
                </div>
            </div>
        </div>

        <!-- 5. Edit Project Detail & Case Study Modal -->
        <div v-if="isEditProjectModalOpen" class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-zinc-200 overflow-hidden my-auto">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <div>
                        <h3 class="font-extrabold text-base text-zinc-950">Edit Detail Proyek & Studi Kasus</h3>
                        <p class="text-[11px] text-zinc-500 mt-0.5">Informasi ini akan muncul di jendela modal detail saat pengunjung mengklik kartu.</p>
                    </div>
                    <button @click="isEditProjectModalOpen = false" class="text-zinc-400 hover:text-zinc-800 text-xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-zinc-700 uppercase tracking-wider text-[10px] mb-1">Judul Proyek</label>
                            <input
                                v-model="editingProject.title"
                                placeholder="Contoh: Zenith Finance App"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:outline-none font-semibold text-zinc-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-zinc-700 uppercase tracking-wider text-[10px] mb-1">Kategori</label>
                            <input
                                v-model="editingProject.category"
                                placeholder="Contoh: UI/UX & Mobile App"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:outline-none font-semibold text-zinc-900"
                            />
                        </div>
                    </div>

                    <!-- Gambar Sampul / Mockup (Direct Upload & URL Fallback) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-zinc-700 uppercase tracking-wider text-[10px]">
                                Foto / Gambar Sampul Mockup
                            </label>
                            <button
                                type="button"
                                @click="showManualProjectImageUrl = !showManualProjectImageUrl"
                                class="text-[11px] text-blue-600 hover:text-blue-700 font-semibold transition-colors"
                            >
                                {{ showManualProjectImageUrl ? 'Sembunyikan URL Manual' : 'Gunakan URL Manual' }}
                            </button>
                        </div>

                        <!-- Upload Zone / Dropzone -->
                        <div
                            @dragover.prevent="isProjectImageDragging = true"
                            @dragleave.prevent="isProjectImageDragging = false"
                            @drop.prevent="onProjectImageDrop"
                            class="p-4 rounded-2xl border-2 border-dashed transition-all bg-zinc-50/70"
                            :class="isProjectImageDragging ? 'border-blue-500 bg-blue-50/50 scale-[1.01]' : 'border-zinc-200 hover:border-zinc-300'"
                        >
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <!-- Thumbnail Preview Box -->
                                <div class="relative w-28 h-24 sm:w-32 sm:h-24 rounded-xl overflow-hidden border border-zinc-200 bg-zinc-100 flex-shrink-0 shadow-2xs group">
                                    <img
                                        v-if="editingProject.image"
                                        :src="editingProject.image"
                                        alt="Mockup Proyek"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-zinc-400 p-2 text-center">
                                        <svg class="w-6 h-6 mb-1 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="text-[9px] font-medium leading-tight">Belum ada foto</span>
                                    </div>

                                    <!-- Loading Spinner Overlay -->
                                    <div
                                        v-if="projectImageUploading"
                                        class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white backdrop-blur-xs"
                                    >
                                        <svg class="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-[8px] font-bold mt-1">Mengompres...</span>
                                    </div>
                                </div>

                                <!-- Action Buttons & Information -->
                                <div class="flex-1 text-center sm:text-left space-y-2 w-full">
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                        <input
                                            ref="projectImageFileInput"
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            class="hidden"
                                            @change="onProjectImageFileChange"
                                        />
                                        <button
                                            type="button"
                                            @click="projectImageFileInput?.click()"
                                            :disabled="projectImageUploading"
                                            class="px-4 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold inline-flex items-center gap-2 shadow-xs transition-all disabled:opacity-50 min-h-[42px]"
                                        >
                                            <LunouIcon name="camera" className="w-4 h-4 text-zinc-300" />
                                            <span>{{ projectImageUploading ? 'Mengompres & Mengunggah...' : 'Pilih Foto dari Galeri / HP' }}</span>
                                        </button>
                                        <button
                                            v-if="editingProject.image"
                                            type="button"
                                            @click="editingProject.image = ''"
                                            class="px-3 py-2 rounded-xl bg-white border border-zinc-200 hover:bg-rose-50 hover:border-rose-300 text-zinc-600 hover:text-rose-600 text-xs font-semibold transition-all min-h-[42px]"
                                        >
                                            Hapus Foto
                                        </button>
                                    </div>

                                    <p class="text-[11px] text-zinc-500 leading-relaxed">
                                        Pilih foto karya atau seret berkas ke sini. Gambar otomatis dikompresi ke format WebP agar loading cepat dan hemat kuota (Maks. 10MB).
                                    </p>

                                    <!-- Feedback Badge -->
                                    <div
                                        v-if="projectImageUploadMessage"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold animate-pulse"
                                    >
                                        <LunouIcon name="check" className="w-3.5 h-3.5 text-emerald-600" />
                                        <span>{{ projectImageUploadMessage }}</span>
                                    </div>

                                    <!-- Error Alert -->
                                    <div v-if="projectImageUploadError" class="text-xs text-rose-600 font-medium">
                                        {{ projectImageUploadError }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual URL Fallback Input (Optional) -->
                        <div v-if="showManualProjectImageUrl" class="pt-2">
                            <input
                                v-model="editingProject.image"
                                placeholder="https://images.unsplash.com/... atau URL gambar eksternal"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:outline-none text-zinc-800 font-mono text-[11px] bg-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-zinc-700 uppercase tracking-wider text-[10px] mb-1">Ringkasan Singkat Kartu Depan</label>
                        <textarea
                            v-model="editingProject.description"
                            rows="2"
                            placeholder="Deskripsi ringkas yang tampil pada preview kartu di halaman depan..."
                            class="w-full p-3 rounded-xl border border-zinc-200 focus:border-blue-600 focus:outline-none text-zinc-700 resize-none"
                        ></textarea>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-3">
                        <span class="block font-bold text-zinc-900 text-xs">Informasi Tambahan Proyek</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-zinc-500 text-[10px] font-semibold mb-1">Peranmu</label>
                                <input
                                    v-model="editingProject.role"
                                    placeholder="Lead UI/UX Designer"
                                    class="w-full bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-zinc-500 text-[10px] font-semibold mb-1">Klien / Merek</label>
                                <input
                                    v-model="editingProject.client"
                                    placeholder="Nama Brand / Klien"
                                    class="w-full bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-zinc-500 text-[10px] font-semibold mb-1">Tahun / Periode</label>
                                <input
                                    v-model="editingProject.year"
                                    placeholder="2026"
                                    class="w-full bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-zinc-500 text-[10px] font-semibold mb-1">Teknologi & Alat (Pisahkan dengan koma)</label>
                            <input
                                v-model="editingProject.toolsText"
                                placeholder="Figma, Vue 3, Tailwind CSS, Hardware IoT"
                                class="w-full bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- In-Depth Story Sections -->
                    <div class="space-y-3">
                        <label class="block font-bold text-zinc-900 text-xs">Narasi Studi Kasus Lengkap</label>
                        <textarea
                            v-model="editingProject.case_study"
                            rows="4"
                            placeholder="Ceritakan latar belakang, proses berpikir, dan cerita lengkap karya ini..."
                            class="w-full p-3 rounded-xl border border-zinc-200 focus:border-blue-600 focus:outline-none text-zinc-700 leading-relaxed"
                        ></textarea>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-amber-800 font-bold text-[10px] uppercase mb-1">Tantangan / Masalah</label>
                                <textarea
                                    v-model="editingProject.challenge"
                                    rows="2"
                                    placeholder="Kendala yang diatasi..."
                                    class="w-full p-2.5 rounded-xl border border-amber-200 bg-amber-50/40 text-[11px] focus:outline-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-blue-800 font-bold text-[10px] uppercase mb-1">Solusi / Pendekatan</label>
                                <textarea
                                    v-model="editingProject.solution"
                                    rows="2"
                                    placeholder="Solusi yang diterapkan..."
                                    class="w-full p-2.5 rounded-xl border border-blue-200 bg-blue-50/40 text-[11px] focus:outline-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-emerald-800 font-bold text-[10px] uppercase mb-1">Hasil / Dampak</label>
                                <textarea
                                    v-model="editingProject.result"
                                    rows="2"
                                    placeholder="Dampak konkret proyek..."
                                    class="w-full p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/40 text-[11px] focus:outline-none"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Optional 3rd Party Link -->
                    <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-zinc-800 text-[11px]">Tautan Pihak Ketiga (Opsional)</span>
                            <span class="text-[10px] text-zinc-400 font-medium">Bisa Dikosongkan</span>
                        </div>
                        <p class="text-[11px] text-zinc-500 leading-relaxed">
                            Jika Anda ingin menyediakan tautan keluar (misal ke Behance, GitHub, atau Demo Live), isi di bawah ini. Jika dikosongkan, pengunjung tetap menikmati seluruh studi kasus secara utuh di website tanpa diarahkan keluar.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <input
                                v-model="editingProject.link"
                                placeholder="https://behance.net/..."
                                class="bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none text-[11px] font-mono"
                            />
                            <input
                                v-model="editingProject.link_label"
                                placeholder="Label tombol (misal: Buka di Behance)"
                                class="bg-white px-3 py-2 rounded-xl border border-zinc-200 focus:outline-none text-[11px]"
                            />
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-zinc-50 border-t border-zinc-200/90 flex justify-end gap-2">
                    <button
                        @click="isEditProjectModalOpen = false"
                        class="px-4 py-2 rounded-xl border border-zinc-300 text-xs font-semibold text-zinc-700 hover:bg-white"
                    >
                        Batal
                    </button>
                    <button
                        @click="saveEditedProject"
                        class="px-5 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all shadow-xs"
                    >
                        Simpan Perubahan Proyek
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. Preview Project Detail Modal in Builder -->
        <div v-if="isPreviewProjectModalOpen && previewProjectItem" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm p-4 sm:p-6 overflow-y-auto flex items-center justify-center" @click.self="isPreviewProjectModalOpen = false">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-zinc-200 overflow-hidden my-auto text-zinc-900">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-2">
                        <span v-if="previewProjectItem.category" class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] uppercase tracking-wider border border-blue-200">
                            {{ previewProjectItem.category }}
                        </span>
                        <span v-if="previewProjectItem.year" class="text-xs font-mono text-zinc-400">
                            {{ previewProjectItem.year }}
                        </span>
                    </div>
                    <button @click="isPreviewProjectModalOpen = false" class="w-8 h-8 rounded-full bg-zinc-100 hover:bg-zinc-200 text-zinc-600 flex items-center justify-center font-bold text-sm">&times;</button>
                </div>

                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <div class="rounded-2xl overflow-hidden bg-zinc-100 max-h-72 border border-zinc-200">
                        <img :src="previewProjectItem.image || 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80'" :alt="previewProjectItem.title" class="w-full h-full object-cover max-h-72" />
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-2xl font-black text-zinc-950 tracking-tight">{{ previewProjectItem.title }}</h3>
                        <p class="text-sm text-zinc-600 leading-relaxed">{{ previewProjectItem.description }}</p>
                    </div>

                    <!-- Metadata Bar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-2xl bg-zinc-50 border border-zinc-200/90 text-xs">
                        <div>
                            <span class="text-[9px] font-bold uppercase text-zinc-400 block">Peran</span>
                            <div class="font-bold text-zinc-900">{{ previewProjectItem.role || 'Kreator' }}</div>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold uppercase text-zinc-400 block">Klien</span>
                            <div class="font-bold text-zinc-900">{{ previewProjectItem.client || 'Mandiri' }}</div>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold uppercase text-zinc-400 block">Tahun</span>
                            <div class="font-bold text-zinc-900">{{ previewProjectItem.year || '2026' }}</div>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold uppercase text-zinc-400 block">Kategori</span>
                            <div class="font-bold text-blue-600">{{ previewProjectItem.category || 'Portfolio' }}</div>
                        </div>
                    </div>

                    <!-- Tools Pills -->
                    <div v-if="previewProjectItem.tools && previewProjectItem.tools.length" class="space-y-1.5">
                        <span class="text-[10px] font-bold uppercase text-zinc-500 block">Alat & Teknologi</span>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="(tool, t) in previewProjectItem.tools" :key="t" class="px-2.5 py-0.5 rounded-lg bg-zinc-100 text-zinc-800 text-xs font-bold border border-zinc-200">
                                {{ tool }}
                            </span>
                        </div>
                    </div>

                    <!-- Story -->
                    <div v-if="previewProjectItem.case_study" class="space-y-1.5 pt-2 border-t border-zinc-100">
                        <span class="text-[10px] font-bold uppercase text-zinc-500 block">Kisah & Studi Kasus</span>
                        <p class="text-xs text-zinc-700 leading-relaxed whitespace-pre-line">{{ previewProjectItem.case_study }}</p>
                    </div>

                    <div v-if="previewProjectItem.challenge" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-amber-800 text-[10px] uppercase">
                            <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Tantangan</span>
                        </div>
                        <p class="text-amber-950">{{ previewProjectItem.challenge }}</p>
                    </div>

                    <div v-if="previewProjectItem.solution" class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-blue-800 text-[10px] uppercase">
                            <LunouIcon name="sparkles" className="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                            <span>Solusi</span>
                        </div>
                        <p class="text-blue-950">{{ previewProjectItem.solution }}</p>
                    </div>

                    <div v-if="previewProjectItem.result" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-emerald-800 text-[10px] uppercase">
                            <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Hasil</span>
                        </div>
                        <p class="text-emerald-950">{{ previewProjectItem.result }}</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-zinc-50 border-t border-zinc-200 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-zinc-400">Mode Pratinjau Pengunjung</span>
                    <div class="flex items-center gap-2">
                        <a
                            v-if="previewProjectItem.link && previewProjectItem.link !== '#'"
                            :href="previewProjectItem.link"
                            target="_blank"
                            class="px-3.5 py-2 rounded-xl border border-zinc-300 text-zinc-700 text-xs font-bold hover:bg-white"
                        >
                            {{ previewProjectItem.link_label || 'Pihak Ketiga' }} &nearr;
                        </a>
                        <button
                            @click="isPreviewProjectModalOpen = false"
                            class="px-5 py-2 rounded-xl bg-zinc-950 text-white text-xs font-bold hover:bg-black"
                        >
                            Tutup Pratinjau
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
