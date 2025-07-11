<?php
require_once 'functions.php';

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database
require_once __DIR__ . '/../config/database.php';

function checkRememberToken() {
    global $pdo;
    
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_phone'])) {
        $token = $_COOKIE['remember_token'];
        $phone = '+62' . $_COOKIE['user_phone']; // Tambahkan +62 karena di cookie disimpan tanpa +62
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE whatsapp_number = ? AND remember_token = ?");
            $stmt->execute([$phone, $token]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Auto login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['whatsapp_number'] = $user['whatsapp_number'];
                $_SESSION['role'] = $user['role'];
                return true;
            } else {
                // Token tidak valid, hapus cookie
                setcookie('remember_token', '', time() - 3600, '/');
                setcookie('user_phone', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            error_log("Remember token check error: " . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * Memeriksa apakah user sudah login
 * 
 * @return bool True jika user sudah login, false jika belum
 */
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check for remember token
    return checkRememberToken();
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Mendapatkan peran user
 * 
 * @return string|null Peran user atau null jika tidak login
 */
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Redirect ke halaman sesuai peran user
 */
function redirectBasedOnRole() {
    if (isset($_SESSION['user_id'])) {
        switch ($_SESSION['role']) {
            case 'creative_director':
                header("Location: admin/dashboard.php");
                break;
            case 'content_team':
                header("Location: content/dashboard.php");
                break;
            case 'production_team':
                header("Location: production/dashboard.php");
                break;
            case 'marketing_team':
                header("Location: marketing/dashboard.php");
                break;
            case 'redaksi':
                header("Location: redaksi/dashboard.php");
                break;
            case 'redaktur_pelaksana':
                header("Location: redaktur/dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    }
}

/**
 * Memeriksa akses user berdasarkan peran yang dibutuhkan
 * 
 * @param string $requiredRole Peran yang dibutuhkan
 * @param string $redirectUrl URL redirect jika tidak memiliki akses
 */
function checkUserAccess($requiredRole, $redirectUrl = '../index.php') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $requiredRole) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Memeriksa apakah user memiliki akses ke halaman berdasarkan peran
 * 
 * @param array $allowedRoles Array peran yang diizinkan
 * @return bool True jika user memiliki akses, false jika tidak
 */
function checkAccess($allowedRoles = []) {
    // Jika tidak ada session user_id, redirect ke login
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Anda harus login terlebih dahulu';
        header('Location: /creative/login.php');
        exit();
    }
    
    // Jika tidak ada batasan peran, izinkan akses
    if (empty($allowedRoles)) {
        return true;
    }
    
    // Jika peran user ada dalam daftar peran yang diizinkan, izinkan akses
    if (in_array($_SESSION['role'], $allowedRoles)) {
        return true;
    }
    
    // Jika tidak memiliki akses, redirect ke halaman error
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini';
    header('Location: /creative/error.php');
    exit();
}

/**
 * Redirect ke halaman dashboard sesuai peran user jika sudah login
 */
function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        switch ($_SESSION['role']) {
            case 'creative_director':
                header('Location: /creative/admin/dashboard.php');
                break;
            case 'content_team':
                header('Location: /creative/content/dashboard.php');
                break;
            case 'production_team':
                header('Location: /creative/production/dashboard.php');
                break;
            case 'redaksi':
                header('Location: /creative/redaksi/dashboard.php');
                break;
            case 'redaktur_pelaksana':
                header('Location: /creative/redaktur/dashboard.php');
                break;
            default:
                header('Location: /creative/index.php');
        }
        exit();
    }
}

// Update status online pengguna
function updateUserActivity() {
    if (isLoggedIn()) {
        global $pdo;
        $userId = $_SESSION['user_id'];
        $sessionId = session_id();
        
        try {
            // Cek apakah sesi sudah ada
            $stmt = $pdo->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_id = ?");
            $stmt->execute([$userId, $sessionId]);
            $sessionExists = $stmt->fetch();
            
            if ($sessionExists) {
                // Update last_activity
                $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE id = ?");
                $stmt->execute([$sessionExists['id']]);
            } else {
                // Buat sesi baru
                $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id) VALUES (?, ?)");
                $stmt->execute([$userId, $sessionId]);
            }
        } catch (PDOException $e) {
            // Abaikan error jika tabel belum ada
        }
    }
}

// Panggil fungsi ini setiap kali halaman dimuat
updateUserActivity();
?>