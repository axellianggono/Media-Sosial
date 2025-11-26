<?php

try {
    $env = parse_ini_file(__DIR__ . '/../.env');
} catch (Exception $e) {
    die("Gagal memuat file konfigurasi: " . $e->getMessage());
}
