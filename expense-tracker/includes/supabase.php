<?php
// includes/supabase.php

class SupabaseDB {
    private $url;
    private $api_key;
    private $headers;

    public function __construct() {
        // Ganti dengan URL dan API Key Supabase Anda
        $this->url = "https://padgfrtvxpwezpikdwmc.supabase.co";
        $this->api_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBhZGdmcnR2eHB3ZXpwaWtkd21jIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM4OTEyNDYsImV4cCI6MjA4OTQ2NzI0Nn0.0gXZdVzN11u0JvBYXgHJhB3vuhxskI7ZsAUWp7dI0I0";
        
        $this->headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->api_key,
            "apikey: " . $this->api_key,
            "Prefer: return=representation"
        ];
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->url . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data && ($method === 'POST' || $method === 'PATCH')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("CURL Error: " . $error);
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            if (!empty($response)) {
                return json_decode($response, true);
            }
            return true;
        }
        
        error_log("HTTP Error: " . $httpCode . " - " . $response);
        return false;
    }

    // ==================== EXPENSES ====================
    
    public function selectExpenses($limit = 100) {
        $result = $this->request('GET', "/rest/v1/expenses?select=*&order=expense_date.desc&limit={$limit}");
        return is_array($result) ? $result : [];
    }

    public function getExpense($id) {
        $result = $this->request('GET', "/rest/v1/expenses?id=eq.{$id}&select=*");
        if (is_array($result) && count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public function insertExpense($data) {
        $expenseData = [
            'category_id' => intval($data['category_id']),
            'amount' => floatval($data['amount']),
            'description' => $data['description'] ?? '',
            'expense_date' => $data['expense_date']
        ];
        
        $result = $this->request('POST', "/rest/v1/expenses", $expenseData);
        return $result !== false;
    }

    public function updateExpense($id, $data) {
        $expenseData = [
            'category_id' => intval($data['category_id']),
            'amount' => floatval($data['amount']),
            'description' => $data['description'] ?? '',
            'expense_date' => $data['expense_date']
        ];
        
        $result = $this->request('PATCH', "/rest/v1/expenses?id=eq.{$id}", $expenseData);
        return $result !== false;
    }

    public function deleteExpense($id) {
        $result = $this->request('DELETE', "/rest/v1/expenses?id=eq.{$id}");
        return $result !== false;
    }

    public function lastInsertId() {
        $result = $this->request('GET', "/rest/v1/expenses?select=id&order=id.desc&limit=1");
        if (is_array($result) && count($result) > 0) {
            return $result[0]['id'];
        }
        return 0;
    }

    // ==================== CATEGORIES ====================
    
    public function getCategories() {
        $result = $this->request('GET', "/rest/v1/categories?select=*&order=name");
        return is_array($result) ? $result : [];
    }
    
    public function insertCategory($name) {
        $result = $this->request('POST', "/rest/v1/categories", ['name' => $name]);
        return $result !== false;
    }
    
    public function insertDefaultCategories() {
        $defaultCategories = [
            ['name' => 'Makanan & Minuman'],
            ['name' => 'Transportasi'],
            ['name' => 'Belanja'],
            ['name' => 'Hiburan'],
            ['name' => 'Kesehatan'],
            ['name' => 'Pendidikan'],
            ['name' => 'Tagihan'],
            ['name' => 'Lainnya']
        ];
        
        foreach ($defaultCategories as $cat) {
            $this->request('POST', "/rest/v1/categories", $cat);
        }
    }

    // ==================== SALDO ====================
    
    public function tambahSaldo($amount) {
        $current_saldo = $this->getSaldo();
        $new_saldo = $current_saldo + $amount;
        return $this->updateSaldo($new_saldo);
    }

    public function getSaldo() {
        $result = $this->request('GET', "/rest/v1/saldo?select=amount&limit=1");
        if (is_array($result) && count($result) > 0) {
            return floatval($result[0]['amount'] ?? 0);
        }
        return 0;
    }

    public function updateSaldo($amount) {
        $existing = $this->request('GET', "/rest/v1/saldo?select=id&limit=1");
        
        if (is_array($existing) && count($existing) > 0) {
            return $this->request('PATCH', "/rest/v1/saldo?id=eq.{$existing[0]['id']}", ['amount' => $amount]);
        } else {
            return $this->request('POST', "/rest/v1/saldo", ['amount' => $amount]);
        }
    }

    public function getTotalPengeluaran() {
        $result = $this->request('GET', "/rest/v1/expenses?select=amount");
        $total = 0;
        if (is_array($result)) {
            foreach ($result as $row) {
                $total += floatval($row['amount'] ?? 0);
            }
        }
        return $total;
    }
}

// Fungsi helper
function supabaseDB() {
    static $db = null;
    if ($db === null) {
        $db = new SupabaseDB();
    }
    return $db;
}
?>
