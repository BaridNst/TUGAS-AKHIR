<script setup>
import { computed, ref } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { motion } from 'motion-v';
import { Head } from '@inertiajs/vue3';
import { createSortedRowModel, rowSortingFeature, sortFns, tableFeatures, useTable } from '@tanstack/vue-table';

const props = defineProps({
    denda: {
        type: Object,
        default: () => ({ baris: [], ringkas: { belumKembali: 0, selesaiTerlambat: 0, totalHari: 0 } }),
    },
});

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-');

    return `${Number(day)} ${months[Number(month) - 1]} ${year}`;
};

const cari = ref('');

const baris = computed(() => props.denda.baris ?? []);
const ringkas = computed(() => props.denda.ringkas ?? { belumKembali: 0, selesaiTerlambat: 0, totalHari: 0 });

const daftar = computed(() => {
    const kata = cari.value.trim().toLowerCase();

    if (kata === '') {
        return baris.value;
    }

    return baris.value.filter((item) =>
        [item.peminjam, item.nim, item.kode, item.nama, item.jenis]
            .filter(Boolean)
            .some((nilai) => String(nilai).toLowerCase().includes(kata))
    );
});

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const rankJenis = (jenis) => (jenis === 'Belum Kembali' ? 0 : 1);

const columns = [
    { id: 'no', header: 'No', enableSorting: false, meta: { className: 'whitespace-nowrap' } },
    { id: 'peminjam', accessorKey: 'peminjam', header: 'Peminjam' },
    { id: 'barang', accessorKey: 'nama', header: 'Barang' },
    { id: 'batas', accessorKey: 'batas', header: 'Batas Kembali', meta: { className: 'whitespace-nowrap' } },
    {
        id: 'hari',
        accessorKey: 'hari',
        header: 'Terlambat',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rowA.original.hari - rowB.original.hari,
    },
    {
        id: 'jenis',
        accessorKey: 'jenis',
        header: 'Kondisi',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rankJenis(rowA.original.jenis) - rankJenis(rowB.original.jenis),
    },
];

const data = computed(() => daftar.value);

const table = useTable({ features, columns, data });
</script>

<template>
    <SidebarLayout title="Denda">
        <Head title="Denda - Admin" />

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
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Keterlambatan &amp; Denda</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Rekap pengembalian yang melewati tenggat waktu</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(244,63,94,0.75)]">
                        Belum Kembali {{ ringkas.belumKembali }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]">
                        Selesai Terlambat {{ ringkas.selesaiTerlambat }}
                    </span>
                    <span class="hidden text-xs text-gray-500 sm:inline">Akumulasi {{ ringkas.totalHari }} hari terlambat</span>
                </div>

                <div class="ml-auto flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <input
                        v-model="cari"
                        type="search"
                        placeholder="Cari peminjam / barang..."
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm placeholder:text-gray-400 focus:border-[#F97316] focus:ring-[#F97316]/40 sm:w-56"
                    />
                </div>
            </div>

            <div class="px-4 pt-3 sm:px-6 sm:pt-4">
                <DataTable :table="table" :data="daftar">
                    <template #cell-no="{ index }">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-extrabold text-gray-600">
                            {{ index + 1 }}
                        </span>
                    </template>

                    <template #cell-peminjam="{ row }">
                        <p class="truncate text-sm font-semibold leading-tight text-gray-900">{{ row.peminjam }}</p>
                        <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">{{ row.nim || '—' }}</p>
                    </template>

                    <template #cell-barang="{ row }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-lg bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-tight text-gray-700 ring-1 ring-gray-200"
                        >
                            {{ row.kode }}
                        </span>
                        <p class="mt-1 truncate text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                    </template>

                    <template #cell-batas="{ value }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-700">{{ formatDate(value) }}</span>
                    </template>

                    <template #cell-hari="{ value }">
                        <span
                            class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200/80"
                        >
                            {{ value }} hari
                        </span>
                    </template>

                    <template #cell-jenis="{ value }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold ring-1"
                            :class="
                                value === 'Belum Kembali'
                                    ? 'bg-rose-50 text-rose-700 ring-rose-200/80'
                                    : 'bg-amber-50 text-amber-700 ring-amber-200/80'
                            "
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #empty>Belum ada keterlambatan. Semua peminjaman berjalan tepat waktu.</template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Hari terlambat dihitung dari tenggat 7 hari sejak tanggal pinjam. Pengembalian yang melewati tenggat otomatis ditandai status Terlambat.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
