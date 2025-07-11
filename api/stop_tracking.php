<?php
// File: api/stop_tracking.php

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

$userId = $_SESSION['user_id'];
$trackingId = $_POST['tracking_id'];

// Validasi tracking
$stmt = $pdo->prepare("SELECT id FROM time_tracking WHERE id = ? AND user_id = ? AND end_time IS NULL");
$stmt->execute([$trackingId, $userId]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Tracking tidak ditemukan atau sudah selesai']);
    exit();
}

try {
    // Stop tracking
    $stmt = $pdo->prepare("UPDATE time_tracking SET end_time = NOW() WHERE id = ?");
    $stmt->execute([$trackingId]);
    
    echo json_encode(['success' => true, 'message' => 'Time tracking berhasil dihentikan']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal menghentikan tracking: ' . $e->getMessage()]);
}
?>
<script>
// Update duration counter in real-time
<?php if ($activeTracking): ?>
function updateDuration() {
    const startTime = new Date('<?php echo $activeTracking['start_time']; ?>').getTime();
    const now = new Date().getTime();
    const diff = now - startTime;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('currentDuration').textContent = 
        hours + ' jam ' + minutes + ' menit ' + seconds + ' detik';
}

setInterval(updateDuration, 1000);
updateDuration();
<?php endif; ?>

// Start tracking form
$('#startTrackingForm').submit(function(e) {
    e.preventDefault();
    
    const taskId = $('#task_id').val();
    const notes = $('#notes').val();
    
    if (!taskId) {
        alert('Silakan pilih task terlebih dahulu');
        return;
    }
    
    $.ajax({
        url: '../api/start_tracking.php',
        type: 'POST',
        data: {
            task_id: taskId,
            notes: notes
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat memulai tracking');
        }
    });
});

// Stop tracking button
$('#stopTrackingBtn').click(function() {
    const trackingId = $(this).data('tracking-id');
    
    $.ajax({
        url: '../api/stop_tracking.php',
        type: 'POST',
        data: {
            tracking_id: trackingId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menghentikan tracking');
        }
    });
});
</script>