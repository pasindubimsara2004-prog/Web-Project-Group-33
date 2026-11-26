<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();
$user = current_user();
$pdo = getDBConnection();

$pageTitle = 'Edit Profile';
$error = '';
$success = '';


$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $faculty = trim($_POST['faculty'] ?? '');
        $batch = trim($_POST['batch'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if (empty($name)) {
            $error = 'Name cannot be left blank.';
        } else {
            try {
                $avatarFilename = $userData['profile_pic'];

                
                if (isset($_FILES['profile_avatar']) && $_FILES['profile_avatar']['error'] === UPLOAD_ERR_OK) {
                    $targetDir = __DIR__ . '/../uploads/avatars';
                    $uploadRes = upload_image($_FILES['profile_avatar'], $targetDir);
                    if ($uploadRes['success']) {
                        $avatarFilename = $uploadRes['filename'];
                    } else {
                        $error = $uploadRes['error'];
                    }
                }

                if (empty($error)) {
                    $upd = $pdo->prepare("UPDATE users SET name = ?, faculty = ?, batch = ?, bio = ?, profile_pic = ? WHERE id = ?");
                    $upd->execute([$name, $faculty, $batch, $bio, $avatarFilename, $user['id']]);

                   
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_faculty'] = $faculty;
                    $_SESSION['profile_pic'] = $avatarFilename;

                    flash('success', 'Profile updated successfully!');
                    header('Location: ' . SITE_URL . '/student/profile.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $userData['password'])) {
            $error = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $pStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pStmt->execute([$hash, $user['id']]);

            flash('success', 'Password successfully changed!');
            header('Location: ' . SITE_URL . '/student/profile.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
          <div><?php echo htmlspecialchars($error); ?></div>
        </div>
      <?php endif; ?>

    
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
        
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($userData['profile_pic']); ?>" 
               class="rounded-circle shadow-sm me-3" width="60" height="60" style="object-fit:cover;">
          <div>
            <h4 class="fw-bold mb-0">Student Profile Information</h4>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($userData['student_id'] ?? 'NSBM Member'); ?> &bull; <?php echo htmlspecialchars($userData['email']); ?></p>
          </div>
        </div>

        <form action="profile.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="form_action" value="update_profile">

          <div class="mb-4">
            <label class="form-label fw-semibold small">Profile Picture / Avatar</label>
            <input type="file" name="profile_avatar" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
            <span class="small text-muted">Select an image to change avatar (Max 5MB).</span>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Student ID (Read-only)</label>
              <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($userData['student_id'] ?? 'ADMIN'); ?>" readonly>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label class="form-label fw-semibold small">Faculty</label>
              <select name="faculty" class="form-select">
                <option value="Faculty of Computing" <?php echo ($userData['faculty'] === 'Faculty of Computing') ? 'selected' : ''; ?>>Faculty of Computing</option>
                <option value="Faculty of Business" <?php echo ($userData['faculty'] === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
                <option value="Faculty of Engineering" <?php echo ($userData['faculty'] === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
                <option value="Faculty of Science" <?php echo ($userData['faculty'] === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold small">Batch</label>
              <input type="text" name="batch" class="form-control" value="<?php echo htmlspecialchars($userData['batch'] ?? '26.1'); ?>">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold small">Artist Biography / Creative Interests</label>
            <textarea name="bio" class="form-control" rows="4" placeholder="Tell other students about your art style, favorite mediums, creative passions..."><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
          </div>

          <button type="submit" class="btn btn-nsbm px-4">
            <i class="bi bi-check2-circle me-1"></i> Update Profile
          </button>
        </form>

      </div>

    
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
        
        <form action="profile.php" method="POST">
          <input type="hidden" name="form_action" value="change_password">

          <div class="mb-3">
            <label class="form-label fw-semibold small">Current Password</label>
            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Min 6 characters" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" minlength="6" placeholder="Re-type new password" required>
            </div>
          </div>

          <button type="submit" class="btn btn-outline-primary px-4">
            Update Password
          </button>
        </form>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
