<?php

require __DIR__ . '/config.php';

try {
    $hostname = $env['HOSTNAME'] ?? 'localhost';
    $username = $env['USERNAME'] ?? 'root';
    $password = $env['PASSWORD'] ?? '';
    $dbName   = $env['DB_NAME'] ?? 'socialite';

    $conn = new mysqli($hostname, $username, $password, $dbName);
} catch (Exception $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}