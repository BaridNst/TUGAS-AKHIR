<script setup>
import { ref, computed } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { motion } from 'motion-v';
import { Head, Link } from '@inertiajs/vue3';
import { FwbDropdown, FwbListGroup, FwbListGroupItem } from 'flowbite-vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalBarang: 0,
            tersedia: 0,
            sedangDipinjam: 0,
            pengajuanPeminjaman: 0,
            pengajuanPengembalian: 0,
            terlambat: 0,
        }),
    },
    grafikTransaksi: {
        type: [Object, Array],
        default: () => ({}),
    },
    kategoriBarang: {
        type: Array,
        default: () => [],
    },
    barangTerpopuler: {
        type: Array,
        default: () => [],
    },
    pengajuanTerbaru: {
        type: Array,
        default: () => [],
    },
    notifikasi: {
        type: Array,
        default: () => [],
    },
    prediksi: {
        type: Array,
        default: () => [],
    },
});

const timeframe = ref('6 Bulan Terakhir');

const barChartData = computed(() => {
    let list = [];
    if (Array.isArray(props.grafikTransaksi)) {
        list = props.grafikTransaksi;
    } else if (props.grafikTransaksi && typeof props.grafikTransaksi === 'object') {
        list = props.grafikTransaksi[timeframe.value] || props.grafikTransaksi['6 Bulan Terakhir'] || [];
    }
    return list.map((item) => ({
        month: item.bulan,
        year: item.tahun,
        peminjaman: item.peminjaman,
        pengembalian: item.pengembalian,
    }));
});

const chartMax = computed(() => {
    const max = Math.max(...barChartData.value.flatMap((item) => [item.peminjaman, item.pengembalian]), 0);
    return Math.max(Math.ceil(max / 10) * 10, 10);
});

const barWidth = (value) => `${Math.min(100, (value / chartMax.value) * 100)}%`;

const hoveredMonth = ref(null);

const warnaKategori = ['#F97316', '#FB923C', '#FDBA74', '#FED7AA', '#E5E7EB'];
const kelasKategori = ['bg-[#F97316]', 'bg-[#FB923C]', 'bg-[#FDBA74]', 'bg-[#FED7AA]', 'bg-gray-300'];

const categoryData = computed(() =>
    props.kategoriBarang.map((item, idx) => ({
        name: item.nama,
        percentage: item.persentase,
        color: warnaKategori[idx % warnaKategori.length],
        class: kelasKategori[idx % kelasKategori.length],
    })),
);

const donutSegments = computed(() => {
    const radius = 40;
    const strokeWidth = 14;
    const circumference = 2 * Math.PI * radius;
    const gap = (5 / 360) * circumference;
    let start = 0;

    return categoryData.value.map((cat) => {
        const arc = (cat.percentage / 100) * circumference;
        const visualArc = Math.max(arc - gap, strokeWidth + 1);
        const dash = Math.max(visualArc - strokeWidth, 1);
        const dashOffset = -(start + strokeWidth / 2);
        start += arc;

        return {
            ...cat,
            dashArray: `${dash} ${circumference - dash}`,
            dashOffset,
        };
    });
});

const activeSegment = ref(null);

const popularItems = computed(() => {
    const maxCount = Math.max(...props.barangTerpopuler.map((item) => item.jumlah), 1);

    return props.barangTerpopuler.map((item, idx) => ({
        rank: idx + 1,
        name: item.nama,
        count: item.jumlah,
        maxCount,
    }));
});

const lencanaStatus = {
    Pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80',
    Disetujui: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80',
    Ditolak: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80',
};

const recentSubmissions = computed(() =>
    props.pengajuanTerbaru.map((item) => ({
        id: item.id,
        type: item.jenis,
        item: item.barang,
        user: item.peminjam,
        time: item.waktu,
        status: item.status,
        statusBadgeClass: lencanaStatus[item.status] ?? lencanaStatus.Pending,
        iconBg: item.jenis === 'Pengembalian'
            ? 'bg-rose-50 text-rose-600 border border-rose-100'
            : 'bg-orange-50 text-orange-600 border border-orange-100',
        isReturn: item.jenis === 'Pengembalian',
    })),
);

