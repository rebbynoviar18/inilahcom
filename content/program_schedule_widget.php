<?php
require_once '../includes/functions/program_schedule.php';

// Get program schedules for the current week
$allWeekSchedules = getCurrentWeekProgramSchedules($pdo);

// Filter to show only Monday through Friday (weekdays)
$weekSchedules = array_filter($allWeekSchedules, function($dayData, $day) {
    // PHP's date('N') returns 1 for Monday through 7 for Sunday
    $dayNum = date('N', strtotime($day));
    return $dayNum >= 1 && $dayNum <= 5; // Monday through Friday
}, ARRAY_FILTER_USE_BOTH);

// Generate unique ID for this widget instance
$widgetId = 'program-schedule-' . uniqid();
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="far fa-calendar-alt me-2 text-primary"></i>
            <span>Jadwal Program Minggu Ini</span>
        </h5>
        <div>
            <a href="../shared/program_schedules.php" class="btn btn-sm btn-outline-primary me-2">
                <i class="fas fa-list-ul me-1"></i> Lihat Semua
            </a>
            <button class="btn btn-sm btn-outline-secondary toggle-widget" data-target="<?= $widgetId ?>">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    
    <div class="card-body p-0 widget-content" id="<?= $widgetId ?>" style="display: none;">
        <div class="row g-0">
            <?php foreach ($weekSchedules as $day => $data): ?>
                <div class="col border-end <?= $data['is_today'] ? 'bg-light' : '' ?>">
                    <div class="day-header text-center p-2 <?= $data['is_today'] ? 'gradient-bg text-white' : 'border-bottom' ?>">
                        <div class="fw-bold"><?= getDayNameIndonesian($day) ?></div>
                        <small><?= date('d/m', strtotime($data['date'])) ?></small>
                    </div>
                    
                    <div class="day-content p-2" style="min-height: 200px;">
                        <?php if (empty($data['schedules'])): ?>
                            <div class="text-center text-muted py-4">
                                <i class="far fa-calendar-times fa-2x mb-2"></i>
                                <p class="small mb-0">Tidak ada program</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['schedules'] as $schedule): ?>
                                <div class="program-item mb-2 p-2 rounded <?= $schedule['is_completed'] ? 'bg-success bg-opacity-10' : 'bg-light' ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-truncate" title="<?= htmlspecialchars($schedule['program_name']) ?>">
                                            <?= htmlspecialchars($schedule['program_name']) ?>
                                        </span>
                                        <span class="badge rounded-pill <?= $schedule['is_completed'] ? 'gradient-badge' : 'bg-secondary' ?>">
                                            <?= $schedule['completed_count'] ?>/<?= $schedule['target_count'] ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($schedule['pic_name']) || !empty($schedule['editor_name'])): ?>
                                        <div class="mt-1 small">
                                            <?php if (!empty($schedule['pic_name'])): ?>
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="far fa-user me-1"></i>
                                                    <span class="text-truncate" title="PIC: <?= htmlspecialchars($schedule['pic_name']) ?>">
                                                        <?= htmlspecialchars($schedule['pic_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($schedule['editor_name'])): ?>
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="fas fa-film me-1"></i>
                                                    <span class="text-truncate" title="Editor: <?= htmlspecialchars($schedule['editor_name']) ?>">
                                                        <?= htmlspecialchars($schedule['editor_name']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Collapsed preview -->
    <div class="card-footer bg-white py-2 collapsed-preview" id="<?= $widgetId ?>-preview">
        <div class="d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                <?php
                $totalPrograms = 0;
                $completedPrograms = 0;
                
                foreach ($weekSchedules as $day => $data) {
                    if (!empty($data['schedules'])) {
                        foreach ($data['schedules'] as $schedule) {
                            $totalPrograms += $schedule['target_count'];
                            $completedPrograms += $schedule['completed_count'];
                        }
                    }
                }
                
                $completionPercentage = $totalPrograms > 0 ? round(($completedPrograms / $totalPrograms) * 100) : 0;
                ?>
                <i class="fas fa-tasks me-1"></i>
                <span><?= $completedPrograms ?> dari <?= $totalPrograms ?> program selesai minggu ini</span>
            </div>
            <div class="progress" style="width: 30%; height: 8px;">
                <div class="progress-bar gradient-progress" role="progressbar" style="width: <?= $completionPercentage ?>%;" 
                     aria-valuenow="<?= $completionPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>

<style>

.program-item {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.program-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 3px solid var(--bs-primary);
}

.day-header {
    transition: all 0.2s ease;
}

.text-truncate {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.toggle-widget {
    transition: transform 0.3s ease;
}

.toggle-widget.active {
    transform: rotate(180deg);
}

.gradient-bg {
    background: linear-gradient(45deg, #5e72e4, #825ee4);
}

.gradient-badge {
    background: linear-gradient(45deg, #5e72e4, #825ee4);
    color: white;
}

.gradient-progress {
    background: linear-gradient(45deg, #5e72e4, #825ee4);
}

.card {
    border-radius: 0.75rem;
    overflow: hidden;
}

.program-item {
    border-radius: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle widget functionality
    const toggleButtons = document.querySelectorAll('.toggle-widget');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const contentElement = document.getElementById(targetId);
            const previewElement = document.getElementById(targetId + '-preview');
            
            // Toggle visibility with smooth animation
            if (contentElement.style.display === 'none') {
                contentElement.style.display = 'block';
                previewElement.style.display = 'none';
                this.classList.add('active');
            } else {
                contentElement.style.display = 'none';
                previewElement.style.display = 'block';
                this.classList.remove('active');
            }
        });
    });
    
    // Save toggle state in localStorage
    function saveToggleState(widgetId, isOpen) {
        localStorage.setItem('widget_' + widgetId, isOpen ? 'open' : 'closed');
    }
    
    // Load toggle state from localStorage
    function loadToggleState(widgetId) {
        return localStorage.getItem('widget_' + widgetId) === 'open';
    }
    
    // Initialize widget states
    toggleButtons.forEach(button => {
        const targetId = button.getAttribute('data-target');
        const contentElement = document.getElementById(targetId);
        const previewElement = document.getElementById(targetId + '-preview');
        
        // Default is closed (as requested)
        contentElement.style.display = 'none';
        previewElement.style.display = 'block';
        button.classList.remove('active');
        
        // Optional: Uncomment below to restore state from localStorage
        /*
        const isOpen = loadToggleState(targetId);
        if (isOpen) {
            contentElement.style.display = 'block';
            previewElement.style.display = 'none';
            button.classList.add('active');
        } else {
            contentElement.style.display = 'none';
            previewElement.style.display = 'block';
            button.classList.remove('active');
        }
        
        // Save state when toggled
        button.addEventListener('click', function() {
            const isCurrentlyOpen = contentElement.style.display !== 'none';
            saveToggleState(targetId, isCurrentlyOpen);
        });
        */
    });
});
</script>