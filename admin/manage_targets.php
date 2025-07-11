<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Ambil daftar user untuk dropdown
try {
    $userQuery = $pdo->query("
        SELECT id, name, user_role 
        FROM users 
        WHERE user_role IN ('content_team', 'production_team') 
        ORDER BY user_role, name
    ");
    $users = $userQuery->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}

$message = '';
$messageType = '';

// Ambil semua target settings yang ada
try {
    $targetQuery = $pdo->query("
        SELECT * FROM target_settings 
        ORDER BY setting_key
    ");
    $targets = $targetQuery->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching target settings: " . $e->getMessage();
    $targets = []; // Inisialisasi array kosong jika query gagal
}

// Jika form disubmit untuk update target
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_targets'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['targets'] as $id => $value) {
            $updateQuery = $pdo->prepare("
                UPDATE target_settings 
                SET setting_value = ? 
                WHERE id = ?
            ");
            $updateQuery->execute([$value, $id]);
        }
        
        $pdo->commit();
        $message = 'Target berhasil diperbarui!';
        $messageType = 'success';
        
        // Refresh data
        $targetQuery = $pdo->query("
            SELECT * FROM target_settings 
            ORDER BY setting_key
        ");
        $targets = $targetQuery->fetchAll();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Terjadi kesalahan: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$pageTitle = "Kelola Target";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Kelola Target Tim</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $messageType ?>" role="alert">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Deskripsi</th>
                                        <th>Nilai Target</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($targets as $target): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            // Tampilkan deskripsi yang lebih user-friendly berdasarkan setting_key
                                            $description = '';
                                            if (strpos($target['setting_key'], 'daily_points_target_content') !== false) {
                                                $description = 'Target Poin Harian Tim Konten';
                                            } elseif (strpos($target['setting_key'], 'daily_points_target_production') !== false) {
                                                $description = 'Target Poin Harian Tim Produksi';
                                            } elseif (strpos($target['setting_key'], 'daily_views_target_instagram') !== false) {
                                                $description = 'Target jumlah Konten Viral Instagram Harian';
                                            } elseif (strpos($target['setting_key'], 'daily_views_target_tiktok') !== false) {
                                                $description = 'Target jumlah Konten Viral TikTok Harian';
                                            } elseif (strpos($target['setting_key'], 'weekly_views_target_instagram') !== false) {
                                                $description = 'Target Views Harian Instagram';
                                            } elseif (strpos($target['setting_key'], 'weekly_views_target_tiktok') !== false) {
                                                $description = 'Target Views Harian TikTok';
                                            } else {
                                                $description = ucfirst(str_replace('_', ' ', $target['setting_key']));
                                            }
                                            echo $description;
                                            ?>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="targets[<?= $target['id'] ?>]" 
                                                value="<?= $target['setting_value'] ?>" step="0.1" min="0">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" name="update_targets" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>