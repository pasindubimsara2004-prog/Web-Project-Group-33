<?php

$pageTitle = 'Student Artists';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();

$facultyFilter = trim($_GET['faculty'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["u.role = 'student'"];
$params = [];

if (!empty($facultyFilter)) {
    $where[] = "u.faculty = ?";
    $params[] = $facultyFilter;
}

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.bio LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$whereClause = implode(' AND ', $where);

$sql = "
    SELECT u.id, u.name, u.faculty, u.batch, u.bio, u.profile_pic,
           COUNT(DISTINCT a.id) AS artworks_count,
           COUNT(DISTINCT l.id) AS total_likes,
           (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) AS followers_count
    FROM users u
    LEFT JOIN artworks a ON u.id = a.user_id AND a.status = 'approved'
    LEFT JOIN artwork_likes l ON a.id = l.artwork_id
    WHERE $whereClause
    GROUP BY u.id
    ORDER BY artworks_count DESC, total_likes DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artists = $stmt->fetchAll();


$followedIds = [];
if (is_logged_in()) {
    $currentUserId = current_user()['id'];
    $fStmt = $pdo->prepare("SELECT following_id FROM user_follows WHERE follower_id = ?");
    $fStmt->execute([$currentUserId]);
    $followedIds = $fStmt->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Student Artists</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Meet NSBM Student Creators</h2>
      <p class="text-muted small mb-0">Browse university artists, sculptors, photographers, and craft makers across faculties</p>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <form action="artists.php" method="GET" class="row g-3">
      <div class="col-md-7">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control border-start-0" placeholder="Search by student name or bio...">
        </div>
      </div>
      <div class="col-md-3">
        <select name="faculty" class="form-select">
          <option value="">All Faculties</option>
          <option value="Faculty of Computing" <?php echo ($facultyFilter === 'Faculty of Computing') ? 'selected' : ''; ?>>Faculty of Computing</option>
          <option value="Faculty of Business" <?php echo ($facultyFilter === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
          <option value="Faculty of Engineering" <?php echo ($facultyFilter === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
          <option value="Faculty of Science" <?php echo ($facultyFilter === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-nsbm w-100">Filter</button>
        <?php if (!empty($search) || !empty($facultyFilter)): ?>
          <a href="artists.php" class="btn btn-light border text-danger" title="Clear Filter"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  
  <?php if (empty($artists)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
      <i class="bi bi-person-x fs-1 text-muted"></i>
      <h5 class="fw-bold mt-3">No Student Creators Found</h5>
      <p class="text-muted">No student artists match your current search filters.</p>
      <a href="artists.php" class="btn btn-nsbm btn-sm mt-2">View All Artists</a>
    </div>
  <?php else: ?>
    <div class="row g-4 mb-5">
      <?php foreach ($artists as $artist): ?>
        <?php $isFollowing = in_array($artist['id'], $followedIds); ?>
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column text-center">
            
            <div class="mb-3">
              <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $artist['id']; ?>">
                <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($artist['profile_pic']); ?>" 
                     alt="<?php echo htmlspecialchars($artist['name']); ?>" 
                     class="rounded-circle shadow-sm" width="90" height="90" style="object-fit:cover;">
              </a>
            </div>

            <h5 class="fw-bold mb-1">
              <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $artist['id']; ?>" class="text-dark text-decoration-none">
                <?php echo htmlspecialchars($artist['name']); ?>
              </a>
            </h5>

            <span class="badge bg-light text-secondary border mx-auto mb-3 small">
              <?php echo htmlspecialchars($artist['faculty']); ?>
            </span>

            <p class="small text-muted mb-4 flex-grow-1 text-truncate-2">
              <?php echo htmlspecialchars($artist['bio'] ?? 'Student creator passionate about showcasing unique art and handicrafts at NSBM.'); ?>
            </p>

            <div class="d-flex justify-content-around py-2 border-top border-bottom small mb-3 bg-light rounded-3">
              <div>
                <span class="d-block fw-bold text-dark"><?php echo $artist['artworks_count']; ?></span>
                <span class="text-muted">Creations</span>
              </div>
              <div>
                <span class="d-block fw-bold text-dark"><?php echo $artist['total_likes']; ?></span>
                <span class="text-muted">Likes</span>
              </div>
              <div>
                <span class="d-block fw-bold text-dark followers-count-<?php echo $artist['id']; ?>"><?php echo $artist['followers_count']; ?></span>
                <span class="text-muted">Followers</span>
              </div>
            </div>

            <div class="d-flex gap-2 mt-auto">
              <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $artist['id']; ?>" class="btn btn-outline-nsbm btn-sm flex-grow-1">
                Portfolio
              </a>
              <?php if (is_logged_in() && current_user()['id'] != $artist['id']): ?>
                <button type="button" 
                        class="btn btn-sm <?php echo $isFollowing ? 'btn-secondary' : 'btn-outline-nsbm'; ?> btn-follow-toggle" 
                        data-artist-id="<?php echo $artist['id']; ?>">
                  <span class="follow-text"><?php echo $isFollowing ? 'Following' : 'Follow'; ?></span>
                </button>
              <?php endif; ?>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
