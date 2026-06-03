<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

function is_admin() { return $_SESSION['role'] === 'admin'; }
function is_owner() { return $_SESSION['role'] === 'owner'; }
function is_kasir() { return $_SESSION['role'] === 'kasir'; }
function only_admin() {
    if (!is_admin()) { header('Location: index.php?err=akses'); exit; }
}
