<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';
import PortfolioBlockRenderer from '@/Components/PortfolioBlockRenderer.vue';

const props = defineProps({
    portfolio: {
        type: Object,
        required: true,
    },
    pages: {
        type: Array,
        default: () => [],
    },
    assets: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
    mainProfileUrl: {
        type: String,
        default: null,
    },
});

// Sorted pages matching the exact user layout order from Studio
const sortedPages = computed(() => {
    if (!props.pages || !props.pages.length) return [];
    return [...props.pages].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
});

// Flipbook page state
const currentFlipPage = ref(1);
const totalFlipPages = ref(8);
const viewMode = ref(props.portfolio.settings?.flipbook_default ? 'flipbook' : 'standard'); // 'flipbook' or 'standard'

// Project Detail Modal State (Self-contained in-app case study view)
const activeProjectDetail = ref(null);
const isProjectDetailOpen = ref(false);

function openProjectDetail(item) {
    activeProjectDetail.value = item;
    isProjectDetailOpen.value = true;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = 'hidden';
    }
}

function closeProjectDetail() {
    isProjectDetailOpen.value = false;
    activeProjectDetail.value = null;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
}

// Theme styles
const themeStyle = computed(() => {
    switch (props.portfolio.theme) {
        case 'creative-dark':
            return {
                bg: 'bg-zinc-950 text-white',
                card: 'bg-zinc-900/90 border border-zinc-800 text-white',
                header: 'bg-zinc-950/90 border-zinc-800 text-white',
                textMuted: 'text-zinc-400',
                accentText: 'text-blue-400',
                accentBg: 'bg-blue-600 text-white',
            };
        case 'obsidian':
            return {
                bg: 'bg-black text-white',
                card: 'bg-zinc-900 border border-zinc-800 text-white',
                header: 'bg-black/90 border-zinc-800 text-white',
                textMuted: 'text-zinc-400',
                accentText: 'text-amber-400',
                accentBg: 'bg-amber-500 text-black',
            };
        case 'minimal-clean':
        default:
            return {
                bg: 'bg-[#fafbfc] text-zinc-900',
                card: 'bg-white border border-zinc-200/90 text-zinc-900 shadow-2xs',
                header: 'bg-white/90 border-zinc-200/80 text-zinc-900',
                textMuted: 'text-zinc-500',
                accentText: 'text-blue-600',
                accentBg: 'bg-zinc-950 text-white',
            };
    }
});

function turnNextPage() {
    if (currentFlipPage.value < totalFlipPages.value) {
        currentFlipPage.value++;
    }
}

function turnPrevPage() {
    if (currentFlipPage.value > 1) {
        currentFlipPage.value--;
    }
}
</script>

