<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_admin();
$pdo = getDBConnection();

$pageTitle = 'Categories';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'bi-palette');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (empty($name)) {
            $error = 'Please enter a category name.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $icon]);
                flash('success', 'Category added successfully.');
                header('Location: ' . SITE_URL . '/admin/categories.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error adding category: ' . $e->getMessage();
            }
        }
    }
   
    elseif ($action === 'edit') {
        $id = (int)$_POST['category_id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'bi-palette');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (empty($name)) {
            $error = 'Category name cannot be empty.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $icon, $id]);
                flash('success', 'Category details updated successfully.');
                header('Location: ' . SITE_URL . '/admin/categories.php');
                exit;
            } catch (Exception $e) {
                $error = 'Error updating category: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)$_POST['category_id'];
        try {
            
            $chk = $pdo->prepare("SELECT COUNT(*) FROM artworks WHERE category_id = ?");
            $chk->execute([$id]);
            $count = (int)$chk->fetchColumn();

            if ($count > 0) {
                flash('danger', 'Cannot delete this category because it contains ' . $count . ' artwork(s). Reassign or remove the artworks first.');
            } else {
                $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $del->execute([$id]);
                flash('success', 'Category deleted successfully.');
            }
            header('Location: ' . SITE_URL . '/admin/categories.php');
            exit;
        } catch (Exception $e) {
            flash('danger', 'Error deleting category: ' . $e->getMessage());
            header('Location: ' . SITE_URL . '/admin/categories.php');
            exit;
        }
    }
}


$categories = $pdo->query("
    SELECT c.*, COUNT(a.id) AS total_artworks,
           SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_count
    FROM categories c
    LEFT JOIN artworks a ON c.id = a.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-decoration-none text-muted">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Categories</li>
    </ol>
  </nav>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
    <div>
      <h2 class="fw-bold mb-1">Manage Art & Handcraft Categories</h2>
      <p class="text-muted small mb-0">Define showcase categories, icons, and descriptions for student submissions</p>
    </div>
    <div class="mt-3 mt-md-0">
      <button type="button" class="btn btn-nsbm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle me-1"></i> Add New Category
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
            <th>Icon</th>
            <th>Category Name</th>
            <th>Slug</th>
            <th>Description</th>
            <th>Total Artworks</th>
            <th>Live / Approved</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <span class="rounded-circle bg-nsbm-light text-nsbm p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                  <i class="bi <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                </span>
              </td>
              <td>
                <strong class="text-dark"><?php echo htmlspecialchars($cat['name']); ?></strong>
              </td>
              <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
              <td class="small text-secondary" style="max-width: 280px;">
                <?php echo htmlspecialchars($cat['description']); ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?php echo $cat['total_artworks']; ?> Items</span></td>
              <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo $cat['approved_count']; ?> Live</span></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  
                  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $cat['id']; ?>">
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                  
                  <form method="POST" action="categories.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger" <?php echo ($cat['total_artworks'] > 0) ? 'disabled title="Cannot delete category containing artworks"' : ''; ?>>
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>

               
                <div class="modal fade" id="editCategoryModal<?php echo $cat['id']; ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 text-start">
                      <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit Category: <?php echo htmlspecialchars($cat['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="POST" action="categories.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                        <div class="modal-body py-3">
                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
                            <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($cat['icon']); ?>" placeholder="e.g. bi-palette, bi-camera, bi-brush">
                          </div>
                          <div class="mb-3">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($cat['description']); ?></textarea>
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


<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Add New Discipline / Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="categories.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Sculptures & Woodwork" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
            <input type="text" name="icon" class="form-control" placeholder="bi-hammer" value="bi-palette">
            <span class="small text-muted">Supports any Bootstrap 5 icon (bi-palette, bi-pencil, bi-camera, bi-brush)</span>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Explain what student creations fall under this category..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-nsbm px-4">Create Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
