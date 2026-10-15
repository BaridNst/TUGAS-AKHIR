<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { motion } from 'motion-v';
import { Head, usePoll } from '@inertiajs/vue3';
import {
    createSortedRowModel,
    rowSortingFeature,
    sortFns,
    tableFeatures,
    useTable,
} from '@tanstack/vue-table';

const props = defineProps({
    barang: {
        type: Array,
        default: () => [],
    },
});

usePoll(5000, { only: ['barang'] });

const clock = ref('');
let clockTimer = null;

const tickClock = () => {
    const now = new Date();
    const pad = (value) => String(value).padStart(2, '0');

    clock.value = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
};

onMounted(() => {
    tickClock();
    clockTimer = setInterval(tickClock, 1000);
});

onUnmounted(() => clearInterval(clockTimer));

const readyCount = computed(() => props.barang.filter((item) => item.status === 'ready').length);

const borrowedCount = computed(() => props.barang.length - readyCount.value);

const isReady = (status) => status === 'ready';

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const data = computed(() => props.barang);

const rankStatus = (status) => (status === 'ready' ? 0 : 1);

const columns = [
    {
        id: 'no',
        header: 'No',
        enableSorting: false,
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'kode',
        accessorKey: 'kode',
        header: 'Kode',
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'nama',
        accessorKey: 'nama',
        header: 'Nama Barang',
    },
    {
        id: 'kategori',
        accessorKey: 'kategori',
        header: 'Kategori',
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'peminjam',
        accessorFn: (row) => row.peminjam?.nama ?? '',
        header: 'Peminjam',
    },
    {
        id: 'jam',
        accessorKey: 'jamSelesai',
        header: 'Jam Selesai',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) =>
            String(rowA.original.jamSelesai ?? '').localeCompare(String(rowB.original.jamSelesai ?? '')),
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rankStatus(rowA.original.status) - rankStatus(rowB.original.status),
    },
];

const table = useTable({ features, columns, data });
</script>

<template>
    <SidebarLayout title="Data Barang">
        <Head title="Data Barang" />

        <motion.div
            class="overflow-hidden rounded-2xl border border-orange-100/80 bg-white shadow-[0_18px_40px_-26px_rgba(249,115,22,0.45)]"
            :initial="{ opacity: 0, y: 8 }"
            :animate="{ opacity: 1, y: 0 }"
            :transition="{ duration: 0.4, delay: 0.06, ease: [0.22, 1, 0.36, 1] }"
        >
            <div class="h-1 w-full bg-gradient-to-r from-[#F97316] via-[#FB923C] to-[#FDBA74]"></div>

            <div class="flex items-center gap-3 border-b border-orange-100 px-4 py-4 sm:gap-4 sm:px-6 sm:py-5">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-[#F97316] ring-1 ring-orange-100"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Data Barang</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Daftar lengkap barang beserta status ketersediaannya</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]"
                    >
                        Ready {{ readyCount }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]"
                    >
                        Dipinjam {{ borrowedCount }}
                    </span>
                    <span class="hidden text-xs text-gray-500 sm:inline">Total {{ barang.length }} barang</span>
                </div>

                <span
                    class="ml-auto rounded-lg border border-orange-100 bg-white px-2.5 py-1.5 text-sm font-semibold tabular-nums text-gray-700 shadow-sm"
                >
                    {{ clock }}
                </span>
            </div>

            <div class="px-4 pt-3 sm:px-6 sm:pt-4">
                <DataTable :table="table" :data="barang">
                    <template #cell-no="{ index }">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-extrabold text-gray-600">
                            {{ index + 1 }}
                        </span>
                    </template>

                    <template #cell-kode="{ value }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-lg bg-gray-100 px-2 py-1 font-mono text-xs font-semibold tracking-tight text-gray-700 ring-1 ring-gray-200"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-nama="{ value }">
                        <span class="text-sm font-semibold text-gray-900">{{ value }}</span>
                    </template>

                    <template #cell-kategori="{ value }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-full bg-orange-50 px-2.5 py-1 text-[11px] font-semibold text-[#C2410C] ring-1 ring-orange-100"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-peminjam="{ row }">
                        <span v-if="row.peminjam" class="text-sm font-medium text-gray-700">
                            {{ row.peminjam.nama }}
                        </span>
                        <span v-else class="text-sm text-gray-500">—</span>
                    </template>

                    <template #cell-jam="{ row }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ row.jamSelesai ?? '—' }}</span>
                    </template>

                    <template #cell-status="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold text-white"
                            :class="
                                isReady(row.status)
                                    ? 'bg-emerald-500 shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]'
                                    : 'bg-amber-500 shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]'
                            "
                        >
                            <svg
                                v-if="isReady(row.status)"
                                class="h-3 w-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {{ isReady(row.status) ? 'Ready' : 'Dipinjam' }}
                        </span>
                    </template>

                    <template #empty>
                        Belum ada data barang.
                    </template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Data diperbarui otomatis setiap 5 detik.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
