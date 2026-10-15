<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const imageError = ref(false);

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);

const isAdmin = computed(() => user.value?.role === 'admin');

const allNavItems = [
    {
        label: 'Dashboard',
        href: route('dashboard'),
        activeRoutes: ['dashboard'],
        icon: 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
    },
    {
        label: 'Peminjaman',
        href: route('peminjaman.index'),
        activeRoutes: ['peminjaman.index'],
        icon: 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
    },
    {
        label: 'Pengembalian',
        href: route('pengembalian.index'),
        activeRoutes: ['pengembalian.index'],
        icon: 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3',
    },
    {
        label: 'Data Barang',
        href: route('barang.index'),
        activeRoutes: ['barang.index'],
        icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    },
    {
        label: 'Riwayat Transaksi',
        href: route('riwayat.index'),
        activeRoutes: ['riwayat.index'],
        icon: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
    },
];

const adminNavItems = [
    {
        label: 'Dashboard',
        href: route('dashboard'),
        activeRoutes: ['dashboard'],
        icon: 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
    },
    {
        label: 'Data Barang & Peminjaman',
        href: route('admin.dataBarangPeminjaman'),
        activeRoutes: [
            'admin.dataBarangPeminjaman',
            'admin.dataBarangPeminjaman.barang',
            'admin.dataBarangPeminjaman.peminjaman',
            'admin.dataBarang',
            'admin.dataPeminjam',
        ],
        icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    },
    {
        label: 'Peminjaman',
        href: route('admin.peminjaman'),
        activeRoutes: ['admin.peminjaman'],
        icon: 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
    },
    {
        label: 'Pengembalian',
        href: route('admin.pengembalian'),
        activeRoutes: ['admin.pengembalian'],
        icon: 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3',
    },
    {
        label: 'Riwayat',
        href: route('admin.riwayat'),
        activeRoutes: ['admin.riwayat'],
        icon: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
    },
    {
        label: 'Prediksi',
        href: route('admin.prediksi'),
        activeRoutes: ['admin.prediksi'],
        icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    },
    {
        label: 'Denda',
        href: route('admin.denda'),
        activeRoutes: ['admin.denda'],
        icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
    },
    {
        label: 'Notifikasi',
        href: route('admin.notifikasi'),
        activeRoutes: ['admin.notifikasi'],
        icon: 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
    },
    {
        label: 'Pengguna',
        href: route('admin.pengguna'),
        activeRoutes: ['admin.pengguna'],
        icon: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    },
    {
        label: 'Pengaturan',
        href: route('admin.pengaturan'),
        activeRoutes: ['admin.pengaturan'],
        icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

const navItems = computed(() => (isAdmin.value ? adminNavItems : allNavItems));

const isActive = (item) => item.activeRoutes.some((name) => route().current(name));

const initials = computed(() => {
    const name = user.value?.name ?? 'Pengguna';

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
});
</script>

<template>
    <div>
        <div
            v-if="open"
            class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden"
            aria-hidden="true"
            @click="emit('close')"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-[#FB923C] shadow-xl shadow-black/10 transition-transform duration-200 ease-out lg:shadow-none"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/20 px-5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-1.5 ring-1 ring-white/50">
                    <img src="/logo-nfc.png" alt="Peminjaman NFC" class="h-full w-full object-contain" />
                </span>
                <span class="text-sm font-bold tracking-tight text-white">Peminjaman NFC</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-white/60">Menu</p>

                <ul class="space-y-1">
                    <li v-for="item in navItems" :key="item.label">
                        <Link
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150"
                            :class="
                                isActive(item)
                                    ? 'bg-white font-semibold text-[#F97316] shadow-sm'
                                    : 'text-white/85 hover:bg-white/15 hover:text-white'
                            "
                            @click="emit('close')"
                        >
                            <svg
                                class="h-5 w-5 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.75"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="shrink-0 space-y-3 border-t border-white/20 p-4">
                <Link
                    :href="route('profile.show')"
                    class="flex w-full items-center gap-3 rounded-xl bg-white/15 px-3 py-2.5 text-left ring-1 ring-white/25 transition-colors duration-150 hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/50"
                    @click="emit('close')"
                >
                    <img
                        v-if="user?.avatar && !imageError"
                        :src="user.avatar"
                        :alt="user?.name ?? 'User'"
                        referrerpolicy="no-referrer"
                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/40 shadow-sm"
                        @error="imageError = true"
                    />
                    <span
                        v-else
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-[#F97316] shadow-sm"
                    >
                        {{ initials }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-white">{{ user?.name ?? 'Pengguna' }}</span>
                        <span v-if="user?.email" class="block truncate text-xs text-white/70">{{ user.email }}</span>
                    </span>
                    <svg
                        class="h-4 w-4 shrink-0 text-white/70"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="group flex w-full items-center justify-start gap-3 rounded-xl bg-white/15 px-3 py-3 ring-1 ring-white/25 transition duration-200 hover:bg-red-500/90 hover:ring-red-400/70 hover:shadow-lg hover:shadow-red-900/20 focus:outline-none focus:ring-2 focus:ring-white/60 active:scale-[0.98]"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white shadow-md shadow-red-900/30 transition duration-200 group-hover:rotate-6 group-hover:scale-110"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.25"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"
                            />
                        </svg>
                    </span>
                    <span class="text-sm font-bold tracking-wide text-white">Logout</span>
                </Link>
            </div>
        </aside>
    </div>
</template>
