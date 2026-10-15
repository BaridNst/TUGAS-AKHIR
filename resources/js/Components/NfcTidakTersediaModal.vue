<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-[#F97316]">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9.348 14.651a3.75 3.75 0 010-5.303m5.303 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788"
                        />
                    </svg>
                </div>

                <h3 class="mt-4 text-center text-base font-extrabold tracking-tight text-gray-900">
                    NFC Tidak Tersedia
                </h3>

                <p class="mt-2 text-center text-sm leading-relaxed text-gray-600">
                    Pindai stiker NFC hanya tersedia di browser
                    <span class="font-semibold text-gray-900">Chrome pada HP Android</span>
                    dengan halaman yang dibuka lewat koneksi aman (HTTPS).
                </p>

                <ul class="mt-4 space-y-2 rounded-xl bg-gray-50 px-3.5 py-3 text-xs leading-relaxed text-gray-600">
                    <li>
                        <span class="font-semibold text-gray-900">Didukung:</span>
                        HP Android dengan NFC aktif dan browser Chrome.
                    </li>
                    <li>
                        <span class="font-semibold text-gray-900">Tidak didukung:</span>
                        iPhone, komputer/laptop, serta peramban selain Chrome.
                    </li>
                    <li>
                        <span class="font-semibold text-gray-900">Tips:</span>
                        pastikan NFC sudah aktif di Pengaturan, lalu muat ulang halaman dan coba lagi.
                    </li>
                </ul>

                <button
                    type="button"
                    class="mt-5 w-full rounded-xl bg-[#F97316] px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-orange-500/30 transition hover:bg-[#EA580C] focus:outline-none focus:ring-2 focus:ring-[#F97316]/40 focus:ring-offset-2 active:scale-95"
                    @click="emit('close')"
                >
                    Mengerti
                </button>
            </div>
        </div>
    </Teleport>
</template>
