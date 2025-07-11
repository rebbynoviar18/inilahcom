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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Hapus pengaturan yang ada
        if (isset($_POST['reset']) && $_POST['reset'] === 'true') {
            $stmt = $pdo->prepare("DELETE FROM task_point_settings");
            $stmt->execute();
            $message = "Semua pengaturan poin telah direset ke nilai default (1.0)";
        } else {
            // Update atau tambahkan pengaturan poin
            foreach ($_POST['points'] as $key => $point) {
                list($team, $category, $taskType) = explode('|', $key);
                $point = floatval($point);
                
                // Cek apakah pengaturan sudah ada
                $stmt = $pdo->prepare("SELECT id FROM task_point_settings WHERE team = ? AND category = ? AND task_type = ?");
                $stmt->execute([$team, $category, $taskType]);
                
                if ($stmt->fetch()) {
                    // Update pengaturan yang ada
                    $stmt = $pdo->prepare("UPDATE task_point_settings SET points = ? WHERE team = ? AND category = ? AND task_type = ?");
                    $stmt->execute([$point, $team, $category, $taskType]);
                } else {
                    // Tambahkan pengaturan baru
                    $stmt = $pdo->prepare("INSERT INTO task_point_settings (team, category, task_type, points) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$team, $category, $taskType, $point]);
                }
            }
            $message = "Pengaturan poin berhasil disimpan";
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil pengaturan poin yang ada
$pointSettings = [];
$stmt = $pdo->query("SELECT * FROM task_point_settings");
while ($row = $stmt->fetch()) {
    $key = $row['team'] . '|' . $row['category'] . '|' . $row['task_type'];
    $pointSettings[$key] = $row['points'];
}

// Data struktur task
$taskStructure = [
    'production_team' => [
        'Daily Content' => ['Carousel', 'Ilustrasi', 'Infografis', 'Reels', 'Single Images', 'Story'],
        'Produksi' => ['Ilustrasi', 'Images', 'Infografis', 'Pitchdeck', 'Video'],
        'Program' => ['Carousel', 'Ilustrasi', 'Infografis', 'Reels', 'Single Images', 'Story']
    ],
    'content_team' => [
        'Daily Content' => ['Carousel', 'Ilustrasi', 'Infografis', 'Reels', 'Single Images', 'Story'],
        'Distribusi' => ['Link', 'Video', 'Images'],
        'Program' => ['Reels', 'Single Images', 'Ilustrasi']
    ]
];

$pageTitle = "Pengaturan Poin Task";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Pengaturan Poin Task</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <p>Tetapkan nilai poin untuk setiap jenis task berdasarkan tim dan kategori. Nilai poin hanya dapat diisi dengan kelipatan 0.5.</p>
                    
                    <form method="POST">
                        <ul class="nav nav-tabs" id="pointTabs" role="tablist">
                            <?php $firstTeam = true; ?>
                            <?php foreach ($taskStructure as $team => $categories): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $firstTeam ? 'active' : '' ?>" 
                                            id="<?= $team ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?= $team ?>-content" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="<?= $team ?>-content" 
                                            aria-selected="<?= $firstTeam ? 'true' : 'false' ?>">
                                        <?= ucwords(str_replace('_', ' ', $team)) ?>
                                    </button>
                                </li>
                                <?php $firstTeam = false; ?>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="pointTabsContent">
                            <?php $firstTeam = true; ?>
                            <?php foreach ($taskStructure as $team => $categories): ?>
                                <div class="tab-pane fade <?= $firstTeam ? 'show active' : '' ?>" 
                                     id="<?= $team ?>-content" 
                                     role="tabpanel" 
                                     aria-labelledby="<?= $team ?>-tab">
                                    
                                    <?php foreach ($categories as $category => $taskTypes): ?>
                                        <h5 class="mt-3"><?= $category ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Jenis Task</th>
                                                        <th width="200">Poin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($taskTypes as $taskType): ?>
                                                        <?php 
                                                        $key = $team . '|' . $category . '|' . $taskType;
                                                        $value = isset($pointSettings[$key]) ? $pointSettings[$key] : 1.0;
                                                        ?>
                                                        <tr>
                                                            <td><?= $taskType ?></td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <button type="button" class="btn btn-outline-secondary decrease-point">-</button>
                                                                    <input type="number" 
                                                                           class="form-control point-input text-center" 
                                                                           name="points[<?= $key ?>]" 
                                                                           value="<?= $value ?>" 
                                                                           step="0.5" 
                                                                           min="0.5" 
                                                                           required
                                                                           readonly>
                                                                    <button type="button" class="btn btn-outline-secondary increase-point">+</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $firstTeam = false; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                            <button type="submit" name="reset" value="true" class="btn btn-danger" 
                                    onclick="return confirm('Apakah Anda yakin ingin mereset semua pengaturan poin ke nilai default (1.0)?')">
                                Reset ke Default
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tombol untuk menambah dan mengurangi nilai poin dengan kelipatan 0.5
    document.querySelectorAll('.increase-point').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.point-input');
            let value = parseFloat(input.value);
            input.value = (value + 0.5).toFixed(1);
        });
    });
    
    document.querySelectorAll('.decrease-point').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.point-input');
            let value = parseFloat(input.value);
            if (value > 0.5) {
                input.value = (value - 0.5).toFixed(1);
            }
        });
    });
});
</script>
<?php include '../includes/footer.php'; ?>