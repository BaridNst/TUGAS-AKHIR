// Pembaca stiker NFC berbasis Web NDEFReader (Chrome di Android, secure context).

export const uidNfc = (serialNumber) =>
    String(serialNumber ?? '').replace(/[^0-9A-Fa-f]/g, '').toUpperCase();

export const nfcDidukung = () =>
    typeof window !== 'undefined' && 'NDEFReader' in window;

const pesanNfc = (error) => {
    switch (error?.name) {
        case 'NotAllowedError':
            return 'Izin NFC ditolak. Izinkan akses NFC pada browser lalu coba lagi.';
        case 'NotSupportedError':
            return 'Perangkat ini tidak mendukung Web NFC. Gunakan Chrome di Android.';
        case 'InvalidStateError':
            return 'Pemindai NFC sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.';
        case 'AbortError':
            return 'Pemindaian dihentikan.';
        default:
            return error?.message || 'Gagal memulai pemindaian NFC.';
    }
};

/**
 * Mulai memindai satu stiker NFC sampai terbaca atau dihentikan.
 *
 * @param {Object} opsi
 * @param {(uid: string) => void} [opsi.onReading] dipanggil dengan UID kanonik (tanpa pemisah, huruf besar)
 * @param {(pesan: string) => void} [opsi.onError] dipanggil saat pembacaan/pemindaian gagal
 * @returns {Promise<() => void>} fungsi untuk menghentikan pemindaian
 */
export const mulaiScanNfc = async ({ onReading, onError } = {}) => {
    if (!nfcDidukung()) {
        onError?.('NFC tidak tersedia. Buka halaman ini dengan HP Android dan browser Chrome.');

        return () => {};
    }

    const reader = new window.NDEFReader();
    const controller = new AbortController();
    let selesai = false;

    const berhenti = () => {
        if (selesai) {
            return;
        }

        selesai = true;

        try {
            controller.abort();
        } catch {
            /* pemindaian sudah berhenti */
        }
    };

    reader.addEventListener('reading', ({ serialNumber }) => {
        const uid = uidNfc(serialNumber);

        berhenti();

        if (!uid) {
            onError?.('Stiker terbaca tetapi UID tidak tersedia. Dekatkan stiker dan pindai ulang.');

            return;
        }

        onReading?.(uid);
    });

    reader.addEventListener('readingerror', () => {
        berhenti();
        onError?.('Gagal membaca stiker. Dekatkan kembali stiker ke pembaca HP.');
    });

    try {
        await reader.scan({ signal: controller.signal });
    } catch (error) {
        berhenti();

        if (error?.name !== 'AbortError') {
            onError?.(pesanNfc(error));
        }
    }

    return berhenti;
};
