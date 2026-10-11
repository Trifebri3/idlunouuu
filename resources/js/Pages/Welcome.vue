<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    showcaseSlides: {
        type: Array,
        default: () => [],
    },
    orderSteps: {
        type: Array,
        default: () => [],
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

const activeKeychainVariant = ref('mascot'); // 'mascot', 'initial', 'leather'
const keychainTapped = ref(false);

function triggerKeychainTap() {
    keychainTapped.value = true;
    try {
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            navigator.vibrate([40, 60, 40]);
        }
    } catch (_) {}
    setTimeout(() => {
        keychainTapped.value = false;
    }, 4500);
}

// -----------------------------------------------------------------
// CAROUSEL SHOWCASE SLIDER STATE (CONTOH FOTO & KARYA VIA ADMIN)
// -----------------------------------------------------------------
const activeSlideIndex = ref(0);

const slides = computed(() => {
    return Array.isArray(props.showcaseSlides) && props.showcaseSlides.length > 0
        ? props.showcaseSlides
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
        ];
});

function nextSlide() {
    if (activeSlideIndex.value < slides.value.length - 1) {
        activeSlideIndex.value++;
    } else {
        activeSlideIndex.value = 0;
    }
}

function prevSlide() {
    if (activeSlideIndex.value > 0) {
        activeSlideIndex.value--;
    } else {
        activeSlideIndex.value = slides.value.length - 1;
    }
}

function goToSlide(idx) {
    activeSlideIndex.value = idx;
}

const currentSlide = computed(() => slides.value[activeSlideIndex.value] || slides.value[0]);

