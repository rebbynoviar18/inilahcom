/**
 * Modul untuk memeriksa notifikasi baru secara berkala
 */

class NotificationChecker {
    constructor(checkInterval = 60000) { // Default: cek setiap 1 menit
        this.checkInterval = checkInterval;
        this.lastNotificationCount = 0;
        this.checkingEnabled = true;
        this.intervalId = null;
    }

    // Mulai pemeriksaan berkala
    start() {
        if (this.intervalId) return;
        
        console.log('Memulai pemeriksaan notifikasi...');
        
        // Periksa segera saat pertama kali
        this.checkNewNotifications();
        
        // Lalu atur interval pemeriksaan berkala
        this.intervalId = setInterval(() => {
            if (this.checkingEnabled) {
                this.checkNewNotifications();
            }
        }, this.checkInterval);
    }

    // Hentikan pemeriksaan berkala
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    // Periksa notifikasi baru
    checkNewNotifications() {
        console.log('Memeriksa notifikasi baru...');
        
        fetch('/creative/shared/api/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                console.log('Data notifikasi:', data);
                
                // Jika ada notifikasi baru
                if (data.success && data.notifications.length > 0) {
                    const newCount = data.unread_count - this.lastNotificationCount;
                    console.log(`Ada ${newCount} notifikasi baru`);
                    
                    // Tampilkan notifikasi desktop jika diizinkan
                    if (window.desktopNotifications && window.desktopNotifications.isGranted() && newCount > 0) {
                        console.log('Menampilkan notifikasi desktop');
                        data.notifications.forEach(notification => {
                            window.desktopNotifications.send(
                                'Notifikasi Baru', 
                                {
                                    body: notification.message,
                                    url: notification.link,
                                    notificationId: notification.id,
                                    requireInteraction: true, // Membuat notifikasi tetap muncul sampai diinteraksi
                                    icon: '/creative/assets/img/logo.png',
                                    badge: '/creative/assets/img/badge.png'
                                }
                            );
                        });
                    } else {
                        console.log('Notifikasi desktop tidak diizinkan atau tidak tersedia');
                    }
                    
                    // Update jumlah notifikasi terakhir
                    this.lastNotificationCount = data.unread_count;
                    
                    // Update badge di UI jika ada
                    this.updateNotificationBadge(data.unread_count);
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }

    // Update badge notifikasi
    updateNotificationBadge(count) {
        const badge = document.querySelector('.fa-bell + .badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
}

// Inisialisasi dan mulai pemeriksaan saat dokumen dimuat
document.addEventListener('DOMContentLoaded', () => {
    // Hanya jalankan jika user sudah login (cek keberadaan dropdown notifikasi)
    if (document.getElementById('notificationDropdown')) {
        // Pastikan desktopNotifications sudah diinisialisasi
        if (typeof desktopNotifications !== 'undefined') {
            // Minta izin notifikasi saat pengguna berinteraksi dengan halaman
            document.addEventListener('click', () => {
                if (desktopNotifications.permission !== 'granted' && desktopNotifications.permission !== 'denied') {
                    desktopNotifications.requestPermission();
                }
            }, { once: true });
            
            // Mulai pemeriksaan notifikasi
            const notificationChecker = new NotificationChecker(30000); // Cek setiap 30 detik
            notificationChecker.start();
        }
    }
});
