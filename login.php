<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';


if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . SITE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . SITE_URL . '/student/dashboard.php');
    }
    exit;
}

$pageTitle = 'Login';
$error = '';
$email = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

  
    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        try {
            $pdo = getDBConnection();
            
         
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

           
            if ($user && password_verify($password, $user['password'])) {
                // Store user details in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['user_faculty'] = $user['faculty'];
                $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'default_avatar.svg';

                flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

            
                if ($user['role'] === 'admin') {
                    header('Location: ' . SITE_URL . '/admin/dashboard.php');
                } else {
                    header('Location: ' . SITE_URL . '/student/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
      
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-nsbm-light text-nsbm rounded-circle mb-3" style="width: 54px; height: 54px;">
            <i class="bi bi-box-arrow-in-right fs-3"></i>
          </div>
          <h3 class="fw-bold mb-1">Sign In</h3>
          <p class="text-muted small">Access your NSBM ArtCraft account</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="needs-validation" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold small">Email Address</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
              <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="student@nsbm.ac.lk" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <label for="password" class="form-label fw-semibold small mb-0">Password</label>
            </div>
            <div class="input-group mt-1">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
              <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
            </div>
          </div>

          <button type="submit" class="btn btn-nsbm w-100 py-2 mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </button>
        </form>

        <div class="text-center small text-muted mb-4">
          Don't have an account? 
          <a href="<?php echo SITE_URL; ?>/register.php" class="text-nsbm fw-bold text-decoration-none">Register here</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>

function setDemo(u, p) {
  document.getElementById('email').value = u;
  document.getElementById('password').value = p;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
