<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Cek apakah tabel user_points ada dan perbaiki foreign key constraint jika perlu
try {
    // Cek apakah tabel user_points ada
    $pdo->query("SELECT 1 FROM user_points LIMIT 1");
    
    // Cek apakah kolom task_id mengizinkan NULL
    $stmt = $pdo->query("
        SELECT IS_NULLABLE 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user_points' 
        AND COLUMN_NAME = 'task_id'
    ");
    
    $isNullable = $stmt->fetch(PDO::FETCH_ASSOC)['IS_NULLABLE'];
    
    if ($isNullable === 'NO') {
        // Ubah kolom task_id untuk mengizinkan NULL
        $pdo->exec("ALTER TABLE user_points MODIFY task_id INT NULL");
        $message = "Kolom task_id berhasil diubah untuk mengizinkan NULL";
    }
    
} catch (PDOException $e) {
    // Tabel tidak ada, buat tabel
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_points (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task_id INT NULL,
                points FLOAT NOT NULL DEFAULT 0,
                description TEXT DEFAULT NULL,
                earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                added_by INT DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
            )
        ");
        $message = "Tabel user_points berhasil dibuat";
    } catch (PDOException $e) {
        $error = "Gagal membuat tabel user_points: " . $e->getMessage();
    }
}

// Cek apakah kolom description ada di tabel user_points
$hasDescriptionColumn = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_points LIKE 'description'");
    $hasDescriptionColumn = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    // Kolom tidak ada
}

// Jika kolom description tidak ada, tambahkan kolom tersebut
if (!$hasDescriptionColumn) {
    try {
        $pdo->exec("ALTER TABLE user_points ADD COLUMN description TEXT DEFAULT NULL");
        $hasDescriptionColumn = true;
    } catch (PDOException $e) {
        $error = "Tidak dapat menambahkan kolom description: " . $e->getMessage();
    }
}

// Cek apakah kolom added_by ada di tabel user_points
$hasAddedByColumn = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_points LIKE 'added_by'");
    $hasAddedByColumn = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    // Kolom tidak ada
}

// Jika kolom added_by tidak ada, tambahkan kolom tersebut
if (!$hasAddedByColumn) {
    try {
        $pdo->exec("ALTER TABLE user_points ADD COLUMN added_by INT DEFAULT NULL");
        $hasAddedByColumn = true;
    } catch (PDOException $e) {
        $error = "Tidak dapat menambahkan kolom added_by: " . $e->getMessage();
    }
}

