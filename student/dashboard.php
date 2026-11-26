<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';


require_student();
$user = current_user();
$pdo = getDBConnection();

$userId = $user['id'];

$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_submissions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        IFNULL(SUM(views_count), 0) AS total_views
    FROM artworks 
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$myStats = $statsStmt->fetch();


$likesStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM artwork_likes l
    JOIN artworks a ON l.artwork_id = a.id
    WHERE a.user_id = ?
");
$likesStmt->execute([$userId]);
$totalLikes = (int)$likesStmt->fetchColumn();


$followersStmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
$followersStmt->execute([$userId]);
$followersCount = (int)$followersStmt->fetchColumn();

$recentArtworksStmt = $pdo->prepare("
    SELECT a.*, c.name AS category_name,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count
    FROM artworks a
    JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recentArtworksStmt->execute([$userId]);
$recentArtworks = $recentArtworksStmt->fetchAll();


$regExpoStmt = $pdo->prepare("
    SELECT e.*, p.registered_at, p.notes AS participant_notes
    FROM exhibition_participants p
    JOIN exhibitions e ON p.exhibition_id = e.id
    WHERE p.user_id = ?
    ORDER BY e.start_date ASC
");
$regExpoStmt->execute([$userId]);
$myExhibitions = $regExpoStmt->fetchAll();

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Student Dashboard</li>
    </ol>
  </nav>

  
  <div class="card border-0 rounded-4 p-4 p-md-5 mb-4 text-white" style="background: linear-gradient(135deg, #004d26 0%, #006633 100%);">
    <div class="row align-items-center">
      <div class="col-md-8">
        <span class="badge bg-white text-nsbm px-3 py-1 rounded-pill fw-bold mb-2 small">
          Student Creator Portal
        </span>
        <h2 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p class="text-white-50 mb-0">
          <?php echo htmlspecialchars($user['faculty']); ?> &bull; Batch <?php echo htmlspecialchars($_SESSION['user_faculty'] ?? '26.1'); ?> &bull; 
          <code><?php echo htmlspecialchars($user['student_id']); ?></code>
        </p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-warning btn-lg fw-bold shadow">
          <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload New Artwork
        </a>
      </div>
    </div>
  </div>

  
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-success-subtle text-success">
          <i class="bi bi-palette-fill"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Submissions</span>
          <h3 class="fw-bold mb-0 text-dark"><?php echo $myStats['total_submissions']; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-primary-subtle text-primary">
          <i class="bi bi-check2-circle"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Approved & Live</span>
          <h3 class="fw-bold mb-0 text-primary"><?php echo $myStats['approved_count'] ?? 0; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-danger-subtle text-danger">
          <i class="bi bi-heart-fill"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Total Likes</span>
          <h3 class="fw-bold mb-0 text-danger"><?php echo $totalLikes; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-info-subtle text-info">
          <i class="bi bi-people-fill"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Followers</span>
          <h3 class="fw-bold mb-0 text-info"><?php echo $followersCount; ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
 
    <div class="col-lg-3">
      <div class="dashboard-sidebar mb-4">
        <div class="text-center pb-3 border-bottom mb-3">
          <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($user['profile_pic']); ?>" 
               class="rounded-circle shadow-sm mb-2" width="70" height="70" style="object-fit:cover;">
          <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
          <span class="small text-muted"><?php echo htmlspecialchars($user['student_id']); ?></span>
        </div>

        <nav class="dashboard-nav">
          <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="nav-link active">
            <i class="bi bi-speedometer2"></i> Dashboard Overview
          </a>
          <a href="<?php echo SITE_URL; ?>/student/my_artworks.php" class="nav-link">
            <i class="bi bi-collection"></i> My Artworks (CRUD)
          </a>
          <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="nav-link">
            <i class="bi bi-plus-circle"></i> Upload Artwork
          </a>
          <a href="<?php echo SITE_URL; ?>/student/my_exhibitions.php" class="nav-link">
            <i class="bi bi-calendar-check"></i> My Exhibitions
          </a>
          <a href="<?php echo SITE_URL; ?>/student/profile.php" class="nav-link">
            <i class="bi bi-person-gear"></i> Edit Profile
          </a>
          <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $user['id']; ?>" class="nav-link">
            <i class="bi bi-globe"></i> View Public Portfolio
          </a>
        </nav>
      </div>
    </div>

   
    <div class="col-lg-9">
      
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0">Recent Art & Craft Submissions</h5>
          <a href="<?php echo SITE_URL; ?>/student/my_artworks.php" class="btn btn-outline-nsbm btn-sm">
            View All Submissions <i class="bi bi-arrow-right"></i>
          </a>
        </div>

        <?php if (empty($recentArtworks)): ?>
          <div class="text-center py-5">
            <i class="bi bi-palette fs-1 text-muted"></i>
            <h6 class="fw-bold mt-2">You haven't uploaded any artworks yet.</h6>
            <p class="small text-muted mb-3">Share your paintings, drawings, pottery, or photography with the NSBM community!</p>
            <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-nsbm btn-sm">Upload Your First Artwork</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Artwork</th>
                  <th>Category</th>
                  <th>Valuation</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentArtworks as $art): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="<?php echo artwork_image_url($art['image_path']); ?>" 
                             class="rounded-3 me-3" width="48" height="48" style="object-fit:cover;">
                        <div>
                          <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="fw-bold text-dark text-decoration-none d-block">
                            <?php echo htmlspecialchars($art['title']); ?>
                          </a>
                          <span class="small text-muted"><?php echo htmlspecialchars($art['medium'] ?? 'Handmade'); ?></span>
                        </div>
                      </div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($art['category_name']); ?></span></td>
                    <td class="small fw-semibold"><?php echo format_lkr($art['price']); ?></td>
                    <td>
                      <?php if ($art['status'] === 'approved'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                          <i class="bi bi-check-circle-fill me-1"></i> Approved
                        </span>
                      <?php elseif ($art['status'] === 'pending'): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                          <i class="bi bi-clock-history me-1"></i> Pending
                        </span>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                          <i class="bi bi-x-circle-fill me-1"></i> Rejected
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?php echo date('M d, Y', strtotime($art['created_at'])); ?></td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        <a href="<?php echo SITE_URL; ?>/student/edit_artwork.php?id=<?php echo $art['id']; ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="<?php echo SITE_URL; ?>/student/delete_artwork.php?id=<?php echo $art['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this artwork submission?');" title="Delete"><i class="bi bi-trash"></i></a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold mb-3"><i class="bi bi-calendar-event text-nsbm me-2"></i>My Registered Showcase Events</h5>
        <?php if (empty($myExhibitions)): ?>
          <p class="text-muted small mb-0">You have not registered for any upcoming campus exhibitions yet. <a href="<?php echo SITE_URL; ?>/exhibitions.php" class="text-nsbm fw-bold">Browse events here</a>.</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($myExhibitions as $expo): ?>
              <div class="col-md-6">
                <div class="p-3 border rounded-3 bg-light">
                  <span class="badge <?php echo ($expo['status'] === 'active') ? 'bg-success' : 'bg-primary'; ?> text-uppercase small mb-2"><?php echo $expo['status']; ?></span>
                  <h6 class="fw-bold mb-1">
                    <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" class="text-dark text-decoration-none">
                      <?php echo htmlspecialchars($expo['title']); ?>
                    </a>
                  </h6>
                  <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo htmlspecialchars($expo['venue']); ?></p>
                  <span class="small text-muted d-block">Registered: <?php echo date('M d, Y', strtotime($expo['registered_at'])); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
