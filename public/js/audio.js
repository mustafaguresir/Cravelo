/**
 * Cravelo — Audio Sistemi (Sessiz Mod)
 * Kullanıcı talebi doğrultusunda tüm geri bildirim ve sistem sesleri tamamen kapatılmıştır.
 */
const GustoAudio = (function () {
    return {
        play: function (type) {
            // Geri bildirim sesleri devre dışı
        },
        toggleMute: function () {
            return true;
        },
        isSoundEnabled: function () {
            return false;
        },
        updateSoundIcon: function () {
            // Sessiz mod aktif, buton simgesi güncellendi
        },
        init: function () {
            // Sessiz mod aktif
        }
    };
})();

if (typeof window !== 'undefined') {
    window.GustoAudio = GustoAudio;
    window.TgoAudio = GustoAudio;
}
