<?php
/**
 * Fungsi untuk mengambil agenda berdasarkan tipe
 */
function getAgendaItems($type, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, title, date 
        FROM agenda_items 
        WHERE type = ? 
        ORDER BY date ASC 
        LIMIT ?
    ");
    
    // Bind parameter dengan tipe data yang benar
    $stmt->bindParam(1, $type, PDO::PARAM_STR);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Fungsi untuk mengambil informasi umum
 */
function getGeneralInfo() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT content FROM general_info ORDER BY id DESC LIMIT 1");
    $result = $stmt->fetch();
    return $result ? $result['content'] : '';
}

/**
 * Fungsi untuk menambah agenda baru
 */
function addAgendaItem($title, $date, $type) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO agenda_items (title, date, type) 
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$title, $date, $type]);
}

/**
 * Fungsi untuk menghapus agenda
 */
function deleteAgendaItem($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM agenda_items WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Fungsi untuk memperbarui informasi umum
 */
function updateGeneralInfo($content) {
    global $pdo;
    
    // Cek apakah sudah ada data
    $stmt = $pdo->query("SELECT COUNT(*) FROM general_info");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // Update data yang ada
        $stmt = $pdo->prepare("UPDATE general_info SET content = ? WHERE id = 1");
        return $stmt->execute([$content]);
    } else {
        // Insert data baru jika belum ada
        $stmt = $pdo->prepare("INSERT INTO general_info (content) VALUES (?)");
        return $stmt->execute([$content]);
    }
}

/**
 * Fungsi untuk mengambil agenda berdasarkan tipe dengan urutan tanggal terbaru
 */
function getAgendaItemsDesc($type, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, title, date 
        FROM agenda_items 
        WHERE type = ? 
        ORDER BY date DESC 
        LIMIT ?
    ");
    
    // Bind parameter dengan tipe data yang benar
    $stmt->bindParam(1, $type, PDO::PARAM_STR);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?>