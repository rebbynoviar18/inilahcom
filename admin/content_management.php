<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'admin' && getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Ambil semua kategori
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $categoryName = trim($_POST['category_name']);
    
    if (!empty($categoryName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$categoryName]);
            $_SESSION['success'] = "Kategori berhasil ditambahkan";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menambahkan kategori: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Nama kategori tidak boleh kosong";
    }
    header("Location: content_management.php");
    exit();
}

// Proses tambah pilar konten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pillar'])) {
    $pillarName = trim($_POST['pillar_name']);
    $categoryId = $_POST['category_id'];
    
    if (!empty($pillarName) && !empty($categoryId)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_pillars (name, category_id) VALUES (?, ?)");
            $stmt->execute([$pillarName, $categoryId]);
            $_SESSION['success'] = "Pilar konten berhasil ditambahkan";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menambahkan pilar konten: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Nama pilar dan kategori harus diisi";
    }
    header("Location: content_management.php");
    exit();
}

// Proses tambah tipe konten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $typeName = trim($_POST['type_name']);
    $categoryId = $_POST['category_id'];
    
    if (!empty($typeName) && !empty($categoryId)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_types (name, category_id) VALUES (?, ?)");
            $stmt->execute([$typeName, $categoryId]);
            $_SESSION['success'] = "Tipe konten berhasil ditambahkan";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menambahkan tipe konten: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Nama tipe dan kategori harus diisi";
    }
    header("Location: content_management.php");
    exit();
}

// Proses hapus kategori
if (isset($_GET['delete_category'])) {
    $categoryId = $_GET['delete_category'];
    
    try {
        // Periksa apakah kategori digunakan dalam task
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Kategori tidak dapat dihapus karena masih digunakan dalam task";
        } else {
            // Hapus pilar konten dan tipe konten terkait terlebih dahulu
            $pdo->prepare("DELETE FROM content_pillars WHERE category_id = ?")->execute([$categoryId]);
            $pdo->prepare("DELETE FROM content_types WHERE category_id = ?")->execute([$categoryId]);
            
            // Hapus kategori
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$categoryId]);
            $_SESSION['success'] = "Kategori berhasil dihapus";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus kategori: " . $e->getMessage();
    }
    header("Location: content_management.php");
    exit();
}

