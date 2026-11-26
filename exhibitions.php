<?php


$pageTitle = 'Campus Exhibitions';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();


$filter = trim($_GET['status'] ?? 'all');

$where = "1=1";
$params = [];
if (in_array($filter, ['upcoming', 'active', 'completed'])) {
    $where = "status = ?";
    $params[] = $filter;
}


$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exhibition_participants WHERE exhibition_id = e.id) AS participant_count
    FROM exhibitions e
    WHERE $where
    ORDER BY 
      CASE e.status 
        WHEN 'active' THEN 1 
        WHEN 'upcoming' THEN 2 
        ELSE 3 
      END,
      e.start_date ASC
");
$stmt->execute($params);
$exhibitions = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Exhibitions</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Campus Exhibitions</h2>
      <p class="text-muted small mb-0">Browse and participate in university showcases and art events</p>
    </div>
    
    <!-- Filter by status -->
    <div class="btn-group mt-3 mt-md-0" role="group">
      <a href="exhibitions.php?status=all" class="btn btn-sm <?php echo ($filter === 'all') ? 'btn-nsbm' : 'btn-outline-secondary'; ?>">All</a>
      <a href="exhibitions.php?status=active" class="btn btn-sm <?php echo ($filter === 'active') ? 'btn-nsbm' : 'btn-outline-secondary'; ?>">Active</a>
      <a href="exhibitions.php?status=upcoming" class="btn btn-sm <?php echo ($filter === 'upcoming') ? 'btn-nsbm' : 'btn-outline-secondary'; ?>">Upcoming</a>
      <a href="exhibitions.php?status=completed" class="btn btn-sm <?php echo ($filter === 'completed') ? 'btn-nsbm' : 'btn-outline-secondary'; ?>">Completed</a>
    </div>
  </div>

  <?php if (empty($exhibitions)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
      <i class="bi bi-calendar-x fs-1 text-muted"></i>
      <h5 class="fw-bold mt-3">No Exhibitions Found</h5>
      <p class="text-muted">There are currently no events matching this status.</p>
    </div>
  <?php else: ?>
    <div class="row g-4 mb-5">
      <?php foreach ($exhibitions as $expo): ?>
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100 d-flex flex-column">
            
            <div style="height: 220px; overflow:hidden; position:relative;">
              <img src="<?php echo SITE_URL . '/uploads/exhibitions/' . htmlspecialchars($expo['banner_image']); ?>" 
                   alt="<?php echo htmlspecialchars($expo['title']); ?>" 
                   class="w-100 h-100" style="object-fit:cover;">
              
              <span class="position-absolute top-0 start-0 m-3 badge <?php echo ($expo['status'] === 'active') ? 'bg-success' : (($expo['status'] === 'upcoming') ? 'bg-primary' : 'bg-secondary'); ?> px-3 py-2 text-uppercase fw-bold shadow">
                <i class="bi bi-circle-fill me-1 small"></i> <?php echo $expo['status']; ?>
              </span>
            </div>

            <div class="card-body p-4 d-flex flex-column flex-grow-1">
              <h4 class="fw-bold mb-2">
                <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" class="text-dark text-decoration-none">
                  <?php echo htmlspecialchars($expo['title']); ?>
                </a>
              </h4>

              <div class="text-warning small fw-semibold mb-3">
                <i class="bi bi-palette-fill me-1"></i> Theme: <?php echo htmlspecialchars($expo['theme']); ?>
              </div>

              <p class="small text-secondary mb-4 flex-grow-1">
                <?php echo htmlspecialchars($expo['description']); ?>
              </p>

              <div class="p-3 bg-light rounded-3 small text-muted mb-3">
                <div class="row g-2">
                  <div class="col-sm-6">
                    <i class="bi bi-calendar3 text-nsbm me-1"></i> <strong>Dates:</strong><br>
                    <?php echo date('M d, Y', strtotime($expo['start_date'])); ?> &ndash; <?php echo date('M d, Y', strtotime($expo['end_date'])); ?>
                  </div>
                  <div class="col-sm-6">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> <strong>Venue:</strong><br>
                    <?php echo htmlspecialchars($expo['venue']); ?>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                <span class="small text-muted">
                  <i class="bi bi-people-fill text-nsbm me-1"></i> <strong><?php echo $expo['participant_count']; ?></strong> Students Registered
                </span>

                <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" class="btn btn-nsbm btn-sm px-3">
                  View Details & Register <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>

            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
