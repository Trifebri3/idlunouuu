<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    isNfcTap: {
        type: Boolean,
        default: false,
    },
    nfcCardId: {
        type: String,
        default: null,
    },
    portfolio: {
        type: Object,
        default: null,
    },
});

const showShareModal = ref(false);
const showNfcBanner = ref(false);
const activeNfcCard = ref('');
const copied = ref(false);
const currentUrl = typeof window !== 'undefined' ? window.location.href : `https://lunou.id/${props.profile.username}`;
const showPortfolioScrollPopup = ref(false);
const dismissedPopup = ref(false);

onMounted(() => {
    // Scroll listener for portfolio teaser popup
    if (props.portfolio && typeof window !== 'undefined') {
        const handleScroll = () => {
            if (dismissedPopup.value) return;
            if (window.scrollY > 150) {
                showPortfolioScrollPopup.value = true;
            } else {
                showPortfolioScrollPopup.value = false;
            }
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const isFromNfc = props.isNfcTap || (urlParams && urlParams.get('from') === 'nfc');
    const cardId = props.nfcCardId || (urlParams ? urlParams.get('card') : '');

    if (isFromNfc) {
        activeNfcCard.value = cardId || '';
        showNfcBanner.value = true;

        try {
            if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
                navigator.vibrate([40, 50, 40]);
            }
        } catch (_) {}

        setTimeout(() => {
            showNfcBanner.value = false;
        }, 6000);
    }
});

function triggerNfcDemo() {
    activeNfcCard.value = props.nfcCardId || 'KEYCHAIN-LUNOU-NFC';
    showNfcBanner.value = true;
    try {
        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            navigator.vibrate([40, 50, 40]);
        }
    } catch (_) {}
    setTimeout(() => {
        showNfcBanner.value = false;
    }, 6000);
}

function copyLink() {
    navigator.clipboard.writeText(currentUrl);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
}

