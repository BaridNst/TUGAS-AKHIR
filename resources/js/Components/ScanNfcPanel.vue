<script setup>
import { computed, ref, watch } from 'vue';
import NfcTidakTersediaModal from '@/Components/NfcTidakTersediaModal.vue';
import { mulaiScanNfc, nfcDidukung, uidNfc } from '@/nfc.js';

const props = defineProps({
    description: {
        type: String,
        default: 'Tempelkan HP anda pada tag/stiker NFC barang untuk memulai proses peminjaman.',
    },
    items: {
        type: Array,
        default: () => [],
    },
    submitLabel: {
        type: String,
        default: 'Ajukan Peminjaman',
    },
    noteLabel: {
        type: String,
        default: 'Keperluan',
    },
    notePlaceholder: {
        type: String,
        default: 'Contoh: Praktikum jaringan komputer',
    },
    submitting: {
        type: Boolean,
        default: false,
    },
    emptyText: {
        type: String,
        default: 'Tidak ada barang yang bisa diajukan saat ini.',
    },
});

const emit = defineEmits(['submit']);

const nfcTersedia = nfcDidukung();

const scanning = ref(false);
const statusNfc = ref('idle'); // 'idle' | 'scanning' | 'terbaca' | 'error'
const pesanNfc = ref(
    nfcTersedia
        ? ''
        : 'NFC tidak tersedia di perangkat ini. Halaman hanya bisa dipindai lewat Chrome di Android.',
);
const uidTerbaca = ref('');
const tagTerpilih = ref('');
const catatan = ref('');
const showPanduanNfc = ref(false);

let hentikanScan = null;

const hentikan = () => {
    if (typeof hentikanScan === 'function') {
        hentikanScan();
    }

    hentikanScan = null;
    scanning.value = false;
};

watch(
    () => props.items,
    (items) => {
        if (!items.length) {
            tagTerpilih.value = '';
            uidTerbaca.value = '';
            hentikan();

            return;
        }

        // Barang hasil scan tidak ada lagi (sudah dipinjam/dihapus) → minta scan ulang
        if (tagTerpilih.value && !items.some((item) => item.nfcUid === tagTerpilih.value)) {
            tagTerpilih.value = '';
            uidTerbaca.value = '';
            statusNfc.value = 'idle';
        }
    },
    { immediate: true },
);

const labelTagTerpilih = computed(() => {
    const item = props.items.find((barang) => barang.nfcUid === tagTerpilih.value);

    return item ? `${item.kode} — ${item.nama}` : '';
});

const terimaTag = (uid) => {
    uidTerbaca.value = uid;

    const item = props.items.find((barang) => uidNfc(barang.nfcUid) === uid);

    if (!item) {
        tagTerpilih.value = '';
        statusNfc.value = 'error';
        pesanNfc.value = `Tag ${uid} belum terdaftar sebagai barang. Hubungi admin untuk mendaftarkan stiker, lalu pindai ulang.`;

        return;
    }

    tagTerpilih.value = item.nfcUid;
    statusNfc.value = 'terbaca';
    pesanNfc.value = '';
};

const toggleScan = async () => {
    if (scanning.value) {
        hentikan();

        return;
    }

    if (!nfcTersedia) {
        showPanduanNfc.value = true;

        return;
    }

    statusNfc.value = 'scanning';
    pesanNfc.value = '';
    scanning.value = true;

    hentikanScan = await mulaiScanNfc({
        onReading: (uid) => {
            hentikanScan = null;
            scanning.value = false;
            terimaTag(uid);
        },
        onError: (pesan) => {
            hentikanScan = null;
            scanning.value = false;
            statusNfc.value = 'error';
            pesanNfc.value = pesan;
        },
    });
};

const ajukan = () => {
    if (props.submitting || !tagTerpilih.value) {
        return;
    }

    emit('submit', {
        nfc_uid: tagTerpilih.value,
        catatan: catatan.value.trim(),
    });
};
</script>

