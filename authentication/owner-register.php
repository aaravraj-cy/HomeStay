<?php
// Owner registration - ONLY ONE OWNER allowed in the system
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . (is_owner() ? 'owner' : 'user') . '/dashboard.php');
}

// Block if owner already exists
if (owner_count() > 0) {
    set_flash('error', 'Owner account already exists. Only one owner is allowed.');
    redirect(BASE_URL . 'authentication/login.php');
}

$error = '';
$name = $email = $phone = $business = $city = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    // Double check one owner only
    if (owner_count() > 0) {
        $error = 'Owner already registered.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $business = trim($_POST['business_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (strlen($name) < 2 || strlen($business) < 2) {
            $error = 'Name and business name are required.';
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
                $error = 'Email already used.';
            } else {
                try {
                    $conn->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, password, role, city) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $email, $phone ?: null, $hash, 'owner', $city ?: null]);
                    $uid = $conn->lastInsertId();

                    $stmt2 = $conn->prepare('INSERT INTO owners (user_id, business_name) VALUES (?, ?)');
                    $stmt2->execute([$uid, $business]);
                    $conn->commit();

                    $_SESSION['user_id'] = $uid;
                    $_SESSION['full_name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'owner';
                    $_SESSION['profile_image'] = null;

                    add_notification($uid, 'Welcome Host!', 'Add your first homestay to get started.', BASE_URL . 'owner/add-homestay.php');
                    set_flash('success', 'Owner account created!');
                    redirect(BASE_URL . 'owner/dashboard.php');
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = 'Registration failed. Try again.';
                }
            }
        }
    }
}

$pageTitle = 'Owner Register';
$hideNav = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-page">
    <div class="auth-visual"><div><h2>Become the Host</h2><p>Only one owner account is allowed</p></div></div>
    <div class="auth-form-wrap">
        <div class="auth-card">
            <h1>Owner registration</h1>
            <p class="text-muted small">This system allows only <strong>one owner</strong>.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">Full name</label><input type="text" name="full_name" class="form-control" value="<?= e($name) ?>" required></div>
                <div class="mb-3"><label class="form-label">Business name</label><input type="text" name="business_name" class="form-control" value="<?= e($business) ?>" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($email) ?>" required></div>
                <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($phone) ?>"></div>
                <div class="mb-3"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?= e($city) ?>"></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirm" class="form-control" required></div>
                <button type="submit" class="btn btn-primary w-100">Create owner account</button>
            </form>
            <p class="mt-3 text-center mb-0"><a href="<?= BASE_URL ?>authentication/login.php">Back to login</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
