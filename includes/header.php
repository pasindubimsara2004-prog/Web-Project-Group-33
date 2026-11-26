<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE; ?></title>
  
 
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
 
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
  
  <script>
    const SITE_URL = "<?php echo SITE_URL; ?>";
  </script>
</head>
<body>


<nav class="navbar navbar-expand-lg navbar-nsbm sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
      <img src="<?php echo SITE_URL; ?>/assets/images/nsbm_logo.png" alt="NSBM Green University Logo" width="36" height="38" class="me-2" style="object-fit: contain;">
      <span>
        NSBM <span class="text-nsbm">ArtCraft</span>
      </span>
    </a>
    
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/index.php">
            <i class="bi bi-house me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'gallery.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/gallery.php">
            <i class="bi bi-grid me-1"></i>Gallery
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'artists.php' || $currentPage === 'artist_profile.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/artists.php">
            <i class="bi bi-people me-1"></i>Artists
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === 'exhibitions.php' || $currentPage === 'exhibition_detail.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/exhibitions.php">
            <i class="bi bi-calendar-event me-1"></i>Exhibitions
          </a>
        </li>
      </ul>

      
      <div class="d-flex align-items-center gap-2">
        <?php if (is_logged_in()): ?>
          
          <?php if (is_student()): ?>
            <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-nsbm btn-sm d-none d-md-inline-flex align-items-center">
              <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Artwork
            </a>
          <?php elseif (is_admin()): ?>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-dark btn-sm d-none d-md-inline-flex align-items-center">
              <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
            </a>
          <?php endif; ?>

         
          <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center p-1 pe-3 border rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="rounded-circle me-2" width="34" height="34" style="object-fit:cover;">
              <span class="small fw-semibold text-truncate" style="max-width: 120px;">
                <?php echo htmlspecialchars($user['name']); ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
              <li class="px-3 py-2 border-bottom">
                <span class="d-block fw-bold"><?php echo htmlspecialchars($user['name']); ?></span>
                <span class="badge <?php echo is_admin() ? 'bg-danger' : 'bg-success'; ?> small">
                  <?php echo ucfirst($user['role']); ?> <?php echo is_student() ? '· ' . htmlspecialchars($user['student_id']) : ''; ?>
                </span>
              </li>

              <?php if (is_student()): ?>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/student/dashboard.php"><i class="bi bi-speedometer2 me-2 text-nsbm"></i>Student Dashboard</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/student/my_artworks.php"><i class="bi bi-collection me-2 text-nsbm"></i>My Submissions</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/student/upload_artwork.php"><i class="bi bi-plus-circle me-2 text-nsbm"></i>Upload New Artwork</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/student/my_exhibitions.php"><i class="bi bi-ticket-perforated me-2 text-nsbm"></i>Exhibition Registrations</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/student/profile.php"><i class="bi bi-person-gear me-2 text-nsbm"></i>My Profile</a></li>
              <?php elseif (is_admin()): ?>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/admin/artworks.php"><i class="bi bi-check2-circle me-2 text-primary"></i>Moderate Artworks</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/admin/categories.php"><i class="bi bi-tags me-2 text-primary"></i>Manage Categories</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/admin/exhibitions.php"><i class="bi bi-calendar-event me-2 text-primary"></i>Manage Exhibitions</a></li>
                <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>/admin/reports.php"><i class="bi bi-bar-chart-line me-2 text-primary"></i>System Reports</a></li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2 text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            </ul>
          </div>

        <?php else: ?>
          <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-nsbm btn-sm px-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </a>
          <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-nsbm btn-sm px-3">
            <i class="bi bi-person-plus-fill me-1"></i> Register
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>


<div class="container mt-3">
  <?php flash(); ?>
</div>

<main>
