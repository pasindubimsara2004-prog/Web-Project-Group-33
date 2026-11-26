<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


function flash($type = null, $message = null) {
    if ($type !== null && $message !== null) {
        $_SESSION['flash_message'] = [
            'type'    => $type,
            'message' => $message
        ];
    } else {
        if (isset($_SESSION['flash_message'])) {
            $msg = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);

            $icon = 'bi-info-circle-fill';
            if ($msg['type'] === 'success') $icon = 'bi-check-circle-fill';
            if ($msg['type'] === 'danger')  $icon = 'bi-exclamation-triangle-fill';
            if ($msg['type'] === 'warning') $icon = 'bi-exclamation-circle-fill';

            echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . ' alert-dismissible fade show d-flex align-items-center" role="alert">';
            echo '  <i class="bi ' . $icon . ' me-2 fs-5"></i>';
            echo '  <div>' . htmlspecialchars($msg['message']) . '</div>';
            echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}


function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}


function upload_image($file, $targetDir, $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'], $maxBytes = 5242880) {
    // 5MB max file size
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Please select a valid image file.'];
    }

    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'Image file is too large. Maximum size allowed is 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed formats: ' . implode(', ', $allowedExts)];
    }

    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

   
    $newFileName = 'art_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $destination = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $newFileName];
    } else {
        return ['success' => false, 'error' => 'Failed to save the uploaded image.'];
    }
}


function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $mins = round($difference / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = round($difference / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 2592000) {
        $days = round($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}


function render_star_rating($rating, $showNum = true, $count = null) {
    $rating = floatval($rating);
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $html = '<span class="text-warning rating-stars" title="' . number_format($rating, 1) . ' out of 5 stars">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill"></i> ';
    }
    if ($halfStar) {
        $html .= '<i class="bi bi-star-half"></i> ';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="bi bi-star"></i> ';
    }
    $html .= '</span>';

    if ($showNum) {
        $html .= ' <span class="fw-semibold text-secondary small">(' . number_format($rating, 1);
        if ($count !== null) {
            $html .= ' · ' . intval($count) . ' reviews';
        }
        $html .= ')</span>';
    }

    return $html;
}


function artwork_image_url($filename) {
    if (empty($filename)) {
        return SITE_URL . '/uploads/artworks/artwork1.jpg';
    }
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'svg') {
        $jpgFile = __DIR__ . '/../uploads/artworks/' . $baseName . '.jpg';
        if (file_exists($jpgFile)) {
            return SITE_URL . '/uploads/artworks/' . $baseName . '.jpg';
        }
    }
    return SITE_URL . '/uploads/artworks/' . htmlspecialchars($filename);
}

function format_lkr($amount) {
    if ($amount === null || $amount === '') {
        return 'Display Only';
    }
    return 'LKR ' . number_format($amount, 2);
}
