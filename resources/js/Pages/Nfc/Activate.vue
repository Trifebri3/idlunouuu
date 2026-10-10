<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LunouIcon from '@/Components/LunouIcon.vue';

const props = defineProps({
    publicId: {
        type: String,
        required: true,
    },
    device: {
        type: Object,
        required: true,
    },
});

function claimNow() {
    router.post(`/nfc/${props.publicId}/activate`);
}
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col items-center justify-between p-6 font-sans selection:bg-blue-600 selection:text-white relative overflow-hidden">
        <Head :title="`Aktivasi Kartu YOIMO NFC · ${publicId}`" />

        <!-- Background Distinctive Brand Ornament -->
        <ApplicationLogo
            className="fixed -top-16 -right-16 w-80 h-80 opacity-[0.03] pointer-events-none select-none"
            variant="2"
        />

        <!-- Header -->
        <header class="w-full max-w-md flex items-center justify-between relative z-10">
            <Link href="/" class="flex items-center gap-2.5">
                <ApplicationLogo className="w-8 h-8" variant="1" />
                <span class="font-extrabold text-zinc-950 text-base tracking-tight">LUNOU</span>
            </Link>

            <span class="px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-[10px] font-bold uppercase tracking-wider">
                YOIMO Hardware
            </span>
        </header>

        <!-- Main Card Setup Area (Clean White) -->
        <main class="w-full max-w-md my-auto flex flex-col items-center text-center space-y-6 relative z-10">
            <!-- Physical Card Simulation in Pure White Titanium Edition -->
            <div class="w-80 h-48 rounded-2xl bg-white border border-zinc-200/90 p-5 shadow-xl relative transform -rotate-1 hover:rotate-0 transition-all duration-300 group cursor-pointer">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo className="w-6 h-6" variant="1" />
                        <span class="font-extrabold text-zinc-900 tracking-widest text-sm">YOIMO</span>
                    </div>
                    <LunouIcon name="nfc" className="w-5 h-5 text-blue-600" />
                </div>
                <div class="mt-8 text-left">
                    <div class="w-10 h-7 rounded bg-gradient-to-tr from-amber-200 via-amber-300 to-yellow-400 border border-amber-300 shadow-xs mb-2"></div>
                    <span class="text-[10px] font-mono text-zinc-400 uppercase tracking-widest">ID KARTU RFID/NFC</span>
                    <div class="font-mono font-black text-zinc-900 text-sm tracking-wider">{{ publicId }}</div>
                </div>
                <div class="absolute bottom-4 right-5 text-[10px] font-mono text-blue-600 font-bold">
                    LUNOU.ID
                </div>
            </div>

            <!-- Content Details -->
            <div class="space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                    <span>Kartu Fisik Terdeteksi dan Belum Terhubung</span>
                </div>
                <h1 class="text-2xl font-black text-zinc-950 tracking-tight">Aktivasi Kartu Pintar Anda</h1>
                <p class="text-xs text-zinc-500 max-w-xs mx-auto leading-relaxed font-normal">
                    Hubungkan kartu YOIMO ini ke profil LUNOU Anda. Setelah terhubung, siapapun yang men-tap kartu ini akan langsung diarahkan ke profil digital Anda.
                </p>
            </div>

            <!-- Action Section -->
            <div class="w-full space-y-3 pt-2">
                <!-- If user is logged in -->
                <template v-if="$page.props.auth?.user">
                    <div class="p-3.5 rounded-2xl bg-white border border-zinc-200/90 text-left flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-3">
                            <img
                                :src="$page.props.auth.user.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                                class="w-10 h-10 rounded-full object-cover border border-zinc-200"
                                alt="User"
                            />
                            <div>
                                <div class="font-bold text-zinc-900 text-xs">{{ $page.props.auth.user.name }}</div>
                                <div class="text-[11px] font-mono text-blue-600">@{{ $page.props.auth.user.profile?.username || 'user' }}</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-emerald-700 font-bold uppercase bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            Aktif
                        </span>
                    </div>

                    <button
                        @click="claimNow"
                        class="w-full py-3.5 rounded-2xl bg-zinc-950 hover:bg-black text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md transition-all hover:scale-[1.01]"
                    >
                        <LunouIcon name="card" className="w-4 h-4 text-cyan-400" />
                        <span>Hubungkan Kartu ke Akun Saya Sekarang</span>
                    </button>
                </template>

                <!-- If user is not logged in -->
                <template v-else>
                    <a
                        href="/auth/google"
                        class="w-full py-3.5 rounded-2xl bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 font-bold text-xs flex items-center justify-center gap-2.5 shadow-xs transition-all"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z"/><path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 10.04 0 12s.45 3.82 1.25 5.42l4.03-3.15Z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z"/></svg>
                        <span>Masuk dengan Google untuk Aktivasi</span>
                    </a>

                    <a
                        href="/auth/dev-login"
                        class="w-full py-2.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-semibold text-xs flex items-center justify-center gap-2 transition-all"
                    >
                        Masuk Cepat Mode Demo
                    </a>
                </template>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center text-[11px] text-zinc-400 font-mono relative z-10">
            URL Tap Chip: lunou.id/nfc/{{ publicId }}
        </footer>
    </div>
</template>
