<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['admin_id'])) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        header("Location: login.php");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
} 