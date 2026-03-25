<?php
// fix_permission.php
echo "<h1>Fix Permission Supabase</h1>";

$url = "https://padgfrtvxpwezpikdwmc.supabase.co";
$api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBhZGdmcnR2eHB3ZXpwaWtkd21jIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM4OTEyNDYsImV4cCI6MjA4OTQ2NzI0Nn0.0gXZdVzN11u0JvBYXgHJhB3vuhxskI7ZsAUWp7dI0I0";

// Test dengan anon key
echo "<h2>Test dengan Anon Key</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . "/rest/v1/categories?select=*&limit=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $api_key,
    "apikey: " . $api_key
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($httpCode == 200) {
    echo "<span style='color:green'>✓ Koneksi berhasil! Permission sudah benar.</span><br>";
    $data = json_decode($response, true);
    echo "Data: ";
    print_r($data);
} else {
    echo "<span style='color:red'>✗ Masih ada masalah permission.</span><br>";
    echo "Error: $response<br>";
    echo "<br><strong>Solusi:</strong> Jalankan SQL di Supabase SQL Editor:<br>";
    echo "<pre>
-- Berikan akses ke anon
GRANT USAGE ON SCHEMA public TO anon;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon;

-- Disable RLS
ALTER TABLE categories DISABLE ROW LEVEL SECURITY;
ALTER TABLE expenses DISABLE ROW LEVEL SECURITY;
ALTER TABLE saldo DISABLE ROW LEVEL SECURITY;
    </pre>";
}

echo "<br><a href='simple_test.php'>Test Lagi</a>";
echo " | <a href='index.php'>Ke Dashboard</a>";
?>