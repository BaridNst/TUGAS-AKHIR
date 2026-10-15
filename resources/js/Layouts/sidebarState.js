import { ref } from 'vue';

/**
 * State sidebar disimpan di level module (bukan di dalam setup) supaya
 * tidak reset saat SidebarLayout di-mount ulang pada perpindahan halaman,
 * sehingga sidebar tidak terlihat "tutup lalu buka" setiap pindah menu.
 */
export const sidebarOpen = ref(false);

export const isDesktop = ref(false);
