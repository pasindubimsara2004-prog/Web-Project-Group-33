<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();
$pdo = getDBConnection();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $artId = (int)$_POST['artwork_id'];

    if ($_POST['action'] === 'approve') {
        $stmt = $pdo->prepare("UPDATE artworks SET status = 'approved', admin_notes = 'Approved by Administrator.' WHERE id = ?");
        $stmt->execute([$artId]);
        flash('success', 'Artwork has been approved.');
    } elseif ($_POST['action'] === 'reject') {
        $notes = trim($_POST['notes'] ?? 'Does not adhere to submission guidelines.');
        $stmt = $pdo->prepare("UPDATE artworks SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $artId]);
        flash('warning', 'Artwork has been rejected.');
    } elseif ($_POST['action'] === 'toggle_feature') {
        $stmt = $pdo->prepare("UPDATE artworks SET is_featured = NOT is_featured WHERE id = ?");
        $stmt->execute([$artId]);
        flash('info', 'Homepage featured status updated.');
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}


$statusFilter = trim($_GET['status'] ?? 'all');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
} elseif ($statusFilter === 'featured') {
    $where[] = "a.is_featured = 1";
}

if ($categoryId) {
    $where[] = "a.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($search)) {
    $where[] = "(a.title LIKE ? OR u.name LIKE ? OR u.student_id LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereClause = implode(' AND ', $where);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$sql = "
    SELECT a.*, u.name AS artist_name, u.student_id, u.faculty AS artist_faculty, c.name AS category_name,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE $whereClause
    ORDER BY a.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artworks = $stmt->fetchAll();

$pageTitle = 'Manage & Moderate Artworks';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Artworks Moderation</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Student Artworks & Handcrafts Moderation</h2>
      <p class="text-muted small mb-0">Approve or reject submissions, feature top works on the homepage, and maintain gallery quality</p>
    </div>
  </div>

  
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <form method="GET" action="artworks.php" class="row g-3">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-muted">Search Submissions</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control border-start-0" placeholder="Title, student name, student ID...">
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold text-muted">Category</label>
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold text-muted">Status</label>
        <select name="status" class="form-select">
          <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
          <option value="pending" <?php echo ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
          <option value="approved" <?php echo ($statusFilter === 'approved') ? 'selected' : ''; ?>>Approved</option>
          <option value="rejected" <?php echo ($statusFilter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
          <option value="featured" <?php echo ($statusFilter === 'featured') ? 'selected' : ''; ?>>Featured Only</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-nsbm w-100">Filter</button>
        <?php if (!empty($search) || $categoryId || $statusFilter !== 'all'): ?>
          <a href="artworks.php" class="btn btn-light border text-danger" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

 
  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0">Submissions (<?php echo count($artworks); ?>)</h5>
    </div>

    <?php if (empty($artworks)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-folder-x fs-1"></i>
        <h6 class="fw-bold mt-2">No Artworks Found</h6>
        <p class="small mb-0">No submissions matched your search criteria.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Artwork Piece</th>
              <th>Student Details</th>
              <th>Category</th>
              <th>Status</th>
              <th>Homepage Featured</th>
              <th>Engagement</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($artworks as $art): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="<?php echo artwork_image_url($art['image_path']); ?>" 
                         class="rounded-3 me-3" width="56" height="56" style="object-fit:cover;">
                    <div>
                      <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" target="_blank" class="fw-bold text-dark text-decoration-none d-block">
                        <?php echo htmlspecialchars($art['title']); ?>
                      </a>
                      <span class="small text-muted"><?php echo htmlspecialchars($art['medium'] ?? 'Handcraft'); ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <strong class="text-dark small d-block"><?php echo htmlspecialchars($art['artist_name']); ?></strong>
                  <span class="small text-muted"><?php echo htmlspecialchars($art['student_id'] ?? ''); ?></span>
                  <span class="small text-muted d-block"><?php echo htmlspecialchars($art['artist_faculty']); ?></span>
                </td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($art['category_name']); ?></span></td>
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
                <td>
                  <form method="POST" action="artworks.php" class="d-inline">
                    <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                    <input type="hidden" name="action" value="toggle_feature">
                    <?php if ($art['is_featured']): ?>
                      <button type="submit" class="btn btn-sm btn-warning fw-semibold shadow-sm" title="Click to remove from homepage feature">
                        <i class="bi bi-star-fill text-dark me-1"></i> Featured
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn btn-sm btn-outline-secondary" title="Click to feature on homepage">
                        <i class="bi bi-star me-1"></i> Make Featured
                      </button>
                    <?php endif; ?>
                  </form>
                </td>
                <td class="small text-muted">
                  <i class="bi bi-heart-fill text-danger me-1"></i> <?php echo $art['likes_count']; ?><br>
                  <i class="bi bi-star-fill text-warning me-1"></i> <?php echo number_format($art['avg_rating'], 1); ?>
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1">
                    <?php if ($art['status'] !== 'approved'): ?>
                      <form method="POST" action="artworks.php" class="d-inline">
                        <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-sm btn-success" title="Approve Artwork"><i class="bi bi-check-lg"></i></button>
                      </form>
                    <?php endif; ?>

                    <?php if ($art['status'] !== 'rejected'): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $art['id']; ?>" title="Reject Artwork">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    <?php endif; ?>

                    <a href="<?php echo SITE_URL; ?>/student/edit_artwork.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Artwork"><i class="bi bi-pencil"></i></a>
                    <a href="<?php echo SITE_URL; ?>/student/delete_artwork.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this artwork?');" title="Delete"><i class="bi bi-trash"></i></a>
                  </div>

                  <!-- Reject Feedback Modal -->
                  <div class="modal fade" id="rejectModal<?php echo $art['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content rounded-4 border-0 text-start">
                        <div class="modal-header border-0 pb-0">
                          <h5 class="modal-title fw-bold">Reject Artwork #<?php echo $art['id']; ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="artworks.php">
                          <input type="hidden" name="artwork_id" value="<?php echo $art['id']; ?>">
                          <input type="hidden" name="action" value="reject">
                          <div class="modal-body py-3">
                            <p class="small text-muted">Provide feedback notes to the student explaining why this piece was rejected.</p>
                            <textarea name="notes" class="form-control" rows="3" required placeholder="Explain why submission was rejected..."></textarea>
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

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
