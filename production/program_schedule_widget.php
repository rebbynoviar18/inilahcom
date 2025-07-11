<?php
require_once '../includes/functions/program_schedule.php';

// Get program schedules for the current week
$weekSchedules = getCurrentWeekProgramSchedules($pdo);
?>

<div class="card mb-4">
    <div class="card-header">
        <h5>Jadwal Program Minggu Ini</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <?php foreach ($weekSchedules as $day => $data): ?>
                            <th class="text-center <?= $data['is_today'] ? 'table-primary' : '' ?>">
                                <?= getDayNameIndonesian($day) ?>
                                <small class="d-block text-muted"><?= date('d/m', strtotime($data['date'])) ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($weekSchedules as $day => $data): ?>
                            <td class="<?= $data['is_today'] ? 'table-primary' : '' ?>" style="min-width: 150px; vertical-align: top;">
                                <?php if (empty($data['schedules'])): ?>
                                    <p class="text-muted small text-center my-2">Tidak ada program</p>
                                <?php else: ?>
                                    <?php foreach ($data['schedules'] as $schedule): ?>
                                        <div class="program-item mb-2 p-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="small"><?= htmlspecialchars($schedule['program_name']) ?></strong>
                                                <span class="badge bg-<?= $schedule['is_completed'] ? 'success' : 'secondary' ?>">
                                                    <?= $schedule['completed_count'] ?>/<?= $schedule['target_count'] ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted">
                                                <div>PIC: <?= htmlspecialchars($schedule['pic_name'] ?? 'Belum ditentukan') ?></div>
                                                <div>Editor: <?= htmlspecialchars($schedule['editor_name'] ?? 'Belum ditentukan') ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>