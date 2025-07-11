<?php
// File: admin/accounts.php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Tambah akun media sosial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $accountName = trim($_POST['name']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO accounts (name, created_by) VALUES (?, ?)");
        $stmt->execute([$accountName, $_SESSION['user_id']]);
        $_SESSION['success'] = "Akun media sosial berhasil ditambahkan";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menambahkan akun: " . $e->getMessage();
    }
    header("Location: accounts.php");
    exit();
}

// Hapus akun
if (isset($_GET['delete'])) {
    $accountId = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $_SESSION['success'] = "Akun berhasil dihapus";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus akun: " . $e->getMessage();
    }
    header("Location: accounts.php");
    exit();
}

$accounts = $pdo->query("SELECT * FROM accounts")->fetchAll();

$pageTitle = "Manajemen Akun Media Sosial";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Akun Media Sosial</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="fas fa-plus"></i> Tambah Akun
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Akun</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?= $account['id'] ?></td>
                                    <td><?= htmlspecialchars($account['name']) ?></td>
                                    <td><?= htmlspecialchars(getUserNameById($account['created_by'])) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($account['created_at'])) ?></td>
                                    <td>
                                        <a href="?delete=<?= $account['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus akun ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<!-- Modal Tambah Akun -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAccountModalLabel">Tambah Akun Media Sosial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="accountName" class="form-label">Nama Akun</label>
                        <input type="text" class="form-control" id="accountName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_account" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>