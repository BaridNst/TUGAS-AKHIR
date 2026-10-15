<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { motion } from 'motion-v';
import { Head } from '@inertiajs/vue3';
import {
    createSortedRowModel,
    rowSortingFeature,
    sortFns,
    tableFeatures,
    useTable,
} from '@tanstack/vue-table';

const props = defineProps({
    riwayat: {
        type: Array,
        default: () => [],
    },
});

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

const berjalanCount = computed(() => props.riwayat.filter((item) => item.status === 'Berjalan').length);

const selesaiCount = computed(() => props.riwayat.length - berjalanCount.value);

const isDone = (status) => status === 'Selesai';

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-');

    return `${Number(day)} ${months[Number(month) - 1]} ${year}`;
};

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const data = computed(() => props.riwayat);

const rankStatus = (status) => (status === 'Berjalan' ? 0 : 1);

const columns = [
    {
        id: 'no',
        header: 'No',
        enableSorting: false,
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'tanggal',
        accessorKey: 'tanggal',
        header: 'Tanggal',
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'kode',
        accessorKey: 'kode',
        header: 'Kode',
        meta: { className: 'whitespace-nowrap' },
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
        meta: { className: 'whitespace-nowrap' },
    },
    {
        id: 'jam',
        accessorKey: 'jam',
        header: 'Jam',
        meta: { className: 'whitespace-nowrap' },
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
    <SidebarLayout title="Riwayat Transaksi">
        <Head title="Riwayat Transaksi" />

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
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Riwayat Transaksi</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Riwayat peminjaman &amp; pengembalian milik akun ini saja</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]"
                    >
                        Berjalan {{ berjalanCount }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]"
                    >
                        Selesai {{ selesaiCount }}
                    </span>
                    <span class="hidden text-xs text-gray-500 sm:inline">Total {{ riwayat.length }} transaksi</span>
                </div>

                <span
                    class="ml-auto rounded-lg border border-orange-100 bg-white px-2.5 py-1.5 text-sm font-semibold tabular-nums text-gray-700 shadow-sm"
                >
                    {{ clock }}
                </span>
            </div>

            <div class="px-4 pt-3 sm:px-6 sm:pt-4">
                <DataTable :table="table" :data="riwayat">
                    <template #cell-no="{ index }">
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-extrabold text-gray-600"
                        >
                            {{ index + 1 }}
                        </span>
                    </template>

                    <template #cell-tanggal="{ value }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-700">{{ formatDate(value) }}</span>
                    </template>

                    <template #cell-kode="{ value }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-lg bg-gray-100 px-2 py-1 font-mono text-xs font-semibold tracking-tight text-gray-700 ring-1 ring-gray-200"
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-barang="{ row }">
                        <p class="truncate text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                        <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">{{ row.kategori }}</p>
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

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Hanya transaksi milik akun yang sedang login yang ditampilkan.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
