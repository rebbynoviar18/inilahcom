<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Inisialisasi variabel
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedTeam = isset($_GET['team']) ? $_GET['team'] : 'production_team';
$message = '';
$error = '';

// Proses form tambah/edit shift
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $shiftDate = $_POST['shift_date'];
    $shiftType = $_POST['shift_type'];
    $adminId = $_SESSION['user_id'];
    
    try {
        // Cek apakah user sudah memiliki shift pada tanggal tersebut
        $existingShift = getUserShiftOnDate($userId, $shiftDate);
        
        $pdo->beginTransaction();
        
        if ($existingShift) {
            // Update shift yang sudah ada
            $stmt = $pdo->prepare("
                UPDATE shifts 
                SET shift_type = ?, created_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$shiftType, $adminId, $existingShift['id']]);
            $message = "Jadwal shift berhasil diperbarui";
        } else {
            // Tambah shift baru
            $stmt = $pdo->prepare("
                INSERT INTO shifts (user_id, shift_date, shift_type, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $shiftDate, $shiftType, $adminId]);
            $message = "Jadwal shift berhasil ditambahkan";
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Hapus shift
if (isset($_GET['delete'])) {
    $shiftId = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->execute([$shiftId]);
        $message = "Jadwal shift berhasil dihapus";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
    
    // Redirect untuk menghindari refresh yang menghapus lagi
    header("Location: manage_shifts.php?date=$selectedDate&team=$selectedTeam");
    exit();
}

// Ambil data shift untuk tanggal yang dipilih
$dailyShifts = getDailyShifts($selectedDate, $selectedTeam);

// Ambil daftar user berdasarkan tim yang dipilih
$teamMembers = getUsersByRole($selectedTeam);

// Ambil data untuk kalender mingguan
$startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
$endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));

$weeklyShifts = [];
for ($i = 0; $i < 7; $i++) {
    $currentDate = date('Y-m-d', strtotime("+$i days", strtotime($startOfWeek)));
    $weeklyShifts[$currentDate] = getDailyShifts($currentDate, $selectedTeam);
}

$pageTitle = "Manajemen Jadwal Shift";
include '../includes/header.php';
?>

