<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/functions/program_schedule.php';

// Pastikan hanya admin yang bisa mengakses
redirectIfNotLoggedIn();
if (getUserRole() !== 'creative_director') {
    header('Location: ../index.php');
    exit;
}

// Cek apakah tabel program_schedules sudah ada
$tableExists = false;
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'program_schedules'");
    $tableExists = $checkTable->rowCount() > 0;
} catch (PDOException $e) {
    // Error checking table
}

// Buat tabel jika belum ada
if (!$tableExists) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `program_schedules` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `program_id` int(11) NOT NULL,
              `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
              `target_count` int(11) NOT NULL DEFAULT 1,
              `pic_id` int(11) DEFAULT NULL,
              `editor_id` int(11) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `program_id` (`program_id`),
              KEY `pic_id` (`pic_id`),
              KEY `editor_id` (`editor_id`),
              CONSTRAINT `program_schedules_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `content_pillars` (`id`) ON DELETE CASCADE,
              CONSTRAINT `program_schedules_ibfk_2` FOREIGN KEY (`pic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
              CONSTRAINT `program_schedules_ibfk_3` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    } catch (PDOException $e) {
        // Error creating table
        $error = "Error creating program_schedules table: " . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new schedule
    if (isset($_POST['add_schedule'])) {
        $programId = $_POST['program_id'] ?? null;
        $dayOfWeek = $_POST['day_of_week'] ?? null;
        $targetCount = $_POST['target_count'] ?? 1;
        $picId = $_POST['pic_id'] ?? null;
        $editorId = $_POST['editor_id'] ?? null;
        
        if ($programId && $dayOfWeek) {
            if (addProgramSchedule($pdo, $programId, $dayOfWeek, $targetCount, $picId, $editorId)) {
                $success = "Jadwal program berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan jadwal program.";
            }
        } else {
            $error = "Program dan hari harus dipilih.";
        }
    }
    
    // Update schedule
    if (isset($_POST['update_schedule'])) {
        $id = $_POST['schedule_id'] ?? null;
        $programId = $_POST['program_id'] ?? null;
        $dayOfWeek = $_POST['day_of_week'] ?? null;
        $targetCount = $_POST['target_count'] ?? 1;
        $picId = $_POST['pic_id'] ?? null;
        $editorId = $_POST['editor_id'] ?? null;
        
        if ($id && $programId && $dayOfWeek) {
            if (updateProgramSchedule($pdo, $id, $programId, $dayOfWeek, $targetCount, $picId, $editorId)) {
                $success = "Jadwal program berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui jadwal program.";
            }
        } else {
            $error = "ID, program, dan hari harus dipilih.";
        }
    }
    
    // Delete schedule
    if (isset($_POST['delete_schedule'])) {
        $id = $_POST['schedule_id'] ?? null;
        
        if ($id) {
            if (deleteProgramSchedule($pdo, $id)) {
                $success = "Jadwal program berhasil dihapus.";
            } else {
                $error = "Gagal menghapus jadwal program.";
            }
        } else {
            $error = "ID jadwal harus dipilih.";
        }
    }
}

// Get all program names from content_pillars
$programs = getAllPrograms($pdo);

// Get all content team members
$contentTeam = getAllContentTeamMembers($pdo);

// Get all production team members
$productionTeam = getAllProductionTeamMembers($pdo);

// Get all program schedules
$schedules = getAllProgramSchedules($pdo);

$pageTitle = "Jadwal Program";
include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $pageTitle ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?= $pageTitle ?></li>
    </ol>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-alt me-1"></i>
            Tambah Jadwal Program Baru
        </div>
        <div class="card-body">
            <form method="post" id="addScheduleForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="program_id" class="form-label">Program</label>
                        <select name="program_id" id="program_id" class="form-select" required>
                            <option value="">-- Pilih Program --</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= $program['id'] ?>"><?= htmlspecialchars($program['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="day_of_week" class="form-label">Hari</label>
                        <select name="day_of_week" id="day_of_week" class="form-select" required>
                            <option value="">-- Pilih Hari --</option>
                            <option value="Monday">Senin</option>
                            <option value="Tuesday">Selasa</option>
                            <option value="Wednesday">Rabu</option>
                            <option value="Thursday">Kamis</option>
                            <option value="Friday">Jumat</option>
                            <option value="Saturday">Sabtu</option>
                            <option value="Sunday">Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="target_count" class="form-label">Target Jumlah</label>
                        <input type="number" name="target_count" id="target_count" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pic_id" class="form-label">PIC (Content Team)</label>
                        <select name="pic_id" id="pic_id" class="form-select">
                            <option value="">-- Pilih PIC --</option>
                            <?php foreach ($contentTeam as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="editor_id" class="form-label">Editor (Production Team)</label>
                        <select name="editor_id" id="editor_id" class="form-select">
                            <option value="">-- Pilih Editor --</option>
                            <?php foreach ($productionTeam as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" name="add_schedule" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Daftar Jadwal Program
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="schedulesTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Hari</th>
                            <th>Target</th>
                            <th>PIC</th>
                            <th>Editor</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?= htmlspecialchars($schedule['program_name']) ?></td>
                                <td><?= getDayNameIndonesian($schedule['day_of_week']) ?></td>
                                <td><?= $schedule['target_count'] ?></td>
                                <td><?= htmlspecialchars($schedule['pic_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($schedule['editor_name'] ?? '-') ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-schedule" 
                                            data-id="<?= $schedule['id'] ?>"
                                            data-program="<?= $schedule['program_id'] ?>"
                                            data-day="<?= $schedule['day_of_week'] ?>"
                                            data-target="<?= $schedule['target_count'] ?>"
                                            data-pic="<?= $schedule['pic_id'] ?? '' ?>"
                                            data-editor="<?= $schedule['editor_id'] ?? '' ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">
                                        <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                        <button type="submit" name="delete_schedule" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada jadwal program.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Jadwal Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="editScheduleForm">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <div class="mb-3">
                        <label for="edit_program_id" class="form-label">Program</label>
                        <select name="program_id" id="edit_program_id" class="form-select" required>
                            <option value="">-- Pilih Program --</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= $program['id'] ?>"><?= htmlspecialchars($program['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_day_of_week" class="form-label">Hari</label>
                        <select name="day_of_week" id="edit_day_of_week" class="form-select" required>
                            <option value="">-- Pilih Hari --</option>
                            <option value="Monday">Senin</option>
                            <option value="Tuesday">Selasa</option>
                            <option value="Wednesday">Rabu</option>
                            <option value="Thursday">Kamis</option>
                            <option value="Friday">Jumat</option>
                            <option value="Saturday">Sabtu</option>
                            <option value="Sunday">Minggu</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_target_count" class="form-label">Target Jumlah</label>
                        <input type="number" name="target_count" id="edit_target_count" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_pic_id" class="form-label">PIC (Content Team)</label>
                        <select name="pic_id" id="edit_pic_id" class="form-select">
                            <option value="">-- Pilih PIC --</option>
                            <?php foreach ($contentTeam as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_editor_id" class="form-label">Editor (Production Team)</label>
                        <select name="editor_id" id="edit_editor_id" class="form-select">
                            <option value="">-- Pilih Editor --</option>
                            <?php foreach ($productionTeam as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_schedule" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#schedulesTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
        }
    });
    
    // Edit schedule button click
    const editButtons = document.querySelectorAll('.edit-schedule');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const programId = this.getAttribute('data-program');
            const day = this.getAttribute('data-day');
            const target = this.getAttribute('data-target');
            const picId = this.getAttribute('data-pic');
            const editorId = this.getAttribute('data-editor');
            
            document.getElementById('edit_schedule_id').value = id;
            document.getElementById('edit_program_id').value = programId;
            document.getElementById('edit_day_of_week').value = day;
            document.getElementById('edit_target_count').value = target;
            document.getElementById('edit_pic_id').value = picId;
            document.getElementById('edit_editor_id').value = editorId;
            
            const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
            modal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>