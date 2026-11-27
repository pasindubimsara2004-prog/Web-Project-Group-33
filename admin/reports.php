<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();
$pdo = getDBConnection();


if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=nsbm_artcraft_submissions_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');

    
    fputcsv($output, ['ID', 'Title', 'Category', 'Artist Name', 'Student ID', 'Faculty', 'Medium', 'Price (LKR)', 'Status', 'Views', 'Likes', 'Avg Rating', 'Date']);

    $exportStmt = $pdo->query("
        SELECT a.id, a.title, c.name AS category_name, u.name AS student_name, u.student_id, u.faculty,
               a.medium, a.price, a.status, a.views_count,
               (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
               (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
               a.created_at
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
    ");

    while ($row = $exportStmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['category_name'],
            $row['student_name'],
            $row['student_id'],
            $row['faculty'],
            $row['medium'],
            $row['price'] ?? '0.00',
            ucfirst($row['status']),
            $row['views_count'],
            $row['likes_count'],
            number_format($row['avg_rating'], 1),
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}


$categoryReport = $pdo->query("
    SELECT c.name AS category_name,
           COUNT(a.id) AS total_submissions,
           SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
           SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
           IFNULL(SUM(a.views_count), 0) AS total_views,
           COUNT(DISTINCT l.id) AS total_likes
    FROM categories c
    LEFT JOIN artworks a ON c.id = a.category_id
    LEFT JOIN artwork_likes l ON a.id = l.artwork_id
    GROUP BY c.id
    ORDER BY total_submissions DESC
")->fetchAll();


$facultyReport = $pdo->query("
    SELECT u.faculty,
           COUNT(DISTINCT u.id) AS registered_students,
           COUNT(DISTINCT a.id) AS total_artworks,
           COUNT(DISTINCT ep.id) AS expo_participations
    FROM users u
    LEFT JOIN artworks a ON u.id = a.user_id
    LEFT JOIN exhibition_participants ep ON u.id = ep.user_id
    WHERE u.role = 'student'
    GROUP BY u.faculty
    ORDER BY total_artworks DESC
")->fetchAll();


$expoReport = $pdo->query("
    SELECT e.id, e.title, e.start_date, e.end_date, e.venue, e.status,
           COUNT(DISTINCT ep.id) AS registered_participants,
           COUNT(DISTINCT ep.artwork_id) AS showcased_pieces
    FROM exhibitions e
    LEFT JOIN exhibition_participants ep ON e.id = ep.exhibition_id
    GROUP BY e.id
    ORDER BY e.start_date DESC
")->fetchAll();


$topArtworks = $pdo->query("
    SELECT a.id, a.title, c.name AS category_name, u.name AS artist_name, u.faculty,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           a.views_count
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'approved'
    ORDER BY likes_count DESC, avg_rating DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Submission & Participation Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb" class="no-print">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Reports</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Submission & Exhibition Participation Reports</h2>
      <p class="text-muted small mb-0">NSBM Green University &bull; Faculty of Computing Web Application Analytics</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 no-print">
      <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
        <i class="bi bi-printer-fill me-1"></i> Print Report
      </button>
      <a href="reports.php?export=csv" class="btn btn-nsbm">
        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export to CSV
      </a>
    </div>
  </div>

  
  <div class="d-none d-print-block mb-4 text-center">
    <h3>NSBM GREEN UNIVERSITY - FACULTY OF COMPUTING</h3>
    <h4>NSBM ArtCraft Platform - Comprehensive Evaluation Report</h4>
    <p class="text-muted">Generated on <?php echo date('F d, Y \a\t h:i A'); ?></p>
    <hr>
  </div>

 
  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-nsbm me-2"></i>1. Submissions by Art & Handcraft Category</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0">
        <thead class="table-light small">
          <tr>
            <th>Discipline / Category</th>
            <th class="text-center">Total Submissions</th>
            <th class="text-center">Approved & Live</th>
            <th class="text-center">Pending Review</th>
            <th class="text-center">Rejected</th>
            <th class="text-center">Total Views</th>
            <th class="text-center">Total Likes</th>
          </tr>
        </thead>
        <tbody>
          <?php 
            $totSub = 0; $totApp = 0; $totPen = 0; $totRej = 0; $totView = 0; $totLike = 0;
            foreach ($categoryReport as $cr): 
              $totSub += $cr['total_submissions'];
              $totApp += $cr['approved_count'];
              $totPen += $cr['pending_count'];
              $totRej += $cr['rejected_count'];
              $totView += $cr['total_views'];
              $totLike += $cr['total_likes'];
          ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($cr['category_name']); ?></strong></td>
              <td class="text-center fw-bold"><?php echo $cr['total_submissions']; ?></td>
              <td class="text-center text-success fw-semibold"><?php echo $cr['approved_count']; ?></td>
              <td class="text-center text-warning fw-semibold"><?php echo $cr['pending_count']; ?></td>
              <td class="text-center text-danger fw-semibold"><?php echo $cr['rejected_count']; ?></td>
              <td class="text-center"><?php echo number_format($cr['total_views']); ?></td>
              <td class="text-center"><?php echo number_format($cr['total_likes']); ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="table-light fw-bold">
            <td>Summary Totals</td>
            <td class="text-center"><?php echo $totSub; ?></td>
            <td class="text-center text-success"><?php echo $totApp; ?></td>
            <td class="text-center text-warning"><?php echo $totPen; ?></td>
            <td class="text-center text-danger"><?php echo $totRej; ?></td>
            <td class="text-center"><?php echo number_format($totView); ?></td>
            <td class="text-center"><?php echo number_format($totLike); ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="row g-4 mb-4">
    
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
        <h5 class="fw-bold mb-3"><i class="bi bi-mortarboard-fill text-primary me-2"></i>2. Participation by Faculty</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light small">
              <tr>
                <th>Faculty</th>
                <th class="text-center">Registered Artists</th>
                <th class="text-center">Total Works</th>
                <th class="text-center">Expo Stalls</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($facultyReport as $fr): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($fr['faculty']); ?></strong></td>
                  <td class="text-center"><?php echo $fr['registered_students']; ?></td>
                  <td class="text-center fw-bold text-nsbm"><?php echo $fr['total_artworks']; ?></td>
                  <td class="text-center"><?php echo $fr['expo_participations']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
        <h5 class="fw-bold mb-3"><i class="bi bi-calendar-check-fill text-warning me-2"></i>3. Exhibition Stalls & Participation</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light small">
              <tr>
                <th>Exhibition Name</th>
                <th class="text-center">Status</th>
                <th class="text-center">Registered Students</th>
                <th class="text-center">Showcased Pieces</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($expoReport as $er): ?>
                <tr>
                  <td>
                    <strong class="d-block text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($er['title']); ?></strong>
                    <span class="small text-muted"><?php echo htmlspecialchars($er['venue']); ?></span>
                  </td>
                  <td class="text-center">
                    <span class="badge <?php echo ($er['status'] === 'active') ? 'bg-success' : 'bg-primary'; ?> text-uppercase small">
                      <?php echo $er['status']; ?>
                    </span>
                  </td>
                  <td class="text-center fw-bold"><?php echo $er['registered_participants']; ?></td>
                  <td class="text-center"><?php echo $er['showcased_pieces']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>


  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <h5 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>4. Top 5 Most Applauded Student Creations</h5>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light small">
          <tr>
            <th>Rank</th>
            <th>Artwork Title</th>
            <th>Category</th>
            <th>Student Artist</th>
            <th>Faculty</th>
            <th class="text-center">Community Likes</th>
            <th class="text-center">Avg Rating</th>
            <th class="text-center">Views</th>
          </tr>
        </thead>
        <tbody>
          <?php $rank = 1; foreach ($topArtworks as $art): ?>
            <tr>
              <td>
                <span class="badge rounded-circle <?php echo ($rank === 1) ? 'bg-warning text-dark' : (($rank === 2) ? 'bg-secondary' : 'bg-light text-dark border'); ?>" style="width: 26px; height: 26px; line-height: 18px;">
                  <?php echo $rank++; ?>
                </span>
              </td>
              <td>
                <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="fw-bold text-dark text-decoration-none">
                  <?php echo htmlspecialchars($art['title']); ?>
                </a>
              </td>
              <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($art['category_name']); ?></span></td>
              <td><strong><?php echo htmlspecialchars($art['artist_name']); ?></strong></td>
              <td class="small text-muted"><?php echo htmlspecialchars($art['faculty']); ?></td>
              <td class="text-center fw-bold text-danger"><i class="bi bi-heart-fill me-1"></i> <?php echo $art['likes_count']; ?></td>
              <td class="text-center fw-bold text-warning"><i class="bi bi-star-fill me-1"></i> <?php echo number_format($art['avg_rating'], 1); ?></td>
              <td class="text-center text-muted"><?php echo number_format($art['views_count']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
