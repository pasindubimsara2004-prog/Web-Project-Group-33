<?php


header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';


if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to like this artwork.', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$artworkId = isset($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : 0;
$userId = current_user()['id'];

if (!$artworkId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid artwork ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    
    $stmt = $pdo->prepare("SELECT id FROM artwork_likes WHERE artwork_id = ? AND user_id = ?");
    $stmt->execute([$artworkId, $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove like
        $del = $pdo->prepare("DELETE FROM artwork_likes WHERE id = ?");
        $del->execute([$existing['id']]);
        $liked = false;
    } else {
        // Add like
        $ins = $pdo->prepare("INSERT INTO artwork_likes (artwork_id, user_id) VALUES (?, ?)");
        $ins->execute([$artworkId, $userId]);
        $liked = true;
    }

    
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = ?");
    $countStmt->execute([$artworkId]);
    $totalLikes = (int)$countStmt->fetchColumn();

    echo json_encode([
        'status'      => 'success',
        'liked'       => $liked,
        'likes_count' => $totalLikes
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
