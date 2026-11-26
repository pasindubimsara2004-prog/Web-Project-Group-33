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
    flash('danger', 'You do not have permission to delete this artwork.');
    header('Location: ' . SITE_URL . '/student/my_artworks.php');
    exit;
}

try {
    
    $delStmt = $pdo->prepare("DELETE FROM artworks WHERE id = ?");
    $delStmt->execute([$artworkId]);

   
    $imagePath = __DIR__ . '/../uploads/artworks/' . $art['image_path'];
    if (file_exists($imagePath) && strpos($art['image_path'], 'artwork') === false) {
        @unlink($imagePath);
    }

    flash('success', 'Artwork "' . htmlspecialchars($art['title']) . '" was successfully deleted.');
} catch (Exception $e) {
    flash('danger', 'Could not delete artwork: ' . $e->getMessage());
}

if (is_admin()) {
    header('Location: ' . SITE_URL . '/admin/artworks.php');
} else {
    header('Location: ' . SITE_URL . '/student/my_artworks.php');
}
exit;
