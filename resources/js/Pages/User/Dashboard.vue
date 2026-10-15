<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { motion } from 'motion-v';
import { Head, Link, usePoll } from '@inertiajs/vue3';
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
    riwayat: {
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

const shownCount = computed(() => Math.min(props.barang.length, 5));

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
    },
    {
        id: 'peminjam',
        accessorFn: (row) => row.peminjam?.nama ?? '',
        header: 'Nama Peminjam',
    },
    {
        id: 'nim',
        accessorFn: (row) => row.peminjam?.nim ?? '',
        header: 'NIM',
        meta: { className: 'hidden xl:table-cell' },
    },
    {
        id: 'barang',
        accessorKey: 'nama',
        header: 'Barang',
    },
    {
        id: 'jam',
        accessorKey: 'jamSelesai',
        header: 'Jam Selesai',
        sortFn: (rowA, rowB) =>
            String(rowA.original.jamSelesai ?? '').localeCompare(String(rowB.original.jamSelesai ?? '')),
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        sortFn: (rowA, rowB) => rankStatus(rowA.original.status) - rankStatus(rowB.original.status),
    },
];

const table = useTable({ features, columns, data });

const berjalanCount = computed(() => props.riwayat.filter((item) => item.status === 'Berjalan').length);

const selesaiCount = computed(() => props.riwayat.length - berjalanCount.value);

const shownRiwayatCount = computed(() => Math.min(props.riwayat.length, 5));

const isDone = (status) => status === 'Selesai';

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-');

    return `${Number(day)} ${months[Number(month) - 1]} ${year}`;
};

const riwayatFeatures = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const riwayatData = computed(() => props.riwayat);

const rankRiwayatStatus = (status) => (status === 'Berjalan' ? 0 : 1);

const riwayatColumns = [
    {
        id: 'no',
        header: 'No',
        enableSorting: false,
    },
    {
        id: 'tanggal',
        accessorKey: 'tanggal',
        header: 'Tanggal',
    },
    {
        id: 'barang',
        accessorKey: 'nama',
        header: 'Barang',
    },
    {
        id: 'aksi',
        accessorKey: 'aksi',
        header: 'Aksi',
    },
    {
        id: 'jam',
        accessorKey: 'jam',
        header: 'Jam',
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        sortFn: (rowA, rowB) => rankRiwayatStatus(rowA.original.status) - rankRiwayatStatus(rowB.original.status),
    },
];

const riwayatTable = useTable({ features: riwayatFeatures, columns: riwayatColumns, data: riwayatData });
</script>

