<?php
// simple_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Test Supabase Connection</h1>";

$url = "https://padgfrtvxpwezpikdwmc.supabase.co";
$api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBhZGdmcnR2eHB3ZXpwaWtkd21jIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM4OTEyNDYsImV4cCI6MjA4OTQ2NzI0Nn0.0gXZdVzN11u0JvBYXgHJhB3vuhxskI7ZsAUWp7dI0I0";

echo "<h2>Test Categories</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . "/rest/v1/categories?select=*");
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
    $data = json_decode($response, true);
    echo "✓ Berhasil mengambil data!<br>";
    echo "Jumlah kategori: " . count($data) . "<br>";
    echo "<pre>";
    print_r(array_slice($data, 0, 3));
    echo "</pre>";
} else {
    echo "✗ Gagal: $response<br>";
}

echo "<h2>Test Insert Expense</h2>";
$testData = [
    'category_id' => 1,
    'amount' => 25000,
    'description' => 'Test dari simple_test ' . date('H:i:s'),
    'expense_date' => date('Y-m-d')
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . "/rest/v1/expenses");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $api_key,
    "apikey: " . $api_key
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($httpCode == 201) {
    echo "<span style='color:green'>✓ Berhasil insert data!</span><br>";
    echo "Response: $response<br>";
} else {
    echo "<span style='color:red'>✗ Gagal insert data!</span><br>";
    echo "Response: $response<br>";
}

echo "<br><a href='index.php'>Kembali ke Dashboard</a>";
?>