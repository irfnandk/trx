<?php
// includes/header.php
session_start();

// Cek apakah menggunakan Supabase
if (!defined('USE_SUPABASE')) {
    define('USE_SUPABASE', true);
}

if (USE_SUPABASE) {
    require_once __DIR__ . '/supabase.php';
}

// Fungsi untuk mendapatkan instance database
function getDB() {
    if (USE_SUPABASE) {
        return supabaseDB();
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aplikasi Keuangan</title>
</head>
<body>