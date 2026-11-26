<?php
/**
 * BUYOERA - Customer Registration
 */

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: account.php");
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        $cleanPhone = str_replace([' ', '-', '+91'], '', $phone);

        if (empty($name)) {
            $error = 'Please enter your full name.';
        } elseif (!$email) {
            $error = 'Please enter a valid email address.';
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $cleanPhone)) {
            $error = 'Please enter a valid 10-digit Indian mobile number.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $db = Database::getConnection();

            // Check if email already exists
            $chk = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $chk->execute(['email' => $email]);
            if ($chk->fetch()) {
                $error = 'An account with this email already exists. Please log in.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("
                    INSERT INTO users (name, email, phone, password, role)
                    VALUES (:name, :email, :phone, :pass, 'customer')
                ");
                $ins->execute([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $cleanPhone,
                    'pass' => $hashedPassword
                ]);

                $newId = $db->lastInsertId();

                // Auto login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';

                set_flash('success', 'Account created successfully! Welcome to BUYOERA.');
                header("Location: account.php");
                exit;
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
          <img src="assets/images/logo.svg" alt="<?= SITE_NAME ?>" height="42" class="mb-3">
          <h2 class="h4 fw-bold text-navy">Join BUYOERA</h2>
          <p class="text-muted small">Create your account for faster checkout, order tracking and members-only deals.</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
            <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label small fw-bold text-navy">Full Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-navy">Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-navy">10-Digit Indian Mobile Number *</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted fw-bold">+91</span>
              <input type="tel" name="phone" class="form-control" placeholder="9820112233" maxlength="10" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-navy">Create Password *</label>
            <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-navy">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
          </div>

          <button type="submit" class="btn btn-buyoera-accent btn-lg w-100 rounded-pill">
            Create BUYOERA Account
          </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
          <p class="small text-muted mb-0">Already have an account? <a href="login.php" class="fw-bold text-primary">Sign in</a></p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
