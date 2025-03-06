<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$username = 'admin';
$password = 'your_secure_password';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $hashed_password);

if ($stmt->execute()) {
    echo "Admin user created successfully";
} else {
    echo "Error creating admin user: " . $conn->error;
} 