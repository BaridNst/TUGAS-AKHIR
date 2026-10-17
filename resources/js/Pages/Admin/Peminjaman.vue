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

const modalSetujuTampil = ref(false);
const itemDisetujui = ref(null);
const jadwal = ref({
    tanggal_mulai: '',
    tanggal_selesai: '',
    jam_mulai: '',
    jam_selesai: '',
});
const galatJadwal = ref('');

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
            [item.peminjam, item.nim, item.kode, item.nama, item.keperluan]
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
    { id: 'keperluan', accessorKey: 'keperluan', header: 'Keperluan' },
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

const getWaktuLokalSekarang = () => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const tanggal = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    const jam = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    return { tanggal, jam };
};

const tambahJamKeWaktu = (jamStr, jamTambah = 2, tanggalStr = '') => {
    if (!jamStr) return { jam: '', tanggal: tanggalStr };
    const [h, m] = jamStr.split(':').map(Number);
    const date = tanggalStr ? new Date(`${tanggalStr}T${jamStr}:00`) : new Date();
    date.setHours(h + jamTambah);
    date.setMinutes(m);

    const pad = (n) => String(n).padStart(2, '0');
    const hasilTanggal = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const hasilJam = `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    return { jam: hasilJam, tanggal: hasilTanggal };
};

const bukaModalSetuju = (item) => {
    itemDisetujui.value = item;
    const { tanggal: tglSekarang, jam: jamSekarang } = getWaktuLokalSekarang();

    // Sesuaikan jam mulai dengan jam lokal saat ini (contoh 19:53)
    const tanggalMulai = tglSekarang;
    const jamMulai = jamSekarang;
    const selesai = tambahJamKeWaktu(jamMulai, 2, tanggalMulai);

    jadwal.value = {
        tanggal_mulai: tanggalMulai,
        tanggal_selesai: selesai.tanggal,
        jam_mulai: jamMulai,
        jam_selesai: selesai.jam,
    };
    galatJadwal.value = '';
    modalSetujuTampil.value = true;
};

const sinkronkanJamSekarang = () => {
    const { tanggal, jam } = getWaktuLokalSekarang();
    jadwal.value.tanggal_mulai = tanggal;
    jadwal.value.jam_mulai = jam;
    const selesai = tambahJamKeWaktu(jam, 2, tanggal);
    jadwal.value.tanggal_selesai = selesai.tanggal;
    jadwal.value.jam_selesai = selesai.jam;
    galatJadwal.value = '';
};

const setDurasi = (jamTambah) => {
    if (!jadwal.value.jam_mulai) {
        sinkronkanJamSekarang();
    }
    const tglMulai = jadwal.value.tanggal_mulai || getWaktuLokalSekarang().tanggal;
    const selesai = tambahJamKeWaktu(jadwal.value.jam_mulai, jamTambah, tglMulai);
    jadwal.value.tanggal_selesai = selesai.tanggal;
    jadwal.value.jam_selesai = selesai.jam;
    galatJadwal.value = '';
};

const setSampaiJam = (targetJam) => {
    jadwal.value.jam_selesai = targetJam;
    if (!jadwal.value.tanggal_selesai) {
        jadwal.value.tanggal_selesai = jadwal.value.tanggal_mulai;
    }
    galatJadwal.value = '';
};

const infoDurasi = computed(() => {
    const { tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai } = jadwal.value;
    if (!tanggal_mulai || !tanggal_selesai || !jam_mulai || !jam_selesai) {
        return null;
    }
    const start = new Date(`${tanggal_mulai}T${jam_mulai}:00`);
    const end = new Date(`${tanggal_selesai}T${jam_selesai}:00`);
    const diffMs = end - start;
    if (diffMs <= 0) {
        return { valid: false, text: 'Jam selesai harus setelah jam mulai' };
    }
    const diffMins = Math.floor(diffMs / (1000 * 60));
    const days = Math.floor(diffMins / (60 * 24));
    const hours = Math.floor((diffMins % (60 * 24)) / 60);
    const mins = diffMins % 60;

    let parts = [];
    if (days > 0) parts.push(`${days} Hari`);
    if (hours > 0) parts.push(`${hours} Jam`);
    if (mins > 0) parts.push(`${mins} Menit`);
    if (parts.length === 0) parts.push('0 Menit');

    return { valid: true, text: parts.join(' ') };
});

const tutupModalSetuju = () => {
    modalSetujuTampil.value = false;
    itemDisetujui.value = null;
    galatJadwal.value = '';
};

const setujui = () => {
    if (!itemDisetujui.value || memproses.value) {
        return;
    }

    const { tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai } = jadwal.value;

    if (!tanggal_mulai || !tanggal_selesai || !jam_mulai || !jam_selesai) {
        galatJadwal.value = 'Tanggal mulai, tanggal selesai, jam mulai, dan jam selesai wajib diisi.';
        return;
    }

    if (tanggal_selesai < tanggal_mulai) {
        galatJadwal.value = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        return;
    }

    if (tanggal_selesai === tanggal_mulai && jam_selesai <= jam_mulai) {
        galatJadwal.value = 'Jam selesai harus setelah jam mulai pada tanggal yang sama.';
        return;
    }

    galatJadwal.value = '';
    memproses.value = itemDisetujui.value.id;

    router.post(
        route('admin.peminjaman.approve', itemDisetujui.value.id),
        { ...jadwal.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                tutupModalSetuju();
            },
            onError: (errors) => {
                galatJadwal.value = errors.tanggal_selesai || errors.jam_selesai || errors.tanggal_mulai || errors.jam_mulai || 'Jadwal tidak valid. Periksa kembali isian.';
            },
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
        route('admin.peminjaman.reject', itemDitolak.value.id),
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
    <SidebarLayout title="Peminjaman">
        <Head title="Peminjaman - Admin" />

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
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Persetujuan Peminjaman</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Setujui atau tolak pengajuan peminjaman dari peminjam</p>
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

                    <template #cell-tanggal="{ value, row }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-700">{{ formatDate(value) }}</span>
                        <p v-if="row.tanggal_selesai && row.tanggal_selesai !== value" class="mt-0.5 whitespace-nowrap text-[11px] tabular-nums text-gray-500">
                            s/d {{ formatDate(row.tanggal_selesai) }}
                        </p>
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

                    <template #cell-keperluan="{ value }">
                        <span class="block max-w-[16rem] truncate text-sm text-gray-600" :title="value || ''">
                            {{ value || '—' }}
                        </span>
                    </template>

                    <template #cell-jam="{ value, row }">
                        <span class="whitespace-nowrap text-sm tabular-nums text-gray-600">{{ value || '—' }}</span>
                        <p v-if="row.jamSelesai" class="mt-0.5 whitespace-nowrap text-[11px] tabular-nums text-gray-500">
                            s/d {{ row.jamSelesai }}
                        </p>
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
                                title="Setujui Peminjaman"
                                :disabled="memproses === row.id"
                                @click="bukaModalSetuju(row)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-gray-500 transition hover:bg-rose-100 hover:text-rose-600 focus:outline-none disabled:opacity-50"
                                title="Tolak Peminjaman"
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

                    <template #empty>Belum ada pengajuan peminjaman.</template>
                </DataTable>
            </div>

            <div class="border-t border-orange-100 px-4 py-2.5 text-xs text-gray-500 sm:px-6 sm:py-3">
                Saat menyetujui, tentukan tanggal mulai s/d tanggal selesai beserta jam pinjamnya. Pengajuan yang disetujui menjadi data transaksi dan mengubah status barang.
            </div>
        </motion.div>

        <Teleport to="body">
            <div
                v-if="modalSetujuTampil"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-md transition-all duration-300"
                @click.self="tutupModalSetuju"
            >
                <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 animate-in fade-in zoom-in-95 duration-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-emerald-50/70 via-teal-50/30 to-white px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/20">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Setujui Peminjaman</h3>
                                <p class="text-xs text-gray-500">Sesuaikan jadwal pinjam dan jam selesai pengembalian</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-all duration-150 hover:bg-gray-200 hover:text-gray-800 active:scale-95 focus:outline-none"
                            title="Tutup Modal"
                            @click="tutupModalSetuju"
                        >
                            <svg class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4 p-6">
                        <!-- Card Ringkasan Barang & Peminjam -->
                        <div class="rounded-2xl border border-emerald-100/80 bg-gradient-to-br from-emerald-50/70 via-emerald-50/30 to-white p-4 shadow-xs">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-md bg-emerald-600 px-2 py-0.5 text-[11px] font-bold tracking-wider text-white shadow-xs">
                                            {{ itemDisetujui?.kode }}
                                        </span>
                                        <span v-if="itemDisetujui?.kategori" class="text-xs font-semibold text-emerald-800/80">
                                            {{ itemDisetujui.kategori }}
                                        </span>
                                    </div>
                                    <h4 class="mt-1.5 truncate text-base font-extrabold text-gray-900">
                                        {{ itemDisetujui?.nama }}
                                    </h4>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600">
                                        <span class="inline-flex items-center gap-1 font-semibold text-gray-800">
                                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ itemDisetujui?.peminjam }}
                                        </span>
                                        <span v-if="itemDisetujui?.nim" class="text-gray-300">·</span>
                                        <span v-if="itemDisetujui?.nim" class="font-mono text-gray-500">
                                            NIM: {{ itemDisetujui.nim }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">
                                        Diajukan: {{ itemDisetujui?.jam || '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Baris Kontrol Waktu & Tombol Sinkronisasi Cepat -->
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-600">Atur Jadwal Peminjaman</span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs transition hover:border-emerald-300 hover:bg-emerald-100 active:scale-95"
                                title="Perbarui jam mulai ke waktu lokal saat ini"
                                @click="sinkronkanJamSekarang"
                            >
                                <svg class="h-3.5 w-3.5 animate-pulse text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Sinkronkan Jam Sekarang
                            </button>
                        </div>

                        <!-- Grid Form Jadwal -->
                        <div class="grid grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700" for="jadwal-mulai">
                                    Tanggal Mulai
                                </label>
                                <input
                                    id="jadwal-mulai"
                                    v-model="jadwal.tanggal_mulai"
                                    type="date"
                                    class="mt-1.5 w-full rounded-xl border-gray-300 text-sm shadow-xs focus:border-emerald-500 focus:ring-emerald-500/40"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700" for="jadwal-selesai">
                                    Tanggal Selesai
                                </label>
                                <input
                                    id="jadwal-selesai"
                                    v-model="jadwal.tanggal_selesai"
                                    type="date"
                                    :min="jadwal.tanggal_mulai"
                                    class="mt-1.5 w-full rounded-xl border-gray-300 text-sm shadow-xs focus:border-emerald-500 focus:ring-emerald-500/40"
                                />
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700" for="jam-mulai">
                                        Jam Mulai (Pinjam)
                                    </label>
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.2 rounded">
                                        Sekarang
                                    </span>
                                </div>
                                <input
                                    id="jam-mulai"
                                    v-model="jadwal.jam_mulai"
                                    type="time"
                                    class="mt-1.5 w-full rounded-xl border-gray-300 font-mono text-sm font-semibold shadow-xs focus:border-emerald-500 focus:ring-emerald-500/40"
                                />
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700" for="jam-selesai">
                                        Jam Selesai (Batas)
                                    </label>
                                    <span v-if="infoDurasi?.valid" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded">
                                        {{ infoDurasi.text }}
                                    </span>
                                </div>
                                <input
                                    id="jam-selesai"
                                    v-model="jadwal.jam_selesai"
                                    type="time"
                                    class="mt-1.5 w-full rounded-xl border-gray-300 font-mono text-sm font-semibold shadow-xs focus:border-emerald-500 focus:ring-emerald-500/40"
                                />
                            </div>
                        </div>

                        <!-- Chip Preset Durasi Cepat -->
                        <div>
                            <p class="mb-1.5 text-[11px] font-bold text-gray-500">Pilih Cepat Durasi Pinjam:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    type="button"
                                    v-for="dur in [
                                        { label: '+1 Jam', hours: 1 },
                                        { label: '+2 Jam (Standar)', hours: 2 },
                                        { label: '+3 Jam', hours: 3 },
                                        { label: '+4 Jam', hours: 4 },
                                    ]"
                                    :key="dur.label"
                                    @click="setDurasi(dur.hours)"
                                    class="rounded-lg border border-gray-200 bg-gray-50/80 px-2.5 py-1 text-xs font-semibold text-gray-700 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95"
                                >
                                    {{ dur.label }}
                                </button>
                                <button
                                    type="button"
                                    @click="setSampaiJam('17:00')"
                                    class="rounded-lg border border-gray-200 bg-gray-50/80 px-2.5 py-1 text-xs font-semibold text-gray-700 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 active:scale-95"
                                >
                                    Sampai 17:00
                                </button>
                            </div>
                        </div>

                        <!-- Live Duration Info Bar -->
                        <div v-if="infoDurasi" class="pt-0.5">
                            <div
                                v-if="infoDurasi.valid"
                                class="flex items-center justify-between rounded-xl border border-teal-100 bg-teal-50/60 px-3.5 py-2.5 text-xs text-teal-900"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 font-bold text-[10px] text-teal-700">⏱</span>
                                    <span>
                                        Total Durasi: <strong class="font-extrabold text-teal-800">{{ infoDurasi.text }}</strong> (Batas kembali: {{ jadwal.jam_selesai }})
                                    </span>
                                </div>
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ infoDurasi.text }}</span>
                            </div>
                        </div>

                        <!-- Alert Galat -->
                        <p v-if="galatJadwal" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-700">
                            {{ galatJadwal }}
                        </p>

                        <!-- Footer Aksi -->
                        <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 pt-4">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none"
                                @click="tutupModalSetuju"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-600/25 transition hover:from-emerald-700 hover:to-teal-700 active:scale-95 focus:outline-none disabled:opacity-60"
                                :disabled="memproses === itemDisetujui?.id || (infoDurasi && !infoDurasi.valid)"
                                @click="setujui"
                            >
                                <svg v-if="memproses === itemDisetujui?.id" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>{{ memproses === itemDisetujui?.id ? 'Memproses...' : 'Setujui Peminjaman' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="modalTampil"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="tutupModal"
            >
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <h3 class="text-base font-bold text-gray-900">Tolak Pengajuan Peminjaman</h3>
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
                                placeholder="Contoh: Barang sedang diperbaiki"
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
