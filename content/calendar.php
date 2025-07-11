<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Periksa login dan role
redirectIfNotLoggedIn();
if (getUserRole() !== 'content_team') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Ambil semua task yang dikerjakan oleh atau dibuat oleh tim content
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.name as category_name, 
           ct.name as content_type_name, 
           a.name as account_name,
           u_creator.name as creator_name, 
           u_assignee.name as assignee_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN content_types ct ON t.content_type_id = ct.id
    LEFT JOIN accounts a ON t.account_id = a.id
    LEFT JOIN users u_creator ON t.created_by = u_creator.id
    LEFT JOIN users u_assignee ON t.assigned_to = u_assignee.id
    WHERE t.assigned_to = ? OR t.created_by = ?
    ORDER BY t.deadline ASC
");
$stmt->execute([$userId, $userId]);
$tasks = $stmt->fetchAll();

// Format data untuk kalender
$events = [];
foreach ($tasks as $task) {
    // Tentukan warna berdasarkan status
    $color = '';
    switch ($task['status']) {
        case 'waiting_confirmation':
        case 'waiting_head_confirmation':
            $color = '#ffc107'; // warning - kuning
            break;
        case 'in_production':
            $color = '#0d6efd'; // primary - biru
            break;
        case 'ready_for_review':
            $color = '#17a2b8'; // info - biru muda
            break;
        case 'revision':
            $color = '#dc3545'; // danger - merah
            break;
        case 'uploaded':
            $color = '#28a745'; // success - hijau
            break;
        case 'completed':
            $color = '#198754'; // success - hijau tua
            break;
        case 'rejected':
            $color = '#dc3545'; // danger - merah
            break;
        default:
            $color = '#6c757d'; // secondary - abu-abu
    }
    
    // Tentukan judul dengan informasi tambahan
    $title = $task['title'];
    if ($task['created_by'] != $userId) {
        $title .= ' (dari ' . $task['creator_name'] . ')';
    }
    
    // Tambahkan ke array events
    $events[] = [
        'id' => $task['id'],
        'title' => $title,
        'start' => $task['deadline'],
        'color' => $color,
        'url' => 'view_task.php?id=' . $task['id'],
        'extendedProps' => [
            'status' => $task['status'],
            'priority' => $task['priority'],
            'category' => $task['category_name'],
            'account' => $task['account_name']
        ]
    ];
}

$pageTitle = "Kalender Task";
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Kalender Task</h4>
                    <div>
                        <a href="tasks.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i> Tampilan List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Event -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Detail Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="eventDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="viewTaskBtn" class="btn btn-primary">Lihat Task</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/id.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'id', // Set locale to Indonesian
        dayHeaderFormat: { weekday: 'long' }, // Use full day names
        events: <?= json_encode($events) ?>,
        eventTimeFormat: { // like '14:30'
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventClick: function(info) {
            // Mencegah navigasi default
            info.jsEvent.preventDefault();
            
            // Ambil data event
            var event = info.event;
            var props = event.extendedProps;
            
            // Tampilkan detail di modal
            var detailsHtml = `
                <table class="table">
                    <tr>
                        <th>Judul</th>
                        <td>${event.title}</td>
                    </tr>
                    <tr>
                        <th>Deadline</th>
                        <td>${new Date(event.start).toLocaleString('id-ID', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        })}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>${getStatusBadgeHtml(props.status)}</td>
                    </tr>
                    <tr>
                        <th>Prioritas</th>
                        <td>${getPriorityBadgeHtml(props.priority)}</td>
                    </tr>
                    <tr>
                        <th>Kategori</th>
                        <td>${props.category}</td>
                    </tr>
                    <tr>
                        <th>Akun</th>
                        <td>${props.account}</td>
                    </tr>
                </table>
            `;
            
            document.getElementById('eventDetails').innerHTML = detailsHtml;
            document.getElementById('viewTaskBtn').href = 'view_task.php?id=' + event.id;
            
            // Tampilkan modal
            var modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        }
    });
    calendar.render();
    
    // Fungsi untuk menampilkan badge status
    function getStatusBadgeHtml(status) {
        var badgeClass = '';
        var statusText = '';
        
        switch(status) {
            case 'waiting_confirmation':
                badgeClass = 'warning';
                statusText = 'Menunggu Konfirmasi';
                break;
            case 'waiting_head_confirmation':
                badgeClass = 'warning';
                statusText = 'Menunggu Konfirmasi Head';
                break;
            case 'in_production':
                badgeClass = 'primary';
                statusText = 'Dalam Produksi';
                break;
            case 'ready_for_review':
                badgeClass = 'info';
                statusText = 'Siap Review';
                break;
            case 'revision':
                badgeClass = 'danger';
                statusText = 'Perlu Revisi';
                break;
            case 'uploaded':
                badgeClass = 'success';
                statusText = 'Telah Upload';
                break;
            case 'completed':
                badgeClass = 'success';
                statusText = 'Selesai';
                break;
            case 'rejected':
                badgeClass = 'danger';
                statusText = 'Ditolak';
                break;
            default:
                badgeClass = 'secondary';
                statusText = status;
        }
        
        return `<span class="badge bg-${badgeClass}">${statusText}</span>`;
    }
    
    // Fungsi untuk menampilkan badge prioritas
    function getPriorityBadgeHtml(priority) {
        var badgeClass = '';
        var priorityText = '';
        
        switch(priority) {
            case 'low':
                badgeClass = 'success';
                priorityText = 'Rendah';
                break;
            case 'medium':
                badgeClass = 'info';
                priorityText = 'Sedang';
                break;
            case 'high':
                badgeClass = 'warning';
                priorityText = 'Tinggi';
                break;
            case 'urgent':
                badgeClass = 'danger';
                priorityText = 'Urgent';
                break;
            default:
                badgeClass = 'secondary';
                priorityText = priority;
        }
        
        return `<span class="badge bg-${badgeClass}">${priorityText}</span>`;
    }
});
</script>

<style>
.fc-event {
    cursor: pointer;
}
</style>

<?php include '../includes/footer.php'; ?>