// Ambil daftar user
$users = [];
$stmt = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('content_team', 'production_team') ORDER BY name");
while ($row = $stmt->fetch()) {
    $users[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedUserId = (int)$_POST['user_id'];
        $points = floatval($_POST['points']);
        $description = trim($_POST['description']);
        
        // Validasi input
        if ($selectedUserId <= 0) {
            throw new Exception("Pilih user terlebih dahulu");
        }
        
        if ($points <= 0) {
            throw new Exception("Poin harus lebih dari 0");
        }
        
        if (empty($description)) {
            throw new Exception("Deskripsi tidak boleh kosong");
        }
        
        // Bulatkan poin ke kelipatan 0.5
        $points = round($points * 2) / 2;
        
        // Simpan poin ke database - gunakan 0 sebagai task_id jika NULL tidak diizinkan
        $stmt = $pdo->prepare("
            INSERT INTO user_points (user_id, points, description, added_by, earned_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$selectedUserId, $points, $description, $userId]);
        
        // Kirim notifikasi ke user
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, link)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $selectedUserId,
            "Anda mendapatkan {$points} poin tambahan: {$description}",
            "points.php"
        ]);
        
        $message = "Berhasil menambahkan {$points} poin ke user";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = "Tambah Poin Manual";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Tambah Poin Manual</h4>
                    <a href="dashboard.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <p>Gunakan form ini untuk menambahkan poin secara manual ke anggota tim.</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Pilih Anggota Tim</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">-- Pilih Anggota Tim --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['name']) ?> (<?= ucwords(str_replace('_', ' ', $user['role'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="points" class="form-label">Jumlah Poin</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary decrease-point">-</button>
                                <input type="number" 
                                       class="form-control point-input text-center" 
                                       id="points"
                                       name="points" 
                                       value="1.0" 
                                       step="0.5" 
                                       min="0.5" 
                                       required
                                       readonly>
                                <button type="button" class="btn btn-outline-secondary increase-point">+</button>
                            </div>
                            <div class="form-text">Poin hanya dapat diisi dengan kelipatan 0.5</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi / Alasan</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <div class="form-text">Contoh: Bonus untuk kinerja luar biasa, Poin tambahan untuk task khusus, dll.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Tambahkan Poin</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Riwayat Penambahan Poin Manual -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Riwayat Penambahan Poin Manual</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Anggota Tim</th>
                                    <th>Poin</th>
                                    <?php if ($hasDescriptionColumn): ?>
                                    <th>Deskripsi</th>
                                    <?php endif; ?>
                                    <?php if ($hasAddedByColumn): ?>
                                    <th>Ditambahkan Oleh</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    if ($hasAddedByColumn && $hasDescriptionColumn) {
                                        $stmt = $pdo->query("
                                            SELECT p.*, u.name as user_name, a.name as added_by_name
                                            FROM user_points p
                                            JOIN users u ON p.user_id = u.id
                                            LEFT JOIN users a ON p.added_by = a.id
                                            WHERE p.task_id IS NULL
                                            ORDER BY p.earned_at DESC
                                            LIMIT 20
                                        ");
                                    } elseif ($hasDescriptionColumn) {
                                        $stmt = $pdo->query("
                                            SELECT p.*, u.name as user_name
                                            FROM user_points p
                                            JOIN users u ON p.user_id = u.id
                                            WHERE p.task_id IS NULL
                                            ORDER BY p.earned_at DESC
                                            LIMIT 20
                                        ");
                                    } else {
                                        $stmt = $pdo->query("
                                            SELECT p.*, u.name as user_name
                                            FROM user_points p
                                            JOIN users u ON p.user_id = u.id
                                            WHERE p.task_id IS NULL
                                            ORDER BY p.earned_at DESC
                                            LIMIT 20
                                        ");
                                    }
                                    $manualPoints = $stmt->fetchAll();
                                } catch (PDOException $e) {
                                    $error = "Error saat mengambil data: " . $e->getMessage();
                                    $manualPoints = [];
                                }
                                
                                if (empty($manualPoints)):
                                ?>
                                    <tr>
                                        <td colspan="<?= ($hasAddedByColumn && $hasDescriptionColumn) ? '5' : (($hasAddedByColumn || $hasDescriptionColumn) ? '4' : '3') ?>" class="text-center">Belum ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($manualPoints as $point): ?>
                                        <tr>
                                            <td><?= date('d M Y H:i', strtotime($point['earned_at'])) ?></td>
                                            <td><?= htmlspecialchars($point['user_name']) ?></td>
                                            <td><?= number_format($point['points'], 1) ?></td>
                                            <?php if ($hasDescriptionColumn): ?>
                                            <td><?= htmlspecialchars($point['description'] ?? '-') ?></td>
                                            <?php endif; ?>
                                            <?php if ($hasAddedByColumn): ?>
                                            <td><?= $point['added_by_name'] ? htmlspecialchars($point['added_by_name']) : 'System' ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tombol untuk menambah dan mengurangi nilai poin dengan kelipatan 0.5
    document.querySelector('.increase-point').addEventListener('click', function() {
        const input = document.getElementById('points');
        let value = parseFloat(input.value);
        input.value = (value + 0.5).toFixed(1);
    });
    
    document.querySelector('.decrease-point').addEventListener('click', function() {
        const input = document.getElementById('points');
        let value = parseFloat(input.value);
        if (value > 0.5) {
            input.value = (value - 0.5).toFixed(1);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>