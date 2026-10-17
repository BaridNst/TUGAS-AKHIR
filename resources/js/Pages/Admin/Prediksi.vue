<script setup>
import { computed, ref } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { motion } from 'motion-v';
import { Head } from '@inertiajs/vue3';
import { FwbDropdown, FwbListGroup, FwbListGroupItem } from 'flowbite-vue';
import { createSortedRowModel, rowSortingFeature, sortFns, tableFeatures, useTable } from '@tanstack/vue-table';

const props = defineProps({
    prediksi: {
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

const cari = ref('');
const filterHasil = ref('semua');
const filterKategori = ref('semua');

const jenisNotifikasiOptions = [
    { id: 'terlambat_ringan', label: 'Terlambat 1-2 Hari' },
    { id: 'terlambat_berat', label: 'Terlambat 3+ Hari' },
    { id: 'barang_rusak', label: 'Barang Rusak' },
    { id: 'barang_hilang', label: 'Barang Hilang' },
    { id: 'pengingat_batas', label: 'Pengingat Batas Waktu' },
];

const kirimLoadingId = ref(null);
const kirimSuccessId = ref(null);
const kirimError = ref(null);
const selectedJenis = ref('terlambat_ringan');

const hasilList = [
    { id: 'semua', label: 'Semua' },
    { id: 'Tepat Waktu', label: 'Tepat Waktu' },
    { id: 'Terlambat', label: 'Terlambat' },
];

const daftar = computed(() => props.prediksi);

const kategoriList = computed(() => [...new Set(daftar.value.map((item) => item.kategori).filter(Boolean))].sort());

const labelKategori = computed(() =>
    filterKategori.value === 'semua' ? 'Semua Kategori' : filterKategori.value
);

const ringkas = computed(() => ({
    total: daftar.value.length,
    terlambat: daftar.value.filter((item) => item.label === 'Terlambat').length,
    tepat: daftar.value.filter((item) => item.label === 'Tepat Waktu').length,
}));

const hitungHasil = (id) => (id === 'semua' ? daftar.value.length : daftar.value.filter((item) => item.label === id).length);

const daftarTersaring = computed(() => {
    const kata = cari.value.trim().toLowerCase();

    return daftar.value.filter((item) => {
        const cocokHasil = filterHasil.value === 'semua' || item.label === filterHasil.value;
        const cocokKategori = filterKategori.value === 'semua' || item.kategori === filterKategori.value;
        const cocokCari =
            kata === '' ||
            [item.peminjam, item.nim, item.nama]
                .filter(Boolean)
                .some((nilai) => String(nilai).toLowerCase().includes(kata));

        return cocokHasil && cocokKategori && cocokCari;
    });
});

const getCsrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
};

const kirimWhatsApp = async (baris) => {
    if (kirimLoadingId.value === baris.id) return;

    const jenis = tentukanJenisDefault(baris);
    kirimLoadingId.value = baris.id;
    kirimSuccessId.value = null;
    kirimError.value = null;

    try {
        const response = await fetch(route('admin.prediksi.kirimWhatsApp'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                pengajuan_id: baris.id,
                jenis: jenis,
            }),
        });

        const data = await response.json();

        if (data.success) {
            kirimSuccessId.value = baris.id;
            setTimeout(() => {
                kirimSuccessId.value = null;
            }, 3000);
        } else {
            kirimError.value = data.message || 'Gagal mengirim WhatsApp';
            setTimeout(() => {
                kirimError.value = null;
            }, 5000);
        }
    } catch (err) {
        kirimError.value = 'Terjadi kesalahan jaringan';
        setTimeout(() => {
            kirimError.value = null;
        }, 5000);
    } finally {
        kirimLoadingId.value = null;
    }
};

const tentukanJenisDefault = (baris) => {
    if (baris.label === 'Terlambat') {
        return baris.sisaHari < -2 ? 'terlambat_berat' : 'terlambat_ringan';
    }
    return 'pengingat_batas';
};

