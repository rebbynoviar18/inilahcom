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
$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Validasi tim yang dipilih
$validTeams = ['production_team']; // Hanya tim produksi yang valid berdasarkan catatan
if (!in_array($selectedTeam, $validTeams)) {
    $selectedTeam = 'production_team';
}

// Ambil daftar user berdasarkan tim yang dipilih
$teamMembers = getUsersByRole($selectedTeam);

$message = '';
$error = '';

// Proses form tambah/edit shift
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $shiftDate = $_POST['shift_date'];
    $shiftType = $_POST['shift_type'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Validasi: Pastikan user tidak memiliki shift lain pada tanggal yang sama
        $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND shift_date = ?");
        $stmt->execute([$userId, $shiftDate]);
        $existingShift = $stmt->fetch();
        
        $pdo->beginTransaction();
        
        if ($existingShift) {
            // Update shift yang sudah ada
            $stmt = $pdo->prepare("
                UPDATE shifts 
                SET shift_type = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$shiftType, $notes, $existingShift['id']]);
            $message = "Jadwal shift berhasil diperbarui";
        } else {
            // Tambah shift baru
            $stmt = $pdo->prepare("
                INSERT INTO shifts (user_id, shift_date, shift_type, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $shiftDate, $shiftType, $notes]);
            $message = "Jadwal shift berhasil ditambahkan";
        }
        
        $pdo->commit();
        
        // Redirect kembali ke halaman manage_shifts
        header("Location: manage_shifts.php?date=$shiftDate&team=$selectedTeam&success=1");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Tambah Jadwal Shift";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Tambah Jadwal Shift</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Anggota Tim</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Pilih Anggota Tim</option>
                                <?php foreach ($teamMembers as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shift_date" class="form-label">Tanggal</label>
                            <input type="date" name="shift_date" id="shift_date" class="form-control" value="<?= $selectedDate ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shift_type" class="form-label">Tipe Shift</label>
                            <select name="shift_type" id="shift_type" class="form-select" required>
                                <option value="morning">Shift Pagi</option>
                                <option value="afternoon">Shift Sore</option>
                                <option value="long">Long Shift (Sabtu/Minggu)</option>
                                <option value="off">Libur</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" class="form-control"></textarea>
                        </div>
                        
                        <input type="hidden" name="team" value="<?= $selectedTeam ?>">
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_shifts.php?date=<?= $selectedDate ?>&team=<?= $selectedTeam ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>