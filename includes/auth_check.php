<?php
/**
 * Authentication Check
 * Include at top of protected pages
 */

session_start();

function isLoggedIn() {
    return isset($_SESSION['member_id']) && !empty($_SESSION['member_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentMember() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['member_id'],
        'username' => $_SESSION['member_username'] ?? 'User'
    ];
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
