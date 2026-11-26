<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_login();
$user = current_user();
$pdo = getDBConnection();

$artworkId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$artworkId) {
    flash('danger', 'Invalid artwork ID.');
    header('Location: ' . SITE_URL . '/student/my_artworks.php');
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM artworks WHERE id = ? LIMIT 1");
$stmt->execute([$artworkId]);
$art = $stmt->fetch();

if (!$art) {
    flash('danger', 'Artwork not found.');
    header('Location: ' . SITE_URL . '/student/my_artworks.php');
    exit;
}


if (!is_admin() && $art['user_id'] != $user['id']) {
    flash('danger', 'You can only edit your own artwork.');
    header('Location: ' . SITE_URL . '/student/my_artworks.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Edit: ' . $art['title'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $medium = trim($_POST['medium'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $is_for_sale = isset($_POST['is_for_sale']) ? 1 : 0;

    if (empty($title) || empty($category_id) || empty($description)) {
        $error = 'Please fill out all required fields: Title, Category, and Description.';
    } else {
        try {
            $imageFilename = $art['image_path'];

            
            if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../uploads/artworks';
                $uploadResult = upload_image($_FILES['artwork_image'], $targetDir);

                if (!$uploadResult['success']) {
                    $error = $uploadResult['error'];
                } else {
                    $imageFilename = $uploadResult['filename'];
                }
            }

            if (empty($error)) {
                $updateStmt = $pdo->prepare("
                    UPDATE artworks 
                    SET title = ?, category_id = ?, medium = ?, dimensions = ?, 
                        description = ?, price = ?, is_for_sale = ?, image_path = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $title,
                    $category_id,
                    $medium,
                    $dimensions,
                    $description,
                    $price,
                    $is_for_sale,
                    $imageFilename,
                    $artworkId
                ]);

                flash('success', 'Artwork details updated successfully!');
                if (is_admin()) {
                    header('Location: ' . SITE_URL . '/admin/artworks.php');
                } else {
                    header('Location: ' . SITE_URL . '/student/my_artworks.php');
                }
                exit;
            }
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
} else {
    $title = $art['title'];
    $category_id = $art['category_id'];
    $medium = $art['medium'];
    $dimensions = $art['dimensions'];
    $description = $art['description'];
    $price = $art['price'];
    $is_for_sale = $art['is_for_sale'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/my_artworks.php" class="text-decoration-none text-muted">My Submissions</a></li>
      <li class="breadcrumb-item active" aria-current="page">Edit Artwork</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-pencil-square fs-4"></i>
          </div>
          <div>
            <h3 class="fw-bold mb-0">Edit Artwork Details</h3>
            <p class="text-muted small mb-0">Update information or replace image for "<?php echo htmlspecialchars($art['title']); ?>"</p>
          </div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
          </div>
        <?php endif; ?>

        <form action="edit_artwork.php?id=<?php echo $art['id']; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
          
        
          <div class="mb-4">
            <label class="form-label fw-bold small">Current Image & Replacement</label>
            <div class="d-flex flex-column flex-sm-row align-items-center gap-3 p-3 bg-light rounded-3 border">
              <img src="<?php echo artwork_image_url($art['image_path']); ?>" 
                   alt="Current artwork" class="rounded-3 shadow-sm" style="max-height: 120px; object-fit: contain;">
              <div class="flex-grow-1">
                <span class="small text-muted d-block mb-1">Want to replace this photo?</span>
                <input type="file" name="artwork_image" id="artwork_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.svg">
                <span class="small text-muted">Leave empty to keep the current image. Max 5MB.</span>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label for="title" class="form-label fw-semibold small">Artwork Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
              <div class="invalid-feedback">Please enter a title for your piece.</div>
            </div>

            <div class="col-md-4">
              <label for="category_id" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="medium" class="form-label fw-semibold small">Medium / Materials Used</label>
              <input type="text" class="form-control" id="medium" name="medium" value="<?php echo htmlspecialchars($medium); ?>" placeholder="e.g. Acrylic on Canvas">
            </div>

            <div class="col-md-6">
              <label for="dimensions" class="form-label fw-semibold small">Dimensions / Scale</label>
              <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo htmlspecialchars($dimensions); ?>" placeholder="e.g. 24 x 36 inches">
            </div>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label fw-semibold small">Description & Concept Story <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
          </div>

          <div class="card bg-light border-0 p-3 rounded-3 mb-4">
            <div class="row g-3 align-items-center">
              <div class="col-md-6">
                <label for="price" class="form-label fw-semibold small mb-1">Valuation / Price (LKR)</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">LKR</span>
                  <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" placeholder="0.00">
                </div>
              </div>

              <div class="col-md-6 pt-md-3">
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" id="is_for_sale" name="is_for_sale" value="1" <?php echo $is_for_sale ? 'checked' : ''; ?>>
                  <label class="form-check-label small fw-semibold" for="is_for_sale">
                    Make available for purchase / buyer inquiries
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="<?php echo SITE_URL; ?>/student/my_artworks.php" class="btn btn-light">Back to List</a>
            <button type="submit" class="btn btn-primary px-4 py-2">
              <i class="bi bi-save me-1"></i> Save Changes
            </button>
          </div>

        </form>

      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
