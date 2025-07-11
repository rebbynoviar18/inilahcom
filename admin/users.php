<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Tambah user baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        $_SESSION['success'] = "User berhasil ditambahkan";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menambahkan user: " . $e->getMessage();
    }
    header("Location: users.php");
    exit();
}

// Hapus user
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Cek apakah user sedang memiliki task
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE created_by = ? OR assigned_to = ?");
    $stmt->execute([$userId, $userId]);
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount > 0) {
        $_SESSION['error'] = "User tidak dapat dihapus karena masih memiliki task";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'creative_director'");
            $stmt->execute([$userId]);
            $_SESSION['success'] = "User berhasil dihapus";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
        }
    }
    header("Location: users.php");
    exit();
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, name")->fetchAll();

$pageTitle = "Manajemen User";
include '../includes/header.php';

// Tambahkan kunci "redaksi" ke dalam array roleLabels
$roleLabels = [
    'creative_director' => '<span class="badge bg-primary">Creative Director</span>',
    'content_team' => '<span class="badge bg-success">Tim Konten</span>',
    'production_team' => '<span class="badge bg-info">Tim Produksi</span>',
    'marketing_team' => '<span class="badge bg-warning">Tim Marketing</span>',
    'redaksi' => '<span class="badge bg-secondary">Tim Redaksi</span>',
    'redaktur_pelaksana' => '<span class="badge bg-dark">Redaktur Pelaksana</span>' // Tambahkan baris ini
];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Daftar User</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php 
                                            echo $roleLabels[$user['role']];
                                        ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if ($user['role'] !== 'creative_director'): ?>
                                        <a href="?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="content_team">Content Team</option>
                            <option value="production_team">Production Team</option>
                            <option value="marketing_team">Marketing Team</option>
                            <option value="creative_director">Creative Director</option>
                            <option value="redaksi">Tim Redaksi</option>
                            <option value="redaktur_pelaksana">Redaktur Pelaksana</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>