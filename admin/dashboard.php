<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';


require_admin();
$admin = current_user();
$pdo = getDBConnection();


$statsQuery = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM artworks) AS total_artworks,
        (SELECT COUNT(*) FROM artworks WHERE status = 'pending') AS pending_artworks,
        (SELECT COUNT(*) FROM artworks WHERE status = 'approved') AS approved_artworks,
        (SELECT COUNT(*) FROM artworks WHERE is_featured = 1) AS featured_artworks,
        (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
        (SELECT COUNT(*) FROM categories) AS total_categories,
        (SELECT COUNT(*) FROM exhibitions) AS total_exhibitions,
        (SELECT COUNT(*) FROM exhibition_participants) AS total_participants
");
$stats = $statsQuery->fetch();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $artId = (int)$_POST['artwork_id'];

    if ($_POST['action'] === 'approve') {
        $stmt = $pdo->prepare("UPDATE artworks SET status = 'approved', admin_notes = ? WHERE id = ?");
        $stmt->execute(['Approved by Administrator.', $artId]);
        flash('success', 'Artwork has been approved.');
    } elseif ($_POST['action'] === 'reject') {
        $notes = trim($_POST['notes'] ?? 'Does not meet submission guidelines.');
        $stmt = $pdo->prepare("UPDATE artworks SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $artId]);
        flash('warning', 'Artwork has been marked as rejected.');
    } elseif ($_POST['action'] === 'toggle_feature') {
        $stmt = $pdo->prepare("UPDATE artworks SET is_featured = NOT is_featured WHERE id = ?");
        $stmt->execute([$artId]);
        flash('info', 'Homepage featured status updated.');
    }
    header('Location: ' . SITE_URL . '/admin/dashboard.php');
    exit;
}


$pendingStmt = $pdo->query("
    SELECT a.*, u.name AS artist_name, u.student_id, u.faculty AS artist_faculty, c.name AS category_name
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'pending'
    ORDER BY a.created_at ASC
    LIMIT 6
");
$pendingArtworks = $pendingStmt->fetchAll();


$recentApprovedStmt = $pdo->query("
    SELECT a.*, u.name AS artist_name, c.name AS category_name
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'approved'
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recentApproved = $recentApprovedStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Admin Dashboard</li>
    </ol>
  </nav>

  
  <div class="card border-0 rounded-4 p-4 p-md-5 mb-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
    <div class="row align-items-center">
      <div class="col-md-8">
        <span class="badge bg-danger px-3 py-1 rounded-pill fw-bold mb-2 small">
          <i class="bi bi-shield-lock-fill me-1"></i> NSBM Administration Portal
        </span>
        <h2 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($admin['name']); ?></h2>
        <p class="text-white-50 mb-0">NSBM ArtCraft Administration Panel</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-warning fw-bold shadow">
          <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate Reports
        </a>
      </div>
    </div>
  </div>

 
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-warning-subtle text-warning">
          <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Pending Review</span>
          <h3 class="fw-bold mb-0 text-warning"><?php echo $stats['pending_artworks']; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-success-subtle text-success">
          <i class="bi bi-check2-circle"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Approved Artworks</span>
          <h3 class="fw-bold mb-0 text-success"><?php echo $stats['approved_count'] ?? $stats['approved_artworks']; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-primary-subtle text-primary">
          <i class="bi bi-people-fill"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Student Creators</span>
          <h3 class="fw-bold mb-0 text-primary"><?php echo $stats['total_students']; ?></h3>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="stat-card">
        <div class="stat-icon bg-info-subtle text-info">
          <i class="bi bi-calendar-event-fill"></i>
        </div>
        <div>
          <span class="text-muted small fw-semibold">Campus Exhibitions</span>
          <h3 class="fw-bold mb-0 text-info"><?php echo $stats['total_exhibitions']; ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    
    <div class="col-lg-3">
      <div class="dashboard-sidebar mb-4">
        <span class="text-muted small fw-bold text-uppercase d-block mb-3 px-3">Management Modules</span>
        <nav class="dashboard-nav">
          <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="nav-link active">
            <i class="bi bi-speedometer2"></i> Dashboard Overview
          </a>
          <a href="<?php echo SITE_URL; ?>/admin/artworks.php" class="nav-link">
            <i class="bi bi-palette"></i> Moderate Artworks
            <?php if ($stats['pending_artworks'] > 0): ?>
              <span class="badge bg-warning text-dark ms-auto"><?php echo $stats['pending_artworks']; ?></span>
            <?php endif; ?>
          </a>
          <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="nav-link">
            <i class="bi bi-tags"></i> Manage Categories
          </a>
          <a href="<?php echo SITE_URL; ?>/admin/exhibitions.php" class="nav-link">
            <i class="bi bi-calendar-check"></i> Manage Exhibitions
          </a>
          <a href="<?php echo SITE_URL; ?>/admin/users.php" class="nav-link">
            <i class="bi bi-person-badge"></i> Student Accounts
          </a>
          <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="nav-link">
            <i class="bi bi-bar-chart-line"></i> Analytics & Reports
          </a>
        </nav>
      </div>
    </div>

    
    <div class="col-lg-9">
      
      
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="fw-bold mb-0"><i class="bi bi-inbox-fill text-warning me-2"></i>Pending Artworks Requiring Moderation</h5>
            <span class="small text-muted">Review, approve, or reject student submissions</span>
          </div>
          <a href="<?php echo SITE_URL; ?>/admin/artworks.php?status=pending" class="btn btn-outline-warning btn-sm text-dark">
            View All Pending (<?php echo $stats['pending_artworks']; ?>)
          </a>
        </div>

        <?php if (empty($pendingArtworks)): ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-check-all fs-1 text-success"></i>
            <h6 class="fw-bold mt-2">All Caught Up!</h6>
            <p class="small mb-0">No submissions are currently awaiting moderation.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Artwork</th>
                  <th>Student Creator</th>
                  <th>Category</th>
                  <th>Submitted</th>
                  <th class="text-end">Moderation</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingArtworks as $art): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="<?php echo artwork_image_url($art['image_path']); ?>" 
                             class="rounded-3 me-3" width="50" height="50" style="object-fit:cover;">
                        <div>
                          <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                            <?php echo htmlspecialchars($art['title']); ?>
                          </a>
                          <span class="small text-muted d-block"><?php echo htmlspecialchars($art['medium'] ?? 'Handcraft'); ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <strong class="text-dark small d-block"><?php echo htmlspecialchars($art['artist_name']); ?></strong>
                      <span class="small text-muted"><?php echo htmlspecialchars($art['student_id']); ?></span>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($art['category_name']); ?></span></td>
                    <td class="small text-muted"><?php echo time_ago($art['created_at']); ?></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <form method="POST" action="dashboard.php" class="d-inline">
                          <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                          <input type="hidden" name="action" value="approve">
                          <button type="submit" class="btn btn-sm btn-success" title="Approve">
                            <i class="bi bi-check-lg"></i> Approve
                          </button>
                        </form>

                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $art['id']; ?>" title="Reject">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </div>

                     
                      <div class="modal fade" id="rejectModal<?php echo $art['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content rounded-4 border-0 text-start">
                            <div class="modal-header border-0 pb-0">
                              <h5 class="modal-title fw-bold">Reject Artwork Submission</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="dashboard.php">
                              <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                              <input type="hidden" name="action" value="reject">
                              <div class="modal-body py-3">
                                <p class="small text-muted">Provide feedback notes to the student explaining why this piece was rejected or how it can be improved.</p>
                                <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Image resolution is too blurry, or please include proper dimension details..." required></textarea>
                              </div>
                              <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>

                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      
      <div class="row g-3">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
            <i class="bi bi-tags-fill fs-2 text-nsbm mb-2"></i>
            <h6 class="fw-bold">Art Categories</h6>
            <p class="small text-muted mb-3">Add or edit disciplines (Painting, Sketching, Pottery, Woodwork)</p>
            <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-outline-nsbm btn-sm mt-auto">Manage Categories</a>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
            <i class="bi bi-calendar-plus-fill fs-2 text-primary mb-2"></i>
            <h6 class="fw-bold">Campus Exhibitions</h6>
            <p class="small text-muted mb-3">Organize upcoming expos, set dates, and review student stall registrations</p>
            <a href="<?php echo SITE_URL; ?>/admin/exhibitions.php" class="btn btn-outline-primary btn-sm mt-auto">Manage Exhibitions</a>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
            <i class="bi bi-bar-chart-line-fill fs-2 text-success mb-2"></i>
            <h6 class="fw-bold">Reports & Exports</h6>
            <p class="small text-muted mb-3">Print or export summary reports of submissions, student engagement & expos</p>
            <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-outline-success btn-sm mt-auto">View Reports</a>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
