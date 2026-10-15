<script setup>
import { motion } from 'motion-v';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'Belum ada data untuk ditampilkan pada halaman ini.',
    },
    icon: {
        type: String,
        default: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
    },
});
</script>

<template>
    <motion.div
        class="flex w-full flex-1 flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-5 py-10 text-center shadow-[0_18px_40px_-30px_rgba(15,23,42,0.45)] sm:rounded-3xl sm:px-6 sm:py-16 lg:py-24"
        :initial="{ opacity: 0, y: 12 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }"
    >
        <span
            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-500/30 sm:h-16 sm:w-16 sm:rounded-3xl"
        >
            <svg class="h-7 w-7 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
        </span>

        <h1 class="mt-4 text-lg font-extrabold tracking-tight text-gray-900 sm:mt-6 sm:text-xl">{{ title }}</h1>
        <p class="mt-2 max-w-md text-sm leading-relaxed text-gray-500">{{ description }}</p>
    </motion.div>
</template>
