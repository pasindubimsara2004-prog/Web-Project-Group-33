<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();

$artistId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$artistId) {
    flash('danger', 'Invalid artist requested.');
    header('Location: ' . SITE_URL . '/artists.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) AS followers_count,
           (SELECT COUNT(*) FROM user_follows WHERE follower_id = u.id) AS following_count,
           (SELECT COUNT(*) FROM artworks WHERE user_id = u.id AND status = 'approved') AS total_artworks,
           (SELECT COUNT(*) FROM artwork_likes l JOIN artworks a ON l.artwork_id = a.id WHERE a.user_id = u.id) AS total_likes
    FROM users u
    WHERE u.id = ? AND u.role = 'student'
    LIMIT 1
");
$stmt->execute([$artistId]);
$artist = $stmt->fetch();

if (!$artist) {
    flash('danger', 'Student artist profile not found.');
    header('Location: ' . SITE_URL . '/artists.php');
    exit;
}

$isFollowing = false;
$isOwnProfile = false;
if (is_logged_in()) {
    $currentUserId = current_user()['id'];
    $isOwnProfile = ($currentUserId == $artistId);
    
    $fCheck = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $fCheck->execute([$currentUserId, $artistId]);
    $isFollowing = (bool)$fCheck->fetch();
}


$statusFilter = ($isOwnProfile || is_admin()) ? "1=1" : "a.status = 'approved'";
$artworksStmt = $pdo->prepare("
    SELECT a.*, c.name AS category_name,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           (SELECT COUNT(*) FROM artwork_ratings WHERE artwork_id = a.id) AS ratings_count
    FROM artworks a
    JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ? AND $statusFilter
    ORDER BY a.created_at DESC
");
$artworksStmt->execute([$artistId]);
$artworks = $artworksStmt->fetchAll();

$pageTitle = $artist['name'] . "'s Creative Portfolio";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/artists.php" class="text-decoration-none text-muted">Artists</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($artist['name']); ?></li>
    </ol>
  </nav>


  <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 bg-white">
    <div class="row align-items-center g-4">
      <div class="col-md-3 text-center">
        <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($artist['profile_pic']); ?>" 
             alt="<?php echo htmlspecialchars($artist['name']); ?>" 
             class="rounded-circle shadow" width="130" height="130" style="object-fit:cover;">
      </div>
      
      <div class="col-md-6 text-center text-md-start">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
          <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($artist['name']); ?></h2>
          <span class="badge bg-success-subtle text-success border border-success-subtle">NSBM Student Artist</span>
        </div>
        
        <p class="text-muted small mb-2">
          <i class="bi bi-mortarboard me-1"></i> <?php echo htmlspecialchars($artist['faculty']); ?> &bull; 
          <span>Batch <?php echo htmlspecialchars($artist['batch']); ?></span>
          <?php if (is_admin() || $isOwnProfile): ?>
            &bull; <code><?php echo htmlspecialchars($artist['student_id']); ?></code>
          <?php endif; ?>
        </p>

        <p class="text-secondary mb-0">
          <?php echo nl2br(htmlspecialchars($artist['bio'] ?? 'This student artist hasn\'t added a biography yet.')); ?>
        </p>
      </div>

      <div class="col-md-3">
        <div class="p-3 bg-light rounded-3 text-center border">
          <div class="row g-2 mb-3">
            <div class="col-4">
              <span class="fs-5 fw-bold d-block text-dark"><?php echo $artist['total_artworks']; ?></span>
              <span class="text-muted small" style="font-size: 0.75rem;">Works</span>
            </div>
            <div class="col-4">
              <span class="fs-5 fw-bold d-block text-dark"><?php echo $artist['total_likes']; ?></span>
              <span class="text-muted small" style="font-size: 0.75rem;">Likes</span>
            </div>
            <div class="col-4">
              <span class="fs-5 fw-bold d-block text-dark followers-count-<?php echo $artist['id']; ?>"><?php echo $artist['followers_count']; ?></span>
              <span class="text-muted small" style="font-size: 0.75rem;">Followers</span>
            </div>
          </div>

          <?php if ($isOwnProfile): ?>
            <a href="<?php echo SITE_URL; ?>/student/profile.php" class="btn btn-outline-secondary btn-sm w-100">
              <i class="bi bi-pencil-square me-1"></i> Edit Profile
            </a>
          <?php elseif (is_logged_in()): ?>
            <button type="button" 
                    class="btn btn-sm w-100 <?php echo $isFollowing ? 'btn-secondary' : 'btn-nsbm'; ?> btn-follow-toggle" 
                    data-artist-id="<?php echo $artist['id']; ?>">
              <i class="bi bi-person-check me-1"></i> <span class="follow-text"><?php echo $isFollowing ? 'Following' : 'Follow Artist'; ?></span>
            </button>
          <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-nsbm btn-sm w-100">
              Follow Artist
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Artworks & Handcrafts by <?php echo htmlspecialchars($artist['name']); ?></h4>
    <span class="text-muted small"><?php echo count($artworks); ?> Items</span>
  </div>

  <?php if (empty($artworks)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
      <i class="bi bi-palette fs-1 text-muted"></i>
      <h5 class="fw-bold mt-3">No Showcase Items Yet</h5>
      <p class="text-muted">This student has not yet published any approved artwork.</p>
    </div>
  <?php else: ?>
    <div class="row g-4 mb-5">
      <?php foreach ($artworks as $art): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card card-art h-100">
            <div class="artwork-img-container">
              <span class="badge-art-category">
                <?php echo htmlspecialchars($art['category_name']); ?>
              </span>
              <?php if ($isOwnProfile || is_admin()): ?>
                <span class="position-absolute top-0 end-0 m-3 badge <?php echo ($art['status'] === 'approved') ? 'bg-success' : (($art['status'] === 'pending') ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                  <?php echo ucfirst($art['status']); ?>
                </span>
              <?php endif; ?>
              <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>">
                <img src="<?php echo artwork_image_url($art['image_path']); ?>" alt="<?php echo htmlspecialchars($art['title']); ?>" loading="lazy">
              </a>
            </div>
            
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title fw-bold mb-0">
                  <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="text-dark text-decoration-none text-truncate d-block" style="max-width: 210px;">
                    <?php echo htmlspecialchars($art['title']); ?>
                  </a>
                </h5>
                <span class="badge bg-light text-secondary border">
                  <?php echo format_lkr($art['price']); ?>
                </span>
              </div>
              
              <p class="small text-muted mb-2">
                <i class="bi bi-brush text-nsbm me-1"></i> <?php echo htmlspecialchars($art['medium'] ?? 'Handmade Craft'); ?>
              </p>

              <div class="mb-3">
                <?php echo render_star_rating($art['avg_rating'], true, $art['ratings_count']); ?>
              </div>

              <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                <span class="small text-muted"><i class="bi bi-heart-fill text-danger me-1"></i> <?php echo $art['likes_count']; ?> Likes</span>
                <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-nsbm">
                  View Piece <i class="bi bi-arrow-right"></i>
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
