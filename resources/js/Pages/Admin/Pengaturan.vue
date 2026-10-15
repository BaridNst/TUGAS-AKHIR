<script setup>
import { computed } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { motion } from 'motion-v';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    sistem: {
        type: Array,
        default: () => [],
    },
});

const user = computed(() => usePage().props.auth?.user ?? null);

const inisial = computed(() => {
    const nama = user.value?.name ?? 'Pengguna';

    return nama
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((bagian) => bagian.charAt(0))
        .join('')
        .toUpperCase();
});
</script>

<template>
    <SidebarLayout title="Pengaturan">
        <Head title="Pengaturan - Admin" />

        <motion.div
            class="overflow-hidden rounded-2xl border border-orange-100/80 bg-white shadow-[0_18px_40px_-26px_rgba(249,115,22,0.45)]"
            :initial="{ opacity: 0, y: 8 }"
            :animate="{ opacity: 1, y: 0 }"
            :transition="{ duration: 0.4, delay: 0.06, ease: [0.22, 1, 0.36, 1] }"
        >
            <div class="h-1 w-full bg-gradient-to-r from-[#F97316] via-[#FB923C] to-[#FDBA74]"></div>

            <div class="flex items-center gap-3 border-b border-orange-100 px-4 py-4 sm:gap-4 sm:px-6 sm:py-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-[#F97316] ring-1 ring-orange-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.044-.146.086-.22.128-.332.183-.582.495-.644.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Pengaturan</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Ringkasan konfigurasi sistem Peminjaman NFC</p>
                </div>
            </div>

            <div class="grid gap-4 p-4 sm:gap-5 sm:p-6 lg:grid-cols-3">
                <!-- Pengaturan Sistem -->
                <div class="lg:col-span-2">
                    <h2 class="text-sm font-bold text-gray-900">Sistem</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="item in sistem"
                            :key="item.label"
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs transition hover:border-orange-100"
                        >
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">{{ item.label }}</p>
                            <p class="mt-1.5 text-lg font-black tracking-tight text-gray-900">{{ item.nilai }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ item.keterangan }}</p>
                        </div>
                    </div>
                </div>

                <!-- Akun Aktif -->
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Akun Aktif</h2>
                    <div class="mt-3 rounded-2xl border border-orange-100 bg-gradient-to-b from-orange-50/80 to-white p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-orange-500 text-sm font-bold text-white shadow-md shadow-orange-500/30">
                                {{ inisial }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-900">{{ user?.name ?? '—' }}</p>
                                <p class="truncate text-xs text-gray-500">{{ user?.email ?? '—' }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2 border-t border-orange-100 pt-3 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-gray-500">Peran</dt>
                                <dd class="font-bold text-gray-900">{{ user?.peran || '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-gray-500">Status Akun</dt>
                                <dd
                                    class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                                    :class="
                                        user?.status_akun === 'Aktif'
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80'
                                            : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'
                                    "
                                >
                                    {{ user?.status_akun || '—' }}
                                </dd>
                            </div>
                        </dl>

                        <Link
                            :href="route('profile.show')"
                            class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#F97316] px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-orange-500/30 transition hover:bg-[#EA580C] focus:outline-none focus:ring-2 focus:ring-[#F97316]/40 focus:ring-offset-2 active:scale-95"
                        >
                            Kelola Profil &amp; Kata Sandi
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Aturan peminjaman dan identifikasi stiker mengikuti konfigurasi aplikasi pada server.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
