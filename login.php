<?php
/**
 * BUYOERA - Admin Login Portal
 */

require_once dirname(__DIR__) . '/includes/functions.php';

if (is_admin()) {
    header("Location: index.php");
    exit;
}

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        if (!$email || empty($password)) {
            $error = 'Please enter administrator credentials.';
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && $user['role'] === 'admin' && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'admin';

                set_flash('success', 'Admin session started. Welcome, ' . htmlspecialchars($user['name']));
                header("Location: index.php");
                exit;
            } else {
                $error = 'Invalid administrator credentials or access denied.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | BUYOERA</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #071120 0%, #0a192f 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

  <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white" style="max-width: 440px; width: 100%;">
    <div class="text-center mb-4">
      <img src="../assets/images/logo.svg" alt="BUYOERA" height="42" class="mb-3">
      <h3 class="fw-bold text-navy mb-1">Admin Portal</h3>
      <p class="text-muted small">Authorized operations &amp; store fulfillment management.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
        <i class="bi bi-shield-x me-1"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label small fw-bold text-navy">Admin Email</label>
        <input type="email" name="email" class="form-control form-control-lg" placeholder="admin@buyoera.com" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@buyoera.com') ?>" required autofocus>
      </div>

      <div class="mb-4">
        <label class="form-label small fw-bold text-navy">Master Password</label>
        <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" value="Admin@1234" required>
      </div>

      <button type="submit" class="btn btn-buyoera-primary btn-lg w-100 rounded-pill mb-3">
        <i class="bi bi-shield-lock me-1"></i> Access Admin Console
      </button>
    </form>

    <div class="p-3 bg-light rounded-3 border small text-muted text-center">
      Default Credentials: <code>admin@buyoera.com</code> / <code>Admin@1234</code>
    </div>

    <div class="text-center mt-3">
      <a href="../index.php" class="small text-muted text-decoration-none">← Return to Storefront</a>
    </div>
  </div>

</body>
</html>