const sedangKirim = (baris) => kirimLoadingId.value === baris.id;
const baruTerkirim = (baris) => kirimSuccessId.value === baris.id;

const features = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

const rankLabel = (label) => (label === 'Terlambat' ? 1 : 0);

const columns = [
    { id: 'no', header: 'No / ID', enableSorting: false, meta: { className: 'whitespace-nowrap text-center' } },
    {
        id: 'tanggal',
        header: 'Tgl Pinjam & Batas',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) =>
            String(rowA.original.batasKembali ?? '').localeCompare(String(rowB.original.batasKembali ?? '')),
    },
    { id: 'peminjam', accessorKey: 'peminjam', header: 'Peminjam' },
    { id: 'barang', accessorKey: 'nama', header: 'Barang' },
    {
        id: 'prediksi',
        accessorKey: 'label',
        header: 'Hasil Prediksi',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rankLabel(rowA.original.label) - rankLabel(rowB.original.label),
    },
    {
        id: 'probabilitas',
        accessorKey: 'probabilitas',
        header: 'Probabilitas',
        meta: { className: 'whitespace-nowrap' },
        sortFn: (rowA, rowB) => rowA.original.probabilitas - rowB.original.probabilitas,
    },
    { id: 'rekomendasi', accessorKey: 'aksi', header: 'Rekomendasi', meta: { className: 'whitespace-nowrap' } },
    { id: 'tindakan', header: 'Tombol Aksi', enableSorting: false, meta: { className: 'whitespace-nowrap' } },
];

const data = computed(() => daftarTersaring.value);

const table = useTable({ features, columns, data });
</script>