const warnaIkon = {
    clock: 'bg-amber-100/80 text-amber-600',
    return: 'bg-rose-100/80 text-rose-600',
    alert: 'bg-red-100/80 text-red-600',
    document: 'bg-blue-100/80 text-blue-600',
};

const notifications = computed(() =>
    props.notifikasi.map((item) => ({
        id: item.id,
        title: item.judul,
        user: item.peminjam,
        time: item.waktu,
        iconBg: warnaIkon[item.ikon] ?? warnaIkon.document,
        iconType: item.ikon,
    })),
);

const ringkasPrediksi = computed(() => ({
    total: props.prediksi.length,
    terlambat: props.prediksi.filter((item) => item.label === 'Terlambat').length,
    tepat: props.prediksi.filter((item) => item.label === 'Tepat Waktu').length,
}));

const risikoTertinggi = computed(() => props.prediksi.slice(0, 3));
</script>

<template>
    <SidebarLayout title="Dashboard">
        <Head title="Dashboard Admin" />

        <div class="space-y-4 sm:space-y-6">
            <!-- Header Welcome & Banner Card -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 sm:text-2xl lg:text-3xl">
                        Halo, Admin!
                    </h1>
                    <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">
                        Berikut ringkasan informasi sistem peminjaman barang NFC.
                    </p>
                </div>

                <div
                    class="relative flex items-center justify-between gap-3 overflow-hidden rounded-2xl border border-orange-200/70 bg-gradient-to-r from-orange-50 via-amber-50 to-orange-100/70 px-3 py-2.5 shadow-xs sm:gap-4 sm:px-4 sm:py-3"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-bold leading-snug text-gray-800 sm:text-sm">
                            Kelola Inventaris<br class="hidden sm:inline" />
                            Lebih Mudah dengan NFC
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#F97316] shadow-md shadow-orange-500/15 ring-1 ring-orange-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric Summary Cards Grid (6 cards) -->
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3.5 xl:grid-cols-6">
                <!-- Card 1: Total Barang -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.05 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-100/80 text-[#F97316]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Total Barang</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.totalBarang }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Seluruh barang terdaftar</p>
                    </div>
                </motion.div>

                <!-- Card 2: Tersedia -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.1 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100/80 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Tersedia</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.tersedia }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Siap dipinjam</p>
                    </div>
                </motion.div>

                <!-- Card 3: Sedang Dipinjam -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.15 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100/80 text-amber-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Sedang Dipinjam</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.sedangDipinjam }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Dalam peminjaman</p>
                    </div>
                </motion.div>

                <!-- Card 4: Pengajuan Peminjaman -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.2 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100/80 text-purple-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Pengajuan Peminjaman</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.pengajuanPeminjaman }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Menunggu persetujuan</p>
                    </div>
                </motion.div>

                <!-- Card 5: Pengajuan Pengembalian -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.25 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100/80 text-rose-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Pengajuan Pengembalian</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.pengajuanPengembalian }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Menunggu persetujuan</p>
                    </div>
                </motion.div>

                <!-- Card 6: Terlambat -->
                <motion.div
                    class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 shadow-xs transition-all hover:shadow-md"
                    :initial="{ opacity: 0, y: 10 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.3, delay: 0.3 }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100/80 text-red-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-3">
                        <p class="text-[11px] font-semibold text-gray-500 sm:text-xs">Terlambat</p>
                        <p class="mt-0.5 text-xl font-black text-gray-900 sm:text-2xl">{{ props.stats.terlambat }}</p>
                        <p class="hidden mt-1 text-[11px] font-medium text-gray-500 sm:block">Perlu ditindaklanjuti</p>
                    </div>
                </motion.div>
            </div>

            <!-- Prediksi Keterlambatan (peringatan dini) -->
            <motion.div
                class="overflow-hidden rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5"
                :initial="{ opacity: 0, y: 10 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.3, delay: 0.35 }"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3 sm:pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                            <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 sm:text-base">Prediksi Keterlambatan</h2>
                            <p class="hidden text-[11px] font-medium text-gray-500 sm:block">
                                Sistem peringatan dini untuk peminjaman yang sedang berjalan
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-[11px] font-bold text-white shadow-[0_4px_10px_-4px_rgba(244,63,94,0.75)]">
                            Terlambat {{ ringkasPrediksi.terlambat }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-white shadow-[0_4px_10px_-4px_rgba(16,185,129,0.75)]">
                            Tepat Waktu {{ ringkasPrediksi.tepat }}
                        </span>
                        <Link
                            :href="route('admin.prediksi')"
                            class="inline-flex min-h-11 items-center text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline"
                        >
                            Lihat Semua
                        </Link>
                    </div>
                </div>

                <div v-if="risikoTertinggi.length" class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div
                        v-for="baris in risikoTertinggi"
                        :key="baris.id"
                        class="rounded-2xl border p-3 transition hover:shadow-sm"
                        :class="
                            baris.label === 'Terlambat'
                                ? 'border-rose-100 bg-rose-50/50'
                                : 'border-emerald-100 bg-emerald-50/40'
                        "
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold ring-1"
                                :class="
                                    baris.label === 'Terlambat'
                                        ? 'bg-rose-50 text-rose-700 ring-rose-200/80'
                                        : 'bg-emerald-50 text-emerald-700 ring-emerald-200/80'
                                "
                            >
                                {{ baris.label }}
                            </span>
                            <span
                                class="text-xs font-extrabold tabular-nums"
                                :class="baris.label === 'Terlambat' ? 'text-rose-600' : 'text-emerald-600'"
                            >
                                {{ baris.persentase }}%
                            </span>
                        </div>

                        <p class="mt-2 truncate text-xs font-bold text-gray-900">{{ baris.peminjam }}</p>
                        <p class="mt-0.5 truncate text-[11px] font-medium text-gray-500">{{ baris.nama }}</p>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-1.5">
                            <span
                                class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold ring-1"
                                :class="
                                    baris.sisaHari < 0
                                        ? 'bg-rose-50 text-rose-700 ring-rose-200/80'
                                        : 'bg-gray-100 text-gray-600 ring-gray-200'
                                "
                            >
                                {{ baris.sisaHari < 0 ? `Lewat ${Math.abs(baris.sisaHari)} hari` : `Sisa ${baris.sisaHari} hari` }}
                            </span>
                            <span v-if="baris.aksi !== '-'" class="text-[10px] font-bold text-amber-600">
                                {{ baris.aksi }}
                            </span>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="mt-4 flex items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/60 px-4 py-6 text-center text-xs font-semibold text-gray-400"
                >
                    Belum ada peminjaman aktif untuk diprediksi.
                </div>

                <p v-if="ringkasPrediksi.total > 3" class="mt-3 text-[11px] font-medium text-gray-400">
                    Menampilkan 3 transaksi dengan risiko tertinggi dari {{ ringkasPrediksi.total }} transaksi aktif.
                </p>
            </motion.div>

            <!-- Middle Section: 3 Columns Grid (Grafik Transaksi, Kategori Barang, Barang Terpopuler) -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-12">
                <!-- Grafik Transaksi (5 cols) -->
                <div class="flex flex-col rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5 lg:col-span-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                                <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-900 sm:text-base">Grafik Transaksi</h2>
                        </div>

                        <div class="flex items-center gap-3">
                            <fwb-dropdown
                                :text="timeframe"
                                close-inside
                                content-wrapper-class="w-full rounded-lg dark:bg-white"
                                trigger-wrapper-class="w-full"
                                trigger-class="w-44 justify-between gap-1.5 rounded-xl border border-gray-200 bg-white py-1.5 pl-3 pr-2.5 text-left text-xs font-semibold text-gray-700 shadow-2xs focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                            >
                                <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                    <fwb-list-group-item @click="timeframe = '6 Bulan Terakhir'">
                                        6 Bulan Terakhir
                                    </fwb-list-group-item>
                                    <fwb-list-group-item @click="timeframe = '3 Bulan Terakhir'">
                                        3 Bulan Terakhir
                                    </fwb-list-group-item>
                                    <fwb-list-group-item @click="timeframe = '1 Tahun'">
                                        1 Tahun
                                    </fwb-list-group-item>
                                </fwb-list-group>
                            </fwb-dropdown>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="mt-3.5 flex items-center justify-end gap-4 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1.5 text-gray-600">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#F97316]"></span>
                            Peminjaman
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-gray-600">
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                            Pengembalian
                        </span>
                    </div>

                    <!-- Chart Container: reui c-chart-12 (vertical layout / horizontal bars) -->
                    <div
                        v-if="barChartData.length"
                        class="mt-4 flex flex-1 flex-col justify-center transition-all duration-300"
                        :class="barChartData.length > 6 ? 'gap-1.5 sm:gap-2' : 'gap-3 sm:gap-4'"
                        @mouseleave="hoveredMonth = null"
                    >
                        <div
                            v-for="(item, idx) in barChartData"
                            :key="idx"
                            class="group relative flex items-center gap-2.5 sm:gap-3"
                            @mouseenter="hoveredMonth = idx"
                        >
                            <span class="w-7 shrink-0 text-right text-[11px] font-semibold text-gray-500 sm:text-xs">
                                {{ item.month }}
                            </span>

                            <div class="flex min-w-0 flex-1 flex-col gap-[2px] pr-4 sm:pr-6">
                                <div
                                    class="rounded-[2px] bg-[#F97316] transition-all duration-500 group-hover:bg-orange-600"
                                    :class="barChartData.length > 6 ? 'h-2 sm:h-2.5' : 'h-3'"
                                    :style="{ width: barWidth(item.peminjaman) }"
                                ></div>
                                <div
                                    class="rounded-[2px] bg-yellow-400 transition-all duration-500 group-hover:brightness-105"
                                    :class="barChartData.length > 6 ? 'h-2 sm:h-2.5' : 'h-3'"
                                    :style="{ width: barWidth(item.pengembalian) }"
                                ></div>
                            </div>

                            <!-- Tooltip (ChartTooltipContent, indicator="dot") -->
                            <div
                                v-if="hoveredMonth === idx"
                                class="pointer-events-none absolute bottom-full left-10 z-20 min-w-40 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
                            >
                                <div class="mb-1.5 border-b border-gray-100 pb-1.5 font-medium text-gray-900">
                                    {{ item.month }} {{ item.year }}
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex w-full items-center justify-between gap-4">
                                        <span class="inline-flex items-center gap-1.5 text-gray-500">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-[2px] bg-[#F97316]"></span>
                                            Peminjaman
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ item.peminjaman }}</span>
                                    </div>
                                    <div class="flex w-full items-center justify-between gap-4">
                                        <span class="inline-flex items-center gap-1.5 text-gray-500">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-[2px] bg-yellow-400"></span>
                                            Pengembalian
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ item.pengembalian }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 flex flex-1 items-center justify-center text-xs font-semibold text-gray-400"
                    >
                        Belum ada data transaksi.
                    </div>
                </div>

                <!-- Kategori Barang (3.5 cols -> lg:col-span-3.5 or col-span-3) -->
                <div class="flex flex-col rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5 lg:col-span-3">
                    <div class="flex items-center gap-2.5 border-b border-gray-100 pb-3 sm:pb-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                            <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-gray-900 sm:text-base">Kategori Barang</h2>
                    </div>

                    <!-- Donut Chart: reui c-chart-22 (active segment donut with center label) -->
                    <div
                        class="relative my-4 flex flex-1 flex-col items-center justify-center"
                        @mouseleave="activeSegment = null"
                    >
                        <div class="relative flex h-36 w-36 items-center justify-center sm:h-40 sm:w-40">
                            <svg class="h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                                <circle
                                    v-for="(seg, idx) in donutSegments"
                                    :key="idx"
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    fill="transparent"
                                    :stroke="seg.color"
                                    stroke-width="14"
                                    stroke-linecap="round"
                                    :stroke-dasharray="seg.dashArray"
                                    :stroke-dashoffset="seg.dashOffset"
                                    class="cursor-pointer transition-transform duration-300"
                                    :style="{
                                        transformBox: 'fill-box',
                                        transformOrigin: 'center',
                                        transform: activeSegment === idx ? 'scale(1.07)' : 'scale(1)',
                                    }"
                                    @mouseenter="activeSegment = idx"
                                />
                            </svg>

                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-2xl font-bold tabular-nums text-gray-900">{{ props.stats.totalBarang }}</span>
                                <span class="mt-0.5 text-xs text-gray-500">Barang</span>
                            </div>

                            <!-- Tooltip (ChartTooltipContent) -->
                            <div
                                v-if="activeSegment !== null"
                                class="pointer-events-none absolute right-0 top-0 z-20 min-w-40 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
                            >
                                <div class="flex w-full items-center justify-between gap-4">
                                    <span class="inline-flex items-center gap-1.5 text-gray-500">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-[2px]"
                                            :style="{ backgroundColor: categoryData[activeSegment].color }"
                                        ></span>
                                        {{ categoryData[activeSegment].name }}
                                    </span>
                                    <span class="font-semibold tabular-nums text-gray-900">
                                        {{ categoryData[activeSegment].percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Category Legend List -->
                        <div v-if="categoryData.length" class="mt-4 w-full space-y-2">
                            <div
                                v-for="(cat, idx) in categoryData"
                                :key="idx"
                                class="flex cursor-pointer items-center justify-between rounded-lg px-1.5 py-1 text-xs font-semibold transition-colors"
                                :class="activeSegment === idx ? 'bg-orange-50' : ''"
                                @mouseenter="activeSegment = idx"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: cat.color }"></span>
                                    <span class="text-gray-600">{{ cat.name }}</span>
                                </div>
                                <span class="font-bold tabular-nums text-gray-900">{{ cat.percentage }}%</span>
                            </div>
                        </div>
                        <div v-else class="mt-4 text-center text-xs font-semibold text-gray-400">
                            Belum ada barang terdaftar.
                        </div>
                    </div>
                </div>

                <!-- Barang Terpopuler (4 cols -> lg:col-span-4) -->
                <div class="flex flex-col rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5 lg:col-span-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                                <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-900 sm:text-base">Barang Terpopuler</h2>
                        </div>
                        <Link
                            :href="route('admin.dataBarang')"
                            class="inline-flex min-h-11 items-center text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline"
                        >
                            Lihat Semua
                        </Link>
                    </div>

                    <!-- Items Progress List -->
                    <div v-if="popularItems.length" class="mt-4 flex flex-1 flex-col justify-around space-y-2.5 sm:space-y-3.5">
                        <div
                            v-for="item in popularItems"
                            :key="item.rank"
                            class="flex items-center justify-between gap-3 text-xs"
                        >
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-orange-50 font-bold text-orange-600 sm:h-6 sm:w-6">
                                {{ item.rank }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex justify-between font-bold text-gray-800">
                                    <span class="truncate">{{ item.name }}</span>
                                </div>
                                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        class="h-full rounded-full bg-[#F97316] transition-all duration-500"
                                        :style="{ width: `${(item.count / item.maxCount) * 100}%` }"
                                    ></div>
                                </div>
                            </div>

                            <span class="shrink-0 font-bold text-gray-500">{{ item.count }} kali</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 flex flex-1 items-center justify-center text-xs font-semibold text-gray-400"
                    >
                        Belum ada data peminjaman.
                    </div>
                </div>
            </div>

            <!-- Bottom Section: 3 Columns Grid (Pengajuan Terbaru, Notifikasi Sistem, NFC Scan Banner) -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-12">
                <!-- Pengajuan Terbaru (4 cols) -->
                <div class="flex flex-col rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5 lg:col-span-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                                <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-900 sm:text-base">Pengajuan Terbaru</h2>
                        </div>
                        <Link
                            :href="route('admin.peminjaman')"
                            class="inline-flex min-h-11 items-center text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline"
                        >
                            Lihat Semua
                        </Link>
                    </div>

                    <div v-if="recentSubmissions.length" class="mt-4 flex flex-1 flex-col justify-between space-y-2 sm:space-y-3">
                        <div
                            v-for="sub in recentSubmissions"
                            :key="sub.id"
                            class="flex items-center justify-between gap-3 rounded-xl p-1.5 transition hover:bg-gray-50/80 sm:p-2"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl" :class="sub.iconBg">
                                    <svg v-if="!sub.isReturn" class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-gray-900">
                                        {{ sub.type }} - {{ sub.item }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] font-medium text-gray-500">
                                        {{ sub.user }} <span class="text-gray-500">&middot;</span> {{ sub.time }}
                                    </p>
                                </div>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="sub.statusBadgeClass">
                                {{ sub.status }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 flex flex-1 items-center justify-center text-xs font-semibold text-gray-400"
                    >
                        Belum ada pengajuan masuk.
                    </div>
                </div>

                <!-- Notifikasi Sistem (4 cols) -->
                <div class="flex flex-col rounded-3xl border border-gray-100 bg-white p-4 shadow-xs sm:p-5 lg:col-span-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100/70 text-orange-600">
                                <svg class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a2.384 2.384 0 01-4.714 0m5.714-2.142A2.384 2.384 0 0112 18.75a2.384 2.384 0 01-3.857-1.668m9.571-3.571V11a6.75 6.75 0 00-13.5 0v2.511l-1.286 1.928A1.125 1.125 0 003.857 17.25h16.286a1.125 1.125 0 00.93-1.811l-1.286-1.928z" />
                                </svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-900 sm:text-base">Notifikasi Sistem</h2>
                        </div>
                        <Link
                            :href="route('admin.notifikasi')"
                            class="inline-flex min-h-11 items-center text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline"
                        >
                            Lihat Semua
                        </Link>
                    </div>

                    <div v-if="notifications.length" class="mt-4 flex flex-1 flex-col justify-between space-y-2 sm:space-y-3">
                        <div
                            v-for="notif in notifications"
                            :key="notif.id"
                            class="flex items-center justify-between gap-3 rounded-xl p-1.5 transition hover:bg-gray-50/80 sm:p-2"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl" :class="notif.iconBg">
                                    <svg v-if="notif.iconType === 'clock'" class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="notif.iconType === 'return'" class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                    <svg v-else-if="notif.iconType === 'alert'" class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-gray-900">{{ notif.title }}</p>
                                    <p class="mt-0.5 text-[11px] font-medium text-gray-500">Peminjam: {{ notif.user }}</p>
                                </div>
                            </div>

                            <span class="shrink-0 text-[11px] font-semibold text-gray-500">{{ notif.time }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 flex flex-1 items-center justify-center text-xs font-semibold text-gray-400"
                    >
                        Belum ada notifikasi.
                    </div>
                </div>

                <!-- NFC Scan CTA Banner Card (4 cols) -->
                <div
                    class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-orange-200/80 bg-gradient-to-br from-[#FFF9F6] via-[#FFF3ED] to-[#FFE7DA] p-4 shadow-xs sm:p-6 lg:col-span-4"
                >
                    <!-- Background Wave Decoration -->
                    <div class="pointer-events-none absolute right-[-20px] top-[-20px] opacity-20">
                        <div class="h-44 w-44 rounded-full border-8 border-orange-500/40"></div>
                        <div class="absolute inset-4 rounded-full border-8 border-orange-500/30"></div>
                    </div>

                    <div>
                        <h2 class="max-w-[220px] text-base font-black leading-snug text-gray-900 sm:text-lg">
                            Gunakan NFC untuk Tambah barang Lebih Cepat
                        </h2>
                        <p class="mt-2 max-w-[240px] text-xs font-medium leading-relaxed text-gray-600">
                            Tempelkan perangkat Anda pada tag NFC barang.
                        </p>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-3 sm:mt-6 sm:gap-4">
                        <Link
                            :href="route('admin.dataBarangPeminjaman.barang', { scan: 1 })"
                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#EA580C] via-[#F97316] to-[#FDBA74] bg-[length:200%_auto] bg-left bg-no-repeat px-5 py-3 text-xs font-bold tracking-wide text-black shadow-lg shadow-orange-600/30 transition-all duration-300 hover:-translate-y-0.5 hover:bg-right hover:scale-105 hover:shadow-xl hover:shadow-orange-600/40 focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-2 active:scale-95 active:translate-y-0"
                        >
                            Mulai Scan
                        </Link>

                        <!-- Phone NFC graphic -->
                        <div class="relative flex h-16 w-14 items-center justify-center rounded-2xl bg-white p-1.5 shadow-md ring-1 ring-orange-200 sm:h-20 sm:w-16">
                            <div class="flex h-full w-full items-center justify-center rounded-xl bg-[#EA580C] text-white">
                                <svg class="h-8 w-8 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.303 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Dots -->
                    <div class="mt-4 flex items-center justify-center gap-1.5 sm:mt-6">
                        <span class="h-1.5 w-1.5 rounded-full bg-orange-300"></span>
                        <span class="h-1.5 w-5 rounded-full bg-orange-500"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-orange-300"></span>
                    </div>
                </div>
            </div>
        </div>
    </SidebarLayout>
</template>

