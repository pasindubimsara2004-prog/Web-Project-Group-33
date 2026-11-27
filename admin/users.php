<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();
$pdo = getDBConnection();

$facultyFilter = trim($_GET['faculty'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($facultyFilter)) {
    $where[] = "u.faculty = ?";
    $params[] = $facultyFilter;
}

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereClause = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT u.*,
           COUNT(DISTINCT a.id) AS total_artworks,
           SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_artworks,
           COUNT(DISTINCT l.id) AS total_likes_received
    FROM users u
    LEFT JOIN artworks a ON u.id = a.user_id
    LEFT JOIN artwork_likes l ON a.id = l.artwork_id
    WHERE $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Student Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">User Accounts</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Registered Student Creators & Staff</h2>
      <p class="text-muted small mb-0">Monitor student registrations, track creative activity, and view student portfolios</p>
    </div>
  </div>

 
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <form method="GET" action="users.php" class="row g-3">
      <div class="col-md-7">
        <label class="form-label small fw-semibold text-muted">Search Users</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control border-start-0" placeholder="Search by name, email, or student ID...">
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold text-muted">Faculty</label>
        <select name="faculty" class="form-select">
          <option value="">All Faculties</option>
          <option value="Faculty of Computing" <?php echo ($facultyFilter === 'Faculty of Computing') ? 'selected' : ''; ?>>Faculty of Computing</option>
          <option value="Faculty of Business" <?php echo ($facultyFilter === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
          <option value="Faculty of Engineering" <?php echo ($facultyFilter === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
          <option value="Faculty of Science" <?php echo ($facultyFilter === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-nsbm w-100">Filter</button>
        <?php if (!empty($search) || !empty($facultyFilter)): ?>
          <a href="users.php" class="btn btn-light border text-danger"><i class="bi bi-x-circle"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light small">
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Student ID</th>
            <th>Faculty & Batch</th>
            <th>Email</th>
            <th>Total Submissions</th>
            <th>Total Likes</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($u['profile_pic']); ?>" 
                       class="rounded-circle me-3" width="40" height="40" style="object-fit:cover;">
                  <strong class="text-dark"><?php echo htmlspecialchars($u['name']); ?></strong>
                </div>
              </td>
              <td>
                <span class="badge <?php echo ($u['role'] === 'admin') ? 'bg-danger' : 'bg-success'; ?>">
                  <?php echo ucfirst($u['role']); ?>
                </span>
              </td>
              <td><code><?php echo htmlspecialchars($u['student_id'] ?? 'N/A'); ?></code></td>
              <td class="small text-secondary">
                <?php echo htmlspecialchars($u['faculty']); ?><br>
                <span class="text-muted">Batch: <?php echo htmlspecialchars($u['batch']); ?></span>
              </td>
              <td class="small"><?php echo htmlspecialchars($u['email']); ?></td>
              <td>
                <span class="badge bg-light text-dark border">
                  <?php echo $u['total_artworks']; ?> (<?php echo $u['approved_artworks']; ?> Live)
                </span>
              </td>
              <td class="small text-muted"><i class="bi bi-heart-fill text-danger me-1"></i> <?php echo $u['total_likes_received']; ?></td>
              <td class="text-end">
                <?php if ($u['role'] === 'student'): ?>
                  <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-nsbm" target="_blank">
                    <i class="bi bi-person-lines-fill me-1"></i> Portfolio
                  </a>
                <?php else: ?>
                  <span class="badge bg-secondary">System Admin</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
