<?php 
$pageTitle = "Judul Halaman";
// require_once '../includes/header.php'; // Baris ini menyebabkan error
?>

<!-- Konten halaman di sini -->

<?php
// File: includes/footer.php
?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('show');
        });
        
        // Fungsi untuk konfirmasi delete
        function confirmDelete(event, message) {
            if (!confirm(message || 'Apakah Anda yakin ingin menghapus item ini?')) {
                event.preventDefault();
                return false;
            }
            return true;
        }
        
        // Inisialisasi tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Inisialisasi popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Auto-hide alert setelah 5 detik
        //setTimeout(function() {
            //document.querySelectorAll('.alert').forEach(function(alert) {
              //  var bsAlert = new bootstrap.Alert(alert);
               // bsAlert.close();
            //});
        //}, 5000);
    </script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Notifikasi Test untuk Admin -->
<?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'creative_director'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Buat panel notifikasi test
    const testPanel = document.createElement('div');
    testPanel.className = 'card position-fixed';
    testPanel.style.bottom = '20px';
    testPanel.style.right = '20px';
    testPanel.style.width = '300px';
    testPanel.style.zIndex = '1050';
    testPanel.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
    
    testPanel.innerHTML = `
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Test Notifikasi</h6>
            <button type="button" class="btn-close btn-close-white" id="closeTestPanel"></button>
        </div>
        <div class="card-body">
            <form id="testNotificationForm">
                <div class="mb-3">
                    <label for="notifTitle" class="form-label">Judul</label>
                    <input type="text" class="form-control" id="notifTitle" value="Test Notifikasi">
                </div>
                <div class="mb-3">
                    <label for="notifMessage" class="form-label">Pesan</label>
                    <textarea class="form-control" id="notifMessage" rows="2">Ini adalah notifikasi test dari admin.</textarea>
                </div>
                <div class="mb-3">
                    <label for="notifUser" class="form-label">Kirim ke User</label>
                    <select class="form-control" id="notifUser">
                        <option value="all">Semua User</option>
                        <option value="self">Diri Sendiri</option>
                        <!-- User lain akan dimuat via AJAX -->
                    </select>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Kirim Notifikasi</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(testPanel);
    
    
    // Sembunyikan panel awalnya
    testPanel.style.display = 'none';
    
    // Toggle panel saat tombol diklik
    toggleButton.addEventListener('click', function() {
        if (testPanel.style.display === 'none') {
            testPanel.style.display = 'block';
            toggleButton.style.display = 'none';
        } else {
            testPanel.style.display = 'none';
            toggleButton.style.display = 'block';
        }
    });
    
    // Tutup panel
    document.getElementById('closeTestPanel').addEventListener('click', function() {
        testPanel.style.display = 'none';
        toggleButton.style.display = 'block';
    });
    
    // Muat daftar user
    fetch('/creative/api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users) {
                const userSelect = document.getElementById('notifUser');
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name + ' (' + user.role + ')';
                    userSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading users:', error));
    
    // Handle form submit
    document.getElementById('testNotificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const title = document.getElementById('notifTitle').value;
        const message = document.getElementById('notifMessage').value;
        const userId = document.getElementById('notifUser').value;
        
        if (!title || !message) {
            alert('Judul dan pesan harus diisi');
            return;
        }
        
        // Kirim notifikasi ke server
        fetch('/creative/api/send_test_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                title: title,
                message: message,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notifikasi berhasil dikirim!');
                
                // Jika dikirim ke diri sendiri, tampilkan notifikasi desktop
                if (userId === 'self' && window.desktopNotifications) {
                    window.desktopNotifications.send(title, {
                        body: message,
                        requireInteraction: true
                    });
                }
            } else {
                alert('Gagal mengirim notifikasi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengirim notifikasi');
        });
    });
});
</script>
<?php endif; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- Tambahkan ini sebelum penutup </body> -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi objek notifikasi desktop
    window.desktopNotifications = new DesktopNotifications();
    
    // Inisialisasi notifikasi desktop
    if (typeof desktopNotifications !== 'undefined') {
        desktopNotifications.init();
        
        // Inisialisasi checker notifikasi
        const notificationChecker = new NotificationChecker(30000); // Cek setiap 30 detik
        notificationChecker.start();
        
        // Log untuk debugging
        console.log('Notifikasi desktop diinisialisasi:', desktopNotifications.isGranted());
    } else {
        console.error('Modul notifikasi desktop tidak dimuat dengan benar');
    }

});
</script>

<!-- Tambahkan di bagian bawah sebelum </body> -->
<script>
// Fungsi untuk memperbarui status online
function updateOnlineStatus() {
    $.ajax({
        url: '<?= getBaseUrl() ?>/api/update_online_status.php',
        type: 'POST',
        cache: false,
        success: function(response) {
            // Status berhasil diperbarui
        },
        error: function(xhr, status, error) {
            console.error("Error updating online status:", error);
        }
    });
}

// Perbarui status setiap 30 detik
$(document).ready(function() {
    // Update saat halaman dimuat
    updateOnlineStatus();
    
    // Set interval untuk update berkala
    setInterval(updateOnlineStatus, 30000);
    
    // Update saat user aktif di halaman
    $(document).on('mousemove keydown click', function() {
        updateOnlineStatus();
    });
});
</script>
    
</body>
</html>