<?php
/**
 * BUYOERA - Forgot Password Handler
 */

require_once __DIR__ . '/includes/functions.php';

$message = null;
$error = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(24));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $upd = $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
                $upd->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);

                $resetLink = "reset-password.php?token=" . urlencode($token);
                $message = "A password reset link has been generated. For testing and security, you can use the link below:";
            } else {
                // Generic response to avoid email enumeration
                $message = "If an account matches that email address, password reset instructions have been generated.";
            }
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
          <div class="fs-1 text-primary mb-2"><i class="bi bi-key"></i></div>
          <h2 class="h4 fw-bold text-navy">Forgot Password?</h2>
          <p class="text-muted small">Enter your email and we'll send you instructions to reset your password.</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
          <div class="alert alert-success py-3 px-3 small rounded-3 mb-3">
            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($message) ?>
            <?php if ($resetLink): ?>
              <div class="mt-3 p-2 bg-white rounded border">
                <strong>Reset Link:</strong><br>
                <a href="<?= htmlspecialchars($resetLink) ?>" class="text-break text-primary fw-bold"><?= htmlspecialchars($resetLink) ?></a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <form action="forgot-password.php" method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-bold text-navy">Registered Email Address</label>
            <input type="email" name="email" class="form-control form-control-lg" placeholder="name@example.com" required autofocus>
          </div>
          <button type="submit" class="btn btn-buyoera-primary btn-lg w-100 rounded-pill">
            Send Reset Instructions
          </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
          <a href="login.php" class="small text-muted text-decoration-none">← Back to Sign In</a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
