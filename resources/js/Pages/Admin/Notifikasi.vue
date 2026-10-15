<script setup>
import { computed } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import AdminEmptyState from '@/Components/AdminEmptyState.vue';
import { motion } from 'motion-v';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    notifikasi: {
        type: Array,
        default: () => [],
    },
});

const warnaIkon = {
    clock: 'bg-amber-100/80 text-amber-600',
    return: 'bg-rose-100/80 text-rose-600',
    alert: 'bg-red-100/80 text-red-600',
    document: 'bg-blue-100/80 text-blue-600',
};

const jumlahPengajuan = computed(
    () => props.notifikasi.filter((item) => item.ikon === 'document' || item.ikon === 'return').length
);
const jumlahKeterlambatan = computed(
    () => props.notifikasi.filter((item) => item.ikon === 'alert' || item.ikon === 'clock').length
);
</script>

<template>
    <SidebarLayout title="Notifikasi">
        <Head title="Notifikasi - Admin" />

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
                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Notifikasi Sistem</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Pengajuan baru dan peminjaman yang perlu ditindaklanjuti</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-2 sm:px-6 sm:py-4">
                <span class="inline-flex items-center rounded-full bg-blue-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(59,130,246,0.75)]">
                    Pengajuan {{ jumlahPengajuan }}
                </span>
                <span class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(244,63,94,0.75)]">
                    Keterlambatan {{ jumlahKeterlambatan }}
                </span>
                <span class="hidden text-xs text-gray-500 sm:inline">Total {{ notifikasi.length }} notifikasi</span>
            </div>

            <div class="px-4 py-4 sm:px-6 sm:py-5">
                <div v-if="notifikasi.length" class="space-y-2 sm:space-y-3">
                    <div
                        v-for="item in notifikasi"
                        :key="`${item.id}-${item.judul}`"
                        class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 p-3 transition hover:bg-gray-50/80 sm:p-3.5"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="warnaIkon[item.ikon] ?? warnaIkon.document">
                                <svg v-if="item.ikon === 'clock'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="item.ikon === 'return'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                <svg v-else-if="item.ikon === 'alert'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                                    />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-900">{{ item.judul }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-500">Peminjam: {{ item.peminjam }}</p>
                            </div>
                        </div>

                        <span class="shrink-0 whitespace-nowrap text-xs font-semibold text-gray-500">{{ item.waktu }}</span>
                    </div>
                </div>

                <AdminEmptyState
                    v-else
                    title="Belum ada notifikasi"
                    description="Notifikasi muncul ketika ada pengajuan baru atau peminjaman yang melewati tenggat."
                    icon="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                />
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Menampilkan maksimal 12 notifikasi terbaru. Persetujuan pengajuan dilakukan melalui menu Peminjaman dan Pengembalian.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
