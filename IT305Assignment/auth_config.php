<?php
session_start();
require 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUser() {
    return $_SESSION['user_data'] ?? null;
}

function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>