<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { motion, AnimatePresence } from 'motion-v';
import { FwbDropdown, FwbListGroup, FwbListGroupItem } from 'flowbite-vue';
import NfcTidakTersediaModal from '@/Components/NfcTidakTersediaModal.vue';
import { mulaiScanNfc, nfcDidukung } from '@/nfc.js';

const props = defineProps({
    initialTab: {
        type: String,
        default: 'barang',
    },
    barang: {
        type: Array,
        default: () => [],
    },
    peminjam: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref(props.initialTab === 'peminjaman' ? 'peminjaman' : 'barang');

watch(
    () => props.initialTab,
    (newTab) => {
        if (newTab) {
            activeTab.value = newTab === 'peminjaman' ? 'peminjaman' : 'barang';
        }
    }
);

const switchTab = (tab) => {
    activeTab.value = tab;
    const routeName = tab === 'peminjaman' ? 'admin.dataBarangPeminjaman.peminjaman' : 'admin.dataBarangPeminjaman.barang';
    router.visit(route(routeName), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Data Barang & Peminjam berasal dari server (tabel barangs dan users)
const barangList = computed(() => props.barang);
const peminjamList = computed(() => props.peminjam);

// Pesan validasi terakhir dari server, hanya untuk form yang baru dikirim
const page = usePage();
const lastForm = ref('barang');
const pesanError = (form, kunci) => {
    if (lastForm.value !== form) return [];
    return kunci.map((key) => page.props.errors?.[key]).filter(Boolean);
};
const barangErrors = computed(() => pesanError('barang', ['nama', 'kategori', 'kode_ntag', 'lokasi', 'status']));
const peminjamErrors = computed(() => pesanError('peminjam', ['nama', 'nim', 'prodi', 'kontak', 'role', 'status']));

// Filters Data Barang
const searchBarang = ref('');
const filterKategoriBarang = ref('Semua Kategori');
const filterStatusBarang = ref('Semua Status');
const currentPageBarang = ref(1);
const itemsPerPageBarang = 5;

// Filters Data Peminjam
const searchPeminjam = ref('');
const filterRolePeminjam = ref('Mahasiswa');
const filterStatusPeminjam = ref('Semua Status');
const currentPagePeminjam = ref(1);
const itemsPerPagePeminjam = 5;

// Reset Filters
const resetBarangFilters = () => {
    searchBarang.value = '';
    filterKategoriBarang.value = 'Semua Kategori';
    filterStatusBarang.value = 'Semua Status';
    currentPageBarang.value = 1;
};

const resetPeminjamFilters = () => {
    searchPeminjam.value = '';
    filterRolePeminjam.value = 'Mahasiswa';
    filterStatusPeminjam.value = 'Semua Status';
    currentPagePeminjam.value = 1;
};

// Filtered Lists
const filteredBarang = computed(() => {
    return barangList.value.filter((item) => {
        const matchesSearch =
            item.nama.toLowerCase().includes(searchBarang.value.toLowerCase()) ||
            item.kode_ntag.toLowerCase().includes(searchBarang.value.toLowerCase()) ||
            item.lokasi.toLowerCase().includes(searchBarang.value.toLowerCase());
        const matchesKategori =
            filterKategoriBarang.value === 'Semua Kategori' || item.kategori === filterKategoriBarang.value;
        const matchesStatus =
            filterStatusBarang.value === 'Semua Status' || item.status === filterStatusBarang.value;
        return matchesSearch && matchesKategori && matchesStatus;
    });
});

const paginatedBarang = computed(() => {
    const start = (currentPageBarang.value - 1) * itemsPerPageBarang;
    return filteredBarang.value.slice(start, start + itemsPerPageBarang);
});

const filteredPeminjam = computed(() => {
    return peminjamList.value.filter((user) => {
        const matchesSearch =
            user.nama.toLowerCase().includes(searchPeminjam.value.toLowerCase()) ||
            user.nim.toLowerCase().includes(searchPeminjam.value.toLowerCase()) ||
            user.kontak.toLowerCase().includes(searchPeminjam.value.toLowerCase());
        const matchesRole =
            filterRolePeminjam.value === 'Semua Peran' || user.role === filterRolePeminjam.value;
        const matchesStatus =
            filterStatusPeminjam.value === 'Semua Status' || user.status === filterStatusPeminjam.value;
        return matchesSearch && matchesRole && matchesStatus;
    });
});

const paginatedPeminjam = computed(() => {
    const start = (currentPagePeminjam.value - 1) * itemsPerPagePeminjam;
    return filteredPeminjam.value.slice(start, start + itemsPerPagePeminjam);
});

// Modals State
const showModalBarang = ref(false);
const modalBarangMode = ref('add'); // 'add' | 'edit' | 'detail' | 'delete'
const selectedBarang = ref(null);
const barangForm = ref({
    nama: '',
    kategori: 'Elektronik',
    kode_ntag: '',
    lokasi: '',
    status: 'Tersedia',
});

// NFC Scan State untuk Tambah Barang
const isScanningNfc = ref(false);
const nfcScanStatus = ref('idle'); // 'idle' | 'scanning' | 'success' | 'error'
const nfcStatusMessage = ref('');
const showPanduanNfc = ref(false);
let hentikanScanNfc = null;

const startNfcScan = async () => {
    if (typeof hentikanScanNfc === 'function') {
        hentikanScanNfc();
        hentikanScanNfc = null;
    }

    if (!nfcDidukung()) {
        showPanduanNfc.value = true;
        nfcScanStatus.value = 'error';
        nfcStatusMessage.value =
            'NFC tidak tersedia. Halaman ini hanya bisa dipakai lewat Chrome di Android (HTTPS/localhost).';
        isScanningNfc.value = false;

        return;
    }

    isScanningNfc.value = true;
    nfcScanStatus.value = 'scanning';
    nfcStatusMessage.value = 'Silakan tempelkan stiker NFC / NTAG pada pembaca HP...';

    hentikanScanNfc = await mulaiScanNfc({
        onReading: (uid) => {
            hentikanScanNfc = null;
            barangForm.value.kode_ntag = uid;
            nfcScanStatus.value = 'success';
            nfcStatusMessage.value = 'Stiker NFC berhasil terbaca!';
            isScanningNfc.value = false;
        },
        onError: (pesan) => {
            hentikanScanNfc = null;
            nfcScanStatus.value = 'error';
            nfcStatusMessage.value = pesan;
            isScanningNfc.value = false;
        },
    });
};

const showModalPeminjam = ref(false);
const modalPeminjamMode = ref('edit'); // 'add' | 'edit' | 'detail' | 'delete'
const selectedPeminjam = ref(null);
const peminjamForm = ref({
    nama: '',
    nim: '',
    prodi: 'Teknologi Informasi',
    kontak: '',
    status: 'Aktif',
    role: 'Mahasiswa',
});

// Actions Barang
const openAddBarang = () => {
    modalBarangMode.value = 'add';
    barangForm.value = {
        nama: '',
        kategori: 'Elektronik',
        kode_ntag: '',
        lokasi: '',
        status: 'Tersedia',
    };
    nfcScanStatus.value = 'idle';
    nfcStatusMessage.value = nfcDidukung()
        ? 'Klik "Mulai Scan" untuk membaca stiker NFC'
        : 'NFC tidak tersedia. Halaman ini hanya bisa dipakai lewat Chrome di Android (HTTPS/localhost).';
    showModalBarang.value = true;
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('scan') === '1' || urlParams.get('openAdd') === '1') {
            openAddBarang();
        }
    }
});

const openEditBarang = (item) => {
    selectedBarang.value = item;
    modalBarangMode.value = 'edit';
    barangForm.value = { ...item };
    nfcScanStatus.value = 'success';
    nfcStatusMessage.value = 'Stiker terdaftar pada barang ini.';
    showModalBarang.value = true;
};

const openDetailBarang = (item) => {
    selectedBarang.value = item;
    modalBarangMode.value = 'detail';
    showModalBarang.value = true;
};

const openDeleteBarang = (item) => {
    selectedBarang.value = item;
    modalBarangMode.value = 'delete';
    showModalBarang.value = true;
};

const saveBarang = () => {
    lastForm.value = 'barang';

    const data = {
        nama: barangForm.value.nama,
        kategori: barangForm.value.kategori,
        kode_ntag: barangForm.value.kode_ntag,
        lokasi: barangForm.value.lokasi,
        status: barangForm.value.status,
    };

    const selesai = () => {
        showModalBarang.value = false;
    };

    if (modalBarangMode.value === 'add') {
        if (!data.kode_ntag) {
            nfcScanStatus.value = 'error';
            nfcStatusMessage.value = 'Stiker belum terbaca. Klik "Mulai Scan NFC" dan tempelkan stiker terlebih dahulu.';

            return;
        }

        router.post(route('admin.barang.store'), data, { preserveScroll: true, onSuccess: selesai });
    } else if (modalBarangMode.value === 'edit') {
        router.put(route('admin.barang.update', selectedBarang.value.id), data, { preserveScroll: true, onSuccess: selesai });
    } else if (modalBarangMode.value === 'delete') {
        router.delete(route('admin.barang.destroy', selectedBarang.value.id), { preserveScroll: true, onSuccess: selesai });
    }
};

// Actions Peminjam
const openEditPeminjam = (user) => {
    selectedPeminjam.value = user;
    modalPeminjamMode.value = 'edit';
    peminjamForm.value = { ...user };
    showModalPeminjam.value = true;
};

const openDetailPeminjam = (user) => {
    selectedPeminjam.value = user;
    modalPeminjamMode.value = 'detail';
    showModalPeminjam.value = true;
};

const openDeletePeminjam = (user) => {
    selectedPeminjam.value = user;
    modalPeminjamMode.value = 'delete';
    showModalPeminjam.value = true;
};

const savePeminjam = () => {
    lastForm.value = 'peminjam';

    const data = {
        nama: peminjamForm.value.nama,
        nim: peminjamForm.value.nim,
        prodi: peminjamForm.value.prodi,
        kontak: peminjamForm.value.kontak,
        role: peminjamForm.value.role,
        status: peminjamForm.value.status,
    };

    const selesai = () => {
        showModalPeminjam.value = false;
    };

    if (modalPeminjamMode.value === 'edit') {
        router.put(route('admin.peminjam.update', selectedPeminjam.value.id), data, { preserveScroll: true, onSuccess: selesai });
    } else if (modalPeminjamMode.value === 'delete') {
        router.delete(route('admin.peminjam.destroy', selectedPeminjam.value.id), { preserveScroll: true, onSuccess: selesai });
    }
};
</script>

<template>
    <SidebarLayout title="Data Barang & Peminjaman">
        <Head title="Data Barang & Peminjaman - Admin" />

        <div class="space-y-6">
            <!-- Header section & Breadcrumb -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Data Barang dan Peminjam</h1>
                    <p class="text-sm text-gray-500">Kelola data barang inventaris dan data pengguna sistem.</p>
                </div>

                <div class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <Link :href="route('dashboard')" class="transition hover:text-gray-600">Beranda</Link>
                    <span>&gt;</span>
                    <Link :href="route('admin.dataBarangPeminjaman')" class="transition hover:text-gray-600">Data Barang & Peminjaman</Link>
                    <span>&gt;</span>
                    <span class="text-gray-700">{{ activeTab === 'peminjaman' ? 'Data Peminjaman' : 'Data Barang' }}</span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        type="button"
                        class="group inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-bold transition-all"
                        :class="[
                            activeTab === 'barang'
                                ? 'border-[#F97316] text-[#F97316]'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        @click="switchTab('barang')"
                    >
                        <svg
                            class="h-5 w-5 transition-colors"
                            :class="activeTab === 'barang' ? 'text-[#F97316]' : 'text-gray-500 group-hover:text-gray-600'"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Data Barang</span>
                    </button>

                    <button
                        type="button"
                        class="group inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-bold transition-all"
                        :class="[
                            activeTab === 'peminjaman' || activeTab === 'peminjam'
                                ? 'border-[#F97316] text-[#F97316]'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                        ]"
                        @click="switchTab('peminjaman')"
                    >
                        <svg
                            class="h-5 w-5 transition-colors"
                            :class="activeTab === 'peminjaman' || activeTab === 'peminjam' ? 'text-[#F97316]' : 'text-gray-500 group-hover:text-gray-600'"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Data Peminjam</span>
                    </button>
                </nav>
            </div>

            <!-- Card 1: Data Barang -->
            <motion.div
                v-show="activeTab === 'barang' || activeTab === 'all'"
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.3 }"
            >
                <!-- Card Header -->
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-[#F97316] ring-1 ring-orange-200/50">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Data Barang</h2>
                            <p class="text-xs text-gray-500">Daftar seluruh barang inventaris yang terdaftar dalam sistem.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#F97316] px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-orange-500/25 transition hover:bg-[#EA580C] focus:outline-none focus:ring-2 focus:ring-[#FB923C]/40 active:scale-95"
                        @click="openAddBarang"
                    >
                        <svg class="h-4 w-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Tambah Barang</span>
                    </button>
                </div>

                <!-- Filters & Search Bar -->
                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div class="relative sm:col-span-6 lg:col-span-6">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchBarang"
                            type="text"
                            placeholder="Cari nama barang atau kode NTAG..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 pl-9 pr-4 py-2 text-xs text-gray-800 transition focus:border-[#F97316] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        />
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <fwb-dropdown
                            class="w-full"
                            :text="filterKategoriBarang"
                            close-inside
                            content-wrapper-class="w-full rounded-lg dark:bg-white"
                            trigger-wrapper-class="w-full"
                            trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs font-medium text-gray-700 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        >
                            <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                <fwb-list-group-item @click="filterKategoriBarang = 'Semua Kategori'">
                                    Semua Kategori
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterKategoriBarang = 'Elektronik'">
                                    Elektronik
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterKategoriBarang = 'Aksesoris'">
                                    Aksesoris
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterKategoriBarang = 'Alat Lab'">
                                    Alat Lab
                                </fwb-list-group-item>
                            </fwb-list-group>
                        </fwb-dropdown>
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <fwb-dropdown
                            class="w-full"
                            :text="filterStatusBarang"
                            close-inside
                            content-wrapper-class="w-full rounded-lg dark:bg-white"
                            trigger-wrapper-class="w-full"
                            trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs font-medium text-gray-700 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        >
                            <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                <fwb-list-group-item @click="filterStatusBarang = 'Semua Status'">
                                    Semua Status
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterStatusBarang = 'Tersedia'">
                                    Tersedia
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterStatusBarang = 'Dipinjam'">
                                    Dipinjam
                                </fwb-list-group-item>
                            </fwb-list-group>
                        </fwb-dropdown>
                    </div>

                    <div class="sm:col-span-12 lg:col-span-2">
                        <button
                            type="button"
                            class="w-full rounded-xl border border-gray-200 bg-white py-2 text-xs font-bold text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200"
                            @click="resetBarangFilters"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Table Data Barang -->
                <div class="overflow-x-auto rounded-xl border border-orange-100/70">
                    <table class="w-full min-w-[700px] text-left text-xs">
                        <thead>
                            <tr class="bg-[#FFF7ED] text-gray-700 border-b border-orange-100">
                                <th scope="col" class="py-3 px-4 font-bold w-12 text-center">No</th>
                                <th scope="col" class="py-3 px-4 font-bold">Nama Barang</th>
                                <th scope="col" class="py-3 px-4 font-bold">Kategori</th>
                                <th scope="col" class="py-3 px-4 font-bold">Kode NTAG</th>
                                <th scope="col" class="py-3 px-4 font-bold">Lokasi</th>
                                <th scope="col" class="py-3 px-4 font-bold text-center">Status</th>
                                <th scope="col" class="py-3 px-4 font-bold text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="(item, index) in paginatedBarang"
                                :key="item.id"
                                class="transition hover:bg-orange-50/40"
                            >
                                <td class="py-3.5 px-4 text-center font-medium text-gray-600">
                                    {{ (currentPageBarang - 1) * itemsPerPageBarang + index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900">
                                    {{ item.nama }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600 font-medium">
                                    {{ item.kategori }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-medium text-gray-700 tracking-wide">
                                    {{ item.kode_ntag }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ item.lokasi }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-block rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="
                                            item.status === 'Tersedia'
                                                ? 'bg-emerald-100/90 text-emerald-700 border border-emerald-200/50'
                                                : 'bg-amber-100/90 text-amber-700 border border-amber-200/50'
                                        "
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Detail (Blue) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-600 transition hover:bg-sky-200 focus:outline-none"
                                            title="Detail Barang"
                                            @click="openDetailBarang(item)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Edit (Amber) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-600 transition hover:bg-amber-200 focus:outline-none"
                                            title="Edit Barang"
                                            @click="openEditBarang(item)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        <!-- Delete (Red) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-rose-100 text-rose-600 transition hover:bg-rose-200 focus:outline-none"
                                            title="Hapus Barang"
                                            @click="openDeleteBarang(item)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paginatedBarang.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-500">
                                    Tidak ada data barang yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination -->
                <div class="mt-4 flex flex-col items-center justify-between gap-3 text-xs text-gray-500 sm:flex-row">
                    <div>
                        Menampilkan <span class="font-bold text-gray-700">1 - 5</span> dari <span class="font-bold text-gray-700">120</span> data
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 disabled:opacity-50"
                            :disabled="currentPageBarang === 1"
                            @click="currentPageBarang--"
                        >
                            &lt;
                        </button>
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#F97316] text-xs font-bold text-white shadow-sm"
                        >
                            1
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            2
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            3
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            4
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            5
                        </button>
                        <span class="px-1 text-gray-500">...</span>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            24
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50">
                            &gt;
                        </button>
                    </div>
                </div>
            </motion.div>

            <!-- Card 2: Data Peminjam -->
            <motion.div
                v-show="activeTab === 'peminjam' || activeTab === 'peminjaman' || activeTab === 'all'"
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.3, delay: 0.1 }"
            >
                <!-- Card Header -->
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-[#F97316] ring-1 ring-orange-200/50">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Data Peminjam</h2>
                            <p class="text-xs text-gray-500">Daftar pengguna yang dapat melakukan peminjaman barang.</p>
                        </div>
                    </div>


                </div>

                <!-- Filters & Search Bar -->
                <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div class="relative sm:col-span-6 lg:col-span-6">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchPeminjam"
                            type="text"
                            placeholder="Cari nama, NIM, atau kontak..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 pl-9 pr-4 py-2 text-xs text-gray-800 transition focus:border-[#F97316] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        />
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <fwb-dropdown
                            class="w-full"
                            :text="filterRolePeminjam"
                            close-inside
                            content-wrapper-class="w-full rounded-lg dark:bg-white"
                            trigger-wrapper-class="w-full"
                            trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs font-medium text-gray-700 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        >
                            <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                <fwb-list-group-item @click="filterRolePeminjam = 'Semua Peran'">
                                    Semua Peran
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterRolePeminjam = 'Mahasiswa'">
                                    Mahasiswa
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterRolePeminjam = 'Dosen'">
                                    Dosen
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterRolePeminjam = 'Staf'">
                                    Staf
                                </fwb-list-group-item>
                            </fwb-list-group>
                        </fwb-dropdown>
                    </div>

                    <div class="sm:col-span-3 lg:col-span-2">
                        <fwb-dropdown
                            class="w-full"
                            :text="filterStatusPeminjam"
                            close-inside
                            content-wrapper-class="w-full rounded-lg dark:bg-white"
                            trigger-wrapper-class="w-full"
                            trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs font-medium text-gray-700 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                        >
                            <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                <fwb-list-group-item @click="filterStatusPeminjam = 'Semua Status'">
                                    Semua Status
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterStatusPeminjam = 'Aktif'">
                                    Aktif
                                </fwb-list-group-item>
                                <fwb-list-group-item @click="filterStatusPeminjam = 'Nonaktif'">
                                    Nonaktif
                                </fwb-list-group-item>
                            </fwb-list-group>
                        </fwb-dropdown>
                    </div>

                    <div class="sm:col-span-12 lg:col-span-2">
                        <button
                            type="button"
                            class="w-full rounded-xl border border-gray-200 bg-white py-2 text-xs font-bold text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200"
                            @click="resetPeminjamFilters"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Table Data Peminjam -->
                <div class="overflow-x-auto rounded-xl border border-orange-100/70">
                    <table class="w-full min-w-[700px] text-left text-xs">
                        <thead>
                            <tr class="bg-[#FFF7ED] text-gray-700 border-b border-orange-100">
                                <th scope="col" class="py-3 px-4 font-bold w-12 text-center">No</th>
                                <th scope="col" class="py-3 px-4 font-bold">Nama Lengkap</th>
                                <th scope="col" class="py-3 px-4 font-bold">NIM</th>
                                <th scope="col" class="py-3 px-4 font-bold">Program Studi</th>
                                <th scope="col" class="py-3 px-4 font-bold">Kontak</th>
                                <th scope="col" class="py-3 px-4 font-bold text-center">Status</th>
                                <th scope="col" class="py-3 px-4 font-bold text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="(user, index) in paginatedPeminjam"
                                :key="user.id"
                                class="transition hover:bg-orange-50/40"
                            >
                                <td class="py-3.5 px-4 text-center font-medium text-gray-600">
                                    {{ (currentPagePeminjam - 1) * itemsPerPagePeminjam + index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900">
                                    {{ user.nama }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-medium text-gray-700">
                                    {{ user.nim }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ user.prodi }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-600 font-mono">
                                    {{ user.kontak }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-block rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="
                                            user.status === 'Aktif'
                                                ? 'bg-emerald-100/90 text-emerald-700 border border-emerald-200/50'
                                                : 'bg-rose-100/90 text-rose-700 border border-rose-200/50'
                                        "
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Detail (Blue) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-600 transition hover:bg-sky-200 focus:outline-none"
                                            title="Detail Peminjam"
                                            @click="openDetailPeminjam(user)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Edit (Amber) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-600 transition hover:bg-amber-200 focus:outline-none"
                                            title="Edit Peminjam"
                                            @click="openEditPeminjam(user)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        <!-- Delete (Red) -->
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-full bg-rose-100 text-rose-600 transition hover:bg-rose-200 focus:outline-none"
                                            title="Hapus Peminjam"
                                            @click="openDeletePeminjam(user)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paginatedPeminjam.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-500">
                                    Tidak ada data peminjam yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination -->
                <div class="mt-4 flex flex-col items-center justify-between gap-3 text-xs text-gray-500 sm:flex-row">
                    <div>
                        Menampilkan <span class="font-bold text-gray-700">1 - 5</span> dari <span class="font-bold text-gray-700">50</span> data
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 disabled:opacity-50"
                            :disabled="currentPagePeminjam === 1"
                            @click="currentPagePeminjam--"
                        >
                            &lt;
                        </button>
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#F97316] text-xs font-bold text-white shadow-sm"
                        >
                            1
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            2
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            3
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            4
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            5
                        </button>
                        <span class="px-1 text-gray-500">...</span>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white font-medium text-gray-600 transition hover:bg-gray-50">
                            10
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50">
                            &gt;
                        </button>
                    </div>
                </div>
            </motion.div>
        </div>

        <!-- Modal Barang -->
        <Teleport to="body">
            <div
                v-if="showModalBarang"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="showModalBarang = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between rounded-t-2xl border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <h3 class="text-base font-bold text-gray-900">
                            <span v-if="modalBarangMode === 'add'">Tambah Barang Baru</span>
                            <span v-else-if="modalBarangMode === 'edit'">Edit Data Barang</span>
                            <span v-else-if="modalBarangMode === 'detail'">Detail Barang</span>
                            <span v-else-if="modalBarangMode === 'delete'">Hapus Barang</span>
                        </h3>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-all duration-200 hover:bg-rose-500 hover:text-white hover:scale-105 active:scale-95 focus:outline-none shadow-2xs"
                            title="Tutup Modal"
                            @click="showModalBarang = false"
                        >
                            <svg class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form Add/Edit -->
                    <div v-if="modalBarangMode === 'add' || modalBarangMode === 'edit'" class="p-6 space-y-4 text-xs">
                        <ul v-if="barangErrors.length" class="space-y-1 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-600">
                            <li v-for="pesan in barangErrors" :key="pesan">{{ pesan }}</li>
                        </ul>
                        <!-- NFC Scan Area UI (Tambah & Edit Barang) -->
                        <div class="rounded-2xl border border-orange-100 bg-gradient-to-b from-orange-50/80 to-white p-4 shadow-xs">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="flex items-center gap-1.5 font-bold text-gray-800 text-xs">
                                    <svg class="h-4 w-4 text-[#F97316]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11c0 2.473.345 4.866.99 7.132" />
                                    </svg>
                                    Scan Stiker NFC
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                    :class="
                                        nfcScanStatus === 'scanning'
                                            ? 'bg-amber-100 text-amber-700 animate-pulse'
                                            : nfcScanStatus === 'success'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="nfcScanStatus === 'scanning' ? 'bg-amber-500' : nfcScanStatus === 'success' ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    {{ nfcScanStatus === 'scanning' ? 'Memindai...' : nfcScanStatus === 'success' ? 'Terbaca' : 'Siap Scan' }}
                                </span>
                            </div>

                            <!-- Interactive Visual Card -->
                            <div class="relative py-3.5 px-4 rounded-xl bg-white border border-gray-100 shadow-xs flex flex-col items-center justify-center text-center">
                                <div class="relative flex h-14 w-14 items-center justify-center mb-2">
                                    <div v-if="nfcScanStatus === 'scanning'" class="absolute inset-0 rounded-full bg-orange-400/20 animate-ping"></div>
                                    <div v-if="nfcScanStatus === 'scanning'" class="absolute -inset-2 rounded-full border border-orange-400/40 animate-pulse"></div>
                                    
                                    <div
                                        class="relative flex h-12 w-12 items-center justify-center rounded-2xl transition-all duration-300"
                                        :class="
                                            nfcScanStatus === 'success'
                                                ? 'bg-emerald-50 text-emerald-600 ring-2 ring-emerald-400/30 scale-105'
                                                : 'bg-orange-100 text-[#F97316]'
                                        "
                                    >
                                        <svg v-if="nfcScanStatus === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11c0 2.473.345 4.866.99 7.132" />
                                        </svg>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-600 font-medium mb-1.5">{{ nfcStatusMessage }}</p>

                                <div v-if="barangForm.kode_ntag" class="my-1 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 text-white font-mono text-xs shadow-xs">
                                    <span class="text-gray-500 text-[10px] uppercase tracking-wider">UID STIKER:</span>
                                    <span class="font-bold tracking-wider text-orange-400">{{ barangForm.kode_ntag }}</span>
                                </div>

                                <!-- Action Buttons Scan -->
                                <div class="mt-2.5 flex flex-wrap items-center justify-center gap-2">
                                    <!-- Tombol Utama Mulai Scan -->
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-[#EA580C] via-[#F97316] to-[#FDBA74] bg-[length:200%_auto] bg-left bg-no-repeat px-4 py-2 text-xs font-bold tracking-wide text-black shadow-md shadow-orange-600/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-right hover:shadow-lg hover:shadow-orange-600/40 focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-2 active:scale-95 disabled:opacity-60"
                                        :disabled="nfcScanStatus === 'scanning'"
                                        @click="startNfcScan"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11c0 2.473.345 4.866.99 7.132" />
                                        </svg>
                                        <span>
                                            <span v-if="nfcScanStatus === 'scanning'">Sedang Memindai...</span>
                                            <span v-else-if="nfcScanStatus === 'success'">Scan Stiker Ulang</span>
                                            <span v-else>Mulai Scan NFC</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Input: Nama Barang -->
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Nama Barang</label>
                            <input
                                v-model="barangForm.nama"
                                type="text"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                                placeholder="Masukkan nama barang"
                            />
                        </div>

                        <!-- Form Input: Kategori -->
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Kategori</label>
                            <fwb-dropdown
                                class="w-full"
                                :text="barangForm.kategori"
                                close-inside
                                content-wrapper-class="w-full rounded-lg dark:bg-white"
                                trigger-wrapper-class="w-full"
                                trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs text-gray-900 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                            >
                                <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                    <fwb-list-group-item @click="barangForm.kategori = 'Elektronik'">
                                        Elektronik
                                    </fwb-list-group-item>
                                    <fwb-list-group-item @click="barangForm.kategori = 'Aksesoris'">
                                        Aksesoris
                                    </fwb-list-group-item>
                                    <fwb-list-group-item @click="barangForm.kategori = 'Alat Lab'">
                                        Alat Lab
                                    </fwb-list-group-item>
                                    <fwb-list-group-item @click="barangForm.kategori = 'Fasilitas Ruang'">
                                        Fasilitas Ruang
                                    </fwb-list-group-item>
                                </fwb-list-group>
                            </fwb-dropdown>
                        </div>

                        <!-- Form Input: Lokasi Penyimpanan -->
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Lokasi Penyimpanan</label>
                            <input
                                v-model="barangForm.lokasi"
                                type="text"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20"
                                placeholder="Masukkan lokasi penyimpanan, contoh: Ruang IT"
                            />
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-4 flex justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50"
                                @click="showModalBarang = false"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="rounded-xl bg-[#F97316] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#EA580C] focus:outline-none focus:ring-2 focus:ring-[#F97316]/40"
                                @click="saveBarang"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>

                    <!-- View Detail -->
                    <div v-else-if="modalBarangMode === 'detail'" class="p-6 space-y-3 text-xs">
                        <div class="rounded-xl bg-orange-50/60 p-4 border border-orange-100 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-gray-900">{{ selectedBarang?.nama }}</div>
                                <div class="text-gray-500 font-mono text-[11px]">NTAG: {{ selectedBarang?.kode_ntag }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="selectedBarang?.status === 'Tersedia' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                {{ selectedBarang?.status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-gray-700">
                            <div><span class="font-semibold text-gray-500">Kategori:</span> {{ selectedBarang?.kategori }}</div>
                            <div><span class="font-semibold text-gray-500">Lokasi:</span> {{ selectedBarang?.lokasi }}</div>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="button" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200" @click="showModalBarang = false">Tutup</button>
                        </div>
                    </div>

                    <!-- Confirm Delete -->
                    <div v-else-if="modalBarangMode === 'delete'" class="p-6 text-xs text-center space-y-3">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">Konfirmasi Hapus</h4>
                        <p class="text-gray-500">Apakah Anda yakin ingin menghapus barang <span class="font-bold text-gray-800">{{ selectedBarang?.nama }}</span>?</p>

                        <div class="pt-4 flex justify-center gap-2">
                            <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50" @click="showModalBarang = false">Batal</button>
                            <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700" @click="saveBarang">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Peminjam -->
        <Teleport to="body">
            <div
                v-if="showModalPeminjam"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                @click.self="showModalPeminjam = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between rounded-t-2xl border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <h3 class="text-base font-bold text-gray-900">
                            <span v-if="modalPeminjamMode === 'add'">Tambah Peminjam Baru</span>
                            <span v-else-if="modalPeminjamMode === 'edit'">Edit Data Peminjam</span>
                            <span v-else-if="modalPeminjamMode === 'detail'">Detail Peminjam</span>
                            <span v-else-if="modalPeminjamMode === 'delete'">Hapus Peminjam</span>
                        </h3>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-all duration-200 hover:bg-rose-500 hover:text-white hover:scale-105 active:scale-95 focus:outline-none shadow-2xs"
                            title="Tutup Modal"
                            @click="showModalPeminjam = false"
                        >
                            <svg class="h-4 w-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Form Add/Edit -->
                    <div v-if="modalPeminjamMode === 'add' || modalPeminjamMode === 'edit'" class="p-6 space-y-4 text-xs">
                        <ul v-if="peminjamErrors.length" class="space-y-1 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-600">
                            <li v-for="pesan in peminjamErrors" :key="pesan">{{ pesan }}</li>
                        </ul>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                            <input v-model="peminjamForm.nama" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:border-[#F97316] focus:outline-none" placeholder="Nama peminjam" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">NIM / NIP</label>
                                <input v-model="peminjamForm.nim" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs font-mono focus:border-[#F97316] focus:outline-none" placeholder="210810101" />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Status</label>
                                <fwb-dropdown
                                    class="w-full"
                                    :text="peminjamForm.status"
                                    close-inside
                                    content-wrapper-class="w-full rounded-lg dark:bg-white"
                                    trigger-wrapper-class="w-full"
                                    trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-xs text-gray-900 focus:border-[#F97316] focus:outline-none"
                                >
                                    <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                                        <fwb-list-group-item @click="peminjamForm.status = 'Aktif'">
                                            Aktif
                                        </fwb-list-group-item>
                                        <fwb-list-group-item @click="peminjamForm.status = 'Nonaktif'">
                                            Nonaktif
                                        </fwb-list-group-item>
                                    </fwb-list-group>
                                </fwb-dropdown>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Program Studi / Unit</label>
                            <input v-model="peminjamForm.prodi" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs focus:border-[#F97316] focus:outline-none" placeholder="Teknologi Informasi" />
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">No. Kontak (WhatsApp)</label>
                            <input v-model="peminjamForm.kontak" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs font-mono focus:border-[#F97316] focus:outline-none" placeholder="0812 3456 7890" />
                        </div>

                        <div class="pt-4 flex justify-end gap-2">
                            <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50" @click="showModalPeminjam = false">Batal</button>
                            <button type="button" class="rounded-xl bg-[#F97316] px-4 py-2 text-xs font-semibold text-white hover:bg-[#EA580C]" @click="savePeminjam">Simpan</button>
                        </div>
                    </div>

                    <!-- View Detail -->
                    <div v-else-if="modalPeminjamMode === 'detail'" class="p-6 space-y-3 text-xs">
                        <div class="rounded-xl bg-orange-50/60 p-4 border border-orange-100 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-gray-900">{{ selectedPeminjam?.nama }}</div>
                                <div class="text-gray-500 font-mono text-[11px]">NIM: {{ selectedPeminjam?.nim }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700">
                                {{ selectedPeminjam?.status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-gray-700">
                            <div><span class="font-semibold text-gray-500">Program Studi:</span> {{ selectedPeminjam?.prodi }}</div>
                            <div><span class="font-semibold text-gray-500">Kontak:</span> {{ selectedPeminjam?.kontak }}</div>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="button" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200" @click="showModalPeminjam = false">Tutup</button>
                        </div>
                    </div>

                    <!-- Confirm Delete -->
                    <div v-else-if="modalPeminjamMode === 'delete'" class="p-6 text-xs text-center space-y-3">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900">Konfirmasi Hapus</h4>
                        <p class="text-gray-500">Apakah Anda yakin ingin menghapus peminjam <span class="font-bold text-gray-800">{{ selectedPeminjam?.nama }}</span>?</p>

                        <div class="pt-4 flex justify-center gap-2">
                            <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50" @click="showModalPeminjam = false">Batal</button>
                            <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700" @click="savePeminjam">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <NfcTidakTersediaModal :show="showPanduanNfc" @close="showPanduanNfc = false" />
    </SidebarLayout>
</template>
