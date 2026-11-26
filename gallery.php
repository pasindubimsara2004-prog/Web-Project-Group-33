<?php


$pageTitle = 'Art Gallery';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();


$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
$filter = trim($_GET['filter'] ?? ''); // 'featured', 'sale'
$sort = trim($_GET['sort'] ?? 'newest'); // 'newest', 'likes', 'rating', 'price_asc', 'price_desc'


$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();


$whereConditions = ["a.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(a.title LIKE ? OR a.description LIKE ? OR a.medium LIKE ? OR u.name LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId) {
    $whereConditions[] = "a.category_id = ?";
    $params[] = $categoryId;
}

if ($filter === 'featured') {
    $whereConditions[] = "a.is_featured = 1";
} elseif ($filter === 'sale') {
    $whereConditions[] = "a.is_for_sale = 1";
}

$whereClause = implode(' AND ', $whereConditions);

$orderBy = "a.created_at DESC";
if ($sort === 'likes') {
    $orderBy = "likes_count DESC, a.created_at DESC";
} elseif ($sort === 'rating') {
    $orderBy = "avg_rating DESC, a.created_at DESC";
} elseif ($sort === 'price_asc') {
    $orderBy = "a.price ASC";
} elseif ($sort === 'price_desc') {
    $orderBy = "a.price DESC";
}

$countSql = "
    SELECT COUNT(*) FROM artworks a
    JOIN users u ON a.user_id = u.id
    WHERE $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);


$sql = "
    SELECT a.*, u.name AS artist_name, u.faculty AS artist_faculty, u.profile_pic AS artist_avatar,
           c.name AS category_name, c.slug AS category_slug,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           (SELECT COUNT(*) FROM artwork_ratings WHERE artwork_id = a.id) AS ratings_count
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artworks = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Gallery</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Student Arts & Handcraft Gallery</h2>
      <p class="text-muted small mb-0">Showing <?php echo number_format($totalItems); ?> approved creative works from NSBM students</p>
    </div>
    <?php if (is_student()): ?>
      <div class="mt-3 mt-md-0">
        <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-nsbm">
          <i class="bi bi-cloud-arrow-up-fill me-1"></i> Submit New Artwork
        </a>
      </div>
    <?php endif; ?>
  </div>

  
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <form action="gallery.php" method="GET" class="row g-3">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-muted">Keywords</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control border-start-0" placeholder="Search title, artist, medium...">
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-semibold text-muted">Filter By Category</label>
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
        <label class="form-label small fw-semibold text-muted">Sort By</label>
        <select name="sort" class="form-select">
          <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
          <option value="likes" <?php echo ($sort === 'likes') ? 'selected' : ''; ?>>Most Applauded</option>
          <option value="rating" <?php echo ($sort === 'rating') ? 'selected' : ''; ?>>Highest Rated</option>
          <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
      </div>

      <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-nsbm w-100">
          <i class="bi bi-funnel-fill me-1"></i> Filter
        </button>
        <?php if (!empty($search) || $categoryId || !empty($filter) || $sort !== 'newest'): ?>
          <a href="gallery.php" class="btn btn-light border text-danger" title="Clear Filters">
            <i class="bi bi-x-circle"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>

   
    <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center">
      <span class="small fw-semibold text-muted me-2">Browse:</span>
      <a href="gallery.php" class="filter-chip <?php echo (!$categoryId && empty($filter)) ? 'active' : ''; ?>">
        All Categories
      </a>
      <a href="gallery.php?filter=featured" class="filter-chip <?php echo ($filter === 'featured') ? 'active' : ''; ?>">
        <i class="bi bi-star-fill text-warning me-1"></i> Featured
      </a>
      <a href="gallery.php?filter=sale" class="filter-chip <?php echo ($filter === 'sale') ? 'active' : ''; ?>">
        <i class="bi bi-tag-fill text-success me-1"></i> Available for Sale
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="gallery.php?category=<?php echo $cat['id']; ?>" class="filter-chip <?php echo ($categoryId == $cat['id']) ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

 
  <?php if (empty($artworks)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
      <div class="py-4">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <h4 class="fw-bold mt-3">No Artworks Found</h4>
        <p class="text-muted">No items match your active filters. Try adjusting your search query or category.</p>
        <a href="gallery.php" class="btn btn-nsbm btn-sm mt-2">Reset All Filters</a>
      </div>
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
              <?php if ($art['is_featured']): ?>
                <span class="badge-featured">
                  <i class="bi bi-star-fill me-1"></i> Featured
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
                <i class="bi bi-brush text-nsbm me-1"></i> <strong>Medium:</strong> <?php echo htmlspecialchars($art['medium'] ?? 'Not specified'); ?>
              </p>

              <div class="mb-3">
                <?php echo render_star_rating($art['avg_rating'], true, $art['ratings_count']); ?>
              </div>

              <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $art['user_id']; ?>" class="d-flex align-items-center text-decoration-none">
                  <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($art['artist_avatar']); ?>" class="rounded-circle me-2" width="28" height="28" style="object-fit:cover;">
                  <span class="small text-muted fw-semibold"><?php echo htmlspecialchars($art['artist_name']); ?></span>
                </a>
                
                <button type="button" class="btn btn-sm btn-outline-danger btn-artwork-like" data-artwork-id="<?php echo $art['id']; ?>">
                  <i class="bi bi-heart me-1"></i> <span class="like-count"><?php echo $art['likes_count']; ?></span>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

   
    <?php if ($totalPages > 1): ?>
      <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php 
              $queryString = http_build_query(array_merge($_GET, ['page' => $i]));
            ?>
            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
              <a class="page-link" href="gallery.php?<?php echo $queryString; ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
