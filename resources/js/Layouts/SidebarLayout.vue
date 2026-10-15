<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import AppFooter from '@/Components/AppFooter.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { isDesktop, sidebarOpen } from '@/Layouts/sidebarState.js';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const page = usePage();

const flash = computed(() => page.props.flash ?? {});

const pesan = computed(() => flash.value.success ?? flash.value.error ?? null);

const sukses = computed(() => Boolean(flash.value.success));

const tampilPesan = ref(false);

let pesanTimer = null;

watch(
    pesan,
    (nilai) => {
        window.clearTimeout(pesanTimer);

        if (!nilai) {
            tampilPesan.value = false;

            return;
        }

        tampilPesan.value = true;
        pesanTimer = window.setTimeout(() => {
            tampilPesan.value = false;
        }, 5000);
    },
    { immediate: true }
);

const tutupPesan = () => {
    window.clearTimeout(pesanTimer);
    tampilPesan.value = false;
};

const onDashboard = computed(() => route().current('dashboard'));

const showBackToDashboard = computed(() => !sidebarOpen.value && !onDashboard.value);

const syncViewport = () => {
    const desktop = window.matchMedia('(min-width: 1024px)').matches;


    if (desktop !== isDesktop.value) {
        isDesktop.value = desktop;
        sidebarOpen.value = desktop;
    }
};

onMounted(() => {
    syncViewport();
    window.addEventListener('resize', syncViewport);
});

onUnmounted(() => {
    window.removeEventListener('resize', syncViewport);
    window.clearTimeout(pesanTimer);
});
</script>

<template>
    <div class="min-h-dvh bg-gray-50">
        <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <Teleport to="body">
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="tampilPesan"
                    class="fixed right-4 top-20 z-50 flex max-w-sm items-start gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-sm sm:right-6"
                    :class="
                        sukses
                            ? 'border-emerald-200 bg-emerald-50/95 text-emerald-800'
                            : 'border-rose-200 bg-rose-50/95 text-rose-800'
                    "
                    role="status"
                >
                    <span
                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                        :class="sukses ? 'bg-emerald-500' : 'bg-rose-500'"
                    >
                        <svg v-if="sukses" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <svg v-else class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>

                    <p class="text-sm font-semibold leading-snug">{{ pesan }}</p>

                    <button
                        type="button"
                        class="shrink-0 rounded-full p-1 transition hover:bg-black/5 focus:outline-none"
                        aria-label="Tutup pesan"
                        @click="tutupPesan"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>
        </Teleport>

        <div
            class="flex min-h-dvh flex-col transition-[padding] duration-200 ease-out"
            :class="sidebarOpen && isDesktop ? 'lg:pl-64' : ''"
        >
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
                <div class="flex h-16 items-center gap-2 px-3 sm:gap-3 sm:px-6">
                    <Link
                        v-if="showBackToDashboard"
                        :href="route('dashboard')"
                        class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:border-[#FB923C]/50 hover:text-[#F97316] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#FB923C]/30"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        <span class="hidden sm:inline">Dashboard</span>
                    </Link>

                    <button
                        type="button"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-[#FB923C]/50 hover:text-[#F97316] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#FB923C]/30"
                        :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
                        :aria-expanded="sidebarOpen"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <span class="hidden h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white p-1 ring-1 ring-gray-200 sm:flex lg:hidden">
                        <img src="/logo-nfc.png" alt="Peminjaman NFC" class="h-full w-full object-contain" />
                    </span>

                    <span class="min-w-0 truncate text-sm font-bold tracking-tight text-gray-900">{{ title }}</span>
                </div>
            </header>

            <main class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
                <motion.div
                    class="flex w-full flex-1 flex-col"
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.4, ease: [0.22, 1, 0.36, 1] }"
                >
                    <slot />
                </motion.div>
            </main>

            <AppFooter />
        </div>
    </div>
</template>
