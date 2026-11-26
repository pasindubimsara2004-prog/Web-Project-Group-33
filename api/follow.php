<?php


header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';


if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to follow artists.', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$artistId = isset($_POST['artist_id']) ? (int)$_POST['artist_id'] : 0;
$currentUserId = current_user()['id'];

if (!$artistId || $artistId === $currentUserId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid artist ID or cannot follow yourself']);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$currentUserId, $artistId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $del = $pdo->prepare("DELETE FROM user_follows WHERE id = ?");
        $del->execute([$existing['id']]);
        $following = false;
    } else {
        $ins = $pdo->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
        $ins->execute([$currentUserId, $artistId]);
        $following = true;
    }

    
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
    $countStmt->execute([$artistId]);
    $followersCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'status'          => 'success',
        'following'       => $following,
        'followers_count' => $followersCount
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
