/**
 * Modul notifikasi desktop untuk Creative Management System
 * Memungkinkan pengiriman notifikasi ke desktop pengguna
 */

class DesktopNotifications {
    constructor() {
        this.supported = 'Notification' in window;
        this.permission = this.supported ? Notification.permission : 'denied';
        this.initialized = false;
        this.lastNotificationCount = 0;
        this.checkInterval = 10000; // Cek setiap 10 detik
        this.intervalId = null;
        this.shownNotifications = new Set(); // Untuk melacak notifikasi yang sudah ditampilkan
    }

    // Inisialisasi dan minta izin jika belum disetujui
    init() {
        if (!this.supported) {
            console.log('Notifikasi desktop tidak didukung di browser ini');
            return false;
        }

        if (this.permission !== 'granted' && this.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                this.permission = permission;
                this.initialized = true;
                
                // Mulai polling jika izin diberikan
                if (permission === 'granted') {
                    this.startPolling();
                }
            });
        } else {
            this.initialized = true;
            
            // Mulai polling jika izin sudah diberikan
            if (this.permission === 'granted') {
                this.startPolling();
            }
        }
        return true;
    }

    // Minta izin notifikasi secara manual
    requestPermission() {
        if (!this.supported) return Promise.reject('Notifikasi tidak didukung');
        
        return Notification.requestPermission().then(permission => {
            this.permission = permission;
            
            // Mulai polling jika izin diberikan
            if (permission === 'granted') {
                this.startPolling();
            }
            
            return permission;
        });
    }

    // Mulai polling untuk notifikasi baru
    startPolling() {
        if (this.intervalId) return;
        
        console.log('Memulai polling notifikasi...');
        
        // Periksa segera
        this.checkNotifications();
        
        // Set interval untuk polling
        this.intervalId = setInterval(() => {
            this.checkNotifications();
        }, this.checkInterval);
    }
    
    // Hentikan polling
    stopPolling() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    // Periksa notifikasi baru
    checkNotifications() {
        if (!this.isGranted()) return;
        
        // Gunakan fetch API untuk memeriksa notifikasi baru
        fetch('/creative/shared/api/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (!data.success) return;
                
                // Update jumlah notifikasi terakhir
                const previousCount = this.lastNotificationCount;
                this.lastNotificationCount = data.unread;
                
                // Update badge di UI jika ada
                const badge = document.querySelector('.fa-bell + .badge');
                if (badge) {
                    if (data.unread > 0) {
                        badge.textContent = data.unread;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                
                // Jika ada notifikasi baru
                if (data.notifications && data.notifications.length > 0) {
                    // Tampilkan notifikasi baru
                    data.notifications.forEach(notification => {
                        // Cek apakah notifikasi ini sudah ditampilkan sebelumnya
                        const notifKey = `notif-${notification.id}`;
                        if (!this.shownNotifications.has(notifKey)) {
                            // Tandai notifikasi ini sebagai sudah ditampilkan
                            this.shownNotifications.add(notifKey);
                            
                            // Kirim notifikasi desktop
                            this.send(
                                'Notifikasi Baru', 
                                {
                                    body: notification.message,
                                    url: notification.link || '/creative/shared/notifications.php',
                                    tag: 'notification-' + notification.id,
                                    requireInteraction: true,
                                    vibrate: [200, 100, 200],
                                    notificationId: notification.id
                                }
                            );
                        }
                    });
                    
                    // Jika jumlah notifikasi bertambah, tampilkan notifikasi ringkasan
                    if (data.unread > previousCount && data.notifications.length > 1) {
                        this.send(
                            'Notifikasi Baru', 
                            {
                                body: `Anda memiliki ${data.unread} notifikasi yang belum dibaca`,
                                url: '/creative/shared/notifications.php',
                                tag: 'summary-notifications',
                                requireInteraction: false,
                                vibrate: [100, 50, 100]
                            }
                        );
                    }
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }

    // Kirim notifikasi ke desktop
    send(title, options = {}) {
        if (!this.isGranted()) return false;

        // Atur opsi default
        const defaultOptions = {
            icon: '/creative/assets/img/logo.png',
            badge: '/creative/assets/img/badge.png',
            body: '',
            tag: 'creative-notification',
            requireInteraction: false
        };

        // Gabungkan dengan opsi yang diberikan
        const notificationOptions = {...defaultOptions, ...options};
       
        // Buat notifikasi
        const notification = new Notification(title, notificationOptions);
       
        // Handler untuk klik notifikasi
        notification.onclick = () => {
            // Tandai notifikasi sebagai terbaca jika ada notificationId
            if (options.notificationId) {
                fetch('/creative/shared/api/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notification_id: options.notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge jika perlu
                        const badge = document.querySelector('.fa-bell + .badge');
                        if (badge) {
                            if (data.unread_count > 0) {
                                badge.textContent = data.unread_count;
                                badge.style.display = '';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                        
                        // Update jumlah notifikasi terakhir
                        this.lastNotificationCount = data.unread_count;
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
            }
           
            // Buka URL jika ada
            if (options.url) {
                window.open(options.url, '_blank');
            }
            
            notification.close();
        };

        return notification;
    }

    // Cek apakah notifikasi diizinkan
    isGranted() {
        return this.supported && this.permission === 'granted';
    }
    
    // Kirim notifikasi test
    sendTestNotification() {
        return this.send('Test Notifikasi', {
            body: 'Ini adalah notifikasi test. Jika Anda melihat ini, berarti notifikasi desktop berfungsi dengan baik.',
            requireInteraction: true,
            vibrate: [200, 100, 200]
        });
    }
    
    // Reset notifikasi yang sudah ditampilkan (berguna untuk testing)
    resetShownNotifications() {
        this.shownNotifications.clear();
    }
}

// Inisialisasi sebagai variabel global
const desktopNotifications = new DesktopNotifications();
document.addEventListener('DOMContentLoaded', () => {
    desktopNotifications.init();
});