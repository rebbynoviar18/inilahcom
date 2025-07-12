<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect jika sudah login
redirectIfLoggedIn();

$error = '';
$success = '';

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $phone = $_POST['forgot_phone'];
    
    if (!empty($phone)) {
        $phone_input = '+62' . $phone;
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE whatsapp_number = ?");
            $stmt->execute([$phone_input]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$token, $expires, $user['id']]);
                
                $success = "Link reset password telah dikirim. Silakan hubungi admin untuk mendapatkan link reset.";
            } else {
                $error = "Nomor HP tidak terdaftar";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Nomor HP harus diisi";
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    
    if (!empty($phone) && !empty($password)) {
        $phone_input = '+62' . $phone;
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, whatsapp_number, password, role, active FROM users WHERE whatsapp_number = ?");
            $stmt->execute([$phone_input]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['active'] == 1) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['whatsapp_number'] = $user['whatsapp_number'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Set cookie jika remember me dicentang
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30 hari
                        
                        // Simpan token di database
                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->execute([$token, $user['id']]);
                        
                        // Set cookie - simpan tanpa +62 untuk kemudahan
                        setcookie('remember_token', $token, $expires, '/');
                        setcookie('user_phone', $phone, $expires, '/'); // $phone adalah input tanpa +62
                    }
                             
                
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'creative_director':
                            header('Location: /admin/dashboard.php');
                            break;
                        case 'content_team':
                            header('Location: /content/dashboard.php');
                            break;
                        case 'production_team':
                            header('Location: /production/dashboard.php');
                            break;
                        case 'redaksi':
                            header('Location: /redaksi/dashboard.php');
                            break;
                        case 'redaktur_pelaksana':
                            header('Location: /redaktur/dashboard.php');
                            break;
                        case 'marketing':
                            header('Location: /marketing/dashboard.php');
                            break;
                        default:
                            header('Location: /index.php');
                    }
                    exit();
                } else {
                    $error = "Akun Anda tidak aktif. Silakan hubungi administrator.";
                }
            } else {
                $error = "Nomor HP atau password salah";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Nomor HP dan password harus diisi";
    }
}

$pageTitle = "Login";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Creative Task Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f8fafc;
            --accent-color: #10b981;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--secondary-color);
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
        }

        /* Left Side - Login Form */
        .login-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: white;
            position: relative;
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
            animation: slideInLeft 0.8s ease-out;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-1);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        .logo-icon i {
            font-size: 24px;
            color: white;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .input-group {
            position: relative;
            display: flex;
            width: 100%;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
            flex: 1;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        /* Password input group styling */
        .password-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-group .form-control {
            border-radius: 12px;
            padding-right: 50px;
            border: 2px solid var(--border-color);
            width: 100%;
        }

        .password-input-group .form-control:focus {
            border: 2px solid var(--primary-color);
        }

        .btn-toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .btn-toggle-password:hover {
            color: var(--primary-color);
        }

        .input-group-text {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
            border-radius: 12px 0 0 12px;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--gradient-1);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .btn-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 1.5rem;
            animation: slideInDown 0.5s ease-out;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
        }

        .alert-info {
            background: #ecfeff;
            color: #0d9488;
        }

                /* Right Side - Slider */
        .login-right {
            flex: 1;
            background: var(--gradient-1);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-height: 100vh;
        }

        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .bg-shape:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 20%;
            animation-delay: 2s;
        }

        .bg-shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 30%;
            animation-delay: 4s;
        }

        .slider-container {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            max-width: 500px;
            padding: 2rem;
        }

        .slide {
            display: none;
            animation: fadeIn 0.8s ease-in-out;
        }

        .slide.active {
            display: block;
        }

        .slide-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .slide-icon i {
            font-size: 32px;
            color: white;
        }

        .slide-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .slide-description {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .slider-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 3;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* Animations */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-right {
                min-height: 300px;
                flex: none;
            }
            
            .login-left {
                padding: 1rem;
            }
            
            .slide-title {
                font-size: 24px;
            }
            
            .slide-description {
                font-size: 16px;
            }
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .btn-secondary {
            background: var(--border-color);
            border: none;
            color: var(--text-dark);
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
            color: var(--text-dark);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Login Form -->
        <div class="login-left">
            <div class="login-form-wrapper">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h1 class="login-title">Selamat Datang</h1>
                    <p class="login-subtitle">Masuk ke Creative Task Management System</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <div class="input-group phone-input">
                            <span class="input-group-text">+62</span>
                            <input type="text" class="form-control" id="phone" name="phone" required 
                                   placeholder="81234567890" 
                                   pattern="[0-9]{10,13}"
                                   title="Masukkan nomor HP tanpa 0 di depan (contoh: 81234567890)"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : (isset($_COOKIE['user_phone']) ? htmlspecialchars($_COOKIE['user_phone']) : ''); ?>">
                        </div>
                        <small class="text-muted">Masukkan nomor HP tanpa 0 di depan</small>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-input-group">
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password Anda">
                            <button class="btn-toggle-password" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="login" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Masuk
                        </button>
                    </div>

                    <div class="text-center">
                        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                            Lupa Password?
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Slider -->
        <div class="login-right">
            <div class="bg-animation">
                <div class="bg-shape"></div>
                <div class="bg-shape"></div>
                <div class="bg-shape"></div>
            </div>
            
            <div class="slider-container">
                <div class="slide active">
                    <div class="slide-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h2 class="slide-title">Tingkatkan Produktivitas</h2>
                    <p class="slide-description">
                        Kelola tugas tim kreatif Anda dengan lebih efisien dan terorganisir. 
                        Pantau progress real-time dan capai target bersama.
                    </p>
                </div>

                <div class="slide">
                    <div class="slide-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="slide-title">Kolaborasi Tim</h2>
                    <p class="slide-description">
                        Bekerja sama dengan tim production, content, marketing, dan redaksi 
                        dalam satu platform terintegrasi.
                    </p>
                </div>

                <div class="slide">
                    <div class="slide-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2 class="slide-title">Analisis Performa</h2>
                    <p class="slide-description">
                        Dapatkan insight mendalam tentang performa tim dan individual 
                        dengan sistem poin dan laporan komprehensif.
                    </p>
                </div>

                <div class="slide">
                    <div class="slide-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h2 class="slide-title">Akses Dimana Saja</h2>
                    <p class="slide-description">
                        Responsive design yang memungkinkan Anda mengakses sistem 
                        dari desktop, tablet, atau smartphone.
                    </p>
                </div>
            </div>

            <div class="slider-dots">
                <span class="dot active" data-slide="0"></span>
                <span class="dot" data-slide="1"></span>
                <span class="dot" data-slide="2"></span>
                                <span class="dot" data-slide="3"></span>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Masukkan nomor HP Anda untuk mendapatkan bantuan reset password dari admin.
                        </div>
                        <div class="form-group">
                            <label for="forgot_phone" class="form-label">Nomor HP</label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="text" class="form-control" id="forgot_phone" name="forgot_phone" required 
                                       placeholder="81234567890" 
                                       pattern="[0-9]{10,13}"
                                       title="Masukkan nomor HP tanpa 0 di depan">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="forgot_password" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>
                            Kirim Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Slider Functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto slide every 4 seconds
        setInterval(nextSlide, 4000);

        // Dot click handlers
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        // Form Animation on Focus
        const formInputs = document.querySelectorAll('.form-control');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Auto hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>