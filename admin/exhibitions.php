<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();
$pdo = getDBConnection();

$pageTitle = 'Manage Exhibitions';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $theme = trim($_POST['theme'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $status = trim($_POST['status'] ?? 'upcoming');

        if (empty($title) || empty($theme) || empty($venue) || empty($start_date) || empty($end_date)) {
            $error = 'Please fill out all required fields.';
        } else {
            $bannerFilename = 'banner_green_horizons.svg';
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/exhibitions';
                $upRes = upload_image($_FILES['banner_image'], $targetDir);
                if ($upRes['success']) {
                    $bannerFilename = $upRes['filename'];
                }
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO exhibitions (title, theme, description, banner_image, start_date, end_date, venue, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $theme, $description, $bannerFilename, $start_date, $end_date, $venue, $status]);
                flash('success', 'New showcase event "' . htmlspecialchars($title) . '" created successfully.');
                header('Location: ' . SITE_URL . '/admin/exhibitions.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error creating exhibition: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit') {
        $id = (int)$_POST['exhibition_id'];
        $title = trim($_POST['title'] ?? '');
        $theme = trim($_POST['theme'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $status = trim($_POST['status'] ?? 'upcoming');

        try {
           
            $exStmt = $pdo->prepare("SELECT banner_image FROM exhibitions WHERE id = ?");
            $exStmt->execute([$id]);
            $currentBanner = $exStmt->fetchColumn();

            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/exhibitions';
                $upRes = upload_image($_FILES['banner_image'], $targetDir);
                if ($upRes['success']) {
                    $currentBanner = $upRes['filename'];
                }
            }

            $stmt = $pdo->prepare("
                UPDATE exhibitions 
                SET title = ?, theme = ?, description = ?, banner_image = ?, start_date = ?, end_date = ?, venue = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $theme, $description, $currentBanner, $start_date, $end_date, $venue, $status, $id]);

            flash('success', 'Exhibition details updated successfully.');
            header('Location: ' . SITE_URL . '/admin/exhibitions.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error updating exhibition: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)$_POST['exhibition_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM exhibitions WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'Exhibition deleted.');
            header('Location: ' . SITE_URL . '/admin/exhibitions.php');
            exit;
        } catch (Exception $e) {
            flash('danger', 'Error deleting exhibition: ' . $e->getMessage());
            header('Location: ' . SITE_URL . '/admin/exhibitions.php');
            exit;
        }
    }
}


$exhibitions = $pdo->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exhibition_participants WHERE exhibition_id = e.id) AS participant_count
    FROM exhibitions e
    ORDER BY e.start_date DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Exhibitions</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Manage Campus Art Exhibitions & Showcases</h2>
      <p class="text-muted small mb-0">Create new expos, configure dates & venues, and monitor registered student stalls</p>
    </div>
    <div class="mt-3 mt-md-0">
      <button type="button" class="btn btn-nsbm" data-bs-toggle="modal" data-bs-target="#addExpoModal">
        <i class="bi bi-calendar-plus me-1"></i> Create New Exhibition
      </button>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
      <div><?php echo htmlspecialchars($error); ?></div>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light small">
          <tr>
            <th>Event Details</th>
            <th>Theme</th>
            <th>Schedule</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Participants</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exhibitions as $expo): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <img src="<?php echo SITE_URL . '/uploads/exhibitions/' . htmlspecialchars($expo['banner_image']); ?>" 
                       class="rounded-3 me-3" width="60" height="40" style="object-fit:cover;">
                  <div>
                    <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                      <?php echo htmlspecialchars($expo['title']); ?>
                    </a>
                  </div>
                </div>
              </td>
              <td><span class="small text-muted"><?php echo htmlspecialchars($expo['theme']); ?></span></td>
              <td class="small">
                <strong><?php echo date('M d, Y', strtotime($expo['start_date'])); ?></strong><br>
                <span class="text-muted">to <?php echo date('M d, Y', strtotime($expo['end_date'])); ?></span>
              </td>
              <td class="small text-secondary"><?php echo htmlspecialchars($expo['venue']); ?></td>
              <td>
                <span class="badge <?php echo ($expo['status'] === 'active') ? 'bg-success' : (($expo['status'] === 'upcoming') ? 'bg-primary' : 'bg-secondary'); ?> text-uppercase small">
                  <?php echo $expo['status']; ?>
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-people-fill text-nsbm me-1"></i> <?php echo $expo['participant_count']; ?> Registered
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" class="btn btn-outline-secondary" target="_blank" title="View Public Page">
                    <i class="bi bi-eye"></i>
                  </a>
                  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editExpoModal<?php echo $expo['id']; ?>" title="Edit Details">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="exhibitions.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exhibition?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exhibition_id" value="<?php echo $expo['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>

                
                <div class="modal fade" id="editExpoModal<?php echo $expo['id']; ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 text-start">
                      <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit Exhibition: <?php echo htmlspecialchars($expo['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="exhibitions.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="exhibition_id" value="<?php echo $expo['id']; ?>">
                        <div class="modal-body py-3">
                          <div class="row g-3 mb-3">
                            <div class="col-md-7">
                              <label class="form-label small fw-semibold">Event Title <span class="text-danger">*</span></label>
                              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($expo['title']); ?>" required>
                            </div>
                            <div class="col-md-5">
                              <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                              <select name="status" class="form-select" required>
                                <option value="upcoming" <?php echo ($expo['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="active" <?php echo ($expo['status'] === 'active') ? 'selected' : ''; ?>>Active Now</option>
                                <option value="completed" <?php echo ($expo['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                              </select>
                            </div>
                          </div>

                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Theme / Motto <span class="text-danger">*</span></label>
                            <input type="text" name="theme" class="form-control" value="<?php echo htmlspecialchars($expo['theme']); ?>" required>
                          </div>

                          <div class="row g-3 mb-3">
                            <div class="col-md-6">
                              <label class="form-label small fw-semibold">Start Date & Time <span class="text-danger">*</span></label>
                              <input type="datetime-local" name="start_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($expo['start_date'])); ?>" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small fw-semibold">End Date & Time <span class="text-danger">*</span></label>
                              <input type="datetime-local" name="end_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($expo['end_date'])); ?>" required>
                            </div>
                          </div>

                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Campus Venue <span class="text-danger">*</span></label>
                            <input type="text" name="venue" class="form-control" value="<?php echo htmlspecialchars($expo['venue']); ?>" required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($expo['description']); ?></textarea>
                          </div>

                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Replace Banner Photo (Optional)</label>
                            <input type="file" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
                          </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-nsbm px-4">Save Changes</button>
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
  </div>

</div>


<div class="modal fade" id="addExpoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Create New Showcase Event / Exhibition</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="exhibitions.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-body py-3">
          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label class="form-label small fw-semibold">Event Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. NSBM Green Horizons Expo 2026" required>
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                <option value="upcoming" selected>Upcoming</option>
                <option value="active">Active Now</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Theme / Motto <span class="text-danger">*</span></label>
            <input type="text" name="theme" class="form-control" placeholder="e.g. Sustainability Meets Creative Imagination" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Start Date & Time <span class="text-danger">*</span></label>
              <input type="datetime-local" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">End Date & Time <span class="text-danger">*</span></label>
              <input type="datetime-local" name="end_date" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Campus Venue <span class="text-danger">*</span></label>
            <input type="text" name="venue" class="form-control" placeholder="e.g. Student Centre Auditorium, Faculty of Computing Atrium" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Event Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide information on exhibition guidelines, target audience, and showcase spaces..." required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Banner Photo (Optional)</label>
            <input type="file" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-nsbm px-4">Create Exhibition</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
