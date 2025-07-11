<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Buat template baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $categoryId = $_POST['category_id'];
    $contentTypeId = $_POST['content_type_id'];
    $contentPillarId = $_POST['content_pillar_id'];
    $description = trim($_POST['description']);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO templates 
            (title, category_id, content_type_id, content_pillar_id, description, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $categoryId, $contentTypeId, $contentPillarId, $description, $userId
        ]);
        $_SESSION['success'] = "Template berhasil dibuat";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal membuat template: " . $e->getMessage();
    }
    header("Location: templates.php");
    exit();
}

// Hapus template
if (isset($_GET['delete'])) {
    $templateId = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ? AND created_by = ?");
        $stmt->execute([$templateId, $userId]);
        $_SESSION['success'] = "Template berhasil dihapus";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus template: " . $e->getMessage();
    }
    header("Location: templates.php");
    exit();
}

$templates = $pdo->prepare("
    SELECT t.*, c.name as category_name, ct.name as content_type_name, cp.name as content_pillar_name
    FROM templates t
    JOIN categories c ON t.category_id = c.id
    JOIN content_types ct ON t.content_type_id = ct.id
    JOIN content_pillars cp ON t.content_pillar_id = cp.id
    WHERE t.created_by = ?
    ORDER BY t.title
");
$templates->execute([$userId]);

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

$pageTitle = "Template Brief";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Template Brief</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                        <i class="fas fa-plus"></i> Buat Template
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Jenis Konten</th>
                                    <th>Pilar Konten</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($template = $templates->fetch()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($template['title']) ?></td>
                                    <td><?= htmlspecialchars($template['category_name']) ?></td>
                                    <td><?= htmlspecialchars($template['content_type_name']) ?></td>
                                    <td><?= htmlspecialchars($template['content_pillar_name']) ?></td>
                                    <td>
                                        <a href="create_task.php?template=<?= $template['id'] ?>" class="btn btn-sm btn-success" title="Gunakan Template">
                                            <i class="fas fa-play"></i>
                                        </a>
                                        <a href="?delete=<?= $template['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus template ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Template -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTemplateModalLabel">Buat Template Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Judul Template</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_type_id" class="form-label">Jenis Konten</label>
                                <select class="form-control" id="content_type_id" name="content_type_id" required>
                                    <option value="">Pilih Kategori terlebih dahulu</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_pillar_id" class="form-label">Pilar Konten</label>
                                <select class="form-control" id="content_pillar_id" name="content_pillar_id" required>
                                    <option value="">Pilih Kategori terlebih dahulu</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Template</label>
                                <textarea class="form-control" id="description" name="description" rows="10" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Dynamic dropdown for content types and pillars based on category
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const contentTypeSelect = document.getElementById('content_type_id');
    const contentPillarSelect = document.getElementById('content_pillar_id');
    
    if (categoryId) {
        // Fetch content types
        fetch(`../api/get_content_types.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                contentTypeSelect.innerHTML = '<option value="">Pilih Jenis Konten</option>';
                data.forEach(item => {
                    contentTypeSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                });
            });
            
        // Fetch content pillars
        fetch(`../api/get_content_pillars.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                contentPillarSelect.innerHTML = '<option value="">Pilih Pilar Konten</option>';
                data.forEach(item => {
                    contentPillarSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                });
            });
    } else {
        contentTypeSelect.innerHTML = '<option value="">Pilih Kategori terlebih dahulu</option>';
        contentPillarSelect.innerHTML = '<option value="">Pilih Kategori terlebih dahulu</option>';
    }
});
</script>

<?php include '../includes/footer.php'; ?>