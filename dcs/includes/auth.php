<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

function getUserInfo() {
    return $_SESSION;
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: modules/dashboard.php");
        exit;
    }
}

function generateApiToken() {
    return bin2hex(random_bytes(32));
}