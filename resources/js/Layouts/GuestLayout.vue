<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

defineProps({
    subtitle: {
        type: String,
        default: 'Masuk untuk melanjutkan',
    },
});

const slides = ['/NFCBOS.webp', '/stikerNFC.webp'];
const current = ref(0);
let timer = null;

const next = () => {
    current.value = (current.value + 1) % slides.length;
};

onMounted(() => {
    timer = setInterval(next, 4000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <!-- PRO UI/UX: split dark/light harmony, no scroll, orange accent -->
    <div class="h-dvh overflow-hidden bg-[#FB923C]">
        <!-- Mobile -->
        <div class="flex h-dvh flex-col overflow-hidden bg-[#FB923C] lg:hidden">
            <div class="flex flex-col items-center px-6 pt-3 sm:pt-4">
                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white p-2 shadow-sm ring-1 ring-black/5 sm:h-12 sm:w-12">
                    <img src="/logo-nfc.png" alt="Peminjaman NFC" class="h-full w-full object-contain" />
                </div>
                <Link href="/" class="mt-0.5 inline-flex min-h-9 items-center text-base font-bold tracking-tight text-white sm:mt-1"> Peminjaman NFC </Link>
            </div>
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden px-4 py-3 sm:px-5 sm:py-4">
                <div class="m-auto w-full rounded-[20px] border border-black/[0.06] bg-white px-4 py-4 shadow-[0_8px_32px_rgba(0,0,0,0.06)] sm:px-5 sm:py-5">
                    <slot />
                </div>
            </div>
            <div class="px-4 py-1.5 text-center text-[11px] font-medium leading-tight text-white/80 sm:py-2">
                Copyright &copy; 2026 PeminjamanNFC. developed by <span class="font-bold text-[#FB923C]">IdeologTech</span>
            </div>
        </div>

        <!-- Desktop PRO -->
        <div class="hidden h-screen overflow-hidden lg:flex">
            <!-- KIRI - Dark premium, menyatu dengan NFCBOS -->
            <div class="relative flex w-[52%] flex-col overflow-visible bg-[#050507]">
                <!-- Mesh gradient background -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#0B1A3A] via-black to-black"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,rgba(59,130,246,0.18),transparent_60%)]"></div>
                <!-- Subtle grid -->
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[length:48px_48px]"></div>
                <!-- Orange accent line top -->
                <div class="absolute left-0 top-0 h-[2px] w-full bg-gradient-to-r from-transparent via-[#FB923C]/50 to-transparent"></div>

                <!-- Image slider - tiap gambar terkunci di posisinya -->
                <div class="relative z-10 flex flex-1 items-center justify-start overflow-visible py-8 pl-0 pr-6 xl:pr-8">
                    <div class="relative flex w-full flex-col items-center justify-center">
                        <!-- Glow behind -->
                        <div class="absolute left-1/2 top-1/2 h-[380px] w-[380px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#3B82F6]/12 blur-[60px] xl:h-[480px] xl:w-[480px]"></div>
                        <div class="relative flex h-[46vh] w-full items-center overflow-visible xl:h-[52vh]">
                            <img
                                src="/NFCBOS.webp"
                                alt="Peminjaman NFC"
                                width="1368"
                                height="775"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                                class="absolute inset-0 h-full w-full object-contain object-left drop-shadow-[0_20px_60px_rgba(59,130,246,0.20)] transition-opacity duration-700"
                                :class="current === 0 ? 'opacity-100' : 'pointer-events-none opacity-0'"
                            />
                            <img
                                src="/stikerNFC.webp"
                                alt="Stiker NFC"
                                width="1200"
                                height="1200"
                                loading="lazy"
                                decoding="async"
                                class="absolute inset-0 h-full w-full object-contain object-center drop-shadow-[0_20px_60px_rgba(59,130,246,0.20)] transition-opacity duration-700"
                                :class="current === 1 ? 'opacity-100' : 'pointer-events-none opacity-0'"
                            />
                        </div>
                        <!-- Dots indicator -->
                        <div class="relative z-10 mt-6 flex items-center justify-center gap-2.5">
                            <button
                                v-for="(_, i) in slides"
                                :key="i"
                                type="button"
                                @click="current = i"
                                :aria-label="'Slide ' + (i + 1)"
                                class="rounded-full transition-all duration-300"
                                :class="i === current ? 'h-2.5 w-7 bg-[#FB923C]' : 'h-2.5 w-2.5 bg-white/25 hover:bg-white/50'"
                            />
                        </div>
                    </div>
                </div>

                <!-- Text branding kiri - elegant -->
                <div class="relative z-20 px-10 pb-10 xl:px-12 xl:pb-12">
                    <p class="text-[11px] font-medium tracking-[0.2em] text-white/50">TEKNOLOGI NFC TERDEPAN</p>
                    <h2 class="mt-5 text-[32px] font-extrabold leading-none tracking-tight text-white xl:text-[38px]">
                        PEMINJAMAN <span class="bg-gradient-to-r from-[#FB923C] to-[#F97316] bg-clip-text text-transparent">NFC</span>
                    </h2>
                    <p class="mt-3 max-w-[420px] text-[13px] font-medium leading-relaxed tracking-wide text-white/60">
                        NIKMATI PEMINJAMAN DENGAN TEKNOLOGI NFC
                    </p>
                </div>
            </div>

            <!-- KANAN - Warm light, card premium -->
            <div class="relative flex w-[48%] flex-col items-center justify-center overflow-hidden bg-[#FB923C] p-4 lg:p-6 xl:p-8">
                <!-- Subtle glow top -->
                <div class="absolute -top-24 left-1/2 h-64 w-[120%] -translate-x-1/2 rounded-full bg-white/10 blur-[60px]"></div>

                <div class="relative z-10 w-full max-w-[420px]">
                    <!-- Logo -->
                    <div class="mb-4 flex flex-col items-center">
                        <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-white p-2 shadow-[0_4px_24px_rgba(0,0,0,0.06)] ring-1 ring-black/[0.04]">
                            <img src="/logo-nfc.png" alt="Peminjaman NFC" class="h-full w-full object-contain" />
                        </div>
                        <h1 class="mt-2 text-xl font-bold tracking-tight text-white">Peminjaman NFC</h1>
                        <p class="mt-0.5 text-xs text-white/70">{{ subtitle }}</p>
                    </div>

                    <!-- Card (tanpa scroll: seluruh form muat satu layar) -->
                    <div class="rounded-[24px] border border-black/[0.06] bg-white p-5 shadow-[0_16px_40px_rgba(0,0,0,0.08),0_4px_12px_rgba(0,0,0,0.04)] xl:p-6">
                        <slot />
                    </div>

                    <p class="mt-3 text-center text-[11px] font-medium text-white/80">
                        Copyright &copy; 2026 PeminjamanNFC. developed by <span class="font-bold text-[#FB923C]">IdeologTech</span>
                    </p>

                </div>
            </div>
        </div>
    </div>
</template>
