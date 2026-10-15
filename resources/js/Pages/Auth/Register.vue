<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { FwbDropdown, FwbListGroup, FwbListGroupItem } from 'flowbite-vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    nim: '',
    program_studi: '',
    no_hp: '',
    peran: 'Mahasiswa',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = 'mt-1 block w-full !py-2.5 !text-sm sm:!py-3 sm:!text-[14px]';
</script>

<template>
    <GuestLayout subtitle="Buat akun baru untuk melanjutkan">
        <Head title="Daftar" />

        <div class="mb-3">
            <h1 class="text-[13px] font-bold leading-tight tracking-tight text-gray-900">BUAT AKUN BARU</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-2.5">
            <div class="grid gap-2.5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <InputLabel for="name" value="Nama" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        :class="inputClass"
                        placeholder="Nama lengkap"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        :class="inputClass"
                        placeholder="email@akun.ac.id"
                        required
                        autocomplete="email"
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>
            </div>

            <div class="grid gap-2.5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <InputLabel for="nim" value="NIM (opsional)" />
                    <TextInput
                        id="nim"
                        v-model="form.nim"
                        type="text"
                        :class="inputClass"
                        placeholder="2210512001"
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

            <div class="grid gap-2.5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <InputLabel for="program_studi" value="Prodi (opsional)" />
                    <TextInput
                        id="program_studi"
                        v-model="form.program_studi"
                        type="text"
                        :class="inputClass"
                        placeholder="Teknologi Informasi"
                        autocomplete="off"
                    />
                    <InputError class="mt-1" :message="form.errors.program_studi" />
                </div>

                <div>
                    <InputLabel for="no_hp" value="No. HP (opsional)" />
                    <TextInput
                        id="no_hp"
                        v-model="form.no_hp"
                        type="tel"
                        :class="inputClass"
                        placeholder="081234567890"
                        autocomplete="tel"
                    />
                    <InputError class="mt-1" :message="form.errors.no_hp" />
                </div>
            </div>

            <div class="grid gap-2.5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        :class="inputClass"
                        placeholder="Min. 8 karakter"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Ulangi Password" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        :class="inputClass"
                        placeholder="Ulangi password"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <PrimaryButton
                class="w-full justify-center !py-3 font-bold"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Daftar
            </PrimaryButton>
        </form>

        <p class="mt-3 text-center text-[13px] text-gray-600">
            Sudah punya akun?
            <Link :href="route('login')" class="font-bold text-[#F97316] hover:underline">Masuk</Link>
        </p>
    </GuestLayout>
</template>