function handleComponentClick(comp) {
    if (!comp.url) return;

    // Send click tracking asynchronously without blocking navigation
    try {
        fetch(`/${props.profile.username}/click/${comp.id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        }).catch(() => {});
    } catch {}

    window.open(comp.url, '_blank', 'noopener,noreferrer');
}

// Theme token styling (White / Light prioritized)
const themeTokens = computed(() => {
    switch (props.profile.theme) {
        case 'sunset':
            return {
                wrapper: 'bg-gradient-to-br from-orange-950 via-rose-950 to-neutral-950 text-white selection:bg-rose-500 selection:text-white',
                card: 'bg-rose-950/40 border border-rose-800/40 text-white hover:border-rose-400/80 hover:bg-rose-900/50 shadow-md',
                featured: 'bg-gradient-to-r from-amber-500 via-rose-500 to-pink-500 text-white font-bold shadow-md hover:scale-[1.01]',
                badge: 'bg-rose-900/50 text-rose-300 border border-rose-700/50',
                textSub: 'text-rose-200/80',
            };
        case 'cyber':
            return {
                wrapper: 'bg-gradient-to-br from-emerald-950 via-neutral-950 to-black text-white selection:bg-emerald-400 selection:text-black',
                card: 'bg-emerald-950/30 border border-emerald-800/40 text-white hover:border-emerald-400/80 hover:bg-emerald-900/40 shadow-md',
                featured: 'bg-emerald-400 text-black font-extrabold hover:bg-emerald-300 shadow-md hover:scale-[1.01]',
                badge: 'bg-emerald-900/50 text-emerald-300 border border-emerald-700/50',
                textSub: 'text-emerald-200/80',
            };
        case 'midnight':
            return {
                wrapper: 'bg-gradient-to-br from-slate-950 via-zinc-900 to-black text-white selection:bg-cyan-500 selection:text-black',
                card: 'bg-zinc-900/80 border border-zinc-800 text-white hover:border-cyan-500/70 hover:bg-zinc-800/90 shadow-md',
                featured: 'bg-cyan-500 text-black font-extrabold hover:bg-cyan-400 shadow-md hover:scale-[1.01]',
                badge: 'bg-cyan-950/60 text-cyan-300 border border-cyan-800/50',
                textSub: 'text-zinc-400',
            };
        case 'clean-light':
        default:
            return {
                wrapper: 'bg-[#fafbfc] text-zinc-900 selection:bg-blue-600 selection:text-white',
                card: 'bg-white border border-zinc-200 text-zinc-800 hover:border-zinc-300 hover:bg-zinc-50/80 shadow-xs',
                featured: 'bg-zinc-950 text-white font-bold hover:bg-black shadow-sm hover:scale-[1.01]',
                badge: 'bg-zinc-100 text-zinc-700 border border-zinc-200',
                textSub: 'text-zinc-500',
            };
    }
});
</script>

<template>
    <div
        class="min-h-screen py-10 px-4 flex flex-col items-center justify-between font-sans transition-colors duration-500 relative overflow-hidden"
        :class="themeTokens.wrapper"
    >
        <Head :title="`${profile.display_name} (@${profile.username}) · LUNOU`">
            <meta name="description" :content="profile.bio || `Temukan tautan dan profil ${profile.display_name} di LUNOU.`" />
        </Head>

        <!-- ON-SCREEN NFC POPUP BANNER (Slide-in Dynamic Pill on Mobile) -->
        <transition
            enter-active-class="transition duration-400 ease-out"
            enter-from-class="-translate-y-full opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="-translate-y-full opacity-0 scale-95"
        >
            <div
                v-if="showNfcBanner"
                class="fixed top-4 inset-x-4 max-w-sm mx-auto z-50 bg-white/95 backdrop-blur-md border border-zinc-200/90 rounded-2xl shadow-xl shadow-zinc-900/10 p-3.5 flex items-center justify-between gap-3 text-zinc-900"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="relative flex-shrink-0">
                        <div class="w-11 h-11 rounded-xl bg-zinc-50 border border-zinc-200 flex items-center justify-center overflow-hidden p-1 shadow-xs">
                            <ApplicationLogo className="w-full h-full object-contain" variant="1" />
                        </div>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-600"></span>
                        </span>
                    </div>

                    <div class="min-w-0 text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-mono font-bold tracking-wider uppercase text-blue-600">GANTUNGAN KUNCI LUNOU</span>
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded-full bg-blue-50 text-[9px] font-semibold text-blue-700 border border-blue-200/60">
                                Terhubung
                            </span>
                        </div>
                        <h4 class="text-xs font-black text-zinc-900 truncate mt-0.5 leading-snug">
                            {{ profile.display_name }}
                        </h4>
                        <p class="text-[10px] text-zinc-500 font-mono truncate">
                            @{{ profile.username }} {{ activeNfcCard ? '· #' + activeNfcCard : '· Sentuhan NFC' }}
                        </p>
                    </div>
                </div>

                <button
                    @click="showNfcBanner = false"
                    class="w-7 h-7 rounded-lg hover:bg-zinc-100 flex items-center justify-center text-zinc-400 hover:text-zinc-700 flex-shrink-0 transition-colors"
                    title="Tutup Notifikasi"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </transition>

        <!-- Background Distinctive Brand Ornament -->
        <ApplicationLogo
            className="fixed -bottom-20 -right-20 w-80 h-80 opacity-[0.03] pointer-events-none select-none"
            variant="1"
        />

        <!-- Top Utilities (Owner Edit Quick Link + Share Button) -->
        <div class="w-full max-w-md flex items-center justify-between mb-4 px-2 relative z-10">
            <div>
                <Link
                    v-if="profile.is_owner"
                    href="/studio"
                    class="px-3 py-1.5 rounded-full bg-white/90 border border-zinc-200 text-xs font-semibold text-blue-600 hover:bg-white flex items-center gap-1.5 shadow-xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Edit Profil</span>
                </Link>
            </div>

            <button
                @click="showShareModal = true"
                class="w-9 h-9 rounded-full bg-white/80 hover:bg-white border border-zinc-200 flex items-center justify-center transition-all shadow-xs"
                title="Bagikan Profil"
            >
                <LunouIcon name="share" className="w-4 h-4 text-zinc-700" />
            </button>
        </div>

        <!-- Main Card Container -->
        <main class="w-full max-w-md flex flex-col items-center text-center my-auto relative z-10">
            <!-- Avatar with Subtle Frame -->
            <div class="relative group mb-4">
                <img
                    :src="profile.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80'"
                    :alt="profile.display_name"
                    class="relative w-24 h-24 rounded-full object-cover border-4 border-white shadow-md"
                />
            </div>

            <!-- Profile Info -->
            <h1 class="text-2xl font-black tracking-tight leading-snug">{{ profile.display_name }}</h1>
            <p class="text-xs font-mono font-medium opacity-75 mt-0.5">@{{ profile.username }}</p>

            <p v-if="profile.bio" class="text-sm mt-3 leading-relaxed max-w-sm px-4 opacity-90" :class="themeTokens.textSub">
                {{ profile.bio }}
            </p>

            <!-- Components Container -->
            <div class="w-full mt-7 space-y-3 px-1">
                <template v-for="comp in profile.components" :key="comp.id">
                    <!-- Heading -->
                    <div v-if="comp.type === 'heading'" class="pt-3 pb-1 text-xs font-bold uppercase tracking-wider opacity-60 text-left px-2">
                        {{ comp.title }}
                    </div>

                    <!-- Text Note -->
                    <div
                        v-else-if="comp.type === 'text'"
                        class="p-4 rounded-2xl text-xs leading-relaxed text-left border"
                        :class="themeTokens.card"
                    >
                        <strong class="block font-semibold mb-1">{{ comp.title }}</strong>
                        <p class="opacity-80">{{ comp.content }}</p>
                    </div>

                    <!-- Interactive Links & Cards -->
                    <div
                        v-else
                        @click="handleComponentClick(comp)"
                        class="w-full p-4 rounded-2xl flex items-center justify-between cursor-pointer transition-all duration-200 group"
                        :class="comp.type === 'featured_link' ? themeTokens.featured : themeTokens.card"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-zinc-100 flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform text-zinc-800">
                                <LunouIcon :name="comp.settings?.icon || 'globe'" className="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="text-left min-w-0">
                                <h2 class="text-sm font-bold truncate leading-snug">{{ comp.title }}</h2>
                                <p v-if="comp.content" class="text-[11px] opacity-75 truncate mt-0.5">{{ comp.content }}</p>
                            </div>
                        </div>

                        <svg class="w-4 h-4 opacity-40 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </template>

                <!-- PORTFOLIO TEASER CARD AT THE BOTTOM (Accessible via Scroll) -->
                <div v-if="portfolio" class="mt-8 pt-6 border-t border-zinc-200/60 w-full">
                    <a
                        :href="portfolio.url"
                        class="block p-5 rounded-3xl bg-gradient-to-br from-white via-blue-50/40 to-indigo-50/20 border border-blue-200/80 shadow-xs hover:shadow-md transition-all group text-left relative overflow-hidden"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                                <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest">Web Portofolio & Karya Resmi</span>
                            </div>
                            <span class="text-xs text-blue-600 font-bold group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>

                        <div class="mt-2.5 space-y-1">
                            <h3 class="text-base font-extrabold text-zinc-950 group-hover:text-blue-600 transition-colors">
                                {{ portfolio.title }}
                            </h3>
                            <p class="text-xs text-zinc-600 line-clamp-2">
                                Buka website portofolio interaktif, studi kasus karya, galeri foto, dan buku digital flipbook resmi.
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-blue-100/80 flex items-center justify-between text-[11px] text-zinc-500">
                            <span class="font-mono text-blue-600 font-semibold">/p/{{ portfolio.slug }}</span>
                            <span class="flex items-center gap-1 font-semibold text-zinc-800">
                                <span>Buka Portofolio</span>
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer Brand (Nuansa Putih Bersih) -->
        <footer class="mt-12 text-center flex flex-col items-center gap-2 relative z-10">
            <Link href="/" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/90 hover:bg-white border border-zinc-200 text-xs font-medium transition-all shadow-xs group">
                <ApplicationLogo className="w-4 h-4" variant="1" />
                <span class="text-zinc-700 font-semibold group-hover:text-zinc-950">me.myoin.id · part of lunou &rarr;</span>
            </Link>
            <span class="text-[10px] tracking-widest uppercase text-zinc-400 font-mono">YOIMO NFC POWERED IDENTITY</span>
        </footer>

        <!-- MODAL: Share & QR Code (Clean White) -->
        <div v-if="showShareModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border border-zinc-200 rounded-3xl max-w-sm w-full p-6 text-center space-y-5 shadow-2xl text-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-5 h-5" variant="1" />
                        <h3 class="text-base font-bold">Bagikan Profil</h3>
                    </div>
                    <button @click="showShareModal = false" class="text-zinc-400 hover:text-zinc-700 text-xl font-bold">&times;</button>
                </div>

                <!-- High Quality SVG QR Code -->
                <div class="p-4 bg-zinc-50 border border-zinc-200 rounded-2xl inline-block shadow-xs">
                    <svg class="w-44 h-44 text-zinc-900" viewBox="0 0 100 100" fill="currentColor">
                        <!-- QR Pattern -->
                        <rect x="0" y="0" width="30" height="30" rx="4" />
                        <rect x="6" y="6" width="18" height="18" fill="white" rx="2" />
                        <rect x="10" y="10" width="10" height="10" rx="1" />

                        <rect x="70" y="0" width="30" height="30" rx="4" />
                        <rect x="76" y="6" width="18" height="18" fill="white" rx="2" />
                        <rect x="80" y="10" width="10" height="10" rx="1" />

                        <rect x="0" y="70" width="30" height="30" rx="4" />
                        <rect x="6" y="76" width="18" height="18" fill="white" rx="2" />
                        <rect x="10" y="80" width="10" height="10" rx="1" />

                        <!-- Center Dots -->
                        <rect x="40" y="10" width="6" height="6" />
                        <rect x="52" y="10" width="6" height="6" />
                        <rect x="40" y="24" width="6" height="6" />
                        <rect x="52" y="24" width="6" height="6" />
                        <rect x="10" y="44" width="6" height="6" />
                        <rect x="24" y="44" width="6" height="6" />
                        <rect x="44" y="44" width="12" height="12" rx="2" />
                        <rect x="70" y="44" width="6" height="6" />
                        <rect x="84" y="44" width="6" height="6" />
                        <rect x="40" y="70" width="6" height="6" />
                        <rect x="52" y="70" width="6" height="6" />
                        <rect x="70" y="70" width="6" height="6" />
                        <rect x="84" y="84" width="6" height="6" />
                    </svg>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-zinc-500">Scan QR Code dengan kamera ponsel atau salin tautan berikut:</p>
                    <div class="p-2.5 rounded-xl bg-zinc-50 border border-zinc-200 text-xs font-mono text-zinc-800 truncate font-semibold">
                        {{ currentUrl }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 pt-1">
                    <button
                        @click="copyLink"
                        class="w-full py-3 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all"
                    >
                        <LunouIcon :name="copied ? 'check' : 'share'" className="w-4 h-4 text-cyan-400" />
                        <span>{{ copied ? 'Tautan Berhasil Disalin' : 'Salin Tautan Profil' }}</span>
                    </button>

                    <button
                        @click="triggerNfcDemo"
                        class="w-full py-2.5 rounded-xl bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 text-zinc-700 font-semibold text-xs flex items-center justify-center gap-2 transition-all"
                    >
                        <LunouIcon name="card" className="w-4 h-4 text-blue-600" />
                        <span>Uji Coba Notifikasi Tap NFC</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FLOATING SCROLL POPUP: Buka Web Portofolio (Aktif saat di-scroll) -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-8 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform translate-y-8 opacity-0 scale-95"
        >
            <div
                v-if="portfolio && showPortfolioScrollPopup && !dismissedPopup"
                class="fixed bottom-6 right-4 sm:right-6 z-50 max-w-xs w-full bg-zinc-950/95 text-white backdrop-blur-md border border-zinc-800 p-3.5 rounded-2xl shadow-2xl flex items-center justify-between gap-3"
            >
                <a :href="portfolio.url" class="flex items-center gap-2.5 min-w-0 flex-1 group text-left">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white flex-shrink-0 shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Web Portofolio</div>
                        <div class="text-xs font-bold text-white truncate group-hover:text-blue-300 transition-colors">
                            {{ portfolio.title }}
                        </div>
                    </div>
                </a>

                <div class="flex items-center gap-1 flex-shrink-0">
                    <a
                        :href="portfolio.url"
                        class="px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] transition-colors"
                    >
                        Buka &rarr;
                    </a>
                    <button
                        @click="dismissedPopup = true"
                        class="p-1 rounded-lg text-zinc-400 hover:text-white transition-colors text-xs font-bold"
                        title="Tutup pop-up"
                    >
                        &times;
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
