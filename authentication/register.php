<?php
// User registration
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$error = '';
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($name) < 2) {
        $error = 'Please enter your name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, password, role, verification_token) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone ?: null, $hash, 'user', $token]);

            $uid = $conn->lastInsertId();
            $_SESSION['user_id'] = $uid;
            $_SESSION['full_name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            $_SESSION['profile_image'] = null;

            add_notification($uid, 'Welcome!', 'Your Sonam Homestay account is ready.', BASE_URL . 'pages/search.php');
            set_flash('success', 'Account created successfully!');
            redirect(BASE_URL . 'user/dashboard.php');
        }
    }
}

$pageTitle = 'Register';
$hideNav = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-page animate__animated animate__fadeIn">
    <!-- Visual Panel -->
    <div class="auth-visual d-flex flex-column justify-content-between py-5 px-5">
        <a href="<?= BASE_URL ?>" class="sn-brand text-white mb-4 d-inline-flex align-items-center gap-2">
            <span class="brand-mark"><i class="fas fa-home"></i></span>
            <span class="text-white fw-bold h4 m-0">Sonam Homestay</span>
        </a>
        <div>
            <h2 class="display-font text-white h1 mb-3 fw-bold" style="line-height: 1.3">Find your home away from home.</h2>
            <p class="text-white-50 mb-0 fs-5">Access thousands of local, cozy, and verified stays and connect with hosts who treat you like family.</p>
        </div>
        <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-3 rounded-3 mt-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-quote-left text-teal fs-3"></i>
            <div class="text-white">
                <p class="small mb-1 text-white-50 font-italic">"Registering was a breeze. Now I book stays in local homes whenever I travel—it's cheaper and much more authentic!"</p>
                <small class="fw-bold text-white">— Priya M., Traveler explorer</small>
            </div>
        </div>
    </div>
    
    <!-- Form Wrapper -->
    <div class="auth-form-wrap">
        <div class="auth-card border">
            <!-- Mobile Brand Header -->
            <div class="text-center mb-4 d-lg-none">
                <a href="<?= BASE_URL ?>" class="sn-brand mb-2">
                    <span class="brand-mark"><i class="fas fa-mountain"></i></span>
                    <span class="brand-text"><?= e(APP_NAME) ?></span>
                </a>
            </div>
            
            <h1 class="h3 display-font fw-bold mb-1">Create guest account</h1>
            <p class="text-muted small mb-4">Start your journey with us today.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrf_field() ?>
                
                <!-- Full Name Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Full name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-user"></i></span>
                        <input type="text" name="full_name" class="form-control border-start-0 ps-0" value="<?= e($name) ?>" required>
                    </div>
                </div>
                
                <!-- Email Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0" value="<?= e($email) ?>" required>
                    </div>
                </div>
                
                <!-- Phone Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Phone number (Optional)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-phone-alt" style="font-size: 0.85rem"></i></span>
                        <input type="text" name="phone" class="form-control border-start-0 ps-0" value="<?= e($phone) ?>">
                    </div>
                </div>
                
                <!-- Password Input -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0" required>
                        <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; height: 100%; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                
                <!-- Confirm Password Input -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Confirm password</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-shield-halved" style="font-size: 0.85rem"></i></span>
                        <input type="password" name="password_confirm" class="form-control border-start-0 border-end-0 ps-0" required>
                        <button type="button" class="password-toggle" style="border: 1px solid var(--sn-border); border-left: 0; background: transparent; border-radius: 0 var(--sn-radius) var(--sn-radius) 0; height: 100%; padding: 0 0.9rem;" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold"><i class="fas fa-user-plus me-2"></i>Sign Up</button>
            </form>
            

            
            <p class="mt-4 mb-0 text-center text-muted small">
                Already have an account? <a href="<?= BASE_URL ?>authentication/login.php" class="fw-semibold text-teal text-decoration-none">Sign in here</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
