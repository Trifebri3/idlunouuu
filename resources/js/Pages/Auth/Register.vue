<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar · Buat Ruang Personal LUNOU" />

        <!-- Segmented Tab Switcher -->
        <div class="flex items-center p-1 bg-zinc-100 rounded-2xl">
            <Link
                href="/register"
                class="flex-1 py-2 text-center rounded-xl text-xs font-bold bg-white text-zinc-950 shadow-xs transition-all"
            >
                Daftar
            </Link>
            <Link
                href="/login"
                class="flex-1 py-2 text-center rounded-xl text-xs font-bold text-zinc-500 hover:text-zinc-900 transition-all"
            >
                Masuk
            </Link>
        </div>

        <!-- Heading Text -->
        <div class="space-y-1">
            <h2 class="text-xl font-black text-zinc-950 tracking-tight">Buat ruang milikmu.</h2>
            <p class="text-xs text-zinc-500">Satu profil untuk semua hal yang ingin kamu bagikan.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-3.5">
            <div>
                <label for="name" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Nama lengkap</label>
                <input
                    id="name"
                    type="text"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Contoh: Tri Wicaksono"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Kata sandi</label>
                <input
                    id="password"
                    type="password"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Konfirmasi Kata sandi</label>
                <input
                    id="password_confirmation"
                    type="password"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-sm hover:shadow transition-all disabled:opacity-50 mt-2"
            >
                {{ form.processing ? 'Menyiapkan ruangmu...' : 'Daftar dengan Email' }}
            </button>
        </form>

        <!-- Divider -->
        <div class="relative flex items-center justify-center my-1">
            <div class="border-t border-zinc-200 w-full"></div>
            <span class="bg-white px-3 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">ATAU</span>
        </div>

        <!-- Google OAuth 1-Click Button -->
        <a
            href="/auth/google"
            class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-zinc-50 border border-zinc-200 text-zinc-800 text-xs font-semibold flex items-center justify-center gap-2.5 shadow-2xs hover:border-zinc-300 transition-all"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z"/><path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 10.04 0 12s.45 3.82 1.25 5.42l4.03-3.15Z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z"/></svg>
            <span>Daftar dengan Google</span>
        </a>

        <!-- Fast Dev Login for Testing -->
        <a
            href="/auth/dev-login"
            class="w-full py-2 px-3 rounded-xl bg-zinc-50 hover:bg-zinc-100 border border-dashed border-zinc-200 text-zinc-600 text-[11px] font-medium text-center transition-all block"
        >
            Masuk Cepat Akun Demo (@tri)
        </a>

        <!-- Terms Footer -->
        <p class="text-[11px] text-zinc-400 text-center leading-relaxed">
            Dengan melanjutkan, kamu menyetujui Ketentuan Layanan dan Kebijakan Privasi LUNOU.
        </p>
    </GuestLayout>
</template>
