<script setup>
import { computed, ref } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { motion } from 'motion-v';
import { Head, router } from '@inertiajs/vue3';
import { createSortedRowModel, rowSortingFeature, sortFns, tableFeatures, useTable } from '@tanstack/vue-table';

const props = defineProps({
    pengajuan: {
        type: Array,
        default: () => [],
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

const labelStatus = {
    pending: 'Menunggu',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
};

const badgeStatus = {
    pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
    disetujui: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
    ditolak: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
};

const filter = ref('semua');
const cari = ref('');
const memproses = ref(null);

const modalTampil = ref(false);
const itemDitolak = ref(null);
const alasan = ref('');

const hitung = (status) => props.pengajuan.filter((item) => item.status === status).length;

const jumlahMenunggu = computed(() => hitung('pending'));
const jumlahDisetujui = computed(() => hitung('disetujui'));
const jumlahDitolak = computed(() => hitung('ditolak'));

const daftar = computed(() => {
    const kata = cari.value.trim().toLowerCase();

    return props.pengajuan.filter((item) => {
        const cocokFilter = filter.value === 'semua' || item.status === filter.value;
        const cocokCari =
            kata === '' ||
            [item.peminjam, item.nim, item.kode, item.nama, item.catatan]
                .filter(Boolean)
                .some((nilai) => String(nilai).toLowerCase().includes(kata));

        return cocokFilter && cocokCari;
    });
});

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const rankStatus = (status) => ({ pending: 0, disetujui: 1, ditolak: 2 }[status] ?? 3);

const columns = [
    { id: 'no', header: 'No', enableSorting: false, meta: { className: 'whitespace-nowrap' } },
    { id: 'tanggal', accessorKey: 'tanggal', header: 'Tanggal', meta: { className: 'whitespace-nowrap' } },
    { id: 'peminjam', accessorKey: 'peminjam', header: 'Peminjam' },
    { id: 'barang', accessorKey: 'nama', header: 'Barang' },
    { id: 'catatan', accessorKey: 'catatan', header: 'Catatan' },
    { id: 'jam', accessorKey: 'jam', header: 'Jam', meta: { className: 'whitespace-nowrap' } },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rankStatus(rowA.original.status) - rankStatus(rowB.original.status),
    },
    { id: 'aksi', header: 'Aksi', enableSorting: false, meta: { className: 'whitespace-nowrap' } },
];

const data = computed(() => daftar.value);

const table = useTable({ features, columns, data });

const setujui = (item) => {
    if (memproses.value) {
        return;
    }

    memproses.value = item.id;

    router.post(
        route('admin.pengembalian.approve', item.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                memproses.value = null;
            },
        }
    );
};

const bukaModalTolak = (item) => {
    itemDitolak.value = item;
    alasan.value = '';
    modalTampil.value = true;
};

const tutupModal = () => {
    modalTampil.value = false;
    itemDitolak.value = null;
    alasan.value = '';
};

const tolak = () => {
    if (!itemDitolak.value || memproses.value) {
        return;
    }

    memproses.value = itemDitolak.value.id;

    router.post(
        route('admin.pengembalian.reject', itemDitolak.value.id),
        { alasan: alasan.value },
        {
            preserveScroll: true,
            onFinish: () => {
                memproses.value = null;
                tutupModal();
            },
        }
    );
};

const filterList = [
    { id: 'semua', label: 'Semua' },
    { id: 'pending', label: 'Menunggu' },
    { id: 'disetujui', label: 'Disetujui' },
    { id: 'ditolak', label: 'Ditolak' },
];
</script>

<template>
    <SidebarLayout title="Pengembalian">
        <Head title="Pengembalian - Admin" />

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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Persetujuan Pengembalian</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Setujui atau tolak pengajuan pengembalian dari peminjam</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(245,158,11,0.75)]"
                    >
                        Menunggu {{ jumlahMenunggu }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]"
                    >
                        Disetujui {{ jumlahDisetujui }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(244,63,94,0.75)]"
                    >
                        Ditolak {{ jumlahDitolak }}
                    </span>
                    <span class="hidden text-xs text-gray-500 sm:inline">Total {{ props.pengajuan.length }} pengajuan</span>
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

            <div class="flex flex-wrap items-center gap-1.5 border-b border-orange-100 px-4 pt-3 sm:px-6 sm:pt-4">
                <button
                    v-for="tab in filterList"
                    :key="tab.id"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                    :class="
                        filter === tab.id
                            ? 'bg-[#F97316] text-white shadow-[0_4px_10px_-4px_rgba(249,115,22,0.75)]'
                            : 'bg-gray-100 text-gray-600 hover:bg-orange-100 hover:text-[#C2410C]'
                    "
                    @click="filter = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="px-4 pt-3 sm:px-6 sm:pt-4">
                <DataTable :table="table" :data="daftar">
                    <template #cell-no="{ index }">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-extrabold text-gray-600">
                            {{ index + 1 }}
                        </span>
                    </template>

                    <template #cell-tanggal="{ value }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-700">{{ formatDate(value) }}</span>
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
                        <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">{{ row.kategori || '—' }}</p>
                    </template>

                    <template #cell-catatan="{ value }">
                        <span class="block max-w-[16rem] truncate text-sm text-gray-600" :title="value || ''">
                            {{ value || '—' }}
                        </span>
                    </template>

                    <template #cell-jam="{ value }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ value || '—' }}</span>
                    </template>

                    <template #cell-status="{ row }">
                        <span class="inline-flex flex-col gap-1">
                            <span class="inline-flex w-fit items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold" :class="badgeStatus[row.status]">
                                {{ labelStatus[row.status] || row.status }}
                            </span>
                            <span v-if="row.disetujui_pada" class="text-[10px] tabular-nums text-gray-500">
                                {{ row.disetujui_pada }}
                            </span>
                            <span v-else-if="row.ditolak_pada" class="text-[10px] tabular-nums text-gray-500">
                                {{ row.ditolak_pada }}
                            </span>
                        </span>
                    </template>

                    <template #cell-aksi="{ row }">
                        <div v-if="row.status === 'pending'" class="flex items-center justify-center gap-1.5">
                            <button
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-gray-500 transition hover:bg-emerald-100 hover:text-emerald-600 focus:outline-none disabled:opacity-50"
                                title="Setujui Pengembalian"
                                :disabled="memproses === row.id"
                                @click="setujui(row)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-gray-500 transition hover:bg-rose-100 hover:text-rose-600 focus:outline-none disabled:opacity-50"
                                title="Tolak Pengembalian"
                                :disabled="memproses === row.id"
                                @click="bukaModalTolak(row)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <span v-else class="inline-block text-xs text-gray-500">Selesai</span>
                    </template>

                    <template #empty>Belum ada pengajuan pengembalian.</template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Pengajuan yang disetujui menjadi data transaksi dan mengembalikan status barang ke tersedia. Pengajuan yang ditolak tidak masuk data transaksi.
            </div>
        </motion.div>

        <Teleport to="body">
            <div
                v-if="modalTampil"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="tutupModal"
            >
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <h3 class="text-base font-bold text-gray-900">Tolak Pengajuan Pengembalian</h3>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 shadow-2xs transition-all duration-200 hover:scale-105 hover:bg-rose-500 hover:text-white active:scale-95 focus:outline-none"
                            title="Tutup Modal"
                            @click="tutupModal"
                        >
                            <svg class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3 text-sm text-rose-800">
                            <p class="font-semibold">{{ itemDitolak?.nama }}</p>
                            <p class="mt-0.5 text-xs text-rose-600">
                                {{ itemDitolak?.peminjam }} · {{ itemDitolak?.kode }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700" for="alasan-tolak">Alasan penolakan (opsional)</label>
                            <textarea
                                id="alasan-tolak"
                                v-model="alasan"
                                rows="3"
                                maxlength="255"
                                placeholder="Contoh: Barang belum dibersihkan"
                                class="mt-1.5 w-full rounded-xl border-gray-300 text-sm shadow-sm placeholder:text-gray-400 focus:border-[#F97316] focus:ring-[#F97316]/40"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none"
                                @click="tutupModal"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none disabled:opacity-60"
                                :disabled="memproses === itemDitolak?.id"
                                @click="tolak"
                            >
                                Tolak Pengajuan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </SidebarLayout>
</template>