<template>
    <SidebarLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="space-y-4 sm:space-y-6">
            <!-- Ringkasan statistik barang (paling atas) -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                <!-- Card 1: Total Barang -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-orange-100 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-md sm:p-4.5"
                >
                    <div class="flex items-center gap-3 sm:gap-3.5">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-white shadow-md shadow-orange-500/25 sm:h-11 sm:w-11 sm:rounded-xl"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-500 sm:text-xs">Total Barang</p>
                            <p class="text-2xl font-black leading-tight tabular-nums text-gray-900 sm:text-3xl">{{ barang.length }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Tersedia -->
                <div
                    class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md sm:p-4.5"
                >
                    <div class="flex items-center gap-3 sm:gap-3.5">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-md shadow-emerald-500/25 sm:h-11 sm:w-11 sm:rounded-xl"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-500 sm:text-xs">Tersedia</p>
                            <p class="text-2xl font-black leading-tight tabular-nums text-emerald-600 sm:text-3xl">{{ readyCount }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Dipinjam -->
                <div
                    class="col-span-2 group relative overflow-hidden rounded-2xl border border-amber-100 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md sm:col-span-1 sm:p-4.5"
                >
                    <div class="flex items-center gap-3 sm:gap-3.5">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/25 sm:h-11 sm:w-11 sm:rounded-xl"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-500 sm:text-xs">Dipinjam</p>
                            <p class="text-2xl font-black leading-tight tabular-nums text-amber-600 sm:text-3xl">{{ borrowedCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA cepat: peminjaman & pengembalian -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <Link
                    :href="route('peminjaman.index')"
                    class="group flex items-center gap-3 rounded-2xl border border-orange-200/80 bg-white px-3 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-md sm:gap-4 sm:px-5 sm:py-4"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-white shadow-md shadow-orange-500/30 transition-transform duration-200 group-hover:scale-105 sm:h-12 sm:w-12 sm:rounded-2xl"
                    >
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                            />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold leading-tight tracking-tight text-gray-900 transition-colors group-hover:text-orange-600 sm:truncate">
                            Lakukan Peminjaman
                        </p>
                        <p class="hidden text-xs text-gray-500 sm:mt-0.5 sm:block sm:truncate">
                            Tempelkan HP pada tag NFC untuk memulai peminjaman
                        </p>
                    </div>

                    <span
                        class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-200 transition-all duration-200 group-hover:bg-orange-500 group-hover:text-white group-hover:ring-orange-500 sm:flex"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </Link>

                <Link
                    :href="route('pengembalian.index')"
                    class="group flex items-center gap-3 rounded-2xl border border-emerald-200/80 bg-white px-3 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md sm:gap-4 sm:px-5 sm:py-4"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30 transition-transform duration-200 group-hover:scale-105 sm:h-12 sm:w-12 sm:rounded-2xl"
                    >
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"
                            />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold leading-tight tracking-tight text-gray-900 transition-colors group-hover:text-emerald-600 sm:truncate">
                            Lakukan Pengembalian
                        </p>
                        <p class="hidden text-xs text-gray-500 sm:mt-0.5 sm:block sm:truncate">
                            Tempelkan HP pada tag NFC untuk mengembalikan barang
                        </p>
                    </div>

                    <span
                        class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200 transition-all duration-200 group-hover:bg-emerald-600 group-hover:text-white group-hover:ring-emerald-600 sm:flex"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </Link>
            </div>

            <motion.div
                class="grid gap-4 sm:gap-6 lg:grid-cols-2"
                :initial="{ opacity: 0, y: 8 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.4, delay: 0.06, ease: [0.22, 1, 0.36, 1] }"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-orange-100/80 bg-white shadow-[0_18px_40px_-26px_rgba(249,115,22,0.45)]"
                >
                    <div class="h-1 w-full bg-gradient-to-r from-[#F97316] via-[#FB923C] to-[#FDBA74]"></div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-orange-100 px-3 py-3 sm:gap-3">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold tracking-tight text-gray-900">Ketersediaan Barang</h2>
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100"
                            >
                                Ready {{ readyCount }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-100"
                            >
                                Dipinjam {{ borrowedCount }}
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-lg border border-orange-100 bg-white px-2.5 py-1.5 text-sm font-semibold tabular-nums text-gray-700 shadow-sm"
                            >
                                {{ clock }}
                            </span>

                            <Link
                                :href="route('barang.index')"
                                class="inline-flex min-h-11 items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-colors hover:border-[#FB923C] hover:text-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#FB923C]/30"
                            >
                                Lihat Semua
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <div class="px-2 pt-2">
                        <DataTable :table="table" :data="barang" :limit="5" compact>
                            <template #cell-no="{ index, row }">
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-xs font-extrabold"
                                    :class="isReady(row.status) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ index + 1 }}
                                </span>
                            </template>

                            <template #cell-peminjam="{ row }">
                                <span class="whitespace-nowrap text-sm font-semibold text-gray-900">{{ row.peminjam?.nama ?? '—' }}</span>
                            </template>

                            <template #cell-nim="{ row }">
                                <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ row.peminjam?.nim ?? '—' }}</span>
                            </template>

                            <template #cell-barang="{ row }">
                                <p class="text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                                <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">
                                    {{ row.kode }} · {{ row.kategori }}
                                </p>
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

                    <div class="border-t border-orange-100 px-3 py-2 text-[11px] leading-relaxed text-gray-500">
                        Menampilkan {{ shownCount }} dari {{ barang.length }} barang · Diperbarui otomatis tiap 5 detik.
                    </div>
                </div>

                <!-- Kartu kanan: pratinjau riwayat transaksi akun ini -->
                <div
                    class="overflow-hidden rounded-2xl border border-orange-100/80 bg-white shadow-[0_18px_40px_-26px_rgba(249,115,22,0.45)]"
                >
                    <div class="h-1 w-full bg-gradient-to-r from-[#F97316] via-[#FB923C] to-[#FDBA74]"></div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-orange-100 px-3 py-3 sm:gap-3">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold tracking-tight text-gray-900">Riwayat Transaksi</h2>
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100"
                            >
                                Selesai {{ selesaiCount }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-100"
                            >
                                Berjalan {{ berjalanCount }}
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span class="hidden text-[11px] text-gray-500 sm:inline">Khusus akun ini</span>

                            <Link
                                :href="route('riwayat.index')"
                                class="inline-flex min-h-11 items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition-colors hover:border-[#FB923C] hover:text-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#FB923C]/30"
                            >
                                Lihat Semua
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <div class="px-2 pt-2">
                        <DataTable :table="riwayatTable" :data="riwayat" :limit="5" compact>
                            <template #cell-no="{ index, row }">
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-xs font-extrabold"
                                    :class="isDone(row.status) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ index + 1 }}
                                </span>
                            </template>

                            <template #cell-tanggal="{ value }">
                                <span class="whitespace-nowrap text-sm tabular-nums text-gray-700">{{ formatDate(value) }}</span>
                            </template>

                            <template #cell-barang="{ row }">
                                <p class="text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                                <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">
                                    {{ row.kode }} · {{ row.kategori }}
                                </p>
                            </template>

                            <template #cell-aksi="{ value }">
                                <span
                                    class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold ring-1"
                                    :class="
                                        value === 'Pinjam'
                                            ? 'bg-orange-50 text-[#C2410C] ring-orange-100'
                                            : 'bg-sky-50 text-sky-700 ring-sky-100'
                                    "
                                >
                                    {{ value }}
                                </span>
                            </template>

                            <template #cell-jam="{ value }">
                                <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ value }}</span>
                            </template>

                            <template #cell-status="{ row }">
                                <span
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold text-white"
                                    :class="
                                        isDone(row.status)
                                            ? 'bg-emerald-500 shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]'
                                            : 'bg-amber-500 shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]'
                                    "
                                >
                                    <svg
                                        v-if="isDone(row.status)"
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
                                    {{ row.status }}
                                </span>
                            </template>

                            <template #empty>
                                Belum ada riwayat transaksi.
                            </template>
                        </DataTable>
                    </div>

                    <div class="border-t border-orange-100 px-3 py-2 text-[11px] leading-relaxed text-gray-500">
                        Menampilkan {{ shownRiwayatCount }} dari {{ riwayat.length }} transaksi · khusus akun ini.
                    </div>
                </div>
            </motion.div>
        </div>
    </SidebarLayout>
</template>
