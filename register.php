<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';


if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/student/dashboard.php');
    exit;
}

$pageTitle = 'Student Registration';
$error = '';
$name = '';
$student_id = '';
$email = '';
$faculty = 'Faculty of Computing';
$batch = '26.1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $faculty = trim($_POST['faculty'] ?? 'Faculty of Computing');
    $batch = trim($_POST['batch'] ?? '26.1');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

  
    if (empty($name) || empty($student_id) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDBConnection();

         
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                $checkId = $pdo->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
                $checkId->execute([$student_id]);
                if ($checkId->fetch()) {
                    $error = 'This Student ID is already registered.';
                } else {
                  
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $defaultAvatar = 'default_avatar.svg';

                    $stmt = $pdo->prepare("
                        INSERT INTO users (student_id, name, email, password, role, faculty, batch, profile_pic)
                        VALUES (?, ?, ?, ?, 'student', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $student_id,
                        $name,
                        $email,
                        $hash,
                        $faculty,
                        $batch,
                        $defaultAvatar
                    ]);

                    flash('success', 'Your account has been created successfully! Please log in.');
                    header('Location: ' . SITE_URL . '/login.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-nsbm-light text-nsbm rounded-circle mb-3" style="width: 54px; height: 54px;">
            <i class="bi bi-person-plus-fill fs-3"></i>
          </div>
          <h3 class="fw-bold mb-1">Create Student Account</h3>
          <p class="text-muted small">Showcase your arts, crafts, and photography at NSBM</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="needs-validation" novalidate>
          
          <div class="mb-3">
            <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="e.g. Kasun Chamara" required>
            <div class="invalid-feedback">Please enter your name.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label for="student_id" class="form-label fw-semibold small">Student ID <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>" placeholder="NSBM/SOC/26.1/XXXX" required>
              <div class="invalid-feedback">Please enter your Student ID.</div>
            </div>
            <div class="col-md-5">
              <label for="batch" class="form-label fw-semibold small">Batch <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="batch" name="batch" value="<?php echo htmlspecialchars($batch); ?>" placeholder="26.1" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="faculty" class="form-label fw-semibold small">Faculty <span class="text-danger">*</span></label>
            <select class="form-select" id="faculty" name="faculty" required>
              <option value="Faculty of Computing" <?php echo ($faculty === 'Faculty of Computing') ? 'selected' : ''; ?>>Faculty of Computing</option>
              <option value="Faculty of Business" <?php echo ($faculty === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
              <option value="Faculty of Engineering" <?php echo ($faculty === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
              <option value="Faculty of Science" <?php echo ($faculty === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="kasun@students.nsbm.ac.lk" required>
            <div class="invalid-feedback">Please enter a valid email.</div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Min 6 characters" required>
              <div class="invalid-feedback">At least 6 characters required.</div>
            </div>
            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-semibold small">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" placeholder="Re-type password" required>
              <div class="invalid-feedback">Passwords must match.</div>
            </div>
          </div>

          <button type="submit" class="btn btn-nsbm w-100 py-2 mb-3">
            <i class="bi bi-person-check-fill me-1"></i> Register
          </button>
        </form>

        <div class="text-center small text-muted">
          Already registered? 
          <a href="<?php echo SITE_URL; ?>/login.php" class="text-nsbm fw-bold text-decoration-none">Sign In</a>
        </div>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