const defaultOrderStepsList = [
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
];
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-blue-600 selection:text-white overflow-x-hidden">
        <Head title="me.myoin.id · part of lunou · More Than a Link. A Little Piece of You." />

        <!-- Navigation Bar (Clean White Lifestyle Brand) -->
        <header class="sticky top-0 z-50 w-full border-b border-zinc-200/80 bg-white/90 backdrop-blur-md transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-3">
                <!-- Brand Logo & Identity -->
                <div class="flex items-center gap-3 flex-shrink-0 min-w-0">
                    <Link href="/" class="flex items-center gap-2.5 sm:gap-3 group flex-shrink-0">
                        <ApplicationLogo className="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-xs group-hover:scale-105 transition-transform flex-shrink-0" variant="1" />
                        <div class="flex flex-col text-left">
                            <span class="font-black text-lg sm:text-xl tracking-tight text-zinc-950 leading-none">me.myoin.id</span>
                            <span class="text-[9px] font-bold text-zinc-400 tracking-wider lowercase mt-0.5 whitespace-nowrap">
                                part of lunou
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center gap-5 lg:gap-7 text-xs font-semibold text-zinc-600 flex-shrink-0">
                    <a href="#space" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Ruang Profil</a>
                    <a href="#showcase" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Contoh Karya</a>
                    <a href="#cara-pesan" class="hover:text-zinc-950 transition-colors whitespace-nowrap font-bold text-blue-600">Cara Pesan</a>
                    <a href="#keychain" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Gantungan Kunci</a>
                    <a href="#pricing" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Pilihan Paket</a>
                    <a
                        :href="saweriaUrl || 'https://saweria.co/Try3'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-amber-600 hover:text-amber-700 font-bold transition-colors whitespace-nowrap inline-flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span>Saweria</span>
                    </a>
                </nav>

                <!-- Auth Action CTAs -->
                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <Link
                        v-if="$page.props.auth?.user"
                        href="/studio"
                        class="whitespace-nowrap px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-sm hover:shadow transition-all inline-flex items-center gap-1.5 flex-shrink-0 min-h-[44px]"
                    >
                        <span>Profil Saya</span>
                        <span class="text-zinc-400">&rarr;</span>
                    </Link>
                    <div v-else class="flex items-center gap-1.5 sm:gap-2.5 flex-shrink-0">
                        <Link
                            href="/login"
                            class="whitespace-nowrap px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-zinc-700 hover:text-zinc-950 text-xs sm:text-sm font-semibold transition-colors flex-shrink-0 min-h-[44px] inline-flex items-center"
                        >
                            Masuk
                        </Link>
                        <Link
                            href="/register"
                            class="whitespace-nowrap px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs sm:text-sm font-bold shadow-sm hover:shadow transition-all inline-flex items-center gap-1.5 flex-shrink-0 group min-h-[44px]"
                        >
                            <span>Buat Profilku</span>
                            <span class="text-zinc-400 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- HERO SECTION: IDENTITAS DIGITALMU -->
        <section class="max-w-7xl w-full mx-auto px-6 pt-16 pb-20 md:pt-24 md:pb-28 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative">
            <!-- Subtle Background Mascot Ornaments -->
            <img src="/icon/2.png" alt="" class="absolute -top-10 right-10 w-96 h-96 opacity-[0.03] pointer-events-none select-none blur-xs" />
            <img src="/icon/3.png" alt="" class="absolute bottom-0 left-0 w-80 h-80 opacity-[0.03] pointer-events-none select-none blur-xs" />

            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-7 text-left relative z-10">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-semibold tracking-wide">
                    <img src="/icon/1.png" alt="" class="w-4 h-4 rounded-full object-contain" />
                    <span>IDENTITAS DIGITALMU</span>
                </div>

                <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-zinc-950 leading-[1.12]">
                    Semua tentang dirimu,<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-cyan-600 to-indigo-600">
                        dalam satu tempat.
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-zinc-600 max-w-xl leading-relaxed font-normal">
                    Satukan profil, karya, dan tautan penting dalam satu halaman yang mudah dibagikan. Kenalkan siapa dirimu dengan caramu sendiri.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 pt-2">
                    <Link
                        href="/register"
                        class="px-7 py-3.5 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-sm flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all min-h-[48px]"
                    >
                        <span>Buat Profilku</span>
                        <span>&rarr;</span>
                    </Link>

                    <Link
                        href="/tri"
                        class="px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-50 border border-zinc-200 text-zinc-800 font-bold text-sm flex items-center justify-center gap-2 shadow-xs transition-all min-h-[48px]"
                    >
                        <LunouIcon name="eye" className="w-4 h-4 text-blue-600" />
                        <span>Lihat Contoh Profil (@tri)</span>
                    </Link>
                </div>

                <!-- Human Value Footnote -->
                <div class="flex flex-wrap items-center gap-6 pt-6 border-t border-zinc-200/80 text-xs text-zinc-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Satu tautan untuk memperkenalkan dirimu</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Bisa dimulai tanpa pengalaman teknis</span>
                    </div>
                </div>
            </div>

            <!-- Right Hero Visual: LUNOU Keychain Charm & Phone Interactive Demo -->
            <div class="lg:col-span-5 flex flex-col items-center justify-center relative z-10">
                <!-- Physical Keychain Interactive Card -->
                <div
                    @click="triggerKeychainTap"
                    class="w-full max-w-sm rounded-3xl bg-white border border-zinc-200 p-6 shadow-xl shadow-zinc-900/5 hover:border-zinc-300 transition-all cursor-pointer relative group"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <div class="flex items-center gap-2">
                            <ApplicationLogo className="w-6 h-6 object-contain" variant="1" />
                            <span class="text-xs font-bold text-zinc-900">Gantungan Kunci LUNOU</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-[10px] font-mono font-bold text-blue-700 border border-blue-200/60">
                            Sentuh untuk Uji Coba Tap
                        </span>
                    </div>

                    <!-- Keychain Visual with Ring & Strap -->
                    <div class="py-6 flex flex-col items-center text-center">
                        <!-- Keyring Loop (SVG Graphic) -->
                        <div class="w-10 h-10 rounded-full border-4 border-zinc-300 shadow-inner -mb-3 z-0 flex items-center justify-center">
                            <div class="w-4 h-4 rounded-full border-2 border-zinc-200"></div>
                        </div>

                        <!-- Keychain Charm (Mascot Character) -->
                        <div
                            class="w-36 h-36 rounded-3xl bg-zinc-50 border-2 border-zinc-200 p-4 flex flex-col items-center justify-between shadow-lg relative z-10 group-hover:scale-105 transition-transform"
                            :class="{ 'ring-4 ring-blue-500/30 scale-105': keychainTapped }"
                        >
                            <img src="/icon/1.png" alt="LUNOU Character Charm" class="w-20 h-20 object-contain drop-shadow-md my-auto" />
                            <div class="w-full pt-1 border-t border-zinc-200/80 flex items-center justify-between text-[9px] font-mono text-zinc-500">
                                <span>TRI WICAKSONO</span>
                                <span>NFC</span>
                            </div>
                        </div>                        <!-- Tap hint -->
                        <div class="mt-4 space-y-1">
                            <p class="text-xs font-bold text-zinc-900">Gantungan kunci praktis untuk membuka identitasmu.</p>
                            <p class="text-[11px] text-zinc-500">Dekatkan ke ponsel ber-NFC atau bagikan via tautan & QR code.</p>
                        </div>
                    </div>

                    <!-- Tapped feedback notification -->
                    <div
                        v-if="keychainTapped"
                        class="absolute inset-x-4 bottom-4 p-3 rounded-2xl bg-zinc-950 text-white text-xs font-medium flex items-center gap-2.5 shadow-xl transition-all"
                    >
                        <ApplicationLogo className="w-5 h-5 object-contain" variant="1" />
                        <div class="min-w-0 text-left">
                            <strong class="block text-white text-xs font-bold">NFC Terdeteksi!</strong>
                            <span class="block text-[10px] text-zinc-400 truncate">Membuka profil: Tri Wicaksono (@tri)</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 01 — THE STORY -->
        <section id="story" class="py-20 md:py-28 bg-white border-y border-zinc-200/80">
            <div class="max-w-4xl mx-auto px-6 text-center space-y-6">
                <span class="px-3 py-1 rounded-full bg-zinc-100 text-zinc-600 text-xs font-mono font-bold uppercase tracking-wider">
                    01 — Cerita di Balik LUNOU
                </span>

                <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                    Satu tempat untuk memperkenalkan dirimu.<br />
                    <span class="text-zinc-500 font-normal">Identitasmu. Karyamu. Ruang personalmu.</span>
                </h2>

                <div class="text-base sm:text-lg text-zinc-600 leading-relaxed font-normal space-y-4 max-w-2xl mx-auto text-left sm:text-center pt-2">
                    <p>
                        Di dunia yang semakin terhubung, kita punya banyak hal untuk diceritakan: karya yang kita bangun, ide yang kita bagikan, dan orang-orang yang ingin tetap terhubung dengan kita.
                    </p>
                    <p>
                        Namun, semuanya sering tersebar di berbagai media sosial dan platform yang berbeda. LUNOU hadir untuk menyatukannya dalam satu profil personal yang rapi, hangat, dan bermakna.
                    </p>
                    <p class="font-medium text-zinc-900">
                        Bukan tentang menjadi lebih terlihat. LUNOU tentang memudahkan orang lain mengenal siapa dirimu yang sesungguhnya.
                    </p>
                </div>
            </div>
        </section>

        <!-- 02 — YOUR OWN SPACE -->
        <section id="space" class="py-20 md:py-28 bg-[#fafbfc]">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6 space-y-6 text-left">
                    <span class="px-3 py-1 rounded-full bg-zinc-100 text-zinc-600 text-xs font-mono font-bold uppercase tracking-wider">
                        02 — Ruang Milikmu
                    </span>

                    <h2 class="text-3xl sm:text-4xl font-black text-zinc-950 tracking-tight leading-snug">
                        Atur bagian profilmu sesuai kebutuhan.
                    </h2>

                    <p class="text-base text-zinc-600 leading-relaxed">
                        Tambahkan foto, tulis bio singkat, dan satukan Instagram, TikTok, LinkedIn, YouTube, portofolio, hingga kontak WhatsApp. Mulai dari yang sederhana, kembangkan saat kamu siap.
                    </p>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <strong class="text-xs font-bold text-zinc-900 block">Katalog Ikon & Platform Lengkap</strong>
                                <span class="text-xs text-zinc-500">Cukup cari nama platform favoritmu atau tambahkan tautan khusus dengan mudah.</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <strong class="text-xs font-bold text-zinc-900 block">Atur & Sembunyikan Kapan Saja</strong>
                                <span class="text-xs text-zinc-500">Ubah urutan atau sembunyikan tautan yang belum ingin ditampilkan tanpa menghapusnya.</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <strong class="text-xs font-bold text-zinc-900 block">Pilihan Tema Sesuai Karaktermu</strong>
                                <span class="text-xs text-zinc-500">Mulai dari Minimal Pearl, Midnight Obsidian, Cyberpunk Emerald, hingga Solar Flare.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3">
                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors"
                        >
                            <span>Mulai buat profilmu sekarang</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Showcase Phone Mockup -->
                <div class="lg:col-span-6 flex justify-center">
                    <div class="w-80 rounded-[40px] bg-white border-8 border-zinc-200/90 p-5 shadow-2xl relative select-none">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <img
                                src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80"
                                alt="Tri"
                                class="w-20 h-20 rounded-full object-cover border-2 border-white shadow-md"
                            />
                            <div>
                                <h3 class="font-black text-base text-zinc-900 leading-tight">Tri Wicaksono</h3>
                                <p class="text-xs text-blue-600 font-mono font-semibold mt-0.5">me.myoin.id/tri</p>
                            </div>
                            <p class="text-xs text-zinc-600 leading-relaxed px-2">
                                Product Designer & Tech Explorer. Menghubungkan orang melalui gantungan kunci NFC dan ruang digital personal.
                            </p>

                            <!-- Social Links Showcase -->
                            <div class="w-full space-y-2 pt-2 text-left">
                                <div class="p-3 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-center justify-between text-xs font-bold text-zinc-800">
                                    <div class="flex items-center gap-2.5">
                                        <LunouIcon name="instagram" className="w-4 h-4 text-pink-600" />
                                        <span>Instagram</span>
                                    </div>
                                    <span class="text-zinc-400">&rarr;</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-center justify-between text-xs font-bold text-zinc-800">
                                    <div class="flex items-center gap-2.5">
                                        <LunouIcon name="linkedin" className="w-4 h-4 text-blue-600" />
                                        <span>LinkedIn Network</span>
                                    </div>
                                    <span class="text-zinc-400">&rarr;</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-center justify-between text-xs font-bold text-zinc-800">
                                    <div class="flex items-center gap-2.5">
                                        <LunouIcon name="youtube" className="w-4 h-4 text-red-600" />
                                        <span>YouTube Channel</span>
                                    </div>
                                    <span class="text-zinc-400">&rarr;</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-zinc-950 text-white flex items-center justify-between text-xs font-bold">
                                    <div class="flex items-center gap-2.5">
                                        <ApplicationLogo className="w-4 h-4 object-contain" variant="1" />
                                        <span>Gantungan Kunci LUNOU</span>
                                    </div>
                                    <span class="text-zinc-400">&rarr;</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 02.5 — CONTOH KARYA & FOTO (SHOWCASE SLIDER - DIATUR VIA ADMIN) -->
        <section id="showcase" class="py-20 md:py-28 bg-[#fafbfc] border-b border-zinc-200/80 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="max-w-3xl mx-auto text-center space-y-4">
                    <span class="px-3.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/70 text-xs font-mono font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                        <LunouIcon name="layers" className="w-3.5 h-3.5" />
                        <span>Koleksi Karya & Foto Nyata</span>
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                        Inspirasi Portofolio & Produk Nyata
                    </h2>
                    <p class="text-base sm:text-lg text-zinc-600 leading-relaxed font-normal">
                        Lihat langsung bagaimana profil digital, lembar karya desain, berkas PDF resmi, dan gantungan kunci pintar LUNOU ditampilkan secara profesional.
                    </p>
                </div>

                <!-- Showcase Carousel Slider Container -->
                <div class="relative max-w-5xl mx-auto">
                    <!-- Slide View Area -->
                    <div class="overflow-hidden rounded-3xl bg-white border border-zinc-200/90 shadow-xl shadow-zinc-950/5">
                        <div class="grid grid-cols-1 lg:grid-cols-12 items-stretch min-h-[460px]">
                            <!-- Left: Large Image Preview -->
                            <div class="lg:col-span-7 relative min-h-[280px] sm:min-h-[360px] lg:min-h-[460px] bg-zinc-900 overflow-hidden group">
                                <img
                                    :src="currentSlide.image_url"
                                    :alt="currentSlide.title"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    @error="$event.target.src = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80'"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                                <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white text-xs">
                                    <span v-if="currentSlide.tag" class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-bold text-[11px] border border-white/30">
                                        {{ currentSlide.tag }}
                                    </span>
                                    <span class="px-2.5 py-1 rounded-full bg-black/40 backdrop-blur-md text-zinc-200 font-mono text-[11px]">
                                        {{ activeSlideIndex + 1 }} / {{ slides.length }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Slide Information & Description -->
                            <div class="lg:col-span-5 p-7 sm:p-10 flex flex-col justify-between text-left space-y-6">
                                <div class="space-y-4">
                                    <div class="space-y-1">
                                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider block">
                                            {{ currentSlide.subtitle || 'Portofolio Terverifikasi' }}
                                        </span>
                                        <h3 class="text-2xl sm:text-3xl font-black text-zinc-950 tracking-tight leading-snug">
                                            {{ currentSlide.title }}
                                        </h3>
                                    </div>

                                    <p class="text-sm text-zinc-600 leading-relaxed font-normal whitespace-pre-line">
                                        {{ currentSlide.description }}
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-zinc-200/80 space-y-4">
                                    <div class="flex items-center gap-3">
                                        <a
                                            v-if="currentSlide.link"
                                            :href="currentSlide.link"
                                            class="px-5 py-3 rounded-2xl bg-zinc-950 hover:bg-black text-white text-xs font-bold inline-flex items-center gap-2 transition-all shadow-md min-h-[44px]"
                                        >
                                            <span>Lihat Contoh Ini</span>
                                            <span>&rarr;</span>
                                        </a>
                                        <Link
                                            href="/register"
                                            class="px-4 py-3 rounded-2xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-bold inline-flex items-center transition-all min-h-[44px]"
                                        >
                                            Buat Milikmu
                                        </Link>
                                    </div>

                                    <!-- Navigation Arrow Buttons & Dots -->
                                    <div class="flex items-center justify-between pt-2">
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                v-for="(_, dIdx) in slides"
                                                :key="dIdx"
                                                @click="goToSlide(dIdx)"
                                                type="button"
                                                class="h-2 rounded-full transition-all"
                                                :class="activeSlideIndex === dIdx ? 'w-7 bg-blue-600' : 'w-2 bg-zinc-300 hover:bg-zinc-400'"
                                                :aria-label="`Slide ${dIdx + 1}`"
                                            ></button>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="prevSlide"
                                                class="w-10 h-10 rounded-xl bg-white border border-zinc-200 text-zinc-700 hover:text-zinc-950 hover:bg-zinc-50 flex items-center justify-center transition-colors shadow-2xs"
                                                aria-label="Slide Sebelumnya"
                                            >
                                                &larr;
                                            </button>
                                            <button
                                                type="button"
                                                @click="nextSlide"
                                                class="w-10 h-10 rounded-xl bg-white border border-zinc-200 text-zinc-700 hover:text-zinc-950 hover:bg-zinc-50 flex items-center justify-center transition-colors shadow-2xs"
                                                aria-label="Slide Selanjutnya"
                                            >
                                                &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 03 — MEET YOUR KEYCHAIN -->
        <section id="keychain" class="py-20 md:py-28 bg-white border-y border-zinc-200/80">
            <div class="max-w-7xl mx-auto px-6 text-center space-y-12">
                <div class="max-w-2xl mx-auto space-y-4">
                    <span class="px-3 py-1 rounded-full bg-zinc-100 text-zinc-600 text-xs font-mono font-bold uppercase tracking-wider">
                        03 — Gantungan Kunci NFC
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                        Bertemu orang baru?<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
                            Bagikan profilmu lebih praktis.
                        </span>
                    </h2>
                    <p class="text-base text-zinc-600 leading-relaxed font-normal">
                        Bukan kartu nama kaku yang disimpan di dompet lalu terlupakan. Gantungan kunci LUNOU selalu kamu bawa di tas, kunci, atau lanyard, memiliki karakter, dan siap membantu orang membuka profilmu melalui ponsel yang mendukung NFC.
                    </p>
                </div>

                <!-- 4 Pillars of Keychain Experience -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-left">
                    <div class="p-6 rounded-3xl bg-zinc-50 border border-zinc-200 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-white border border-zinc-200 flex items-center justify-center text-blue-600 font-bold text-sm shadow-xs">
                            01
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Pilih Desainmu</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Pilih bentuk, warna, karakter maskot LUNOU favoritmu, atau inisial namamu yang punya makna personal bagimu.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-zinc-50 border border-zinc-200 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-white border border-zinc-200 flex items-center justify-center text-blue-600 font-bold text-sm shadow-xs">
                            02
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Hubungkan Akunmu</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Chip NFC resmi kami siapkan untuk langsung membuka profil digital atau tautan penting yang kamu tentukan.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-zinc-50 border border-zinc-200 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-white border border-zinc-200 flex items-center justify-center text-blue-600 font-bold text-sm shadow-xs">
                            03
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Bagikan Sekali Sentuh</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Cukup dekatkan ke ponsel yang mendukung NFC. Untuk ponsel lain, profil tetap dapat dibuka dengan mudah melalui tautan atau QR code.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-zinc-50 border border-zinc-200 space-y-3">
                        <div class="w-10 h-10 rounded-2xl bg-white border border-zinc-200 flex items-center justify-center text-blue-600 font-bold text-sm shadow-xs">
                            04
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Selalu Ada Bersamamu</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Aksesori harian tanpa baterai yang awet, ramah lingkungan, dan selalu siap menemani perkenalan bermaknami setiap hari.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 04 — PILIHAN PAKET (SOFT-SELLING TRANSPARENT PRICING) -->
        <section id="pricing" class="py-20 md:py-28 bg-[#fafbfc] border-b border-zinc-200/80">
            <div class="max-w-7xl mx-auto px-6 space-y-14">
                <div class="max-w-3xl mx-auto text-center space-y-4">
                    <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/70 text-xs font-mono font-bold uppercase tracking-wider">
                        04 — Pilihan Ruang & Paket
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                        Pilih ruang yang sesuai untukmu.
                    </h2>
                    <p class="text-base sm:text-lg text-zinc-600 leading-relaxed font-normal">
                        Mulai dari profil sederhana, lalu tambahkan lebih banyak ruang dan fitur ketika kamu membutuhkannya. Transparan, tanpa biaya tersembunyi.
                    </p>
                </div>

                <!-- 3 Product Cards Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                    <!-- PRODUCT A: Web Portofolio Digital -->
                    <div class="rounded-3xl bg-white border border-zinc-200/90 p-7 sm:p-8 flex flex-col justify-between shadow-xs hover:border-zinc-300 transition-all text-left space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200/60">
                                    Digital Portofolio
                                </span>
                                <span class="text-xs text-zinc-400 font-mono">Tanpa Fisik</span>
                            </div>

                            <div>
                                <h3 class="text-xl font-black text-zinc-950">Web Portofolio Pro</h3>
                                <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
                                    Editor visual serasa Canva untuk membuat portfolio web personal resmi dengan tautan khusus.
                                </p>
                            </div>

                            <!-- Pricing -->
                            <div class="py-3 border-y border-zinc-100 flex items-baseline gap-2">
                                <template v-if="freeModeEnabled">
                                    <span class="text-3xl font-black text-emerald-600 font-mono">GRATIS</span>
                                    <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                        Promo Bebas Biaya
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-3xl font-black text-zinc-950 font-mono">Rp 49.000</span>
                                    <span class="text-xs text-zinc-500 font-medium">/ tahun</span>
                                </template>
                            </div>

                            <!-- What's Included -->
                            <div class="space-y-2.5 pt-1">
                                <div class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Yang Kamu Dapatkan:</div>
                                <ul class="space-y-2 text-xs text-zinc-600">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Editor visual drag-and-drop tanpa koding</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Alamat web khusus: <code class="font-mono text-blue-600 font-bold">me.myoin.id/p/kamu</code></span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Penampil dokumen PDF model Flipbook interaktif</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Galeri studi kasus karya & tautan tak terbatas</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-zinc-400">
                                        <svg class="w-4 h-4 text-zinc-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>Tidak termasuk gantungan kunci fisik NFC</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <Link
                            href="/register"
                            class="w-full py-3.5 px-4 rounded-2xl bg-zinc-100 hover:bg-zinc-200 text-zinc-900 font-bold text-xs flex items-center justify-center gap-2 transition-all min-h-[44px]"
                        >
                            <span>{{ freeModeEnabled ? 'Mulai Portofolio Pro Gratis' : 'Mulai Portofolio Pro' }}</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>

                    <!-- PRODUCT B: Gantungan Kunci NFC Saja -->
                    <div class="rounded-3xl bg-white border border-zinc-200/90 p-7 sm:p-8 flex flex-col justify-between shadow-xs hover:border-zinc-300 transition-all text-left space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200/60">
                                    Perangkat Fisik
                                </span>
                                <span class="text-xs text-zinc-400 font-mono">Sekali Beli</span>
                            </div>

                            <div>
                                <h3 class="text-xl font-black text-zinc-950">Gantungan Kunci NFC Saja</h3>
                                <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
                                    Aksesori gantungan kunci ber-chip NFC untuk membuka tautan profilmu dalam satu sentuhan ke ponsel.
                                </p>
                            </div>

                            <!-- Pricing -->
                            <div class="py-3 border-y border-zinc-100 flex items-baseline gap-2">
                                <span class="text-3xl font-black text-zinc-950 font-mono">Rp 49.000</span>
                                <span class="text-xs text-zinc-500 font-medium">sekali beli · aktif selamanya</span>
                            </div>

                            <!-- What's Included -->
                            <div class="space-y-2.5 pt-1">
                                <div class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Yang Kamu Dapatkan:</div>
                                <ul class="space-y-2 text-xs text-zinc-600">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>1 unit Gantungan Kunci LUNOU (Karakter / Inisial)</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Chip NFC resmi diprogram membuka link pilihanmu</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Bebas dihubungkan ke profil sosial atau link pribadimu</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Tanpa baterai, awet, & proses kirim 1–2 hari kerja</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-zinc-400">
                                        <svg class="w-4 h-4 text-zinc-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>Tidak termasuk akses Web Portofolio Pro</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <Link
                            href="/register"
                            class="w-full py-3.5 px-4 rounded-2xl bg-zinc-100 hover:bg-zinc-200 text-zinc-900 font-bold text-xs flex items-center justify-center gap-2 transition-all min-h-[44px]"
                        >
                            <span>Pesan Gantungan Kunci Saja</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>

                    <!-- PRODUCT C: Bundle All-in-One -->
                    <div class="rounded-3xl bg-zinc-950 text-white p-7 sm:p-8 flex flex-col justify-between shadow-xl ring-2 ring-blue-500/80 relative text-left space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold border border-blue-400/40">
                                    Pilihan Terpadu
                                </span>
                                <span class="text-xs text-blue-300 font-mono">Fisik + Digital</span>
                            </div>

                            <div>
                                <h3 class="text-xl font-black text-white">Paket Bundel Lengkap</h3>
                                <p class="text-xs text-zinc-300 mt-1 leading-relaxed">
                                    Profil digital dan keychain, dalam satu pilihan terpadu untuk kamu yang ingin karya digital dan perangkat fisik sekaligus.
                                </p>
                            </div>

                            <!-- Pricing -->
                            <div class="py-3 border-y border-zinc-800 flex items-baseline gap-2">
                                <span class="text-3xl font-black text-white font-mono">Rp 89.000</span>
                                <span class="text-xs text-zinc-400 font-medium">/ tahun + 1 unit fisik</span>
                            </div>

                            <!-- What's Included -->
                            <div class="space-y-2.5 pt-1">
                                <div class="text-xs font-bold text-white uppercase tracking-wider">Semua Yang Termasuk:</div>
                                <ul class="space-y-2 text-xs text-zinc-300">
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Semua fitur Web Portofolio Pro selama 1 tahun penuh</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>1 unit Gantungan Kunci NFC Custom pilihanmu</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>NFC otomatis diprogram membuka portofolio resmimu</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Pengemasan rapi & pengiriman ke seluruh Indonesia</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <Link
                            href="/register"
                            class="w-full py-3.5 px-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-md min-h-[44px]"
                        >
                            <span>Pilih Paket Bundel</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- WHOLESALE & CUSTOM ORDER BANNER -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-zinc-200/90 shadow-xs flex flex-col md:flex-row items-center justify-between gap-6 text-left">
                    <div class="space-y-1.5 max-w-xl">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-zinc-100 text-zinc-700 text-[11px] font-bold">
                            <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Pesanan Grosir & Komunitas</span>
                        </div>
                        <h4 class="text-base sm:text-lg font-black text-zinc-950">
                            Butuh untuk Kantor, Event Kampus, atau Merchandise Komunitas?
                        </h4>
                        <p class="text-xs sm:text-sm text-zinc-600 leading-relaxed">
                            Pesan mulai dari 10 unit dengan logo custom instansimu dan pendampingan langsung oleh tim kami melalui WhatsApp.
                        </p>
                    </div>

                    <a
                        href="https://wa.me/6285862319524?text=Halo%20Admin%20Lunou,%20saya%20ingin%20konsultasi%20pemesanan%20grosir%20gantungan%20kunci%20NFC%20/%20bundle"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-6 py-3.5 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition-all whitespace-nowrap min-h-[44px]"
                    >
                        <span>Konsultasi via WhatsApp</span>
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- 04.5 — CARA PEMESANAN (DIATUR VIA ADMIN) -->
        <section id="cara-pesan" class="py-20 md:py-28 bg-white border-b border-zinc-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
                <div class="max-w-3xl mx-auto text-center space-y-4">
                    <span class="px-3.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/70 text-xs font-mono font-bold uppercase tracking-wider inline-flex items-center gap-1.5">
                        <LunouIcon name="sparkles" className="w-3.5 h-3.5" />
                        <span>Alur Pemesanan & Aktivasi</span>
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                        Cara Mudah Memesan & Mulai Berkarya
                    </h2>
                    <p class="text-base sm:text-lg text-zinc-600 leading-relaxed font-normal">
                        Proses cepat, transparan, dan terverifikasi aman. Hanya butuh beberapa menit sampai karyamu tayang secara publik.
                    </p>
                </div>

                <!-- Steps Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                    <div
                        v-for="(st, stIndex) in (orderSteps && orderSteps.length > 0 ? orderSteps : defaultOrderStepsList)"
                        :key="stIndex"
                        class="p-6 sm:p-7 rounded-3xl bg-[#fafbfc] border border-zinc-200/90 shadow-2xs hover:shadow-md hover:border-blue-200 transition-all flex flex-col justify-between space-y-5 text-left relative group"
                    >
                        <div class="space-y-4">
                            <!-- Step Number Badge -->
                            <div class="flex items-center justify-between">
                                <span class="w-10 h-10 rounded-2xl bg-blue-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-blue-600/20 group-hover:scale-105 transition-transform">
                                    {{ st.step_number || stIndex + 1 }}
                                </span>
                                <span class="px-2.5 py-1 rounded-full bg-white border border-zinc-200 text-zinc-700 text-[10px] font-bold uppercase tracking-wider">
                                    {{ st.badge || `Langkah ${stIndex + 1}` }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-zinc-950 tracking-tight leading-snug">
                                    {{ st.title }}
                                </h3>
                                <p class="text-xs text-zinc-600 leading-relaxed font-normal">
                                    {{ st.description }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-zinc-200/60 flex items-center justify-between text-xs font-semibold text-blue-600">
                            <span>Tahap {{ st.step_number || stIndex + 1 }}</span>
                            <span class="text-zinc-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </div>
                </div>

                <!-- CTA Banner under Cara Pesan -->
                <div class="p-7 sm:p-9 rounded-3xl bg-gradient-to-r from-zinc-950 via-zinc-900 to-blue-950 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 text-left">
                    <div class="space-y-1.5 max-w-xl">
                        <span class="text-[11px] font-bold text-blue-400 uppercase tracking-wider">Siap Memulai Sekarang?</span>
                        <h4 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            Wujudkan Website Portofolio Impianmu Hari Ini
                        </h4>
                        <p class="text-xs sm:text-sm text-zinc-300 leading-relaxed">
                            {{ freeModeEnabled ? 'Kabar gembira! Mode Akses Bebas Biaya sedang aktif. Kamu dapat langsung menikmati fitur Pro secara gratis tanpa biaya.' : 'Dapatkan akses editor portofolio visual ala Canva dan opsi pengiriman gantungan kunci YOIMO NFC ke rumahmu.' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 flex-shrink-0 w-full sm:w-auto">
                        <Link
                            href="/register"
                            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-white hover:bg-zinc-100 text-zinc-950 font-bold text-xs flex items-center justify-center gap-2 shadow-md transition-all min-h-[44px]"
                        >
                            <span>Daftar Sekarang</span>
                            <span>&rarr;</span>
                        </Link>
                        <a
                            href="https://wa.me/628123456789?text=Halo%20LUNOU,%20saya%20ingin%20tanya%20tentang%20pemesanan%20portofolio%20dan%20NFC"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full sm:w-auto px-5 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs flex items-center justify-center gap-2 transition-all min-h-[44px]"
                        >
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.203c.044.072.044.419-.1.824z"/></svg>
                            <span>Bantuan CS</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 05 — MADE FOR EVERY STORY -->
        <section id="stories" class="py-20 md:py-28 bg-white border-b border-zinc-200/80">
            <div class="max-w-7xl mx-auto px-6 space-y-12">
                <div class="max-w-2xl mx-auto text-center space-y-4">
                    <span class="px-3 py-1 rounded-full bg-zinc-100 text-zinc-600 text-xs font-mono font-bold uppercase tracking-wider">
                        05 — Untuk Setiap Perjalanan
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                        Dibuat untuk siapa pun yang ingin berbagi ceritanya.
                    </h2>
                    <p class="text-base text-zinc-600 leading-relaxed font-normal">
                        LUNOU dirancang untuk siapa pun yang ingin memperkenalkan identitasnya dengan cara yang lebih personal, rapi, dan menyenangkan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="p-6 rounded-3xl bg-[#fafbfc] border border-zinc-200/90 shadow-2xs space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <LunouIcon name="sparkles" className="w-5 h-5" />
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Kreator & Seniman</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Bagikan portofolio karya, video terbaru, akun Instagram, TikTok, dan Spotify dalam satu halaman saat bertemu di pameran atau event kreatif.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-[#fafbfc] border border-zinc-200/90 shadow-2xs space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                            <LunouIcon name="globe" className="w-5 h-5" />
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Mahasiswa & Fresh Graduate</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Gantungkan di tas kampus atau lanyard. Satukan proyek perkuliahan, akun organisasi, LinkedIn, dan media sosial saat networking karir.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-[#fafbfc] border border-zinc-200/90 shadow-2xs space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <LunouIcon name="chart" className="w-5 h-5" />
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Profesional & Freelancer</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Perkenalkan layanan dan karya terbaikmu dengan cara yang segar dan berkesan. Jauh lebih praktis dan modern dibanding kartu nama konvensional.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-[#fafbfc] border border-zinc-200/90 shadow-2xs space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                            <LunouIcon name="heart" className="w-5 h-5" />
                        </div>
                        <h3 class="text-base font-bold text-zinc-950">Pelaku Usaha & Komunitas</h3>
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Satukan nomor pemesanan WhatsApp, katalog produk, lokasi toko, dan tautan komunitas dalam satu akses mudah untuk pelangganmu.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 06 — YOUR STORY STARTS HERE -->
        <section class="py-20 md:py-28 bg-white border-t border-zinc-200/80">
            <div class="max-w-3xl mx-auto px-6 text-center space-y-8">
                <span class="px-3 py-1 rounded-full bg-zinc-100 text-zinc-600 text-xs font-mono font-bold uppercase tracking-wider">
                    06 — Mulai Langkahmu
                </span>

                <h2 class="text-3xl sm:text-5xl font-black text-zinc-950 tracking-tight leading-tight">
                    Satu tempat untuk memperkenalkan dirimu.
                </h2>

                <p class="text-base sm:text-lg text-zinc-600 leading-relaxed max-w-xl mx-auto">
                    Mulai dari yang sederhana. Kembangkan saat kamu siap.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 pt-2">
                    <Link
                        href="/register"
                        class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-sm shadow-md hover:shadow-lg transition-all min-h-[48px] flex items-center justify-center"
                    >
                        Buat Profilku Sekarang &rarr;
                    </Link>

                    <Link
                        href="/login"
                        class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 text-zinc-800 font-bold text-sm transition-all min-h-[48px] flex items-center justify-center"
                    >
                        Masuk ke Akun
                    </Link>
                </div>
            </div>
        </section>

        <!-- 07 — DUKUNGAN & DONASI SAWERIA -->
        <section id="donasi" class="py-16 md:py-20 bg-[#fafbfc] border-t border-zinc-200/80">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200/80 text-amber-800 text-xs font-bold">
                    <svg class="w-4 h-4 text-amber-500 fill-amber-500" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span>DUKUNGAN & APRESIASI KREATOR</span>
                </div>

                <div class="space-y-3">
                    <h2 class="text-2xl sm:text-4xl font-black text-zinc-950 tracking-tight">
                        Dukung & Donasi untuk Pengembangan Platform
                    </h2>
                    <p class="text-sm sm:text-base text-zinc-600 max-w-xl mx-auto leading-relaxed">
                        Suka dengan platform me.myoin.id? Kamu bisa mentraktir kopi atau memberikan dukungan donasi langsung melalui Saweria agar kami dapat terus menghadirkan pembaruan terbaik.
                    </p>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a
                        :href="saweriaUrl || 'https://saweria.co/Try3'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-7 py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all inline-flex items-center gap-2.5 min-h-[44px]"
                    >
                        <svg class="w-4 h-4 fill-white" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <span>Dukung Kami di Saweria (saweria.co/Try3)</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-zinc-200 py-12 bg-white text-xs text-zinc-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                    <div class="space-y-1.5 flex flex-col items-center md:items-start">
                        <Link href="/" class="flex items-center gap-2.5">
                            <ApplicationLogo className="w-7 h-7 object-contain" variant="1" />
                            <span class="font-black text-zinc-900 tracking-tight text-base">me.myoin.id</span>
                            <span class="text-zinc-400 text-xs">· part of lunou</span>
                        </Link>
                        <p class="text-[11px] text-zinc-400 max-w-sm">
                            Platform identitas digital personal, web portofolio builder ala Canva, dan gantungan kunci pintar YOIMO NFC.
                        </p>
                    </div>

                    <!-- Footer Navigation Columns / Links -->
                    <div class="flex flex-wrap items-center justify-center md:justify-end gap-x-5 gap-y-2 text-zinc-600 font-medium text-xs">
                        <Link href="/register" class="hover:text-zinc-950 transition-colors">Daftar Akun</Link>
                        <Link href="/login" class="hover:text-zinc-950 transition-colors">Masuk</Link>
                        <a href="#showcase" class="hover:text-zinc-950 transition-colors">Contoh Karya</a>
                        <a href="#cara-pesan" class="hover:text-zinc-950 transition-colors">Cara Pesan</a>
                        <Link href="/tri" class="hover:text-zinc-950 transition-colors">Contoh Profil (@tri)</Link>
                        <Link href="/p/tri" class="hover:text-zinc-950 transition-colors">Contoh Portofolio (/p/tri)</Link>
                        <Link href="/syarat-ketentuan" class="hover:text-zinc-950 font-bold text-zinc-800 transition-colors">Syarat & Ketentuan</Link>
                        <Link href="/syarat-ketentuan" class="hover:text-zinc-950 font-bold text-zinc-800 transition-colors">Kebijakan Privasi</Link>
                        <a
                            :href="saweriaUrl || 'https://saweria.co/Try3'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-amber-600 hover:text-amber-700 font-bold inline-flex items-center gap-1 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span>Donasi Saweria</span>
                        </a>
                        <a
                            href="https://wa.me/628123456789?text=Halo%20LUNOU,%20saya%20butuh%20bantuan"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:text-emerald-700 text-emerald-600 font-bold inline-flex items-center gap-1 transition-colors"
                        >
                            <span>WhatsApp Bantuan</span>
                        </a>
                    </div>
                </div>

                <div class="pt-6 border-t border-zinc-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-zinc-400">
                    <div>
                        &copy; {{ new Date().getFullYear() }} me.myoin.id · part of lunou. Hak Cipta Dilindungi Undang-Undang.
                    </div>
                    <div>
                        Keamanan Terenkripsi AES-256 · Tanpa Iklan Pihak Ketiga
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
