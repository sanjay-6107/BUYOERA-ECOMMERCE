<?php
/**
 * BUYOERA - Password Reset Page
 */

require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
if (empty($token)) {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires > :now LIMIT 1");
$stmt->execute(['token' => $token, 'now' => date('Y-m-d H:i:s')]);
$user = $stmt->fetch();

$error = null;
if (!$user) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $newPass = trim($_POST['password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        if (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password = :pass, reset_token = NULL, reset_expires = NULL WHERE id = :id");
            $upd->execute(['pass' => $hashed, 'id' => $user['id']]);

            set_flash('success', 'Your password has been successfully reset! Please sign in with your new password.');
            header("Location: login.php");
            exit;
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
          <h2 class="h4 fw-bold text-navy">Set New Password</h2>
          <p class="text-muted small">Enter and confirm your new secure password.</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
          <form action="reset-password.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="mb-3">
              <label class="form-label small fw-bold text-navy">New Password *</label>
              <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required autofocus>
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-navy">Confirm New Password *</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn btn-buyoera-primary btn-lg w-100 rounded-pill">
              Update Password
            </button>
          </form>
        <?php else: ?>
          <div class="text-center mt-3">
            <a href="forgot-password.php" class="btn btn-outline-primary rounded-pill px-4">Request New Reset Link</a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
