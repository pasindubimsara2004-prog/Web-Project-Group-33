<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student();
$user = current_user();
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT p.*, e.title AS exhibition_title, e.theme, e.start_date, e.end_date, e.venue, e.status AS exhibition_status, e.banner_image,
           a.title AS artwork_title, a.image_path AS artwork_image
    FROM exhibition_participants p
    JOIN exhibitions e ON p.exhibition_id = e.id
    LEFT JOIN artworks a ON p.artwork_id = a.id
    WHERE p.user_id = ?
    ORDER BY e.start_date ASC
");
$stmt->execute([$user['id']]);
$registrations = $stmt->fetchAll();

$pageTitle = 'My Exhibitions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Exhibitions</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">My Exhibition Participation & Registrations</h2>
      <p class="text-muted small mb-0">Manage your showcase events, registered stalls, and exhibition submissions</p>
    </div>
    <div class="mt-3 mt-md-0">
      <a href="<?php echo SITE_URL; ?>/exhibitions.php" class="btn btn-nsbm">
        <i class="bi bi-calendar-plus me-1"></i> Browse Upcoming Exhibitions
      </a>
    </div>
  </div>

  <?php if (empty($registrations)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
      <i class="bi bi-calendar-x fs-1 text-muted"></i>
      <h5 class="fw-bold mt-3">No Active Registrations</h5>
      <p class="text-muted small mb-3">You haven't signed up to participate in any campus showcases or art exhibitions yet.</p>
      <div>
        <a href="<?php echo SITE_URL; ?>/exhibitions.php" class="btn btn-nsbm btn-sm">Explore Campus Exhibitions</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($registrations as $reg): ?>
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100 d-flex flex-column">
            
            <div style="height: 160px; overflow:hidden; position:relative;">
              <img src="<?php echo SITE_URL . '/uploads/exhibitions/' . htmlspecialchars($reg['banner_image']); ?>" 
                   alt="<?php echo htmlspecialchars($reg['exhibition_title']); ?>" 
                   class="w-100 h-100" style="object-fit:cover;">
              
              <span class="position-absolute top-0 start-0 m-3 badge <?php echo ($reg['exhibition_status'] === 'active') ? 'bg-success' : 'bg-primary'; ?> px-3 py-2 text-uppercase fw-bold shadow">
                <?php echo $reg['exhibition_status']; ?>
              </span>
            </div>

            <div class="card-body p-4 d-flex flex-column flex-grow-1">
              <h5 class="fw-bold mb-1">
                <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $reg['exhibition_id']; ?>" class="text-dark text-decoration-none">
                  <?php echo htmlspecialchars($reg['exhibition_title']); ?>
                </a>
              </h5>
              <p class="text-warning small fw-semibold mb-3">Theme: <?php echo htmlspecialchars($reg['theme']); ?></p>

              <div class="p-3 bg-light rounded-3 small text-muted mb-3">
                <div class="mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <strong>Venue:</strong> <?php echo htmlspecialchars($reg['venue']); ?></div>
                <div><i class="bi bi-calendar3 text-nsbm me-1"></i> <strong>Dates:</strong> <?php echo date('M d, Y', strtotime($reg['start_date'])); ?> &ndash; <?php echo date('M d, Y', strtotime($reg['end_date'])); ?></div>
              </div>

              <?php if (!empty($reg['artwork_title'])): ?>
                <div class="p-2 bg-white rounded border d-flex align-items-center gap-2 mb-3">
                  <?php if (!empty($reg['artwork_image'])): ?>
                    <img src="<?php echo artwork_image_url($reg['artwork_image']); ?>" 
                         class="rounded" width="40" height="40" style="object-fit:cover;">
                  <?php endif; ?>
                  <div>
                    <span class="small text-muted d-block" style="font-size:0.75rem;">Selected Showcase Artwork:</span>
                    <strong class="text-dark small"><?php echo htmlspecialchars($reg['artwork_title']); ?></strong>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($reg['notes'])): ?>
                <p class="small text-muted fst-italic mb-3">
                  <strong>Special Requests:</strong> <?php echo htmlspecialchars($reg['notes']); ?>
                </p>
              <?php endif; ?>

              <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                <span class="small text-muted">Signed up: <?php echo date('M d, Y', strtotime($reg['registered_at'])); ?></span>
                
                <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $reg['exhibition_id']; ?>&cancel_registration=1" 
                   class="btn btn-outline-danger btn-sm" 
                   onclick="return confirm('Cancel this registration?');">
                  Cancel Registration
                </a>
              </div>

            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