<template>
    <SidebarLayout title="Prediksi">
        <Head title="Prediksi - Admin" />

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
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">
                        Prediksi Keterlambatan Pengembalian Barang
                    </h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">
                        Sistem peringatan dini untuk transaksi yang sedang berjalan
                    </p>
                </div>
            </div>

            <!-- Info Model AI Random Forest -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-indigo-100 bg-gradient-to-r from-indigo-50/70 via-purple-50/40 to-orange-50/40 px-4 py-2.5 text-xs text-indigo-950 sm:px-6">
                <div class="flex items-center gap-2">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-white">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                    </span>
                    <span>Algoritma: <strong>Random Forest Classifier (Scikit-Learn, 100 Trees)</strong></span>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-gray-600">
                    <span class="rounded-md bg-white/80 px-2 py-0.5 font-medium shadow-2xs">Evaluasi Akurasi: <strong>75.00%</strong></span>
                    <span class="hidden sm:inline text-gray-400">|</span>
                    <span class="hidden md:inline">Fitur: Riwayat Pinjam, Terlambat, Durasi, Peran & Kategori</span>
                </div>
            </div>

            <!-- Ringkas & pencarian -->
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-orange-100 bg-orange-50/60 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(244,63,94,0.75)]">
                        Terlambat {{ ringkas.terlambat }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]">
                        Tepat Waktu {{ ringkas.tepat }}
                    </span>
                    <span class="hidden text-xs text-gray-500 sm:inline">{{ ringkas.total }} transaksi aktif dipinjam</span>
                </div>

                <div class="ml-auto flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <fwb-dropdown
                        class="w-full sm:w-44"
                        :text="labelKategori"
                        close-inside
                        content-wrapper-class="w-full rounded-lg dark:bg-white"
                        trigger-wrapper-class="w-full"
                        trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs text-gray-900 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                    >
                        <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                            <fwb-list-group-item @click="filterKategori = 'semua'">Semua Kategori</fwb-list-group-item>
                            <fwb-list-group-item
                                v-for="kategori in kategoriList"
                                :key="kategori"
                                @click="filterKategori = kategori"
                            >
                                {{ kategori }}
                            </fwb-list-group-item>
                        </fwb-list-group>
                    </fwb-dropdown>

                    <input
                        v-model="cari"
                        type="search"
                        placeholder="Cari peminjam / NIM / barang..."
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm placeholder:text-gray-400 focus:border-[#F97316] focus:ring-[#F97316]/40 sm:w-56"
                    />
                </div>
            </div>

            <!-- Filter hasil prediksi -->
            <div class="flex flex-wrap items-center gap-1.5 border-b border-orange-100 px-4 py-2.5 sm:gap-2 sm:px-6">
                <button
                    v-for="tab in hasilList"
                    :key="tab.id"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                    :class="
                        filterHasil === tab.id
                            ? 'bg-[#F97316] text-white shadow-[0_4px_10px_-4px_rgba(249,115,22,0.7)]'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    "
                    @click="filterHasil = tab.id"
                >
                    {{ tab.label }} ({{ hitungHasil(tab.id) }})
                </button>
            </div>

            <!-- Jenis Notifikasi Dropdown (global default) -->
            <div class="flex flex-wrap items-center gap-2 border-b border-orange-100 px-4 py-2.5 sm:px-6">
                <span class="text-xs font-semibold text-gray-500">Jenis Notifikasi Default:</span>
                <fwb-dropdown
                    :text="jenisNotifikasiOptions.find(o => o.id === selectedJenis.value)?.label || 'Pilih...'"
                    close-inside
                    content-wrapper-class="w-full rounded-lg dark:bg-white"
                    trigger-wrapper-class="w-full"
                    trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs text-gray-900 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                >
                    <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                        <fwb-list-group-item
                            v-for="opt in jenisNotifikasiOptions"
                            :key="opt.id"
                            @click="selectedJenis.value = opt.id"
                        >
                            {{ opt.label }}
                        </fwb-list-group-item>
                    </fwb-list-group>
                </fwb-dropdown>
            </div>

            <!-- Error Toast -->
            <div
                v-if="kirimError"
                class="mx-4 mt-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 sm:mx-6"
            >
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1 text-xs leading-relaxed text-rose-800">
                    {{ kirimError }}
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
                    @click="kirimError = null"
                >
                    Tutup
                </button>
            </div>

            <div class="px-4 pt-3 sm:px-6 sm:pt-4">
                <DataTable :table="table" :data="daftarTersaring">
                    <template #cell-no="{ row, index }">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-extrabold text-gray-600">
                            {{ index + 1 }}
                        </span>
                        <span class="mt-0.5 block max-w-[46px] truncate font-mono text-[9px] text-gray-400" :title="row.id">
                            {{ row.id }}
                        </span>
                    </template>

                    <template #cell-tanggal="{ row }">
                        <p class="whitespace-nowrap text-sm font-semibold leading-tight text-gray-900">
                            {{ formatDate(row.tanggalPinjam) }}
                        </p>
                        <p class="mt-0.5 whitespace-nowrap text-[11px] leading-tight text-gray-500">
                            batas {{ formatDate(row.batasKembali) }}
                        </p>
                        <span
                            class="mt-1 inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold ring-1"
                            :class="
                                row.sisaHari < 0
                                    ? 'bg-rose-50 text-rose-700 ring-rose-200/80'
                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                            "
                        >
                            {{ row.sisaHari < 0 ? `Lewat ${Math.abs(row.sisaHari)} hari` : `Sisa ${row.sisaHari} hari` }}
                        </span>
                    </template>

                    <template #cell-peminjam="{ row }">
                        <p class="truncate text-sm font-semibold leading-tight text-gray-900">{{ row.peminjam }}</p>
                        <p class="mt-0.5 truncate text-[11px] leading-tight text-gray-500">
                            {{ row.nim || 'Dosen' }}<span v-if="row.kontak"> · {{ row.kontak }}</span>
                        </p>
                    </template>

                    <template #cell-barang="{ row }">
                        <span
                            class="inline-flex items-center whitespace-nowrap rounded-lg bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold tracking-tight text-gray-700 ring-1 ring-gray-200"
                        >
                            {{ row.kode }}
                        </span>
                        <p class="mt-1 truncate text-sm font-semibold leading-tight text-gray-900">{{ row.nama }}</p>
                        <p class="mt-0.5 text-[11px] leading-tight text-gray-500">
                            {{ row.kategori || 'Tanpa kategori' }} · Jumlah {{ row.jumlah }}
                        </p>
                    </template>

                    <template #cell-prediksi="{ value }">
                        <span
                            class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold ring-1"
                            :class="
                                value === 'Terlambat'
                                    ? 'bg-rose-50 text-rose-700 ring-rose-200/80'
                                    : 'bg-emerald-50 text-emerald-700 ring-emerald-200/80'
                            "
                        >
                            {{ value }}
                        </span>
                    </template>

                    <template #cell-probabilitas="{ row }">
                        <span
                            class="block whitespace-nowrap text-sm font-extrabold tabular-nums"
                            :class="row.label === 'Terlambat' ? 'text-rose-600' : 'text-emerald-600'"
                        >
                            {{ row.persentase }}%
                        </span>
                        <span class="block whitespace-nowrap text-[10px] text-gray-400">
                            {{ row.label === 'Terlambat' ? 'risiko terlambat' : 'peluang tepat waktu' }}
                        </span>
                    </template>

                    <template #cell-rekomendasi="{ value }">
                        <span
                            class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-bold ring-1"
                            :class="
                                value === 'Kirim Notifikasi'
                                    ? 'bg-rose-500 text-white ring-rose-500/60'
                                    : value === 'Perlu Pengingat'
                                      ? 'bg-amber-50 text-amber-700 ring-amber-200/80'
                                      : 'bg-gray-100 text-gray-400 ring-gray-200'
                            "
                        >
                            {{ value === '-' ? '—' : value }}
                        </span>
                    </template>

                    <template #cell-tindakan="{ row }">
                        <button
                            v-if="row.aksi !== '-'"
                            type="button"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl px-3 py-1.5 text-[11px] font-extrabold transition-all duration-200"
                            :class="
                                sedangKirim(row)
                                    ? 'bg-blue-600 text-white cursor-wait'
                                    : baruTerkirim(row)
                                        ? 'bg-emerald-600 text-white cursor-default'
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-600/30 active:scale-95'
                            "
                            :disabled="sedangKirim(row)"
                            @click="kirimWhatsApp(row)"
                        >
                            <svg
                                v-if="sedangKirim(row)"
                                class="h-3.5 w-3.5 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            <svg
                                v-else-if="baruTerkirim(row)"
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <svg
                                v-else
                                class="h-3.5 w-3.5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                />
                            </svg>
                            <span v-if="sedangKirim(row)">Mengirim...</span>
                            <span v-else-if="baruTerkirim(row)">Terkirim</span>
                            <span v-else>Kirim WhatsApp</span>
                        </button>
                        <span v-else class="text-gray-300">—</span>
                    </template>

                    <template #empty>
                        {{
                            daftar.length === 0
                                ? 'Belum ada peminjaman aktif. Prediksi akan muncul begitu ada transaksi disetujui.'
                                : 'Tidak ada transaksi yang cocok dengan pencarian atau filter.'
                        }}
                    </template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs leading-relaxed text-gray-500 sm:px-6 sm:py-3">
                Prediksi keterlambatan dihitung dari riwayat ketepatan peminjam, sisa tenggat, dan pengalaman
                peminjaman. Tombol aksi mengirim notifikasi WhatsApp ke peminjam sesuai jenis pelanggaran.
                <span class="font-semibold text-emerald-600">WhatsApp Gateway aktif</span> (konfigurasi di .env).
            </div>
        </motion.div>
    </SidebarLayout>
</template>
