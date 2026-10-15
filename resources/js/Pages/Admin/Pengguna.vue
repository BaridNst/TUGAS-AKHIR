<script setup>
import { computed, ref } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { motion } from 'motion-v';
import { Head } from '@inertiajs/vue3';
import { createSortedRowModel, rowSortingFeature, sortFns, tableFeatures, useTable } from '@tanstack/vue-table';

const props = defineProps({
    pengguna: {
        type: Array,
        default: () => [],
    },
});

const cari = ref('');

const totalAktif = computed(() => props.pengguna.filter((item) => item.status === 'Aktif').length);
const totalNonaktif = computed(() => props.pengguna.filter((item) => item.status !== 'Aktif').length);

const daftar = computed(() => {
    const kata = cari.value.trim().toLowerCase();

    if (kata === '') {
        return props.pengguna;
    }

    return props.pengguna.filter((item) =>
        [item.nama, item.email, item.nim, item.prodi, item.kontak, item.peran]
            .filter(Boolean)
            .some((nilai) => String(nilai).toLowerCase().includes(kata))
    );
});

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const columns = [
    { id: 'no', header: 'No', enableSorting: false, meta: { className: 'whitespace-nowrap' } },
    { id: 'nama', accessorKey: 'nama', header: 'Nama' },
    { id: 'identitas', accessorKey: 'nim', header: 'NIM' },
    { id: 'prodi', accessorKey: 'prodi', header: 'Program Studi' },
    { id: 'kontak', accessorKey: 'kontak', header: 'Kontak' },
    { id: 'status', accessorKey: 'status', header: 'Status', meta: { className: 'whitespace-nowrap' } },
];

const data = computed(() => daftar.value);

const table = useTable({ features, columns, data });
</script>

<template>
    <SidebarLayout title="Pengguna">
        <Head title="Pengguna - Admin" />

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
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Daftar Pengguna</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Seluruh akun pengguna aplikasi Peminjaman NFC</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center rounded-full bg-orange-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(249,115,22,0.75)]">
                        Total {{ pengguna.length }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]">
                        Aktif {{ totalAktif }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-400 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(107,114,128,0.75)]">
                        Nonaktif {{ totalNonaktif }}
                    </span>
                </div>

                <div class="ml-auto flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <input
                        v-model="cari"
                        type="search"
                        placeholder="Cari nama / NIM..."
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

                    <template #cell-nama="{ row }">
                        <p class="truncate text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                        <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">{{ row.email }}</p>
                    </template>

                    <template #cell-identitas="{ row }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-lg bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-tight text-gray-700 ring-1 ring-gray-200"
                        >
                            {{ row.nim || '—' }}
                        </span>
                        <p class="mt-1 truncate text-[11px] leading-tight text-gray-500">{{ row.peran || '—' }}</p>
                    </template>

                    <template #cell-prodi="{ value }">
                        <span class="block truncate text-sm text-gray-700">{{ value || '—' }}</span>
                    </template>

                    <template #cell-kontak="{ value }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ value || '—' }}</span>
                    </template>

                    <template #cell-status="{ value }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold"
                            :class="
                                value === 'Aktif'
                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80'
                                    : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'
                            "
                        >
                            {{ value || '—' }}
                        </span>
                    </template>

                    <template #empty>Belum ada akun pengguna.</template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Akun peminjam dapat diubah atau dihapus melalui menu Data Barang &amp; Peminjaman — tab Data Peminjaman.
            </div>
        </motion.div>
    </SidebarLayout>
</template>
