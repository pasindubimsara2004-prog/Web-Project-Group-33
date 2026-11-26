<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();

$expoId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$expoId) {
    flash('danger', 'Invalid exhibition ID.');
    header('Location: ' . SITE_URL . '/exhibitions.php');
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM exhibitions WHERE id = ? LIMIT 1");
$stmt->execute([$expoId]);
$expo = $stmt->fetch();

if (!$expo) {
    flash('danger', 'Exhibition not found.');
    header('Location: ' . SITE_URL . '/exhibitions.php');
    exit;
}

$currentUserId = is_logged_in() ? current_user()['id'] : null;


$isRegistered = false;
$userRegistration = null;
if (is_student()) {
    $regCheck = $pdo->prepare("SELECT * FROM exhibition_participants WHERE exhibition_id = ? AND user_id = ?");
    $regCheck->execute([$expoId, $currentUserId]);
    $userRegistration = $regCheck->fetch();
    $isRegistered = (bool)$userRegistration;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_expo') {
    require_student();
    $artworkId = !empty($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : null;
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($isRegistered) {
            flash('info', 'You are already registered for this event.');
        } else {
            $regStmt = $pdo->prepare("
                INSERT INTO exhibition_participants (exhibition_id, user_id, artwork_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $regStmt->execute([$expoId, $currentUserId, $artworkId, $notes]);
            flash('success', 'You have registered for ' . htmlspecialchars($expo['title']) . '!');
            header("Location: " . SITE_URL . "/exhibition_detail.php?id=" . $expoId);
            exit;
        }
    } catch (Exception $e) {
        flash('danger', 'Registration failed: ' . $e->getMessage());
    }
}


if (isset($_GET['cancel_registration']) && is_student()) {
    $cancelStmt = $pdo->prepare("DELETE FROM exhibition_participants WHERE exhibition_id = ? AND user_id = ?");
    $cancelStmt->execute([$expoId, $currentUserId]);
    flash('info', 'Your exhibition registration has been cancelled.');
    header("Location: " . SITE_URL . "/exhibition_detail.php?id=" . $expoId);
    exit;
}

$studentArtworks = [];
if (is_student()) {
    $myArtStmt = $pdo->prepare("SELECT id, title FROM artworks WHERE user_id = ? AND status = 'approved' ORDER BY title ASC");
    $myArtStmt->execute([$currentUserId]);
    $studentArtworks = $myArtStmt->fetchAll();
}


$partStmt = $pdo->prepare("
    SELECT p.*, u.name AS student_name, u.faculty AS student_faculty, u.profile_pic AS student_avatar,
           a.title AS artwork_title, a.image_path AS artwork_image, a.id AS art_id
    FROM exhibition_participants p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN artworks a ON p.artwork_id = a.id
    WHERE p.exhibition_id = ?
    ORDER BY p.registered_at ASC
");
$partStmt->execute([$expoId]);
$participants = $partStmt->fetchAll();

$pageTitle = $expo['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/exhibitions.php" class="text-decoration-none text-muted">Exhibitions</a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width: 280px;" aria-current="page"><?php echo htmlspecialchars($expo['title']); ?></li>
    </ol>
  </nav>


  <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5 bg-white">
    <div style="max-height: 380px; overflow:hidden;">
      <img src="<?php echo SITE_URL . '/uploads/exhibitions/' . htmlspecialchars($expo['banner_image']); ?>" 
           alt="<?php echo htmlspecialchars($expo['title']); ?>" 
           class="w-100 h-100" style="object-fit:cover;">
    </div>

    <div class="card-body p-4 p-md-5">
      <div class="row g-4 align-items-center">
        <div class="col-lg-8">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge <?php echo ($expo['status'] === 'active') ? 'bg-success' : (($expo['status'] === 'upcoming') ? 'bg-primary' : 'bg-secondary'); ?> px-3 py-2 text-uppercase fw-bold">
              <?php echo $expo['status']; ?>
            </span>
            <span class="text-muted small">
              <i class="bi bi-clock-history me-1"></i> Posted <?php echo time_ago($expo['created_at']); ?>
            </span>
          </div>

          <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($expo['title']); ?></h2>
          <h5 class="text-warning fw-semibold mb-3">Theme: <?php echo htmlspecialchars($expo['theme']); ?></h5>
          <p class="text-secondary lead fs-6 mb-4"><?php echo nl2br(htmlspecialchars($expo['description'])); ?></p>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded-3">
                <span class="text-muted small d-block"><i class="bi bi-calendar-range text-nsbm me-1"></i> Exhibition Schedule</span>
                <strong><?php echo date('D, M d, Y - h:i A', strtotime($expo['start_date'])); ?></strong><br>
                <span class="small text-muted">to <?php echo date('D, M d, Y - h:i A', strtotime($expo['end_date'])); ?></span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded-3">
                <span class="text-muted small d-block"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Showcase Venue</span>
                <strong><?php echo htmlspecialchars($expo['venue']); ?></strong><br>
                <span class="small text-muted">NSBM Green University Campus</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 text-center">
          <div class="card border-0 bg-nsbm-light p-4 rounded-4 text-center">
            <i class="bi bi-person-badge fs-1 text-nsbm mb-2"></i>
            <h4 class="fw-bold text-nsbm mb-1"><?php echo count($participants); ?></h4>
            <p class="text-muted small mb-3">Student Exhibitors Registered</p>

            <?php if (is_student()): ?>
              <?php if ($isRegistered): ?>
                <div class="alert alert-success py-2 mb-3 small">
                  <i class="bi bi-check-circle-fill me-1"></i> You are registered for this expo!
                </div>
                <a href="exhibition_detail.php?id=<?php echo $expoId; ?>&cancel_registration=1" 
                   class="btn btn-outline-danger btn-sm w-100" 
                   onclick="return confirm('Are you sure you want to cancel your registration?');">
                  Cancel Registration
                </a>
              <?php elseif ($expo['status'] === 'completed'): ?>
                <button class="btn btn-secondary w-100" disabled>Event Concluded</button>
              <?php else: ?>
                <button type="button" class="btn btn-nsbm w-100 py-2 shadow" data-bs-toggle="modal" data-bs-target="#registerExpoModal">
                  <i class="bi bi-pencil-square me-1"></i> Register to Participate
                </button>
              <?php endif; ?>
            <?php elseif (is_admin()): ?>
              <a href="<?php echo SITE_URL; ?>/admin/exhibitions.php?action=edit&id=<?php echo $expo['id']; ?>" class="btn btn-dark btn-sm w-100">
                <i class="bi bi-gear-fill me-1"></i> Manage This Exhibition
              </a>
            <?php else: ?>
              <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-nsbm w-100">
                Sign In to Participate
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

 
  <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-nsbm me-2"></i>Participating Student Exhibitors</h4>
        <p class="text-muted small mb-0">Browse student artists presenting their work in this exhibition</p>
      </div>
      <span class="badge bg-secondary"><?php echo count($participants); ?> Registered</span>
    </div>

    <?php if (empty($participants)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-1"></i>
        <p class="mt-2 mb-0">No students registered yet. Be the first to register!</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($participants as $p): ?>
          <div class="col-md-6 col-lg-4">
            <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column">
              <div class="d-flex align-items-center mb-3">
                <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($p['student_avatar']); ?>" 
                     class="rounded-circle me-3" width="46" height="46" style="object-fit:cover;">
                <div>
                  <h6 class="mb-0 fw-bold">
                    <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $p['user_id']; ?>" class="text-dark text-decoration-none">
                      <?php echo htmlspecialchars($p['student_name']); ?>
                    </a>
                  </h6>
                  <span class="small text-muted"><?php echo htmlspecialchars($p['student_faculty']); ?></span>
                </div>
              </div>

              <?php if (!empty($p['artwork_title'])): ?>
                <div class="d-flex align-items-center gap-2 p-2 bg-white rounded border mb-2 mt-auto">
                  <?php if (!empty($p['artwork_image'])): ?>
                    <img src="<?php echo artwork_image_url($p['artwork_image']); ?>" 
                         class="rounded" width="40" height="40" style="object-fit:cover;">
                  <?php endif; ?>
                  <div class="overflow-hidden">
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Exhibiting Artwork:</span>
                    <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $p['art_id']; ?>" class="fw-semibold text-nsbm small text-truncate d-block">
                      <?php echo htmlspecialchars($p['artwork_title']); ?>
                    </a>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($p['notes'])): ?>
                <p class="small text-muted fst-italic mb-0">
                  "<?php echo htmlspecialchars($p['notes']); ?>"
                </p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>


<?php if (is_student() && !$isRegistered): ?>
<div class="modal fade" id="registerExpoModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="registerModalLabel">Register for Exhibition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="exhibition_detail.php?id=<?php echo $expo['id']; ?>">
        <input type="hidden" name="action" value="register_expo">
        <div class="modal-body py-4">
          <p class="small text-muted mb-4">
            Submit your registration to showcase your art or handcraft pieces at <strong><?php echo htmlspecialchars($expo['title']); ?></strong>.
          </p>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Select Artwork to Showcase (Optional)</label>
            <select name="artwork_id" class="form-select">
              <option value="">-- Showcase new / In-progress craft at stall --</option>
              <?php foreach ($studentArtworks as $art): ?>
                <option value="<?php echo $art['id']; ?>"><?php echo htmlspecialchars($art['title']); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Choose an approved artwork from your portfolio or bring items on exhibition day.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Notes / Space Request (Optional)</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Easel required, table space for 2 clay vases, or electrical outlet needed..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-nsbm px-4">Confirm Registration</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
