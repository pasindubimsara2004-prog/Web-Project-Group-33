<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student();
$user = current_user();
$pdo = getDBConnection();


$statusFilter = trim($_GET['status'] ?? 'all');

$where = ["a.user_id = ?"];
$params = [$user['id']];

if (in_array($statusFilter, ['approved', 'pending', 'rejected'])) {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT a.*, c.name AS category_name,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           (SELECT COUNT(*) FROM artwork_ratings WHERE artwork_id = a.id) AS ratings_count
    FROM artworks a
    JOIN categories c ON a.category_id = c.id
    WHERE $whereClause
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$artworks = $stmt->fetchAll();

$pageTitle = 'My Submissions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">My Submissions</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">My Artwork & Handcraft Submissions</h2>
      <p class="text-muted small mb-0">Track moderation approval, view engagement metrics, and edit or manage your creations</p>
    </div>
    <div class="mt-3 mt-md-0">
      <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-nsbm">
        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload New Artwork
      </a>
    </div>
  </div>

  <ul class="nav nav-pills mb-4">
    <li class="nav-item">
      <a class="nav-link <?php echo ($statusFilter === 'all') ? 'active bg-nsbm' : 'text-dark'; ?>" href="my_artworks.php?status=all">
        All Submissions
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo ($statusFilter === 'approved') ? 'active bg-success' : 'text-dark'; ?>" href="my_artworks.php?status=approved">
        <i class="bi bi-check-circle-fill me-1 text-success"></i> Approved
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo ($statusFilter === 'pending') ? 'active bg-warning text-dark' : 'text-dark'; ?>" href="my_artworks.php?status=pending">
        <i class="bi bi-clock-history me-1 text-warning"></i> Pending Review
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo ($statusFilter === 'rejected') ? 'active bg-danger' : 'text-dark'; ?>" href="my_artworks.php?status=rejected">
        <i class="bi bi-x-circle-fill me-1 text-danger"></i> Needs Revision / Rejected
      </a>
    </li>
  </ul>

  <?php if (empty($artworks)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
      <i class="bi bi-folder2-open fs-1 text-muted"></i>
      <h5 class="fw-bold mt-3">No Submissions Found</h5>
      <p class="text-muted small mb-3">You do not have any submissions in this category.</p>
      <div>
        <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-nsbm btn-sm">Upload Artwork Now</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($artworks as $art): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card card-art h-100 d-flex flex-column">
            
            <div class="artwork-img-container">
              <span class="badge-art-category">
                <?php echo htmlspecialchars($art['category_name']); ?>
              </span>
              
              <span class="position-absolute top-0 end-0 m-3 badge <?php 
                echo ($art['status'] === 'approved') ? 'bg-success' : (($art['status'] === 'pending') ? 'bg-warning text-dark' : 'bg-danger'); 
              ?> shadow-sm">
                <?php echo ucfirst($art['status']); ?>
              </span>

              <img src="<?php echo artwork_image_url($art['image_path']); ?>" alt="<?php echo htmlspecialchars($art['title']); ?>">
            </div>

            <div class="card-body p-4 d-flex flex-column flex-grow-1">
              <h5 class="card-title fw-bold mb-1">
                <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="text-dark text-decoration-none">
                  <?php echo htmlspecialchars($art['title']); ?>
                </a>
              </h5>

              <p class="small text-muted mb-2">
                <i class="bi bi-brush text-nsbm me-1"></i> <?php echo htmlspecialchars($art['medium'] ?? 'Handmade'); ?>
              </p>

              <div class="d-flex justify-content-between align-items-center mb-3 small">
                <span class="text-muted"><i class="bi bi-heart-fill text-danger me-1"></i> <?php echo $art['likes_count']; ?> Likes</span>
                <span class="text-muted"><i class="bi bi-eye me-1"></i> <?php echo $art['views_count']; ?> Views</span>
                <span class="fw-bold text-nsbm"><?php echo format_lkr($art['price']); ?></span>
              </div>

              <?php if (!empty($art['admin_notes'])): ?>
                <div class="p-2 rounded bg-light border small text-muted mb-3">
                  <strong>Moderator Feedback:</strong><br>
                  <em><?php echo htmlspecialchars($art['admin_notes']); ?></em>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-eye"></i> View
                </a>
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo SITE_URL; ?>/student/edit_artwork.php?id=<?php echo $art['id']; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                  </a>
                  <a href="<?php echo SITE_URL; ?>/student/delete_artwork.php?id=<?php echo $art['id']; ?>" 
                     class="btn btn-outline-danger" 
                     onclick="return confirm('Are you sure you want to permanently delete this artwork?');">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </div>

            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
