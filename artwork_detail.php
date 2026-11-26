<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();

$artworkId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$artworkId) {
    flash('danger', 'Artwork not found.');
    header('Location: ' . SITE_URL . '/gallery.php');
    exit;
}


$stmt = $pdo->prepare("
    SELECT a.*, u.id AS artist_id, u.name AS artist_name, u.email AS artist_email, 
           u.faculty AS artist_faculty, u.batch AS artist_batch, u.profile_pic AS artist_avatar, u.bio AS artist_bio,
           c.name AS category_name, c.slug AS category_slug,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           (SELECT COUNT(*) FROM artwork_ratings WHERE artwork_id = a.id) AS ratings_count
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
    LIMIT 1
");
$stmt->execute([$artworkId]);
$art = $stmt->fetch();

if (!$art) {
    flash('danger', 'Artwork not found or has been removed.');
    header('Location: ' . SITE_URL . '/gallery.php');
    exit;
}

if ($art['status'] !== 'approved') {
    if (!is_admin() && (!is_student() || current_user()['id'] != $art['user_id'])) {
        flash('warning', 'This artwork is currently pending review by administrators.');
        header('Location: ' . SITE_URL . '/gallery.php');
        exit;
    }
}


$pdo->prepare("UPDATE artworks SET views_count = views_count + 1 WHERE id = ?")->execute([$artworkId]);


$currentUserId = is_logged_in() ? current_user()['id'] : null;
$hasLiked = false;
$userRating = null;
$isFollowingArtist = false;

if ($currentUserId) {
    $likeCheck = $pdo->prepare("SELECT id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
    $likeCheck->execute([$artworkId, $currentUserId]);
    $hasLiked = (bool)$likeCheck->fetch();

    $ratingCheck = $pdo->prepare("SELECT rating FROM artwork_ratings WHERE artwork_id = ? AND user_id = ?");
    $ratingCheck->execute([$artworkId, $currentUserId]);
    $ratingRow = $ratingCheck->fetch();
    if ($ratingRow) {
        $userRating = (int)$ratingRow['rating'];
    }

    $followCheck = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $followCheck->execute([$currentUserId, $art['artist_id']]);
    $isFollowingArtist = (bool)$followCheck->fetch();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    require_login();
    $commentText = trim($_POST['comment'] ?? '');
    if (empty($commentText)) {
        flash('warning', 'Comment cannot be empty.');
    } else {
        $insertComment = $pdo->prepare("INSERT INTO artwork_comments (artwork_id, user_id, comment) VALUES (?, ?, ?)");
        $insertComment->execute([$artworkId, $currentUserId, $commentText]);
        flash('success', 'Your comment has been posted!');
        header("Location: " . SITE_URL . "/artwork_detail.php?id=" . $artworkId . "#comments-section");
        exit;
    }
}

if (isset($_GET['delete_comment']) && is_numeric($_GET['delete_comment'])) {
    require_login();
    $commentId = (int)$_GET['delete_comment'];
    
    
    $commStmt = $pdo->prepare("SELECT user_id FROM artwork_comments WHERE id = ?");
    $commStmt->execute([$commentId]);
    $comment = $commStmt->fetch();
    if ($comment && ($comment['user_id'] == $currentUserId || is_admin())) {
        $delStmt = $pdo->prepare("DELETE FROM artwork_comments WHERE id = ?");
        $delStmt->execute([$commentId]);
        flash('info', 'Comment removed.');
    }
    header("Location: " . SITE_URL . "/artwork_detail.php?id=" . $artworkId . "#comments-section");
    exit;
}

$commentsStmt = $pdo->prepare("
    SELECT c.*, u.name AS commenter_name, u.profile_pic AS commenter_avatar, u.role AS commenter_role
    FROM artwork_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.artwork_id = ?
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$artworkId]);
$comments = $commentsStmt->fetchAll();

$relatedStmt = $pdo->prepare("
    SELECT a.*, u.name AS artist_name, u.profile_pic AS artist_avatar,
           c.name AS category_name
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'approved' AND a.category_id = ? AND a.id != ?
    ORDER BY a.created_at DESC
    LIMIT 3
");
$relatedStmt->execute([$art['category_id'], $artworkId]);
$relatedArtworks = $relatedStmt->fetchAll();

$pageTitle = $art['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
 
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/gallery.php" class="text-decoration-none text-muted">Gallery</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/gallery.php?category=<?php echo $art['category_id']; ?>" class="text-decoration-none text-muted"><?php echo htmlspecialchars($art['category_name']); ?></a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page"><?php echo htmlspecialchars($art['title']); ?></li>
    </ol>
  </nav>

  <?php if ($art['status'] !== 'approved'): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
      <i class="bi bi-clock-history fs-4 me-3"></i>
      <div>
        <strong>Status Notice:</strong> This artwork is currently 
        <span class="badge bg-warning text-dark text-uppercase"><?php echo $art['status']; ?></span>.
        It is only visible to you and administrators until approved.
        <?php if (!empty($art['admin_notes'])): ?>
          <div class="mt-1 small">Admin Feedback: <em><?php echo htmlspecialchars($art['admin_notes']); ?></em></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-5">
    
   
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="position-relative text-center p-3" style="background: #f8fafc;">
          <img src="<?php echo artwork_image_url($art['image_path']); ?>" 
               alt="<?php echo htmlspecialchars($art['title']); ?>" 
               class="img-fluid rounded-3 shadow-sm" 
               style="max-height: 540px; width: auto; object-fit: contain;">
        </div>

        <div class="card-body p-4 border-top">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
       
            <button type="button" 
                    class="btn <?php echo $hasLiked ? 'btn-danger' : 'btn-outline-danger'; ?> btn-artwork-like px-4 py-2" 
                    data-artwork-id="<?php echo $art['id']; ?>">
              <i class="bi <?php echo $hasLiked ? 'bi-heart-fill' : 'bi-heart'; ?> me-2"></i>
              <span class="like-text"><?php echo $hasLiked ? 'Liked' : 'Like'; ?></span>
              (<span class="like-count"><?php echo $art['likes_count']; ?></span>)
            </button>

            
            <div class="text-muted small">
              <span class="me-3"><i class="bi bi-eye-fill me-1"></i> <?php echo number_format($art['views_count']); ?> Views</span>
              <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($art['created_at'])); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-nsbm-light text-nsbm px-3 py-2 rounded-pill fw-semibold">
            <?php echo htmlspecialchars($art['category_name']); ?>
          </span>
          <?php if ($art['is_featured']): ?>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
              <i class="bi bi-star-fill me-1"></i> Featured Showcase
            </span>
          <?php endif; ?>
        </div>

        <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($art['title']); ?></h2>
        
        
        <div class="d-flex align-items-center mb-3">
          <?php echo render_star_rating($art['avg_rating'], false); ?>
          <span class="ms-2 fw-bold" id="average-rating-display"><?php echo number_format($art['avg_rating'], 1); ?></span>
          <span class="text-muted small ms-1">(<?php echo $art['ratings_count']; ?> reviews)</span>
        </div>

       
        <div class="p-3 bg-light rounded-3 mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">Valuation / Price:</span>
            <span class="fs-4 fw-bold text-nsbm"><?php echo format_lkr($art['price']); ?></span>
          </div>
          <?php if ($art['is_for_sale']): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle small mt-1">
              <i class="bi bi-check2-circle me-1"></i> Available for Purchase / Inquiries
            </span>
          <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary small mt-1">
              <i class="bi bi-eye me-1"></i> Exhibition & Display Only
            </span>
          <?php endif; ?>
        </div>

       
        <div class="mb-4">
          <h6 class="fw-bold text-muted small text-uppercase">Description</h6>
          <p class="text-secondary" style="white-space: pre-line;"><?php echo htmlspecialchars($art['description']); ?></p>
        </div>

        
        <div class="row g-2 mb-4 text-secondary small border-top pt-3">
          <div class="col-6">
            <span class="d-block text-muted">Medium / Technique</span>
            <strong class="text-dark"><?php echo htmlspecialchars($art['medium'] ?? 'Not specified'); ?></strong>
          </div>
          <div class="col-6">
            <span class="d-block text-muted">Dimensions / Scale</span>
            <strong class="text-dark"><?php echo htmlspecialchars($art['dimensions'] ?? 'Standard'); ?></strong>
          </div>
        </div>

       
        <div class="border rounded-3 p-3 bg-light d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($art['artist_avatar']); ?>" 
                 alt="<?php echo htmlspecialchars($art['artist_name']); ?>" 
                 class="rounded-circle me-3 border" width="50" height="50" style="object-fit:cover;">
            <div>
              <h6 class="mb-0 fw-bold">
                <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $art['artist_id']; ?>" class="text-dark text-decoration-none">
                  <?php echo htmlspecialchars($art['artist_name']); ?>
                </a>
              </h6>
              <span class="small text-muted d-block"><?php echo htmlspecialchars($art['artist_faculty']); ?> (<?php echo htmlspecialchars($art['artist_batch']); ?>)</span>
            </div>
          </div>

          <?php if (is_logged_in() && current_user()['id'] != $art['artist_id']): ?>
            <button type="button" 
                    class="btn btn-sm <?php echo $isFollowingArtist ? 'btn-secondary' : 'btn-outline-nsbm'; ?> btn-follow-toggle" 
                    data-artist-id="<?php echo $art['artist_id']; ?>">
              <span class="follow-text"><?php echo $isFollowingArtist ? 'Following' : 'Follow'; ?></span>
            </button>
          <?php endif; ?>
        </div>

      </div>

    
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h6 class="fw-bold mb-2"><i class="bi bi-star-fill text-warning me-1"></i> Rate This Creation</h6>
        <p class="small text-muted mb-3">Give this student's work a score from 1 to 5 stars.</p>
        
        <?php if (is_logged_in()): ?>
          <div class="d-flex align-items-center gap-3">
            <div class="star-rating-select">
              <input type="radio" id="star5" name="user_star" value="5" data-artwork-id="<?php echo $art['id']; ?>" <?php echo ($userRating === 5) ? 'checked' : ''; ?>><label for="star5"><i class="bi bi-star-fill"></i></label>
              <input type="radio" id="star4" name="user_star" value="4" data-artwork-id="<?php echo $art['id']; ?>" <?php echo ($userRating === 4) ? 'checked' : ''; ?>><label for="star4"><i class="bi bi-star-fill"></i></label>
              <input type="radio" id="star3" name="user_star" value="3" data-artwork-id="<?php echo $art['id']; ?>" <?php echo ($userRating === 3) ? 'checked' : ''; ?>><label for="star3"><i class="bi bi-star-fill"></i></label>
              <input type="radio" id="star2" name="user_star" value="2" data-artwork-id="<?php echo $art['id']; ?>" <?php echo ($userRating === 2) ? 'checked' : ''; ?>><label for="star2"><i class="bi bi-star-fill"></i></label>
              <input type="radio" id="star1" name="user_star" value="1" data-artwork-id="<?php echo $art['id']; ?>" <?php echo ($userRating === 1) ? 'checked' : ''; ?>><label for="star1"><i class="bi bi-star-fill"></i></label>
            </div>
          </div>
          <div id="rating-status-message" class="small mt-2">
            <?php if ($userRating): ?>
              <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> You rated this <?php echo $userRating; ?> stars.</span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-light border small mb-0">
            Please <a href="<?php echo SITE_URL; ?>/login.php" class="text-nsbm fw-bold">sign in</a> to leave a rating and review.
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  
  <div class="row" id="comments-section">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
        
        <h4 class="fw-bold mb-4">
          <i class="bi bi-chat-left-text-fill text-nsbm me-2"></i> Community Comments (<?php echo count($comments); ?>)
        </h4>

        
        <?php if (is_logged_in()): ?>
          <form method="POST" action="artwork_detail.php?id=<?php echo $art['id']; ?>" class="mb-5">
            <input type="hidden" name="action" value="add_comment">
            <div class="d-flex gap-3">
              <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars(current_user()['profile_pic']); ?>" 
                   alt="Avatar" class="rounded-circle" width="44" height="44" style="object-fit:cover;">
              <div class="flex-grow-1">
                <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Write an encouraging review or constructive critique..." required></textarea>
                <button type="submit" class="btn btn-nsbm btn-sm px-4">
                  <i class="bi bi-send-fill me-1"></i> Post Comment
                </button>
              </div>
            </div>
          </form>
        <?php else: ?>
          <div class="alert alert-light border text-center py-4 mb-4 rounded-3">
            <i class="bi bi-chat-quote fs-2 text-muted"></i>
            <p class="mb-2 mt-2">Want to join the discussion or give feedback on this artwork?</p>
            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-nsbm btn-sm px-4">Sign In to Comment</a>
          </div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
          <div class="text-center py-4 text-muted">
            <p>No comments posted yet. Be the first to share your thoughts!</p>
          </div>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($comments as $comm): ?>
              <div class="p-3 rounded-3 bg-light border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex align-items-center">
                    <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($comm['commenter_avatar']); ?>" 
                         class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                    <div>
                      <strong class="text-dark small d-block"><?php echo htmlspecialchars($comm['commenter_name']); ?></strong>
                      <span class="text-muted" style="font-size: 0.75rem;"><?php echo time_ago($comm['created_at']); ?></span>
                    </div>
                  </div>

                  <?php if (is_logged_in() && ($comm['user_id'] == current_user()['id'] || is_admin())): ?>
                    <a href="artwork_detail.php?id=<?php echo $art['id']; ?>&delete_comment=<?php echo $comm['id']; ?>" 
                       class="text-danger small" 
                       onclick="return confirm('Are you sure you want to delete this comment?');"
                       title="Delete comment">
                      <i class="bi bi-trash"></i>
                    </a>
                  <?php endif; ?>
                </div>

                <p class="mb-0 text-secondary small" style="white-space: pre-line;"><?php echo htmlspecialchars($comm['comment']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

   
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold mb-3">Related in <?php echo htmlspecialchars($art['category_name']); ?></h5>
        
        <?php if (empty($relatedArtworks)): ?>
          <p class="small text-muted mb-0">No other artworks in this category yet.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($relatedArtworks as $rel): ?>
              <div class="d-flex gap-3 align-items-center border-bottom pb-3">
                <img src="<?php echo artwork_image_url($rel['image_path']); ?>" 
                     class="rounded-3" width="70" height="70" style="object-fit:cover;">
                <div class="flex-grow-1">
                  <h6 class="mb-1 fw-bold">
                    <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $rel['id']; ?>" class="text-dark text-decoration-none small text-truncate d-block" style="max-width: 180px;">
                      <?php echo htmlspecialchars($rel['title']); ?>
                    </a>
                  </h6>
                  <span class="small text-muted d-block"><?php echo htmlspecialchars($rel['artist_name']); ?></span>
                  <span class="badge bg-light text-secondary border mt-1"><?php echo format_lkr($rel['price']); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
