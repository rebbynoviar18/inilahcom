<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Pastikan hanya creative director yang bisa mengakses
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambah agenda redaksi
    if (isset($_POST['add_redaksi'])) {
        $title = $_POST['redaksi_title'];
        $date = $_POST['redaksi_date'];
        if (addAgendaItem($title, $date, 'redaksi')) {
            $_SESSION['success'] = 'Agenda redaksi berhasil ditambahkan';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan agenda redaksi';
        }
        header('Location: manage_info.php');
        exit;
    }
    
    // Tambah agenda settings
    if (isset($_POST['add_settings'])) {
        $title = $_POST['settings_title'];
        $date = $_POST['settings_date'];
        if (addAgendaItem($title, $date, 'settings')) {
            $_SESSION['success'] = 'Agenda settings berhasil ditambahkan';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan agenda settings';
        }
        header('Location: manage_info.php');
        exit;
    }
    
    // Update informasi umum
    if (isset($_POST['update_general'])) {
        $content = $_POST['general_content'];
        if (updateGeneralInfo($content)) {
            $_SESSION['success'] = 'Informasi umum berhasil diperbarui';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui informasi umum';
        }
        header('Location: manage_info.php');
        exit;
    }
    
    // Hapus agenda
    if (isset($_POST['delete_agenda'])) {
        $id = $_POST['agenda_id'];
        if (deleteAgendaItem($id)) {
            $_SESSION['success'] = 'Agenda berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus agenda';
        }
        header('Location: manage_info.php');
        exit;
    }
}

// Ambil data untuk ditampilkan
$redaksiAgenda = getAgendaItems('redaksi');
$settingsAgenda = getAgendaItems('settings');
$generalInfo = getGeneralInfo();

$pageTitle = "Kelola Informasi Dashboard";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Informasi Dashboard</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Informasi Umum -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informasi Umum</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <textarea id="general_content" name="general_content" class="form-control" rows="5"><?= htmlspecialchars($generalInfo) ?></textarea>
                </div>
                <button type="submit" name="update_general" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Agenda Redaksi -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Agenda Redaksi</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="redaksi_title" class="form-label">Judul</label>
                                <input type="text" class="form-control" id="redaksi_title" name="redaksi_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="redaksi_date" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="redaksi_date" name="redaksi_date" required>
                            </div>
                        </div>
                        <button type="submit" name="add_redaksi" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i> Tambah Agenda
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">#</th>
                                    <th width="50%">Judul</th>
                                    <th width="25%">Tanggal</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($redaksiAgenda)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada agenda redaksi</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($redaksiAgenda as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($item['title']) ?></td>
                                        <td><?= date('d M Y', strtotime($item['date'])) ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda ini?');">
                                                <input type="hidden" name="agenda_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="delete_agenda" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Agenda Settings -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Agenda Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="settings_title" class="form-label">Judul</label>
                                <input type="text" class="form-control" id="settings_title" name="settings_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="settings_date" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="settings_date" name="settings_date" required>
                            </div>
                        </div>
                        <button type="submit" name="add_settings" class="btn btn-warning">
                            <i class="fas fa-plus me-2"></i> Tambah Agenda
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">#</th>
                                    <th width="50%">Judul</th>
                                    <th width="25%">Tanggal</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($settingsAgenda)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada agenda settings</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($settingsAgenda as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($item['title']) ?></td>
                                        <td><?= date('d M Y', strtotime($item['date'])) ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda ini?');">
                                                <input type="hidden" name="agenda_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="delete_agenda" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
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
// Inisialisasi TinyMCE untuk editor WYSIWYG
document.addEventListener('DOMContentLoaded', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#general_content',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>