<template>
    <div :class="['min-h-screen font-sans selection:bg-blue-600 selection:text-white flex flex-col antialiased', themeStyle.bg]">
        <Head :title="`${portfolio.title} · me.myoin.id (part of lunou)`" />

        <!-- Top Navigation -->
        <header :class="['h-16 border-b backdrop-blur-md sticky top-0 z-40 px-4 sm:px-8 flex items-center justify-between gap-3 shadow-2xs transition-all', themeStyle.header]">
            <!-- Left: Author Brand -->
            <div class="flex items-center gap-3 min-w-0">
                <Link href="/" class="flex items-center gap-2 group flex-shrink-0">
                    <ApplicationLogo className="w-7 h-7 sm:w-8 sm:h-8 flex-shrink-0" variant="1" />
                </Link>

                <div class="flex items-center gap-2 min-w-0">
                    <span class="font-extrabold text-sm sm:text-base tracking-tight truncate">{{ user.name }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 hidden sm:inline-block">
                        Portfolio
                    </span>
                </div>
            </div>

            <!-- Right: Quick Navigation & Bio Link -->
            <div class="flex items-center gap-2 sm:gap-3 text-xs flex-shrink-0">
                <Link
                    v-if="isOwner"
                    href="/studio/portfolio"
                    class="px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 font-bold hover:bg-blue-100 transition-colors flex items-center gap-1"
                >
                    <LunouIcon name="pencil" className="w-3.5 h-3.5 text-blue-600" />
                    <span class="hidden sm:inline">Edit Portofolio</span>
                </Link>

                <a
                    v-if="mainProfileUrl"
                    :href="mainProfileUrl"
                    class="px-3.5 py-1.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 font-semibold transition-colors flex items-center gap-1.5 text-zinc-800"
                    title="Buka profil bio & tautan sosial"
                >
                    <span>Profil Sosial</span>
                    <span class="text-zinc-400">&rarr;</span>
                </a>
            </div>
        </header>

        <!-- Main Portfolio Content (Seamless, Responsive, 1:1 Sorted Layout Order) -->
        <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-8 py-8 sm:py-16 space-y-12 sm:space-y-20">
            <template v-for="(page, pageIdx) in sortedPages" :key="page.id || pageIdx">
                <!-- 1. COVER / HERO SECTION (Sama persis dengan tata letak Studio: Banner di atas, konten di bawah) -->
                <section
                    v-if="page.page_type === 'cover'"
                    :id="`page-${page.id}`"
                    class="space-y-6 sm:space-y-8"
                >
                    <!-- Cover Header Banner -->
                    <div
                        v-if="page.content?.cover_image"
                        class="w-full h-56 sm:h-80 md:h-[420px] rounded-3xl overflow-hidden shadow-xs bg-zinc-100 border border-zinc-200/80 relative"
                    >
                        <img
                            :src="page.content.cover_image"
                            :alt="page.content?.headline || portfolio.title"
                            class="w-full h-full object-cover"
                        />
                    </div>

                    <!-- Clean Hero Typography (Tanpa container box yang mengurung) -->
                    <div class="space-y-4 max-w-3xl">
                        <div class="flex items-center gap-2">
                            <span class="px-3.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-extrabold uppercase tracking-wider">
                                {{ page.content?.author_title || 'Kreator & Profesional' }}
                            </span>
                            <span v-if="page.content?.location" class="text-xs text-zinc-500 font-medium">
                                &bull; {{ page.content?.location }}
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight text-zinc-950">
                            {{ page.content?.headline || portfolio.title }}
                        </h1>

                        <p class="text-base sm:text-lg text-zinc-600 leading-relaxed max-w-2xl">
                            {{ page.content?.subheadline || portfolio.bio }}
                        </p>

                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <a
                                href="#projects"
                                class="px-6 py-3 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-md transition-all inline-flex items-center gap-2 min-h-11"
                            >
                                <span>{{ page.content?.cta_text || 'Lihat Proyek Saya' }}</span>
                                <span>&darr;</span>
                            </a>
                            <a
                                v-if="pages.some(p => p.page_type === 'pdf_book')"
                                href="#pdf-document"
                                class="px-5 py-3 rounded-2xl bg-zinc-100 hover:bg-zinc-200 border border-zinc-200 text-zinc-800 font-semibold text-xs sm:text-sm transition-all inline-flex items-center gap-2 min-h-11"
                            >
                                <LunouIcon name="card" className="w-4 h-4" />
                                <span>Buka Buku Digital / PDF</span>
                            </a>
                        </div>
                    </div>

                    <!-- Cover Additional Free Blocks -->
                    <div v-if="page.content?.blocks && page.content.blocks.length" class="pt-4">
                        <PortfolioBlockRenderer :blocks="page.content.blocks" :themeStyle="themeStyle" />
                    </div>
                </section>

                <!-- 2. ABOUT ME SECTION -->
                <section
                    v-else-if="page.page_type === 'about'"
                    :id="`page-${page.id}`"
                    class="space-y-6 pt-2"
                >
                    <div class="space-y-2">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 inline-block">
                            Tentang Saya
                        </span>
                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-zinc-950">
                            {{ page.content?.heading || 'Dedikasi & Filosofi Berkarya' }}
                        </h2>
                    </div>

                    <p class="text-base sm:text-lg leading-relaxed text-zinc-600 max-w-3xl whitespace-pre-line">
                        {{ page.content?.bio_paragraph || portfolio.bio }}
                    </p>

                    <!-- Skills -->
                    <div v-if="page.content?.skills && page.content.skills.length" class="space-y-2.5 pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                            Keahlian Utama
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(skill, i) in page.content.skills"
                                :key="i"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-zinc-100 text-zinc-800 border border-zinc-200/80 shadow-2xs"
                            >
                                {{ skill }}
                            </span>
                        </div>
                    </div>

                    <!-- Experience -->
                    <div v-if="page.content?.experience && page.content.experience.length" class="space-y-3 pt-4 border-t border-zinc-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                            Pengalaman & Rekam Jejak
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="(exp, i) in page.content.experience"
                                :key="i"
                                class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 space-y-1"
                            >
                                <div class="font-bold text-sm text-zinc-900">{{ exp.role }}</div>
                                <div class="text-xs text-zinc-600 font-medium">{{ exp.company }}</div>
                                <div class="text-[11px] text-zinc-400 font-mono">{{ exp.period }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- About Additional Free Blocks -->
                    <div v-if="page.content?.blocks && page.content.blocks.length" class="pt-4">
                        <PortfolioBlockRenderer :blocks="page.content.blocks" :themeStyle="themeStyle" />
                    </div>
                </section>

                <!-- 3. PROJECTS SHOWCASE SECTION -->
                <section
                    v-else-if="page.page_type === 'projects'"
                    id="projects"
                    class="space-y-6 pt-2"
                >
                    <div class="space-y-1.5">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 inline-block">
                            Showcase Karya
                        </span>
                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-zinc-950">
                            {{ page.content?.heading || 'Proyek & Galeri Terpilih' }}
                        </h2>
                        <p class="text-sm sm:text-base text-zinc-500 max-w-2xl leading-relaxed">
                            {{ page.content?.description || 'Eksplorasi ide, implementasi desain, dan hasil nyata.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                        <div
                            v-for="(item, i) in (page.content?.items || [])"
                            :key="i"
                            @click="openProjectDetail(item)"
                            class="rounded-3xl overflow-hidden flex flex-col group hover:shadow-xl hover:-translate-y-0.5 transition-all cursor-pointer border border-zinc-200/90 bg-white"
                        >
                            <div class="h-48 sm:h-56 bg-zinc-100 overflow-hidden relative">
                                <img
                                    :src="item.image || 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80'"
                                    :alt="item.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <span class="text-xs font-bold text-white inline-flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-xl">
                                        <span>Buka Detail Studi Kasus</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </span>
                                </div>
                                <span v-if="item.category" class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md text-zinc-900 font-bold text-[10px] uppercase tracking-wider shadow-xs">
                                    {{ item.category }}
                                </span>
                            </div>

                            <div class="p-5 sm:p-6 space-y-3 flex-1 flex flex-col justify-between">
                                <div class="space-y-1.5">
                                    <h3 class="font-extrabold text-base sm:text-lg tracking-tight text-zinc-950 group-hover:text-blue-600 transition-colors">
                                        {{ item.title }}
                                    </h3>
                                    <p class="text-xs leading-relaxed line-clamp-2 text-zinc-500">
                                        {{ item.description }}
                                    </p>
                                </div>

                                <!-- Tools & Technologies Pills -->
                                <div v-if="item.tools && item.tools.length" class="flex flex-wrap gap-1.5 pt-1">
                                    <span
                                        v-for="(tool, tIdx) in item.tools.slice(0, 3)"
                                        :key="tIdx"
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-zinc-100 text-zinc-700"
                                    >
                                        {{ tool }}
                                    </span>
                                    <span v-if="item.tools.length > 3" class="text-[10px] text-zinc-400 font-semibold self-center">
                                        +{{ item.tools.length - 3 }}
                                    </span>
                                </div>

                                <!-- Action Buttons Row -->
                                <div class="pt-3 border-t border-zinc-100/80 flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        @click.stop="openProjectDetail(item)"
                                        class="px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-2xs hover:opacity-90 bg-zinc-950 text-white"
                                    >
                                        <span>Lihat Detail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>

                                    <!-- TOMBOL KE PIHAK KE 3 ITU OPSIONAL -->
                                    <a
                                        v-if="item.link && item.link !== '#' && item.link.trim() !== ''"
                                        :href="item.link"
                                        target="_blank"
                                        @click.stop
                                        class="text-[11px] font-semibold text-zinc-500 hover:text-zinc-900 transition-colors inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg hover:bg-zinc-100/80"
                                        title="Tautan eksternal (opsional)"
                                    >
                                        <span>{{ item.link_label || 'Pihak ke-3' }}</span>
                                        <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Additional Free Blocks -->
                    <div v-if="page.content?.blocks && page.content.blocks.length" class="pt-4">
                        <PortfolioBlockRenderer :blocks="page.content.blocks" :themeStyle="themeStyle" />
                    </div>
                </section>

                <!-- 4. PDF DIGITAL FLIPBOOK / EMBEDDED DOCUMENT SECTION -->
                <section
                    v-else-if="page.page_type === 'pdf_book'"
                    id="pdf-document"
                    class="space-y-6 pt-2"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-xs font-extrabold uppercase tracking-widest text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 inline-block">
                                Digital Flipbook & Dokumen
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-zinc-950">
                                {{ page.content?.heading || 'Buku Portofolio & Dokumen Resmi' }}
                            </h2>
                            <p class="text-xs sm:text-sm text-zinc-500">
                                {{ page.content?.description || 'Buka halaman dokumen dalam mode buku digital atau pembaca dokumen standar.' }}
                            </p>
                        </div>

                        <!-- Mode Toggle (Flipbook vs Standar) -->
                        <div class="flex items-center gap-1 p-1 rounded-2xl bg-zinc-100 border border-zinc-200 text-xs font-bold w-fit">
                            <button
                                @click="viewMode = 'flipbook'"
                                type="button"
                                class="px-3 py-1.5 rounded-xl transition-all"
                                :class="viewMode === 'flipbook' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-950'"
                            >
                                Mode Buku Digital
                            </button>
                            <button
                                @click="viewMode = 'standard'"
                                type="button"
                                class="px-3 py-1.5 rounded-xl transition-all"
                                :class="viewMode === 'standard' ? 'bg-zinc-950 text-white shadow-2xs' : 'text-zinc-600 hover:text-zinc-950'"
                            >
                                Mode Standar / Ponsel
                            </button>
                        </div>
                    </div>

                    <!-- FLIPBOOK INTERACTIVE VIEWER -->
                    <div v-if="viewMode === 'flipbook'" class="space-y-4">
                        <div class="bg-gradient-to-b from-zinc-100 to-zinc-200/90 rounded-3xl p-4 sm:p-8 flex flex-col items-center justify-center min-h-[360px] sm:min-h-[460px] relative shadow-inner overflow-hidden border border-zinc-300">
                            <!-- Book Simulation Sheet -->
                            <div class="w-full max-w-xl aspect-[4/3] bg-white rounded-2xl shadow-xl border border-zinc-200 p-6 sm:p-8 flex flex-col justify-between transition-all transform duration-300">
                                <!-- Book Page Header -->
                                <div class="flex items-center justify-between border-b border-zinc-100 pb-2 text-[10px] sm:text-xs text-zinc-400 font-mono">
                                    <span>{{ page.content?.document_title || 'Document.pdf' }}</span>
                                    <span>Halaman {{ currentFlipPage }} dari {{ totalFlipPages }}</span>
                                </div>

                                <!-- Page Sample Content Preview -->
                                <div class="space-y-4 my-auto text-center py-4">
                                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto shadow-2xs">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="font-bold text-zinc-950 text-base sm:text-lg">
                                            {{ currentFlipPage === 1 ? (page.content?.document_title || 'Sampul Dokumen Portofolio') : `Bab ${currentFlipPage}: Deskripsi & Lampiran Proyek` }}
                                        </div>
                                        <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                                            Dokumen interaktif terverifikasi. Gunakan navigasi panah di bawah untuk membuka halaman berikutnya.
                                        </p>
                                    </div>
                                </div>

                                <!-- Book Page Footer -->
                                <div class="border-t border-zinc-100 pt-2 flex items-center justify-between text-[11px] text-zinc-400">
                                    <span>&copy; {{ user.name }}</span>
                                    <span class="font-mono">{{ currentFlipPage }} / {{ totalFlipPages }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Flipbook Controls -->
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <button
                                @click="turnPrevPage"
                                :disabled="currentFlipPage === 1"
                                class="px-4 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-bold disabled:opacity-40 transition-colors flex items-center gap-1.5"
                            >
                                &larr; Halaman Sebelumnya
                            </button>

                            <div class="font-mono text-zinc-500 font-bold">
                                Halaman {{ currentFlipPage }} / {{ totalFlipPages }}
                            </div>

                            <button
                                @click="turnNextPage"
                                :disabled="currentFlipPage === totalFlipPages"
                                class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold disabled:opacity-40 transition-colors flex items-center gap-1.5"
                            >
                                Halaman Berikutnya &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- STANDARD / MOBILE DOCUMENT VIEWER -->
                    <div v-else class="space-y-4">
                        <div class="p-6 rounded-2xl bg-zinc-50 border border-zinc-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm border border-red-200 flex-shrink-0">
                                    PDF
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-zinc-950">{{ page.content?.document_title || 'Dokumen Portofolio Resmi.pdf' }}</h3>
                                    <div class="text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                                        <span>{{ page.content?.file_size || '2.4 MB' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ page.content?.pages_count || 8 }} Halaman</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    v-if="page.content?.pdf_url"
                                    :href="page.content.pdf_url"
                                    target="_blank"
                                    class="px-4 py-2 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold shadow-2xs transition-all"
                                >
                                    Buka di Tab Baru
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Document Download Notice -->
                    <div v-if="page.content?.allow_download" class="flex items-center justify-between pt-2 border-t border-zinc-100 text-xs text-zinc-500">
                        <span>Dokumen diizinkan untuk diunduh oleh pemilik karya.</span>
                        <a
                            v-if="page.content?.pdf_url"
                            :href="page.content.pdf_url"
                            download
                            class="text-blue-600 font-bold hover:underline flex items-center gap-1"
                        >
                            <span>Unduh Berkas PDF</span>
                            <span>&darr;</span>
                        </a>
                    </div>

                    <!-- PDF Book Additional Free Blocks -->
                    <div v-if="page.content?.blocks && page.content.blocks.length" class="pt-4">
                        <PortfolioBlockRenderer :blocks="page.content.blocks" :themeStyle="themeStyle" />
                    </div>
                </section>

                <!-- 5. CONTACT & COLLABORATION SECTION -->
                <section
                    v-else-if="page.page_type === 'contact'"
                    id="contact"
                    class="space-y-6 pt-2"
                >
                    <div class="space-y-1">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 inline-block">
                            Hubungi Saya
                        </span>
                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-zinc-950">
                            {{ page.content?.heading || 'Mari Berkolaborasi' }}
                        </h2>
                        <p class="text-sm sm:text-base text-zinc-500 max-w-2xl leading-relaxed">
                            {{ page.content?.subheading || 'Terbuka untuk tawaran proyek kreatif dan kolaborasi baru.' }}
                        </p>
                    </div>

                    <!-- Contact Buttons -->
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <a
                            v-if="page.content?.whatsapp"
                            :href="`https://wa.me/${page.content.whatsapp.replace(/[^0-9]/g, '')}`"
                            target="_blank"
                            class="px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 min-h-11"
                        >
                            <LunouIcon name="whatsapp" className="w-4 h-4" />
                            <span>Kirim Pesan WhatsApp</span>
                        </a>

                        <a
                            v-if="page.content?.email"
                            :href="`mailto:${page.content.email}`"
                            class="px-6 py-3 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 min-h-11"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>Kirim Email</span>
                        </a>
                    </div>

                    <!-- Social Handles -->
                    <div v-if="page.content?.socials && page.content.socials.length" class="pt-4 border-t border-zinc-100 flex flex-wrap items-center gap-4 text-xs font-semibold">
                        <a
                            v-for="(soc, i) in page.content.socials"
                            :key="i"
                            :href="soc.url"
                            target="_blank"
                            class="hover:underline flex items-center gap-1 text-blue-600 font-bold"
                        >
                            <span>{{ soc.platform }}:</span>
                            <span>{{ soc.handle }}</span>
                        </a>
                    </div>

                    <!-- Contact Additional Free Blocks -->
                    <div v-if="page.content?.blocks && page.content.blocks.length" class="pt-4">
                        <PortfolioBlockRenderer :blocks="page.content.blocks" :themeStyle="themeStyle" />
                    </div>
                </section>

                <!-- 6. CUSTOM / CANVA FREE CANVAS PAGE (PRESTASI, GALERI, SERTIFIKASI, DLL.) -->
                <section
                    v-else
                    :id="`page-${page.id}`"
                    class="space-y-6 pt-2"
                >
                    <div v-if="(page.content?.heading && page.content.heading !== 'Halaman Baru') || (page.content?.description && !page.content.description.startsWith('Tambahkan elemen'))" class="space-y-1">
                        <h2 v-if="page.content?.heading && page.content.heading !== 'Halaman Baru'" class="text-2xl sm:text-4xl font-black tracking-tight text-zinc-950">
                            {{ page.content.heading }}
                        </h2>
                        <p v-if="page.content?.description && !page.content.description.startsWith('Tambahkan elemen')" class="text-sm sm:text-base text-zinc-500 max-w-2xl leading-relaxed">
                            {{ page.content.description }}
                        </p>
                    </div>

                    <!-- Render Blocks with Super Responsive System -->
                    <PortfolioBlockRenderer :blocks="page.content?.blocks || []" :themeStyle="themeStyle" />
                </section>

                <!-- Clean Subtle Section Divider (Kecuali Halaman Terakhir) -->
                <div v-if="pageIdx < sortedPages.length - 1" class="border-t border-zinc-200/60 my-8 sm:my-14"></div>
            </template>
        </main>

        <!-- Footer -->
        <footer :class="['border-t py-8 px-4 sm:px-8 mt-12 text-center text-xs space-y-2', themeStyle.header]">
            <div class="flex items-center justify-center gap-2">
                <ApplicationLogo className="w-5 h-5" variant="1" />
                <span class="font-extrabold tracking-tight">me.myoin.id</span>
                <span class="text-[10px] text-zinc-400 font-medium">part of lunou</span>
            </div>
            <p :class="themeStyle.textMuted">
                Portofolio resmi {{ user.name }}. Diterbitkan dengan me.myoin.id Web Portfolio Builder.
            </p>
            <div class="flex items-center justify-center gap-4 pt-1 text-[11px]">
                <Link href="/terms" class="hover:underline" :class="themeStyle.textMuted">Syarat & Ketentuan</Link>
                <span>&bull;</span>
                <Link href="/" class="hover:underline" :class="themeStyle.textMuted">Buat Portofolio Anda</Link>
            </div>
        </footer>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL LENGKAP PROYEK & STUDI KASUS (SEMUA SELESAI DI SINI) -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="isProjectDetailOpen && activeProjectDetail"
                    class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm p-4 sm:p-6 md:p-10 flex items-center justify-center"
                    @click.self="closeProjectDetail"
                >
                    <div
                        class="w-full max-w-3xl rounded-3xl overflow-hidden shadow-2xl relative my-auto transition-all max-h-[90vh] flex flex-col bg-white border border-zinc-200 text-zinc-900"
                    >
                        <!-- Modal Sticky Header -->
                        <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between gap-3 bg-white/95 backdrop-blur-md sticky top-0 z-20">
                            <div class="flex items-center gap-2 min-w-0">
                                <span v-if="activeProjectDetail.category" class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] uppercase tracking-wider border border-blue-200">
                                    {{ activeProjectDetail.category }}
                                </span>
                                <span v-if="activeProjectDetail.year" class="text-xs font-mono text-zinc-400 font-medium">
                                    {{ activeProjectDetail.year }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="closeProjectDetail"
                                class="w-8 h-8 rounded-full bg-zinc-100 hover:bg-zinc-200 text-zinc-600 hover:text-zinc-900 flex items-center justify-center transition-all text-sm font-bold"
                                title="Tutup"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Modal Scrollable Body -->
                        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
                            <!-- Hero Image -->
                            <div class="rounded-2xl overflow-hidden bg-zinc-100 max-h-96 relative border border-zinc-200 shadow-xs">
                                <img
                                    :src="activeProjectDetail.image || 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80'"
                                    :alt="activeProjectDetail.title"
                                    class="w-full h-full object-cover max-h-96"
                                />
                            </div>

                            <!-- Title & Summary -->
                            <div class="space-y-2">
                                <h2 class="text-2xl sm:text-3xl font-black text-zinc-950 tracking-tight leading-snug">
                                    {{ activeProjectDetail.title }}
                                </h2>
                                <p class="text-sm sm:text-base text-zinc-600 leading-relaxed">
                                    {{ activeProjectDetail.description }}
                                </p>
                            </div>

                            <!-- Metadata Bar (Grid) -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-zinc-50 border border-zinc-200/90 text-xs">
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Peran</span>
                                    <div class="font-bold text-zinc-900">{{ activeProjectDetail.role || 'Kreator & Desainer' }}</div>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Klien / Entitas</span>
                                    <div class="font-bold text-zinc-900">{{ activeProjectDetail.client || 'Proyek Mandiri' }}</div>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Waktu Pengerjaan</span>
                                    <div class="font-bold text-zinc-900">{{ activeProjectDetail.year || '2026' }}</div>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Kategori</span>
                                    <div class="font-bold text-blue-600">{{ activeProjectDetail.category || 'Portfolio' }}</div>
                                </div>
                            </div>

                            <!-- Tools & Technologies Pills -->
                            <div v-if="activeProjectDetail.tools && activeProjectDetail.tools.length" class="space-y-2">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Teknologi & Alat yang Digunakan</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(tool, t) in activeProjectDetail.tools"
                                        :key="t"
                                        class="px-3 py-1 rounded-xl bg-zinc-100 text-zinc-800 text-xs font-bold border border-zinc-200"
                                    >
                                        {{ tool }}
                                    </span>
                                </div>
                            </div>

                            <!-- In-Depth Case Study / Story Sections -->
                            <div class="space-y-5 pt-2 border-t border-zinc-100">
                                <!-- Narasi Utama / Studi Kasus -->
                                <div v-if="activeProjectDetail.case_study" class="space-y-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Kisah & Latar Belakang Karya</h4>
                                    <div class="text-sm text-zinc-700 leading-relaxed whitespace-pre-line space-y-3 font-normal">
                                        {{ activeProjectDetail.case_study }}
                                    </div>
                                </div>

                                <!-- Challenge & Problem -->
                                <div v-if="activeProjectDetail.challenge" class="p-4 sm:p-5 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-1.5">
                                    <div class="flex items-center gap-2 text-amber-800 font-bold text-xs uppercase tracking-wider">
                                        <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>Tantangan & Masalah Utama</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-amber-950 leading-relaxed">
                                        {{ activeProjectDetail.challenge }}
                                    </p>
                                </div>

                                <!-- Solution & Approach -->
                                <div v-if="activeProjectDetail.solution" class="p-4 sm:p-5 rounded-2xl bg-blue-50/70 border border-blue-200 space-y-1.5">
                                    <div class="flex items-center gap-2 text-blue-800 font-bold text-xs uppercase tracking-wider">
                                        <LunouIcon name="sparkles" className="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                                        <span>Solusi & Pendekatan Teknis</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-blue-950 leading-relaxed">
                                        {{ activeProjectDetail.solution }}
                                    </p>
                                </div>

                                <!-- Result & Impact -->
                                <div v-if="activeProjectDetail.result" class="p-4 sm:p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-1.5">
                                    <div class="flex items-center gap-2 text-emerald-800 font-bold text-xs uppercase tracking-wider">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Hasil & Dampak Nyata</span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-emerald-950 leading-relaxed">
                                        {{ activeProjectDetail.result }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer Action Bar -->
                        <div class="px-6 py-4 bg-zinc-50 border-t border-zinc-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span class="text-[11px] text-zinc-400">
                                Portofolio terverifikasi di me.myoin.id · part of lunou
                            </span>

                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                <!-- TOMBOL KE PIHAK KE 3 ITU OPSIONAL -->
                                <a
                                    v-if="activeProjectDetail.link && activeProjectDetail.link !== '#' && activeProjectDetail.link.trim() !== ''"
                                    :href="activeProjectDetail.link"
                                    target="_blank"
                                    class="px-4 py-2.5 rounded-xl border border-zinc-300 hover:bg-white text-zinc-700 text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-2xs"
                                    title="Tautan opsional ke platform luar"
                                >
                                    <span>{{ activeProjectDetail.link_label || 'Buka di Pihak Ketiga' }}</span>
                                    <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>

                                <button
                                    type="button"
                                    @click="closeProjectDetail"
                                    class="px-5 py-2.5 rounded-xl bg-zinc-950 hover:bg-black text-white text-xs font-bold transition-all shadow-2xs"
                                >
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
