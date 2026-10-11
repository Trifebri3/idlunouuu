<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    termsContent: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="min-h-screen bg-[#fafbfc] text-zinc-900 flex flex-col font-sans selection:bg-blue-600 selection:text-white antialiased">
        <Head title="Syarat & Ketentuan Platform · me.myoin.id (part of lunou)" />

        <!-- Header -->
        <header class="h-16 border-b border-zinc-200/80 bg-white/90 backdrop-blur-md sticky top-0 z-40 px-4 sm:px-8 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-2.5 group">
                <ApplicationLogo className="w-8 h-8 flex-shrink-0" variant="1" />
                <div class="flex flex-col text-left leading-none">
                    <span class="font-extrabold text-zinc-950 text-base tracking-tight">me.myoin.id</span>
                    <span class="text-[9px] text-zinc-400 font-medium lowercase">part of lunou</span>
                </div>
            </Link>

            <div class="flex items-center gap-3 text-xs">
                <Link href="/" class="text-zinc-600 hover:text-zinc-950 font-medium">
                    &larr; Beranda
                </Link>
                <Link
                    href="/studio"
                    class="px-3.5 py-1.5 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold transition-all shadow-2xs"
                >
                    Studio
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-8 space-y-6">
            <div class="bg-white border border-zinc-200/90 rounded-3xl p-6 sm:p-10 shadow-2xs space-y-6">
                <div class="border-b border-zinc-100 pb-5 space-y-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold uppercase tracking-wider">
                        Platform Policy & Governance
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-zinc-950 tracking-tight">
                        Syarat, Ketentuan & Kebijakan Platform me.myoin.id
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-500">
                        Terakhir diperbarui: 11 Oktober 2026 · Berlaku untuk seluruh pengguna me.myoin.id (part of lunou) & Web Portfolio Builder.
                    </p>
                </div>

                <!-- Structured Terms Content -->
                <div class="prose prose-zinc prose-sm sm:prose-base max-w-none space-y-5 text-xs sm:text-sm text-zinc-700 leading-relaxed">
                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 text-blue-950 space-y-1">
                        <strong class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            Prinsip Ramah Pengguna & Keamanan Data
                        </strong>
                        <p class="text-xs text-blue-900 leading-relaxed">
                            LUNOU menjamin kepemilikan penuh atas karyamu, tidak menyisipkan iklan pada profilmu, dan melindungi informasimu dengan enkripsi berstandar tinggi.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">1. Ketentuan Umum Layanan Portfolio LUNOU</h2>
                        <p>
                            Platform LUNOU Portfolio Builder disediakan untuk memudahkan pengguna menyusun, mendokumentasikan, dan mempublikasikan karya personal, dokumen portofolio, dan materi profesional secara digital tanpa memerlukan pemahaman pemrograman maupun konfigurasi server yang rumit.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">2. Hak Cipta & Kepemilikan Konten Kreator</h2>
                        <p>
                            Pengguna memegang penuh 100% hak cipta atas seluruh karya, foto, teks, desain, dan berkas PDF yang diunggah ke LUNOU Portfolio. Pengguna bertanggung jawab penuh memastikan materi yang ditampilkan tidak melanggar hak kekayaan intelektual (HAKI) pihak lain atau memuat konten terlarang.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">3. Kebijakan Pembayaran & Aktivasi Akses Premium</h2>
                        <p>
                            Akses fitur premium LUNOU Portfolio Creator Pro diaktifkan setelah bukti transfer manual diverifikasi dan disetujui oleh admin LUNOU. Verifikasi pembayaran dilakukan secara teliti untuk mencegah penyalahgunaan. Setelah disetujui, hak akses aktif secara otomatis sesuai durasi paket (misalnya 30 hari) dan dapat diperpanjang kembali.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">4. Batasan Berkas & Dokumen Digital</h2>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Setiap pengguna aktif berhak mengunggah hingga 10 berkas PDF resmi.</li>
                            <li>Batas ukuran maksimal per berkas PDF adalah 5 MB.</li>
                            <li>Format gambar yang didukung adalah JPG, PNG, dan WebP.</li>
                            <li>Pengguna dapat mengaktifkan atau menonaktifkan izin unduh berkas bagi pengunjung publik.</li>
                        </ul>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">5. Kriptografi & Keamanan Data Pengguna</h2>
                        <p>
                            Seluruh data sensitif pembayaran, nomor rekening bank, serta catatan identitas pelanggan dienkripsi secara ketat di basis data menggunakan algoritma kriptografi AES-256-CBC dengan verifikasi HMAC. Kami tidak membagikan data Anda kepada pihak ketiga.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-base sm:text-lg font-bold text-zinc-950">6. Kontak & Layanan Bantuan Resmi</h2>
                        <p>
                            Untuk pertanyaan, bantuan teknis, kendala aktivasi, konfirmasi pembayaran, maupun kerjasama, silakan hubungi tim kami secara langsung:
                        </p>
                        <div class="p-4 rounded-2xl bg-zinc-50 border border-zinc-200/80 flex flex-wrap items-center gap-4 text-xs font-semibold">
                            <a
                                href="https://wa.me/6285862319524?text=Halo%20Admin%20LUNOU,%20saya%20butuh%20bantuan"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 text-emerald-700 hover:text-emerald-800"
                            >
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.203c.044.072.044.419-.1.824z"/></svg>
                                <span>WhatsApp: +62 858-6231-9524</span>
                            </a>
                            <span class="text-zinc-300">|</span>
                            <a
                                href="mailto:hello@yotainovasi.id"
                                class="inline-flex items-center gap-2 text-zinc-800 hover:text-zinc-950"
                            >
                                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Email: hello@yotainovasi.id</span>
                            </a>
                        </div>
                    </div>

                    <!-- Custom Admin Terms if configured -->
                    <div v-if="termsContent" class="pt-4 border-t border-zinc-100 whitespace-pre-line font-normal">
                        {{ termsContent }}
                    </div>
                </div>

                <div class="pt-6 border-t border-zinc-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-500">
                    <div>&copy; {{ new Date().getFullYear() }} me.myoin.id · part of lunou. Hak Cipta Dilindungi.</div>
                    <Link href="/studio" class="text-blue-600 font-bold hover:underline">
                        Mulai Buat Portofolio Anda &rarr;
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
