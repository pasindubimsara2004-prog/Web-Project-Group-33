<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';


if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to submit a rating.', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$artworkId = isset($_POST['artwork_id']) ? (int)$_POST['artwork_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$userId = current_user()['id'];

if (!$artworkId || $rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating. Must be between 1 and 5 stars.']);
    exit;
}

try {
    $pdo = getDBConnection();

   
    $stmt = $pdo->prepare("SELECT id FROM artwork_ratings WHERE artwork_id = ? AND user_id = ?");
    $stmt->execute([$artworkId, $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare("UPDATE artwork_ratings SET rating = ? WHERE id = ?");
        $update->execute([$rating, $existing['id']]);
    } else {
        $insert = $pdo->prepare("INSERT INTO artwork_ratings (artwork_id, user_id, rating) VALUES (?, ?, ?)");
        $insert->execute([$artworkId, $userId, $rating]);
    }

   
    $avgStmt = $pdo->prepare("SELECT IFNULL(AVG(rating), 0) AS avg_rating, COUNT(*) AS count FROM artwork_ratings WHERE artwork_id = ?");
    $avgStmt->execute([$artworkId]);
    $res = $avgStmt->fetch();

    echo json_encode([
        'status'        => 'success',
        'rating'        => $rating,
        'avg_rating'    => number_format($res['avg_rating'], 1),
        'ratings_count' => (int)$res['count']
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