// Proses hapus pilar konten
if (isset($_GET['delete_pillar'])) {
    $pillarId = $_GET['delete_pillar'];
    
    try {
        // Periksa apakah pilar digunakan dalam task
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE content_pillar_id = ?");
        $stmt->execute([$pillarId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Pilar konten tidak dapat dihapus karena masih digunakan dalam task";
        } else {
            $pdo->prepare("DELETE FROM content_pillars WHERE id = ?")->execute([$pillarId]);
            $_SESSION['success'] = "Pilar konten berhasil dihapus";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus pilar konten: " . $e->getMessage();
    }
    header("Location: content_management.php");
    exit();
}

// Proses hapus tipe konten
if (isset($_GET['delete_type'])) {
    $typeId = $_GET['delete_type'];
    
    try {
        // Periksa apakah tipe digunakan dalam task
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE content_type_id = ?");
        $stmt->execute([$typeId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Tipe konten tidak dapat dihapus karena masih digunakan dalam task";
        } else {
            $pdo->prepare("DELETE FROM content_types WHERE id = ?")->execute([$typeId]);
            $_SESSION['success'] = "Tipe konten berhasil dihapus";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus tipe konten: " . $e->getMessage();
    }
    header("Location: content_management.php");
    exit();
}

$pageTitle = "Manajemen Konten";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Manajemen Konten</h2>
            <p class="text-muted">Kelola kategori, pilar konten, dan tipe konten</p>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Kategori</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">Tambah Kategori</button>
                    </form>
                    
                    <hr>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                    <td>
                                        <a href="?delete_category=<?= $category['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus kategori ini? Semua pilar dan tipe konten terkait juga akan dihapus.')">
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
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Pilar Konten</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="pillar_category_id" class="form-label">Kategori</label>
                            <select class="form-control" id="pillar_category_id" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pillar_name" class="form-label">Nama Pilar Konten</label>
                            <input type="text" class="form-control" id="pillar_name" name="pillar_name" required>
                        </div>
                        <button type="submit" name="add_pillar" class="btn btn-primary">Tambah Pilar Konten</button>
                    </form>
                    
                    <hr>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pillarTableBody">
                                <!-- Data pilar konten akan dimuat melalui AJAX -->
                                <tr>
                                    <td colspan="3" class="text-center">Pilih kategori untuk melihat pilar konten</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tipe Konten</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <label for="type_category_id" class="form-label">Kategori</label>
                            <select class="form-control" id="type_category_id" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="type_name" class="form-label">Nama Tipe Konten</label>
                            <input type="text" class="form-control" id="type_name" name="type_name" required>
                        </div>
                        <button type="submit" name="add_type" class="btn btn-primary">Tambah Tipe Konten</button>
                    </form>
                    
                    <hr>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="typeTableBody">
                                <!-- Data tipe konten akan dimuat melalui AJAX -->
                                <tr>
                                    <td colspan="3" class="text-center">Pilih kategori untuk melihat tipe konten</td>
                                </tr>
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
    // Fungsi untuk memuat pilar konten berdasarkan kategori
    function loadContentPillars(categoryId) {
        fetch(`../api/get_content_pillars.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                const tableBody = document.getElementById('pillarTableBody');
                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Tidak ada pilar konten untuk kategori ini</td></tr>';
                    return;
                }
                
                tableBody.innerHTML = '';
                data.forEach(pillar => {
                    tableBody.innerHTML += `
                        <tr>
                            <td>${pillar.name}</td>
                            <td>${pillar.category_name}</td>
                            <td>
                                <a href="?delete_pillar=${pillar.id}" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus pilar konten ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('pillarTableBody').innerHTML = 
                    '<tr><td colspan="3" class="text-center text-danger">Error memuat data</td></tr>';
            });
    }
    
    // Fungsi untuk memuat tipe konten berdasarkan kategori
    function loadContentTypes(categoryId) {
        fetch(`../api/get_content_types.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                const tableBody = document.getElementById('typeTableBody');
                if (data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Tidak ada tipe konten untuk kategori ini</td></tr>';
                    return;
                }
                
                tableBody.innerHTML = '';
                data.forEach(type => {
                    tableBody.innerHTML += `
                        <tr>
                            <td>${type.name}</td>
                            <td>${type.category_name}</td>
                            <td>
                                <a href="?delete_type=${type.id}" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus tipe konten ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('typeTableBody').innerHTML = 
                    '<tr><td colspan="3" class="text-center text-danger">Error memuat data</td></tr>';
            });
    }
    
    // Event listener untuk dropdown kategori pilar
    document.getElementById('pillar_category_id').addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId) {
            loadContentPillars(categoryId);
        } else {
            document.getElementById('pillarTableBody').innerHTML = 
                '<tr><td colspan="3" class="text-center">Pilih kategori untuk melihat pilar konten</td></tr>';
        }
    });
    
    // Event listener untuk dropdown kategori tipe
    document.getElementById('type_category_id').addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId) {
            loadContentTypes(categoryId);
        } else {
            document.getElementById('typeTableBody').innerHTML = 
                '<tr><td colspan="3" class="text-center">Pilih kategori untuk melihat tipe konten</td></tr>';
        }
    });
    
    // Muat semua pilar dan tipe konten saat halaman dimuat
    const allPillars = document.createElement('option');
    allPillars.value = 'all';
    allPillars.textContent = 'Semua Kategori';
    
    // Tambahkan opsi "Semua Kategori" ke dropdown
    const pillarCategorySelect = document.getElementById('pillar_category_id');
    const typeCategorySelect = document.getElementById('type_category_id');
    
    pillarCategorySelect.insertBefore(allPillars.cloneNode(true), pillarCategorySelect.firstChild.nextSibling);
    typeCategorySelect.insertBefore(allPillars.cloneNode(true), typeCategorySelect.firstChild.nextSibling);
});
</script>
<?php include '../includes/footer.php'; ?>