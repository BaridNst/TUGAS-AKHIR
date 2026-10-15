<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { FwbDropdown, FwbListGroup, FwbListGroupItem } from 'flowbite-vue';
import { Head, router, useForm } from '@inertiajs/vue3';

import { computed, ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const imageError = ref(false);

const initials = computed(() => {
    const parts = (props.user?.name ?? 'Pengguna').trim().split(/\s+/);
    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
});

const form = useForm({
    no_hp: props.user.no_hp || '',
    nim: props.user.nim || '',
    program_studi: props.user.program_studi || '',
    peran: props.user.peran || 'Mahasiswa',
});

const submit = () => {
    form.post(route('profile.complete.store'));
};

const logout = () => {
    router.post(route('logout'));
};

const inputClass = 'mt-1 block w-full !py-2.5 !text-sm sm:!py-3 sm:!text-[14px]';
</script>

<template>
    <GuestLayout subtitle="Satu langkah lagi untuk mulai meminjam">
        <Head title="Lengkapi Profil" />

        <div class="mb-3">
            <h1 class="text-[13px] font-bold leading-tight tracking-tight text-gray-900">
                LENGKAPI PROFIL ANDA
            </h1>
            <p class="mt-1 text-xs text-gray-500">
                Silakan isi data akademik & kontak di bawah ini sebelum melanjutkan.
            </p>
        </div>

        <!-- Google User Card -->
        <div class="mb-4 flex items-center gap-3 rounded-xl border border-orange-100 bg-orange-50/70 p-2.5 sm:p-3">
            <img
                v-if="user.avatar && !imageError"
                :src="user.avatar"
                :alt="user.name"
                referrerpolicy="no-referrer"
                class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm"
                @error="imageError = true"
            />
            <div
                v-else
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-[#FB923C] to-[#EA580C] text-xs font-bold text-white shadow-sm"
            >
                {{ initials }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <p class="truncate text-xs font-bold text-gray-900 sm:text-sm">{{ user.name }}</p>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">
                        Google Terhubung
                    </span>
                </div>
                <p class="truncate text-[11px] text-gray-600">{{ user.email }}</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-3">
            <!-- No HP -->
            <div>
                <InputLabel for="no_hp" value="Nomor Telepon / WhatsApp" />
                <TextInput
                    id="no_hp"
                    v-model="form.no_hp"
                    type="tel"
                    :class="inputClass"
                    placeholder="Contoh: 081234567890"
                    required
                    autofocus
                    autocomplete="tel"
                />
                <InputError class="mt-1" :message="form.errors.no_hp" />
            </div>

            <!-- NIM & Peran (Dropdown) -->
            <div class="grid gap-2.5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <InputLabel for="nim" value="NIM" />
                    <TextInput
                        id="nim"
                        v-model="form.nim"
                        type="text"
                        :class="inputClass"
                        placeholder="Contoh: 2210512001"
                        required
                        autocomplete="off"
                    />
                    <InputError class="mt-1" :message="form.errors.nim" />
                </div>

                <div>
                    <InputLabel for="peran" value="Peran" />
                    <fwb-dropdown
                        id="peran"
                        class="mt-1 w-full"
                        :text="form.peran"
                        close-inside
                        content-wrapper-class="w-full rounded-lg dark:bg-white"
                        trigger-wrapper-class="w-full"
                        trigger-class="w-full justify-between gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-left text-sm text-gray-900 transition focus:border-[#F97316] focus:outline-none focus:ring-2 focus:ring-[#F97316]/20 sm:rounded-2xl sm:px-4 sm:py-3 sm:text-[14px]"
                    >
                        <fwb-list-group class="dropdown-opsi w-full text-sm dark:bg-white dark:text-gray-900 dark:border-gray-200">
                            <fwb-list-group-item @click="form.peran = 'Mahasiswa'">Mahasiswa</fwb-list-group-item>
                            <fwb-list-group-item @click="form.peran = 'Dosen'">Dosen</fwb-list-group-item>
                        </fwb-list-group>
                    </fwb-dropdown>
                    <InputError class="mt-1" :message="form.errors.peran" />
                </div>
            </div>

            <!-- Program Studi -->
            <div>
                <InputLabel for="program_studi" value="Program Studi" />
                <TextInput
                    id="program_studi"
                    v-model="form.program_studi"
                    type="text"
                    :class="inputClass"
                    placeholder="Contoh: Teknologi Informasi"
                    required
                    autocomplete="off"
                />
                <InputError class="mt-1" :message="form.errors.program_studi" />
            </div>

            <PrimaryButton
                class="w-full justify-center !py-3 font-bold"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>Simpan & Masuk ke Dashboard</span>
            </PrimaryButton>
        </form>

        <div class="mt-3.5 text-center">
            <button
                type="button"
                @click="logout"
                class="text-xs font-semibold text-gray-500 transition hover:text-red-600 hover:underline"
            >
                Bukan akun Anda? Keluar / Ganti Akun
            </button>
        </div>
    </GuestLayout>
</template>
