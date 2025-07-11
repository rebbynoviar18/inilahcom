<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Inisialisasi target untuk bulan ini
function initializeMonthlyTargets() {
    global $pdo;
    
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $currentDate = $startDate;
    
    // Ambil semua user dengan role content_team atau production_team
    $userQuery = $pdo->query("
        SELECT id, role FROM users 
        WHERE role IN ('content_team', 'production_team')
    ");
    $users = $userQuery->fetchAll();
    
    // Inisialisasi target untuk setiap user dan setiap hari dalam bulan ini
    while (strtotime($currentDate) <= strtotime($endDate)) {
        // Target untuk setiap user
        foreach ($users as $user) {
            // Target poin berdasarkan role
            $targetPoints = ($user['role'] == 'content_team') ? 10.00 : 8.00;
            
            // Cek apakah sudah ada target untuk user dan tanggal ini
            $checkQuery = $pdo->prepare("
                SELECT id FROM user_targets 
                WHERE user_id = ? AND target_date = ? AND target_type = 'daily'
            ");
            $checkQuery->execute([$user['id'], $currentDate]);
            
            if (!$checkQuery->fetch()) {
                // Insert target baru
                $insertQuery = $pdo->prepare("
                    INSERT INTO user_targets (user_id, target_type, target_points, target_date)
                    VALUES (?, 'daily', ?, ?)
                ");
                $insertQuery->execute([$user['id'], $targetPoints, $currentDate]);
            }
        }
        
        // Target viral untuk Instagram dan TikTok
        $platforms = ['instagram', 'tiktok'];
        foreach ($platforms as $platform) {
            // Target count berdasarkan platform
            $targetCount = ($platform == 'instagram') ? 3 : 2;
            
            // Cek apakah sudah ada target untuk platform dan tanggal ini
            $checkQuery = $pdo->prepare("
                SELECT id FROM viral_target_tracking 
                WHERE platform = ? AND target_date = ?
            ");
            $checkQuery->execute([$platform, $currentDate]);
            
            if (!$checkQuery->fetch()) {
                // Insert target baru
                $insertQuery = $pdo->prepare("
                    INSERT INTO viral_target_tracking (platform, target_count, target_date)
                    VALUES (?, ?, ?)
                ");
                $insertQuery->execute([$platform, $targetCount, $currentDate]);
            }
        }
        
        // Pindah ke hari berikutnya
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    
    return true;
}

$message = '';
$messageType = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['initialize_targets'])) {
        if (initializeMonthlyTargets()) {
            $message = 'Target untuk bulan ini berhasil diinisialisasi!';
            $messageType = 'success';
        } else {
            $message = 'Terjadi kesalahan saat menginisialisasi target.';
            $messageType = 'danger';
        }
    }
}

$pageTitle = "Inisialisasi Target";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Inisialisasi Target Bulanan</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $messageType ?>" role="alert">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>Fitur ini akan membuat target harian untuk semua anggota tim untuk bulan ini (<?= date('F Y') ?>).</p>
                    <p>Target yang akan dibuat:</p>
                    <ul>
                        <li>Target individu untuk tim konten: 10 poin per hari</li>
                        <li>Target individu untuk tim produksi: 8 poin per hari</li>
                        <li>Target kolektif Instagram: 3 konten viral per hari</li>
                        <li>Target kolektif TikTok: 2 konten viral per hari</li>
                    </ul>
                    
                    <form method="post">
                        <button type="submit" name="initialize_targets" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Inisialisasi Target Bulan Ini
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>