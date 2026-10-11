<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

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
                <nav class="hidden md:flex items-center gap-6 lg:gap-8 text-xs font-semibold text-zinc-600 flex-shrink-0">
                    <a href="#story" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Cerita</a>
                    <a href="#space" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Ruang Profil</a>
                    <a href="#keychain" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Gantungan Kunci</a>
                    <a href="#pricing" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Pilihan Paket</a>
                    <a href="#stories" class="hover:text-zinc-950 transition-colors whitespace-nowrap">Untuk Siapa</a>
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
                                <span class="text-3xl font-black text-zinc-950 font-mono">Rp 49.000</span>
                                <span class="text-xs text-zinc-500 font-medium">/ tahun</span>
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
                            <span>Mulai Portofolio Pro</span>
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

        <!-- Footer -->
        <footer class="border-t border-zinc-200 py-10 bg-white text-center text-xs text-zinc-500">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <ApplicationLogo className="w-6 h-6 object-contain" variant="1" />
                    <span class="font-black text-zinc-900 tracking-tight">me.myoin.id</span>
                    <span class="text-zinc-400">· part of lunou</span>
                </div>

                <div class="flex items-center gap-6 text-zinc-500 text-xs">
                    <Link href="/register" class="hover:text-zinc-950">Daftar</Link>
                    <Link href="/login" class="hover:text-zinc-950">Masuk</Link>
                    <Link href="/tri" class="hover:text-zinc-950">Contoh Profil</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
