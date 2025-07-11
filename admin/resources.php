<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
// Include autoloader Composer dan class S3 Client
require_once '../vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header("Location: ../index.php");
    exit();
}

// Muat konfigurasi dan inisialisasi S3 Client
$s3Config = require '../config/s3.php';
$s3Client = new S3Client([
    'credentials' => $s3Config['credentials'],
    'region'      => $s3Config['region'],
    'version'     => $s3Config['version'],
    'endpoint'    => $s3Config['endpoint']
]);
$bucketName = $s3Config['bucket'];


// Logika untuk mengunggah resource ke S3/Spaces
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $file = $_FILES['resource_file'];
    
    // Validasi file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error'] = "Jenis file tidak diizinkan";
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
        $_SESSION['error'] = "Ukuran file terlalu besar (maksimal 5MB)";
    } else {
        // Generate unique filename dan S3 key
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $objectKey = 'resources/' . $filename; // Simpan dalam "folder" di bucket
        
        try {
            // Unggah file ke object storage
            $s3Client->putObject([
                'Bucket'     => $bucketName,
                'Key'        => $objectKey,
                'SourceFile' => $file['tmp_name'],
                'ContentType'=> $file['type'],
                'ACL'        => 'private' // 'private' lebih aman, akses via pre-signed URL
            ]);
            
            // Simpan path (object key) ke database
            $stmt = $pdo->prepare("INSERT INTO resources (name, file_path, type, uploaded_by) VALUES (?, ?, ?, ?)");
            // Simpan $objectKey, bukan $filename
            $stmt->execute([$name, $objectKey, $type, $_SESSION['user_id']]);
            $_SESSION['success'] = "Resource berhasil diupload";

        } catch (S3Exception $e) {
            $_SESSION['error'] = "Gagal mengupload file: " . $e->getMessage();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menyimpan resource: " . $e->getMessage();
            // Jika gagal simpan DB, hapus file yang sudah terunggah ke S3/Spaces
            $s3Client->deleteObject(['Bucket' => $bucketName, 'Key' => $objectKey]);
        }
    }
    header("Location: resources.php");
    exit();
}

// === DIUBAH: Logika untuk menghapus resource dari S3/Spaces ===
if (isset($_GET['delete'])) {
    $resourceId = $_GET['delete'];
    
    try {
        // Dapatkan info file (object key)
        $stmt = $pdo->prepare("SELECT file_path FROM resources WHERE id = ?");
        $stmt->execute([$resourceId]);
        $objectKey = $stmt->fetchColumn();
        
        // Hapus dari database terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
        $stmt->execute([$resourceId]);
        
        // Hapus file dari object storage
        if ($objectKey) {
            $s3Client->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => $objectKey,
            ]);
        }
        
        $_SESSION['success'] = "Resource berhasil dihapus";
    } catch (PDOException | S3Exception $e) { // Tangkap kedua jenis exception
        $_SESSION['error'] = "Gagal menghapus resource: " . $e->getMessage();
    }
    header("Location: resources.php");
    exit();
}

$resources = $pdo->query("
    SELECT r.*, u.name as uploaded_by_name 
    FROM resources r
    JOIN users u ON r.uploaded_by = u.id
    ORDER BY r.created_at DESC
")->fetchAll();

$pageTitle = "Resource Library";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Resource Library</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadResourceModal">
                        <i class="fas fa-plus"></i> Upload Resource
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>File</th>
                                    <th>Diupload Oleh</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $resource): ?>
                                <tr>
                                    <td><?= htmlspecialchars($resource['name']) ?></td>
                                    <td><?= htmlspecialchars($resource['type']) ?></td>
                                    <td>
                                        <?php
                                            // Buat Pre-signed URL yang aman dan sementara
                                            try {
                                                $cmd = $s3Client->getCommand('GetObject', [
                                                    'Bucket' => $bucketName,
                                                    'Key'    => $resource['file_path']
                                                ]);
                                                $request = $s3Client->createPresignedRequest($cmd, '+20 minutes');
                                                $presignedUrl = (string) $request->getUri();
                                            } catch (Exception $e) {
                                                $presignedUrl = '#'; // Tampilkan link mati jika ada error
                                            }
                                        ?>
                                        <a href="<?= htmlspecialchars($presignedUrl) ?>" target="_blank">
                                            Lihat File
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($resource['uploaded_by_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($resource['created_at'])) ?></td>
                                    <td>
                                        <a href="?delete=<?= $resource['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus resource ini?')">
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

<!-- Modal Upload Resource -->
<div class="modal fade" id="uploadResourceModal" tabindex="-1" aria-labelledby="uploadResourceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadResourceModalLabel">Upload Resource Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Resource</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Jenis Resource</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="Logo">Logo</option>
                            <option value="Brand Guideline">Brand Guideline</option>
                            <option value="Template">Template</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="resource_file" class="form-label">File Resource</label>
                        <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                        <small class="text-muted">Format yang didukung: JPG, PNG, GIF, PDF, DOC/DOCX (Maksimal 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>