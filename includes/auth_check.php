<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';


function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'          => $_SESSION['user_id'],
        'name'        => $_SESSION['user_name'] ?? '',
        'email'       => $_SESSION['user_email'] ?? '',
        'role'        => $_SESSION['user_role'] ?? 'student',
        'student_id'  => $_SESSION['student_id'] ?? '',
        'faculty'     => $_SESSION['user_faculty'] ?? '',
        'profile_pic' => $_SESSION['profile_pic'] ?? 'default_avatar.svg'
    ];
}


function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}


function is_student() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}


function require_login() {
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}


function require_admin() {
    require_login();
    if (!is_admin()) {
        flash('danger', 'Access denied. You do not have administrator permissions.');
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}


function require_student() {
    require_login();
    if (!is_student()) {
        flash('warning', 'This section is only available for registered students.');
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}
