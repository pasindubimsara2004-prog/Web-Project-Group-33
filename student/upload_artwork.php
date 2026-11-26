<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_student();
$user = current_user();
$pdo = getDBConnection();

$pageTitle = 'Upload Artwork';
$error = '';


$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$title = '';
$category_id = '';
$medium = '';
$dimensions = '';
$description = '';
$price = '';
$is_for_sale = 0;


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
    } elseif (!isset($_FILES['artwork_image']) || $_FILES['artwork_image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select an image file for your artwork.';
    } else {
        
        $targetDir = __DIR__ . '/../uploads/artworks';
        $uploadResult = upload_image($_FILES['artwork_image'], $targetDir);

        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            try {
                $imageFilename = $uploadResult['filename'];
                $status = 'pending'; 

                $stmt = $pdo->prepare("
                    INSERT INTO artworks (user_id, category_id, title, description, medium, dimensions, image_path, price, is_for_sale, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    $category_id,
                    $title,
                    $description,
                    $medium,
                    $dimensions,
                    $imageFilename,
                    $price,
                    $is_for_sale,
                    $status
                ]);

                flash('success', 'Your artwork has been submitted for review!');
                header('Location: ' . SITE_URL . '/student/my_artworks.php');
                exit;
            } catch (Exception $e) {
                $error = 'Failed to submit artwork: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Upload Artwork</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
          <div class="rounded-circle bg-nsbm-light text-nsbm p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-cloud-arrow-up-fill fs-4"></i>
          </div>
          <div>
            <h3 class="fw-bold mb-0">Upload Artwork or Handcraft</h3>
            <p class="text-muted small mb-0">Submit your creation for display in the NSBM ArtCraft Gallery</p>
          </div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
          </div>
        <?php endif; ?>

        <form action="upload_artwork.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
          
          
          <div class="mb-4">
            <label class="form-label fw-bold small">Artwork Image / Photo <span class="text-danger">*</span></label>
            <div class="image-preview-zone" id="preview-box" onclick="document.getElementById('artwork_image').click();">
              <i class="bi bi-image fs-1 text-muted d-block mb-2"></i>
              <span class="fw-semibold text-nsbm d-block">Click here to browse and select photo</span>
              <span class="small text-muted">Supported formats: JPG, PNG, WEBP, SVG (Max 5MB)</span>
            </div>
            <input type="file" name="artwork_image" id="artwork_image" class="d-none image-upload-input" data-preview-target="#preview-box" accept=".jpg,.jpeg,.png,.webp,.svg" required>
            <div class="invalid-feedback">Please choose an image file for your artwork.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label for="title" class="form-label fw-semibold small">Artwork Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="e.g. Serenity at Pitipana" required>
              <div class="invalid-feedback">Please enter a title for your piece.</div>
            </div>

            <div class="col-md-4">
              <label for="category_id" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Please select a category.</div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="medium" class="form-label fw-semibold small">Medium / Materials Used</label>
              <input type="text" class="form-control" id="medium" name="medium" value="<?php echo htmlspecialchars($medium); ?>" placeholder="e.g. Acrylic on Canvas, Terracotta Clay, Recycled Timber">
            </div>

            <div class="col-md-6">
              <label for="dimensions" class="form-label fw-semibold small">Dimensions / Scale</label>
              <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo htmlspecialchars($dimensions); ?>" placeholder="e.g. 24 x 36 inches, 15 x 10 cm">
            </div>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label fw-semibold small">Description & Concept Story <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the creative idea, techniques, inspiration, or symbolism behind your artwork..." required><?php echo htmlspecialchars($description); ?></textarea>
            <div class="invalid-feedback">Please provide a description of your work.</div>
          </div>

          <div class="card bg-light border-0 p-3 rounded-3 mb-4">
            <div class="row g-3 align-items-center">
              <div class="col-md-6">
                <label for="price" class="form-label fw-semibold small mb-1">Valuation / Price (LKR)</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">LKR</span>
                  <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" placeholder="0.00 (Leave empty if not for sale)">
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
            <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-nsbm px-4 py-2">
              <i class="bi bi-cloud-arrow-up-fill me-1"></i> Submit for Review
            </button>
          </div>

        </form>

      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