<div class="container mt-4">
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Manajemen Jadwal Shift</h4>
                    <div>
                        <select id="teamSelector" class="form-select form-select-sm d-inline-block me-2" style="width: auto;">
                            <option value="production_team" <?= $selectedTeam == 'production_team' ? 'selected' : '' ?>>Tim Produksi</option>
                            <option value="content_team" <?= $selectedTeam == 'content_team' ? 'selected' : '' ?>>Tim Konten</option>
                        </select>
                        <input type="date" id="dateSelector" class="form-control form-control-sm d-inline-block" style="width: auto;" value="<?= $selectedDate ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tambah/Edit Shift</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Anggota Tim</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Pilih Anggota Tim</option>
                                <?php foreach ($teamMembers as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="shift_date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="shift_date" name="shift_date" value="<?= $selectedDate ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="shift_type" class="form-label">Tipe Shift</label>
                            <select class="form-select" id="shift_type" name="shift_type" required>
                                <option value="morning">Shift Pagi</option>
                                <option value="afternoon">Shift Sore</option>
                                <option value="long">Long Shift (Sabtu/Minggu)</option>
                                <option value="off">Libur</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Jadwal Shift: <?= date('d F Y', strtotime($selectedDate)) ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Tipe Shift</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dailyShifts)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Belum ada jadwal shift untuk tanggal ini</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dailyShifts as $shift): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($shift['user_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getShiftTypeColor($shift['shift_type']) ?>">
                                                    <?= getShiftTypeLabel($shift['shift_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?delete=<?= $shift['id'] ?>&date=<?= $selectedDate ?>&team=<?= $selectedTeam ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Hapus jadwal shift ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Jadwal Mingguan: <?= date('d M', strtotime($startOfWeek)) ?> - <?= date('d M Y', strtotime($endOfWeek)) ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <?php for ($i = 0; $i < 7; $i++): ?>
                                        <?php $day = date('D, d', strtotime("+$i days", strtotime($startOfWeek))); ?>
                                        <th><?= $day ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['name']) ?></td>
                                        <?php for ($i = 0; $i < 7; $i++): ?>
                                            <?php 
                                                $currentDate = date('Y-m-d', strtotime("+$i days", strtotime($startOfWeek)));
                                                $memberShift = null;
                                                
                                                foreach ($weeklyShifts[$currentDate] as $shift) {
                                                    if ($shift['user_id'] == $member['id']) {
                                                        $memberShift = $shift;
                                                        break;
                                                    }
                                                }
                                            ?>
                                            <td class="text-center">
                                                <?php if ($memberShift): ?>
                                                    <span class="badge bg-<?= getShiftTypeColor($memberShift['shift_type']) ?>">
                                                        <?= getShiftTypeLabel($memberShift['shift_type']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="../shared/shifts.php" class="btn btn-secondary">Kembali ke Jadwal</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menampilkan notifikasi sukses tanpa refresh halaman
    function showSuccessNotification(message) {
        // Buat elemen alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success shift-success-alert';
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '1050';
        alertDiv.style.boxShadow = '0 7px 14px rgba(50, 50, 93, .1), 0 3px 6px rgba(0, 0, 0, .08)';
        alertDiv.innerHTML = `
            <strong><i class="fas fa-check-circle"></i> Berhasil!</strong> 
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Tambahkan ke body
        document.body.appendChild(alertDiv);
        
        // Hapus setelah 5 detik
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Tangkap form submit - perbaikan: gunakan querySelector karena form tidak memiliki ID
    const shiftForm = document.querySelector('.card form');
    if (shiftForm) {
        // Tambahkan ID ke form untuk referensi lebih mudah
        shiftForm.id = 'shiftForm';
        
        shiftForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simpan nilai tanggal sebelum submit
            const currentDate = document.getElementById('shift_date').value;
            const currentTeam = document.getElementById('teamSelector').value;
            
            // Kirim form dengan AJAX
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Tampilkan notifikasi sukses
                showSuccessNotification('Jadwal shift berhasil ditambahkan');
                
                // Reset hanya field user_id
                const userSelect = document.getElementById('user_id');
                if (userSelect) userSelect.selectedIndex = 0;
                
                // Refresh tabel jadwal dengan AJAX
                fetch(`manage_shifts.php?date=${currentDate}&team=${currentTeam}`)
                    .then(response => response.text())
                    .then(html => {
                        // Parse HTML response untuk mendapatkan tabel jadwal
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Update tabel jadwal harian
                        const newDailyTable = doc.querySelector('.col-md-8 .card:first-child .table-responsive');
                        if (newDailyTable) {
                            document.querySelector('.col-md-8 .card:first-child .table-responsive').innerHTML = newDailyTable.innerHTML;
                        }
                        
                        // Update tabel jadwal mingguan
                        const newWeeklyTable = doc.querySelector('.col-md-8 .card:last-child .table-responsive');
                        if (newWeeklyTable) {
                            document.querySelector('.col-md-8 .card:last-child .table-responsive').innerHTML = newWeeklyTable.innerHTML;
                        }
                    });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        });
    }
    
    // Event listener untuk perubahan tanggal dan tim
    document.getElementById('dateSelector').addEventListener('change', function() {
        const selectedDate = this.value;
        const selectedTeam = document.getElementById('teamSelector').value;
        window.location.href = `manage_shifts.php?date=${selectedDate}&team=${selectedTeam}`;
    });
    
    document.getElementById('teamSelector').addEventListener('change', function() {
        const selectedTeam = this.value;
        const selectedDate = document.getElementById('dateSelector').value;
        window.location.href = `manage_shifts.php?date=${selectedDate}&team=${selectedTeam}`;
    });
});
</script>

<?php include '../includes/footer.php'; ?>