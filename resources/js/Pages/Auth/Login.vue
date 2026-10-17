<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
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

const loginWithGoogle = () => {
    window.location.href = '/auth/google/redirect';
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div v-if="status" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">
            {{ status }}
        </div>

        <div v-if="$page.props.flash?.error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-red-100">
            {{ $page.props.flash.error }}
        </div>

        <div class="mb-3">
            <h1 class="text-[13px] font-bold leading-tight tracking-tight text-gray-900">PEMINJAMAN DENGAN TEKNOLOGI TERBARU</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-3">
            <div>
                <InputLabel for="email" value="Email atau Nama Pengguna" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="text"
                    class="mt-1 block w-full !py-2.5 !text-sm sm:!py-3 sm:!text-[14px]"
                    placeholder="Masukkan email atau nama pengguna"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full !py-2.5 !text-sm sm:!py-3 sm:!text-[14px]"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <label class="flex min-h-8 cursor-pointer items-center py-0.5">
                <Checkbox v-model:checked="form.remember" name="remember" class="rounded-md" />
                <span class="ms-2.5 text-sm text-gray-600">Ingat saya</span>
            </label>

            <PrimaryButton
                class="w-full justify-center !py-3 font-bold"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Masuk
            </PrimaryButton>

            <div class="relative py-1">
                <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-gray-200" /></div>
                <div class="relative flex justify-center text-xs"><span class="bg-white px-3 text-gray-500">atau</span></div>
            </div>

            <button
                type="button"
                class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition hover:border-black/15 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-black/5"
                @click="loginWithGoogle"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                <span>Login menggunakan Google</span>
            </button>
        </form>

        <p class="mt-3 text-center text-[13px] text-gray-600">
            Belum punya akun?
            <Link :href="route('register')" class="font-bold text-[#F97316] hover:underline">Daftar sekarang</Link>
        </p>
    </GuestLayout>
</template>