<template>
    <div class="flex flex-1 flex-col items-center justify-center px-5 py-8 text-center sm:px-6 sm:py-10">
        <div class="relative">
            <span class="absolute -inset-6 animate-ping rounded-[2rem] bg-orange-300/40" aria-hidden="true"></span>
            <span
                class="absolute -inset-3 animate-ping rounded-[2rem] bg-orange-200/60 [animation-delay:150ms]"
                aria-hidden="true"
            ></span>
            <span
                class="relative flex h-28 w-28 items-center justify-center rounded-[2rem] bg-orange-500 text-white shadow-xl shadow-orange-500/40 sm:h-32 sm:w-32"
            >
                <svg class="h-14 w-14 sm:h-16 sm:w-16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.348 14.651a3.75 3.75 0 010-5.303m5.303 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788"
                    />
                </svg>
            </span>
        </div>

        <h2 class="mt-6 text-lg font-extrabold tracking-tight text-gray-900 sm:mt-8 sm:text-xl">
            Siap Scan <span class="text-[#F97316]">NFC</span>
        </h2>

        <p class="mt-1.5 text-sm leading-normal text-gray-500 sm:mt-2 sm:max-w-md sm:leading-relaxed">
            {{ description }}
        </p>

        <button
            type="button"
            class="group mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#EA580C] via-[#F97316] to-[#FDBA74] bg-[length:200%_auto] bg-left bg-no-repeat px-6 py-3.5 text-sm font-bold tracking-wide text-black shadow-lg shadow-orange-600/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-right hover:shadow-xl hover:shadow-orange-600/40 focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-2 active:translate-y-0 active:scale-95 sm:mt-7 sm:w-auto sm:px-8"
            :aria-pressed="scanning"
            @click="toggleScan"
        >
            <svg
                class="h-5 w-5 transition-transform duration-300 group-hover:scale-110"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11c0 2.473.345 4.866.99 7.132"
                />
            </svg>
            {{ scanning ? 'Hentikan Scan' : 'Mulai Scan Sekarang' }}
        </button>

        <p
            v-if="scanning"
            class="mt-4 inline-flex items-center gap-2 rounded-full bg-orange-50 px-3.5 py-2 text-xs font-semibold text-[#C2410C] ring-1 ring-orange-100 sm:mt-5 sm:px-4"
        >
            <span class="h-2 w-2 animate-pulse rounded-full bg-[#F97316]"></span>
            Mendeteksi tag NFC — tempelkan HP anda ke tag barang
        </p>

        <p
            v-else-if="pesanNfc"
            class="mt-4 inline-flex max-w-md items-start gap-2 rounded-2xl bg-rose-50 px-3.5 py-2 text-left text-xs font-semibold leading-relaxed text-rose-600 ring-1 ring-rose-100 sm:mt-5 sm:px-4"
        >
            {{ pesanNfc }}
        </p>

        <div
            v-if="!items.length"
            class="mt-4 w-full max-w-md rounded-2xl border border-orange-100 bg-orange-50/60 px-4 py-5 text-sm text-gray-600 sm:mt-5"
        >
            {{ emptyText }}
        </div>

        <div
            v-else-if="tagTerpilih"
            class="mt-4 w-full max-w-md rounded-2xl border border-orange-100 bg-white p-4 text-left shadow-sm sm:mt-5 sm:p-5"
        >
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#9A3412]">
                Tag terdeteksi (hasil scan NFC)
            </p>

            <div
                class="mt-2 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 font-mono text-xs text-white shadow-xs"
            >
                <span class="text-[10px] uppercase tracking-wider text-gray-500">UID STIKER:</span>
                <span class="font-bold tracking-wider text-orange-400">{{ uidTerbaca }}</span>
            </div>

            <label class="mt-3 block text-xs font-bold text-gray-700">Barang</label>
            <div class="mt-1.5 w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">
                {{ labelTagTerpilih }}
            </div>

            <label class="mt-4 block text-xs font-bold text-gray-700" for="catatan-scan">{{ noteLabel }}</label>
            <input
                id="catatan-scan"
                v-model="catatan"
                type="text"
                maxlength="255"
                :placeholder="notePlaceholder"
                class="mt-1.5 w-full rounded-xl border-gray-300 text-sm shadow-sm placeholder:text-gray-500 focus:border-[#F97316] focus:ring-[#F97316]/40"
            />

            <button
                type="button"
                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="submitting || !tagTerpilih"
                @click="ajukan"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                {{ submitting ? 'Mengirim Pengajuan...' : submitLabel }}
            </button>

            <p class="mt-2.5 text-center text-[11px] leading-relaxed text-gray-500">
                Pengajuan akan masuk ke antrean dan menunggu persetujuan admin.
            </p>
        </div>

        <NfcTidakTersediaModal :show="showPanduanNfc" @close="showPanduanNfc = false" />
    </div>
</template>
