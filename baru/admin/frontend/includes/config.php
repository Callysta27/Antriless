<?php

$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'root';
$password   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname     = getenv('DB_NAME') ?: 'db_kantin';

// membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// memeriksa koneksi benar atau salah
if ($conn->connect_error) {
    die("koneksi failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>