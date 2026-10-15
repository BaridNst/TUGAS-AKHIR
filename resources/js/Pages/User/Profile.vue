<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { motion } from 'motion-v';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const tabs = [
    {
        key: 'profile',
        label: 'Informasi Profil',
        icon: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    },
    {
        key: 'security',
        label: 'Keamanan Akun',
        icon: 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
    },
];

const activeTab = ref('profile');

const imageError = ref(false);

const initials = computed(() => {
    const parts = (props.user?.name ?? 'Pengguna').trim().split(/\s+/);

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
});

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    no_hp: props.user.no_hp ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordChecks = computed(() => [
    { label: 'Minimal 8 karakter', passed: passwordForm.password.length >= 8 },
    {
        label: 'Konfirmasi kata sandi cocok',
        passed:
            passwordForm.password.length > 0 &&
            passwordForm.password === passwordForm.password_confirmation,
    },
]);

const isProfileDirty = computed(
    () => profileForm.name !== props.user.name || profileForm.email !== props.user.email || profileForm.no_hp !== (props.user.no_hp ?? ''),
);

const submitProfile = () => {
    profileForm.put(route('profile.update'), {
        preserveScroll: true,
    });
};

const submitPassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onFinish: () => passwordForm.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Profil" />

    <div class="relative min-h-dvh overflow-hidden bg-gray-50">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div
                class="absolute -top-52 left-1/2 h-[440px] w-[900px] -translate-x-1/2 rounded-full bg-gradient-to-br from-[#F97316]/30 via-[#FB923C]/20 to-transparent blur-3xl"
            ></div>
            <div
                class="absolute -bottom-40 -left-32 h-[360px] w-[360px] rounded-full bg-[#FDBA74]/30 blur-3xl"
            ></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(251,146,60,0.08),transparent_55%)]"></div>
        </div>

        <header class="relative z-20 mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex items-center gap-2">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:border-[#FB923C]/50 hover:text-[#F97316] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#FB923C]/30"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span class="hidden sm:inline">Dashboard</span>
                </Link>

                <span
                    class="ml-1 inline-flex h-11 min-w-0 items-center gap-2 truncate rounded-xl bg-white px-2.5 text-sm font-bold tracking-tight text-gray-900 shadow-sm ring-1 ring-gray-200 sm:px-3"
                >
                    <span class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-md bg-white p-0.5 ring-1 ring-gray-200">
                        <img src="/logo-nfc.png" alt="Peminjaman NFC" class="h-full w-full object-contain" />
                    </span>
                    Profil Saya
                </span>
            </div>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="group inline-flex h-11 items-center gap-2.5 rounded-xl border border-red-200 bg-white px-2 text-sm font-semibold text-red-600 shadow-sm transition hover:border-red-300 hover:bg-red-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-200 active:scale-[0.95] sm:pr-3.5"
            >
                <span
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-red-500 text-white shadow-md shadow-red-500/30 transition duration-200 group-hover:rotate-6 group-hover:scale-110"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.25"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"
                        />
                    </svg>
                </span>
                <span class="hidden sm:inline">Logout</span>
            </Link>
        </header>

        <main class="relative z-10 mx-auto w-full max-w-6xl px-4 pb-8 sm:px-6 sm:pb-16">
            <motion.section
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#F97316] via-[#FB923C] to-[#FDBA74] p-5 shadow-[0_24px_60px_-30px_rgba(249,115,22,0.75)] sm:p-8"
                :initial="{ opacity: 0, y: 16 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
            >
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/15 blur-2xl"></div>
                    <div class="absolute -bottom-24 right-1/3 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                    <div
                        class="absolute inset-0 opacity-[0.12] bg-[linear-gradient(rgba(255,255,255,0.6)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.6)_1px,transparent_1px)] bg-[length:36px_36px]"
                    ></div>
                </div>

                <div class="relative flex items-center gap-4 sm:gap-5">
                    <img
                        v-if="user?.avatar && !imageError"
                        :src="user.avatar"
                        :alt="profileForm.name"
                        referrerpolicy="no-referrer"
                        class="h-16 w-16 shrink-0 rounded-2xl object-cover shadow-xl ring-4 ring-white/40 sm:h-24 sm:w-24 sm:rounded-3xl"
                        @error="imageError = true"
                    />
                    <span
                        v-else
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white text-xl font-extrabold text-[#F97316] shadow-xl ring-4 ring-white/40 sm:h-24 sm:w-24 sm:rounded-3xl sm:text-3xl"
                    >
                        {{ initials }}
                    </span>

                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-extrabold tracking-tight text-white sm:text-3xl">
                            {{ profileForm.name || 'Pengguna' }}
                        </h1>
                        <p class="truncate text-sm font-medium text-white/85">
                            {{ profileForm.email || '—' }}
                        </p>
                    </div>
                </div>
            </motion.section>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:mt-6 sm:gap-6 lg:grid-cols-3">
                <motion.aside
                    class="order-2 space-y-4 sm:space-y-6 lg:order-1 lg:col-span-1"
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.45, delay: 0.08, ease: [0.22, 1, 0.36, 1] }"
                >
                    <div class="rounded-3xl border border-gray-100 bg-white p-4 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.45)] sm:p-6">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-900">Detail Akun</h2>

                        <dl class="mt-4 space-y-3">
                            <div class="flex items-start justify-between gap-4 rounded-2xl bg-gray-50 px-4 py-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nama</dt>
                                <dd class="min-w-0 truncate text-right text-sm font-semibold text-gray-900">
                                    {{ profileForm.name || '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 rounded-2xl bg-gray-50 px-4 py-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</dt>
                                <dd class="min-w-0 truncate text-right text-sm font-semibold text-gray-900">
                                    {{ profileForm.email || '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        class="rounded-3xl border border-orange-100 bg-gradient-to-br from-orange-50 to-white p-4 shadow-[0_18px_40px_-30px_rgba(249,115,22,0.5)] sm:p-6"
                    >
                        <p class="text-sm font-bold text-gray-900">Keamanan akun</p>
                        <p class="mt-1 text-sm leading-relaxed text-gray-500">
                            Perbarui kata sandi secara berkala agar akun Peminjaman NFC tetap aman.
                        </p>
                        <button
                            type="button"
                            class="mt-4 inline-flex w-full min-h-[44px] items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#F97316] ring-1 ring-orange-200 transition hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-300"
                            @click="activeTab = 'security'"
                        >
                            Buka Pengaturan Keamanan
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </motion.aside>

                <motion.section
                    class="order-1 lg:order-2 lg:col-span-2"
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.45, delay: 0.16, ease: [0.22, 1, 0.36, 1] }"
                >
                    <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-[0_18px_40px_-30px_rgba(15,23,42,0.45)]">
                        <div class="flex gap-1 border-b border-gray-100 bg-gray-50/70 p-1.5 sm:p-2">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-2xl px-2 py-3 text-[13px] font-bold leading-5 transition focus:outline-none focus:ring-2 focus:ring-orange-300 sm:gap-2 sm:px-4 sm:text-sm"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-white text-[#F97316] shadow-sm ring-1 ring-gray-200'
                                        : 'text-gray-500 hover:bg-white/70 hover:text-gray-800'
                                "
                                @click="activeTab = tab.key"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="tab.icon" />
                                </svg>
                                {{ tab.label }}
                            </button>
                        </div>

                        <div class="p-4 sm:p-7">
                            <form v-if="activeTab === 'profile'" class="space-y-4 sm:space-y-5" @submit.prevent="submitProfile">
                                <div>
                                    <h2 class="text-base font-bold tracking-tight text-gray-900">Informasi Profil</h2>
                                    <p class="mt-1 text-sm text-gray-500">Nama dan email yang digunakan pada akun Anda.</p>
                                </div>

                                <div>
                                    <InputLabel for="name" value="Nama Lengkap" />
                                    <TextInput
                                        id="name"
                                        v-model="profileForm.name"
                                        type="text"
                                        class="mt-1.5 block w-full"
                                        autocomplete="name"
                                    />
                                    <InputError class="mt-2" :message="profileForm.errors.name" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Alamat Email" />
                                    <TextInput
                                        id="email"
                                        v-model="profileForm.email"
                                        type="email"
                                        class="mt-1.5 block w-full"
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-2" :message="profileForm.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="no_hp" value="Nomor Telepon (WhatsApp)" />
                                    <TextInput
                                        id="no_hp"
                                        v-model="profileForm.no_hp"
                                        type="tel"
                                        class="mt-1.5 block w-full"
                                        autocomplete="tel"
                                        placeholder="08xxxxxxxxxx"
                                    />
                                    <InputError class="mt-2" :message="profileForm.errors.no_hp" />
                                </div>

                                <div
                                    class="flex flex-col gap-3 rounded-2xl bg-gray-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4"
                                >
                                    <p class="text-xs font-medium text-gray-500">
                                        {{
                                            isProfileDirty
                                                ? 'Ada perubahan yang belum disimpan.'
                                                : 'Tidak ada perubahan untuk disimpan.'
                                        }}
                                    </p>

                                    <div class="flex items-center gap-3">
                                        <Transition
                                            enter-active-class="transition ease-in-out duration-200"
                                            enter-from-class="opacity-0"
                                            enter-to-class="opacity-100"
                                        >
                                            <span
                                                v-if="profileForm.recentlySuccessful"
                                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-200"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                Tersimpan
                                            </span>
                                        </Transition>

                                        <button
                                            type="submit"
                                            :disabled="profileForm.processing || !isProfileDirty"
                                            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-orange-300 disabled:opacity-60 disabled:shadow-none"
                                        >
                                            <svg
                                                v-if="profileForm.processing"
                                                class="h-4 w-4 animate-spin"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                            </svg>
                                            Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form v-else class="space-y-4 sm:space-y-5" @submit.prevent="submitPassword">
                                <div>
                                    <h2 class="text-base font-bold tracking-tight text-gray-900">Ganti Kata Sandi</h2>
                                    <p class="mt-1 text-sm text-gray-500">Gunakan minimal 8 karakter dan kombinasi unik.</p>
                                </div>

                                <div>
                                    <InputLabel for="current_password" value="Kata Sandi Saat Ini" />
                                    <TextInput
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        class="mt-1.5 block w-full"
                                        autocomplete="current-password"
                                    />
                                    <InputError class="mt-2" :message="passwordForm.errors.current_password" />
                                </div>

                                <div>
                                    <InputLabel for="password" value="Kata Sandi Baru" />
                                    <TextInput
                                        id="password"
                                        v-model="passwordForm.password"
                                        type="password"
                                        class="mt-1.5 block w-full"
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-2" :message="passwordForm.errors.password" />
                                </div>

                                <div>
                                    <InputLabel for="password_confirmation" value="Konfirmasi Kata Sandi Baru" />
                                    <TextInput
                                        id="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        class="mt-1.5 block w-full"
                                        autocomplete="new-password"
                                    />
                                </div>

                                <ul class="flex flex-wrap gap-2">
                                    <li
                                        v-for="check in passwordChecks"
                                        :key="check.label"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                                        :class="
                                            check.passed
                                                ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200'
                                                : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200'
                                        "
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                :d="check.passed ? 'M4.5 12.75l6 6 9-13.5' : 'M6 18L18 6M6 6l12 12'"
                                            />
                                        </svg>
                                        {{ check.label }}
                                    </li>
                                </ul>

                                <div
                                    class="flex flex-col gap-3 rounded-2xl bg-gray-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4"
                                >
                                    <p class="text-xs font-medium text-gray-500">Sesi Anda tetap aktif setelah diperbarui.</p>

                                    <div class="flex items-center gap-3">
                                        <Transition
                                            enter-active-class="transition ease-in-out duration-200"
                                            enter-from-class="opacity-0"
                                            enter-to-class="opacity-100"
                                        >
                                            <span
                                                v-if="passwordForm.recentlySuccessful"
                                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600 ring-1 ring-emerald-200"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                Tersimpan
                                            </span>
                                        </Transition>

                                        <button
                                            type="submit"
                                            :disabled="passwordForm.processing"
                                            class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-orange-300 disabled:opacity-60 disabled:shadow-none"
                                        >
                                            <svg
                                                v-if="passwordForm.processing"
                                                class="h-4 w-4 animate-spin"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                            </svg>
                                            Perbarui Kata Sandi
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </motion.section>
            </div>

            <p class="mt-6 text-center text-[11px] font-semibold text-[#F97316] sm:mt-10">
                Copyright &copy; 2026 PeminjamanNFC. developed by <span class="font-bold text-[#C2410C]">IdeologTech</span>
            </p>
        </main>
    </div>
</template>
