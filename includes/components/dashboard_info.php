<?php
require_once __DIR__ . '/../functions/info_functions.php';

// Ambil data untuk ditampilkan
// Ubah query untuk mengurutkan berdasarkan tanggal terbaru
$redaksiAgenda = getAgendaItemsDesc('redaksi', 10);
$settingsAgenda = getAgendaItemsDesc('settings', 10);
$generalInfo = getGeneralInfo();
?>

<!-- Informasi Dashboard - Modern & Compact Version -->
<div class="row g-3 mb-3">
    <!-- Informasi Umum -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    Informasi Umum
                </div>
            </div>
            <div class="card-body p-3 small">
                <?php echo $generalInfo; ?>
            </div>
        </div>
    </div>

    <!-- Agenda Redaksi -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    Agenda Redaksi
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($redaksiAgenda)): ?>
                    <p class="text-muted small p-3 mb-0">Tidak ada agenda redaksi saat ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <tbody class="small">
                                <?php foreach ($redaksiAgenda as $index => $item): ?>
                                <tr>
                                    <td width="77%" style="padding: 0.5rem 1rem;"><?= $index + 1 ?>. <?= htmlspecialchars($item['title']) ?></td>
                                    <td width="23%" style="padding: 0.5rem 1rem;font-size:0.6rem;"><?= date('d M Y', strtotime($item['date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Agenda Settings -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    Agenda Settings
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($settingsAgenda)): ?>
                    <p class="text-muted small p-3 mb-0">Tidak ada agenda settings saat ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <tbody class="small">
                                <?php foreach ($settingsAgenda as $index => $item): ?>
                                <tr>
                                    <td width="77%" style="padding: 0.5rem 1rem;"><?= $index + 1 ?>. <?= htmlspecialchars($item['title']) ?></td>
                                    <td width="23%" style="padding: 0.5rem 1rem;font-size:0.6rem;"><?= date('d M Y', strtotime($item['date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>