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
    router.post(route('peminjaman.store'), payload, {
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
    <SidebarLayout title="Peminjaman">
        <Head title="Peminjaman" />

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
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-gray-900">Peminjaman</h1>
                    <p class="hidden truncate text-sm text-gray-500 sm:block">Mulai peminjaman dengan memindai tag NFC barang</p>
                </div>

                <span
                    class="ml-auto hidden shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200/80 sm:inline-flex"
                >
                    {{ props.barang.length }} barang siap dipinjam
                </span>
            </div>

            <ScanNfcPanel
                description="Tempelkan HP anda pada tag/stiker NFC barang untuk memulai proses peminjaman."
                :items="props.barang"
                :submitting="submitting"
                submit-label="Ajukan Peminjaman"
                note-label="Keperluan"
                note-placeholder="Contoh: Praktikum jaringan komputer"
                empty-text="Semua barang sedang dipinjam. Coba lagi nanti."
                @submit="ajukan"
            />
        </motion.div>
    </SidebarLayout>
</template>
