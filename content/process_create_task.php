
// Pastikan status awal untuk marketing team adalah 'waiting_head_confirmation'
if ($_SESSION['role'] === 'marketing_team') {
    $status = 'waiting_head_confirmation';
} else {
    $status = 'draft'; // Default untuk tim lain
}

// Kemudian gunakan variabel $status saat menyimpan task baru

// Ambil kategori dan jenis task untuk menghitung poin
$stmt = $pdo->prepare("SELECT c.name as category_name, ct.name as content_type_name FROM categories c JOIN content_types ct ON ct.id = ? WHERE c.id = ?");
$stmt->execute([$contentTypeId, $categoryId]);
$taskInfo = $stmt->fetch();

// Tentukan tim berdasarkan role user
$team = getTeamFromRole($_SESSION['role']);

// Hitung poin task
$points = getTaskPoints($team, $taskInfo['category_name'], $taskInfo['content_type_name']);

// Tambahkan poin ke query penyimpanan task
$stmt = $pdo->prepare("
    INSERT INTO tasks (title, description, deadline, priority, points, status, created_by, assigned_to, account_id, category_id, content_type_id, content_pillar_id, brief)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$title, $description, $deadline, $priority, $points, $status, $userId, $assignedTo, $accountId, $categoryId, $contentTypeId, $contentPillarId, $brief]);

// Ambil kategori dan jenis task untuk menghitung poin
$stmt = $pdo->prepare("SELECT c.name as category_name, ct.name as content_type_name FROM categories c JOIN content_types ct ON ct.id = ? WHERE c.id = ?");
$stmt->execute([$contentTypeId, $categoryId]);
$taskInfo = $stmt->fetch();

// Tentukan tim berdasarkan role user
$team = getTeamFromRole($_SESSION['role']);

// Hitung poin task
$points = getTaskPoints($team, $taskInfo['category_name'], $taskInfo['content_type_name']);

// Tambahkan poin ke query penyimpanan task
$stmt = $pdo->prepare("
    INSERT INTO tasks (title, description, deadline, priority, points, status, created_by, assigned_to, account_id, category_id, content_type_id, content_pillar_id, brief)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$title, $description, $deadline, $priority, $points, $status, $userId, $assignedTo, $accountId, $categoryId, $contentTypeId, $contentPillarId, $brief]);
