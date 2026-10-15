<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import ScanNfcPanel from '@/Components/ScanNfcPanel.vue';
import { motion } from 'motion-v';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    barang: {
        type: Array,
        default: () => [],
    },
});

const submitting = ref(false);

const ajukan = (payload) => {
    router.post(route('pengembalian.store'), payload, {
        preserveScroll: true,
        onStart: () => {
            submitting.value = true;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <SidebarLayout title="Pengembalian">
        <Head title="Pengembalian" />

        <motion.div
            class="flex flex-1 flex-col overflow-hidden rounded-2xl border border-orange-100/80 bg-white shadow-[0_18px_40px_-26px_rgba(249,115,22,0.45)]"
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Pengembalian</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Kembalikan barang dengan memindai tag NFC barang</p>
                </div>

                <span
                    class="ml-auto hidden shrink-0 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200/80 sm:inline-flex"
                >
                    {{ props.barang.length }} barang sedang anda pinjam
                </span>
            </div>

            <ScanNfcPanel
                description="Tempelkan HP anda pada tag/stiker NFC barang yang sedang anda pinjam untuk memulai proses pengembalian."
                :items="props.barang"
                :submitting="submitting"
                submit-label="Ajukan Pengembalian"
                note-label="Catatan kondisi barang"
                note-placeholder="Contoh: Kondisi baik, kelengkapan lengkap"
                empty-text="Anda tidak memiliki barang yang sedang dipinjam."
                @submit="ajukan"
            />
        </motion.div>
    </SidebarLayout>
</template>
