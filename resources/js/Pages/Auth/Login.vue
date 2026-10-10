<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk · Ruang Personal LUNOU" />

        <!-- Segmented Tab Switcher -->
        <div class="flex items-center p-1 bg-zinc-100 rounded-2xl">
            <Link
                href="/register"
                class="flex-1 py-2 text-center rounded-xl text-xs font-bold text-zinc-500 hover:text-zinc-900 transition-all"
            >
                Daftar
            </Link>
            <Link
                href="/login"
                class="flex-1 py-2 text-center rounded-xl text-xs font-bold bg-white text-zinc-950 shadow-xs transition-all"
            >
                Masuk
            </Link>
        </div>

        <div v-if="status" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
            {{ status }}
        </div>

        <!-- Google OAuth Config Alert -->
        <div v-if="$page.props.errors?.google" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-1 text-left">
            <div class="font-bold flex items-center gap-1.5 text-amber-950">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Pengaturan Google OAuth (.env)</span>
            </div>
            <p class="leading-relaxed text-amber-800 text-[11px]">{{ $page.props.errors.google }}</p>
        </div>

        <!-- Heading Text -->
        <div class="space-y-1">
            <h2 class="text-xl font-black text-zinc-950 tracking-tight">Masuk ke ruang personalmu.</h2>
            <p class="text-xs text-zinc-500">Lanjutkan ceritamu dan kelola tautan serta gantungan kuncimu di id.lunou.tech.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-3.5">
            <div>
                <label for="email" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-bold text-zinc-700 uppercase tracking-wider">Kata sandi</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[11px] text-zinc-500 hover:text-zinc-950 font-medium"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>
                <input
                    id="password"
                    type="password"
                    class="w-full bg-zinc-50 border border-zinc-200 focus:bg-white focus:border-zinc-950 rounded-xl px-3.5 py-2.5 text-xs text-zinc-900 focus:outline-none transition-colors"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-zinc-300 text-zinc-950 focus:ring-0" />
                    <span class="ms-2 text-xs text-zinc-600">Ingat saya</span>
                </label>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 rounded-xl bg-zinc-950 hover:bg-black text-white font-bold text-xs shadow-sm hover:shadow transition-all disabled:opacity-50 mt-2"
            >
                {{ form.processing ? 'Membuka ruangmu...' : 'Masuk ke Akun' }}
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
            <span>Masuk dengan Google</span>
        </a>

        <!-- Fast Dev Login for Testing (trygaming303@gmail.com & demo) -->
        <div class="space-y-1.5 pt-1">
            <a
                href="/auth/dev-google-login?email=trygaming303@gmail.com"
                class="w-full py-2 px-3 rounded-xl bg-blue-50/80 hover:bg-blue-100 border border-blue-200 text-blue-700 text-[11px] font-semibold text-center transition-all flex items-center justify-center gap-1.5"
            >
                <span>Masuk Cepat: trygaming303@gmail.com</span>
                <span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-blue-200 text-blue-800 font-bold">Simulasi Google</span>
            </a>

            <a
                href="/auth/dev-login"
                class="w-full py-1.5 px-3 rounded-xl bg-zinc-50 hover:bg-zinc-100 border border-dashed border-zinc-200 text-zinc-600 text-[11px] font-medium text-center transition-all block"
            >
                Masuk Cepat Akun Demo (@tri)
            </a>
        </div>

        <!-- Terms Footer -->
        <p class="text-[11px] text-zinc-400 text-center leading-relaxed">
            Dengan melanjutkan, kamu menyetujui Ketentuan Layanan dan Kebijakan Privasi LUNOU.
        </p>
    </GuestLayout>
</template>
