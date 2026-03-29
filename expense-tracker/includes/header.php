<?php
// includes/header.php
session_start();

if (!defined('USE_SUPABASE')) {
    define('USE_SUPABASE', true);
}

if (USE_SUPABASE) {
    require_once __DIR__ . '/supabase.php';
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Aplikasi Keuangan</title>
</head>
<body>
