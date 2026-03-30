<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'includes/header.php';

// Get database instance
$db = getDB();

// Handle CRUD Operations
$message = '';
$message_type = '';

// TAMBAH SALDO ONLINE
if (isset($_POST['action']) && $_POST['action'] === 'tambah_saldo_online') {
    $saldo = str_replace(['.', ','], ['', '.'], $_POST['saldo']);
    $saldo_numeric = floatval($saldo);
    
    if ($saldo_numeric > 0) {
        $result = $db->tambahSaldoOnline($saldo_numeric);
        $redirect = $result ? 'saldo_online_added' : 'error';
        header("Location: index.php?$redirect=1");
        exit;
    } else {
        header("Location: index.php?error=1");
        exit;
    }
}

// TAMBAH SALDO CASH
if (isset($_POST['action']) && $_POST['action'] === 'tambah_saldo_cash') {
    $saldo = str_replace(['.', ','], ['', '.'], $_POST['saldo']);
    $saldo_numeric = floatval($saldo);
    
    if ($saldo_numeric > 0) {
        $result = $db->tambahSaldoCash($saldo_numeric);
        $redirect = $result ? 'saldo_cash_added' : 'error';
        header("Location: index.php?$redirect=1");
        exit;
    } else {
        header("Location: index.php?error=1");
        exit;
    }
}

// Update Saldo Online (atur ulang)
if (isset($_POST['action']) && $_POST['action'] === 'update_saldo_online') {
    $saldo = str_replace(['.', ','], ['', '.'], $_POST['saldo']);
    $db->updateSaldoOnline(floatval($saldo));
    header('Location: index.php?saldo_online_updated=1');
    exit;
}

// Update Saldo Cash (atur ulang)
if (isset($_POST['action']) && $_POST['action'] === 'update_saldo_cash') {
    $saldo = str_replace(['.', ','], ['', '.'], $_POST['saldo']);
    $db->updateSaldoCash(floatval($saldo));
    header('Location: index.php?saldo_cash_updated=1');
    exit;
}

// Create Expense
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $amount = str_replace(['.', ','], ['', '.'], $_POST['amount']);
    $source = $_POST['source'] ?? 'online';
    
    $data = [
        'category_id' => intval($_POST['category_id'] ?? 0),
        'amount'      => floatval($amount),
        'description' => $_POST['description'] ?? '',
        'expense_date'=> $_POST['expense_date'] ?? date('Y-m-d'),
        'source'      => $source
    ];
    
    // Kurangi saldo sesuai sumber
    $saldo_online = $db->getSaldoOnline();
    $saldo_cash = $db->getSaldoCash();
    
    if ($source === 'online' && $saldo_online < $amount) {
        $message = 'Saldo online tidak mencukupi!';
        $message_type = 'error';
    } elseif ($source === 'cash' && $saldo_cash < $amount) {
        $message = 'Uang cash tidak mencukupi!';
        $message_type = 'error';
    } else {
        $result = $db->insertExpense($data);
        
        if ($result) {
            // Kurangi saldo
            $db->kurangiSaldo($amount, $source);
            
            if (isset($_POST['print_receipt'])) {
                $last_id = $db->lastInsertId();
                header("Location: receipt.php?id=" . $last_id . "&auto_print=1");
                exit;
            }
            
            $redirect = 'success';
        } else {
            $redirect = 'error';
        }
        header("Location: index.php?$redirect=1");
        exit;
    }
}

// Update Expense
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $amount = str_replace(['.', ','], ['', '.'], $_POST['amount']);
    $source = $_POST['source'] ?? 'online';
    
    $data = [
        'category_id' => intval($_POST['category_id'] ?? 0),
        'amount'      => floatval($amount),
        'description' => $_POST['description'] ?? '',
        'expense_date'=> $_POST['expense_date'] ?? date('Y-m-d'),
        'source'      => $source
    ];
    
    $result = $db->updateExpense($id, $data);
    
    if ($result && isset($_POST['print_receipt'])) {
        header("Location: receipt.php?id=" . $id . "&auto_print=1");
        exit;
    }
    
    $redirect = $result ? 'updated' : 'error';
    header("Location: index.php?$redirect=1");
    exit;
}

// Delete Expense
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $expense = $db->getExpense($id);
    $result = $db->deleteExpense($id);
    
    if ($result && $expense) {
        // Kembalikan saldo
        $db->tambahSaldo($expense['amount'], $expense['source']);
    }
    
    $redirect = $result ? 'deleted' : 'error';
    header("Location: index.php?$redirect=1");
    exit;
}

// Get data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_data = $db->getExpense($id);
}

$expenses = $db->selectExpenses();
$categories = $db->getCategories();

if (!is_array($categories)) {
    $categories = [];
}

if (empty($categories)) {
    $categories = [
        ['id' => 1, 'name' => 'Makanan & Minuman'],
        ['id' => 2, 'name' => 'Transportasi'],
        ['id' => 3, 'name' => 'Belanja'],
        ['id' => 4, 'name' => 'Hiburan'],
        ['id' => 5, 'name' => 'Kesehatan'],
        ['id' => 6, 'name' => 'Pendidikan'],
        ['id' => 7, 'name' => 'Tagihan'],
        ['id' => 8, 'name' => 'Lainnya']
    ];
}

$categories_map = [];
foreach ($categories as $cat) {
    if (isset($cat['id']) && isset($cat['name'])) {
        $categories_map[$cat['id']] = $cat['name'];
    }
}

// Saldo info
$saldo_online = $db->getSaldoOnline();
$saldo_cash = $db->getSaldoCash();
$total_pengeluaran = $db->getTotalPengeluaran();
$total_saldo = $saldo_online + $saldo_cash;

// Statistics
$bulan_ini = date('Y-m');
$hari_ini = date('Y-m-d');
$total_bulan_ini = 0;
$total_hari_ini = 0;
$transaksi_count = is_array($expenses) ? count($expenses) : 0;
$pengeluaran_terbesar = 0;

if (is_array($expenses)) {
    foreach ($expenses as $expense) {
        $amount = floatval($expense['amount'] ?? 0);
        $date = $expense['expense_date'] ?? '';
        
        if (strpos($date, $bulan_ini) === 0) $total_bulan_ini += $amount;
        if ($date === $hari_ini) $total_hari_ini += $amount;
        if ($amount > $pengeluaran_terbesar) $pengeluaran_terbesar = $amount;
    }
}

$rata_rata = $transaksi_count > 0 ? round($total_pengeluaran / $transaksi_count) : 0;

$msg_map = [
    'success' => 'Pengeluaran berhasil ditambahkan',
    'updated' => 'Pengeluaran berhasil diupdate',
    'deleted' => 'Pengeluaran berhasil dihapus',
    'saldo_online_added' => 'Saldo online berhasil ditambahkan',
    'saldo_cash_added' => 'Saldo cash berhasil ditambahkan',
    'saldo_online_updated' => 'Saldo online berhasil diupdate',
    'saldo_cash_updated' => 'Saldo cash berhasil diupdate',
    'error' => 'Gagal memproses data'
];

foreach ($msg_map as $key => $msg) {
    if (isset($_GET[$key])) {
        $message = $msg;
        $message_type = ($key === 'error') ? 'error' : 'success';
        break;
    }
}

$recent_expenses = is_array($expenses) ? array_slice($expenses, 0, 10) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Aplikasi Keuangan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
            line-height: 1.5;
        }
        .app { max-width: 480px; margin: 0 auto; padding: 20px 16px 32px; min-height: 100vh; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #1e293b, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .header-date { font-size: 13px; color: #64748b; margin-top: 4px; }
        .menu-btn { background: white; border: 1px solid #e2e8f0; width: 44px; height: 44px; border-radius: 12px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .menu-btn:active { background: #f1f5f9; transform: scale(0.95); }
        
        /* Menu Dropdown */
        .menu-dropdown { display: none; background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px; overflow: hidden; }
        .menu-dropdown.show { display: block; }
        .menu-item { display: block; padding: 14px 20px; text-decoration: none; color: #1e293b; border-bottom: 1px solid #f1f5f9; font-size: 15px; font-weight: 500; }
        .menu-item:active { background: #f8fafc; }
        .menu-item:last-child { border-bottom: none; }
        
        /* Saldo Cards */
        .saldo-container { display: flex; gap: 12px; margin-bottom: 20px; }
        .saldo-card { flex: 1; border-radius: 20px; padding: 16px; color: white; }
        .saldo-card.online { background: linear-gradient(135deg, #0f172a, #1e293b); }
        .saldo-card.cash { background: linear-gradient(135deg, #065f46, #047857); }
        .saldo-label { font-size: 11px; opacity: 0.8; margin-bottom: 8px; text-transform: uppercase; }
        .saldo-nominal { font-size: 20px; font-weight: 700; }
        .saldo-small { font-size: 11px; margin-top: 8px; opacity: 0.7; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 24px; }
        .stat-item { background: white; border-radius: 16px; padding: 12px 8px; text-align: center; border: 1px solid #e2e8f0; }
        .stat-label { font-size: 11px; color: #64748b; margin-bottom: 6px; text-transform: uppercase; font-weight: 500; }
        .stat-value { font-size: 14px; font-weight: 700; color: #0f172a; }
        
        /* Quick Actions */
        .quick-actions { display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap; }
        .action-btn { flex: 1; padding: 12px; border-radius: 40px; font-size: 13px; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
        .action-btn:active { transform: scale(0.97); }
        .action-btn.primary { background: #0f172a; color: white; }
        .action-btn.secondary { background: white; color: #0f172a; border: 1px solid #e2e8f0; }
        .action-btn.online { background: #1e293b; color: white; }
        .action-btn.cash { background: #065f46; color: white; }
        
        /* Button Select */
        .btn-select { background: #3b82f6; border: none; color: white; font-size: 13px; font-weight: 600; padding: 6px 16px; border-radius: 20px; cursor: pointer; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-header h3 { font-size: 18px; font-weight: 600; color: #0f172a; }
        .view-all { color: #3b82f6; text-decoration: none; font-size: 13px; font-weight: 500; padding: 6px 12px; }
        
        /* Transaction List */
        .transaction-list { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; }
        .transaction-item { display: flex; justify-content: space-between; padding: 14px; border-bottom: 1px solid #f1f5f9; }
        .transaction-item:last-child { border-bottom: none; }
        .transaction-item.selected { background: #e8f0fe; }
        .transaction-left { display: flex; gap: 10px; flex: 1; align-items: center; }
        .transaction-checkbox { width: 18px; height: 18px; display: none; accent-color: #3b82f6; }
        .transaction-date { text-align: center; min-width: 45px; }
        .date-day { font-size: 18px; font-weight: 700; }
        .date-month { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .transaction-category { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
        .transaction-desc { font-size: 11px; color: #64748b; }
        .transaction-source { font-size: 10px; color: #3b82f6; margin-top: 2px; }
        .transaction-right { text-align: right; }
        .transaction-amount { font-weight: 700; color: #dc2626; font-size: 13px; margin-bottom: 6px; }
        .transaction-actions { display: flex; gap: 8px; justify-content: flex-end; }
        .action-edit, .action-delete, .action-print { text-decoration: none; font-size: 11px; padding: 3px 8px; border-radius: 5px; font-weight: 500; }
        .action-edit { background: #f1f5f9; color: #0f172a; }
        .action-print { background: #e8f0fe; color: #3b82f6; }
        .action-delete { background: #fee2e2; color: #dc2626; }
        
        .insight-card { background: #fef9e3; border-radius: 16px; padding: 14px; margin-top: 16px; border: 1px solid #fde68a; }
        .insight-label { font-size: 11px; color: #92400e; margin-bottom: 4px; }
        .insight-value { font-size: 16px; font-weight: 700; color: #78350f; }
        .empty-state { text-align: center; padding: 40px 20px; background: white; border-radius: 20px; border: 1px solid #e2e8f0; }
        .empty-state p { color: #64748b; font-size: 14px; }
        
        /* Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: flex-end; z-index: 1000; }
        .modal-content { background: white; width: 100%; border-radius: 24px 24px 0 0; padding: 24px 20px 32px; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 20px; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; width: 40px; height: 40px; }
        .input-group { margin-bottom: 16px; }
        .input-label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; }
        .modal-input { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 15px; background: #f8fafc; }
        .modal-input:focus { outline: none; border-color: #3b82f6; }
        .modal-actions { display: flex; gap: 12px; margin-top: 20px; }
        .modal-btn { flex: 1; padding: 12px; border: none; border-radius: 40px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .modal-btn.primary { background: #0f172a; color: white; }
        .modal-btn.secondary { background: #f1f5f9; color: #0f172a; }
        .source-options { display: flex; gap: 12px; margin: 16px 0; }
        .source-option { flex: 1; padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .source-option.selected { border-color: #3b82f6; background: #e8f0fe; }
        .source-option.online { color: #1e293b; }
        .source-option.cash { color: #065f46; }
        
        .notification { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; position: relative; padding-right: 48px; font-size: 13px; }
        .notification-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .notification-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .notification-close { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 16px; cursor: pointer; }
        
        #multiSelectActions { display: flex; gap: 12px; margin-top: 16px; justify-content: center; }
        #cetakGabunganBtn { background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 40px; font-weight: 600; }
        small { font-size: 11px; display: block; margin-top: 6px; color: #64748b; }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <div>
            <h1>Keuangan</h1>
            <div class="header-date"><?php echo date('l, d M Y'); ?></div>
        </div>
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
    </div>

    <div class="menu-dropdown" id="menuDropdown">
        <a href="index.php" class="menu-item active">Dashboard</a>
        <a href="report.php" class="menu-item">Laporan</a>
        <a href="#" onclick="showTambahSaldoForm('online'); return false;" class="menu-item">Tambah Saldo Online</a>
        <a href="#" onclick="showTambahSaldoForm('cash'); return false;" class="menu-item">Tambah Uang Cash</a>
        <a href="#" onclick="showAturSaldoForm('online'); return false;" class="menu-item">Atur Saldo Online</a>
        <a href="#" onclick="showAturSaldoForm('cash'); return false;" class="menu-item">Atur Uang Cash</a>
    </div>

    <!-- Saldo Cards -->
    <div class="saldo-container">
        <div class="saldo-card online">
            <div class="saldo-label">Saldo Online</div>
            <div class="saldo-nominal">Rp <?php echo number_format($saldo_online, 0, ',', '.'); ?></div>
            <div class="saldo-small">e-wallet, bank, dll</div>
        </div>
        <div class="saldo-card cash">
            <div class="saldo-label">Uang Cash</div>
            <div class="saldo-nominal">Rp <?php echo number_format($saldo_cash, 0, ',', '.'); ?></div>
            <div class="saldo-small">tunai / fisik</div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-item"><div class="stat-label">Total Saldo</div><div class="stat-value">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></div></div>
        <div class="stat-item"><div class="stat-label">Bulan Ini</div><div class="stat-value">Rp <?php echo number_format($total_bulan_ini, 0, ',', '.'); ?></div></div>
        <div class="stat-item"><div class="stat-label">Hari Ini</div><div class="stat-value">Rp <?php echo number_format($total_hari_ini, 0, ',', '.'); ?></div></div>
        <div class="stat-item"><div class="stat-label">Transaksi</div><div class="stat-value"><?php echo $transaksi_count; ?>x</div></div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <button class="action-btn primary" onclick="showForm('add')">+ Tambah</button>
        <button class="action-btn online" onclick="showTambahSaldoForm('online')">Tambah Online</button>
        <button class="action-btn cash" onclick="showTambahSaldoForm('cash')">Tambah Cash</button>
        <a href="report.php" class="action-btn secondary">Laporan</a>
    </div>

    <!-- Form Tambah Saldo -->
    <div id="formTambahSaldo" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header"><h3 id="tambahSaldoTitle">Tambah Saldo</h3><button class="modal-close" onclick="hideForm('tambahSaldo')">×</button></div>
            <form method="POST" id="tambahSaldoForm">
                <input type="hidden" name="action" id="tambahSaldoAction" value="">
                <div class="input-group">
                    <label class="input-label">Nominal Tambahan</label>
                    <input type="text" class="modal-input" name="saldo" placeholder="Masukkan nominal" onkeyup="formatRupiah(this)" autocomplete="off" required>
                    <small id="tambahSaldoInfo"></small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn secondary" onclick="hideForm('tambahSaldo')">Batal</button>
                    <button type="submit" class="modal-btn primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Atur Saldo -->
    <div id="formAturSaldo" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header"><h3 id="aturSaldoTitle">Atur Saldo</h3><button class="modal-close" onclick="hideForm('aturSaldo')">×</button></div>
            <form method="POST" id="aturSaldoForm">
                <input type="hidden" name="action" id="aturSaldoAction" value="">
                <div class="input-group">
                    <label class="input-label">Saldo Baru</label>
                    <input type="text" class="modal-input" name="saldo" placeholder="Masukkan saldo baru" onkeyup="formatRupiah(this)" autocomplete="off" required>
                    <small id="aturSaldoInfo" style="color:#dc2626;">Perhatian: Ini akan mengatur ulang saldo</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn secondary" onclick="hideForm('aturSaldo')">Batal</button>
                    <button type="submit" class="modal-btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Add/Edit -->
    <div id="formAdd" class="modal" style="display: <?php echo $edit_data ? 'flex' : 'none'; ?>;">
        <div class="modal-content">
            <div class="modal-header"><h3><?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Pengeluaran</h3><button class="modal-close" onclick="hideForm('add')">×</button></div>
            <form method="POST" onsubmit="return validateForm(this)">
                <input type="hidden" name="action" value="<?php echo $edit_data ? 'update' : 'create'; ?>">
                <?php if ($edit_data): ?><input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>"><?php endif; ?>
                
                <div class="input-group">
                    <label class="input-label">Kategori</label>
                    <select name="category_id" class="modal-input" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_data && $edit_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label">Jumlah</label>
                    <input type="text" class="modal-input" name="amount" required value="<?php echo $edit_data ? number_format($edit_data['amount'], 0, ',', '.') : ''; ?>" placeholder="0" onkeyup="formatRupiah(this)">
                </div>

                <div class="input-group">
                    <label class="input-label">Sumber Dana</label>
                    <div class="source-options">
                        <div class="source-option online" onclick="selectSource('online')">
                            <div>💳 Online</div>
                            <small style="font-size:10px">Rp <?php echo number_format($saldo_online, 0, ',', '.'); ?></small>
                        </div>
                        <div class="source-option cash" onclick="selectSource('cash')">
                            <div>💰 Cash</div>
                            <small style="font-size:10px">Rp <?php echo number_format($saldo_cash, 0, ',', '.'); ?></small>
                        </div>
                    </div>
                    <input type="hidden" name="source" id="selectedSource" value="online">
                </div>

                <div class="input-group">
                    <label class="input-label">Keterangan</label>
                    <input type="text" class="modal-input" name="description" value="<?php echo htmlspecialchars($edit_data['description'] ?? ''); ?>" placeholder="Contoh: Belanja bulanan">
                </div>

                <div class="input-group">
                    <label class="input-label">Tanggal</label>
                    <input type="date" class="modal-input" name="expense_date" value="<?php echo $edit_data['expense_date'] ?? date('Y-m-d'); ?>" required>
                </div>

                <div class="print-option">
                    <label class="checkbox-label">
                        <input type="checkbox" name="print_receipt" value="1" <?php echo !$edit_data ? 'checked' : ''; ?>>
                        <span>Cetak struk setelah simpan</span>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn secondary" onclick="hideForm('add')">Batal</button>
                    <button type="submit" class="modal-btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="notification notification-<?php echo $message_type; ?>">
        <span><?php echo $message; ?></span>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <!-- Recent Transactions -->
    <div class="recent-section">
        <div class="section-header">
            <h3>Transaksi Terbaru</h3>
            <div>
                <button class="btn-select" id="selectModeBtn" onclick="toggleSelectMode()">Pilih</button>
                <a href="report.php" class="view-all">Lihat Semua</a>
            </div>
        </div>

        <form method="GET" action="multi_receipt.php" id="multiSelectForm">
            <div class="transaction-list">
                <?php if (empty($recent_expenses)): ?>
                    <div class="empty-state"><p>Belum ada transaksi</p><p style="font-size:12px;">Klik tombol Tambah untuk mencatat pengeluaran</p></div>
                <?php else: ?>
                    <?php foreach ($recent_expenses as $e): ?>
                        <div class="transaction-item" data-id="<?php echo $e['id']; ?>">
                            <div class="transaction-left">
                                <input type="checkbox" name="ids[]" value="<?php echo $e['id']; ?>" class="transaction-checkbox">
                                <div class="transaction-date">
                                    <span class="date-day"><?php echo date('d', strtotime($e['expense_date'])); ?></span>
                                    <span class="date-month"><?php echo date('M', strtotime($e['expense_date'])); ?></span>
                                </div>
                                <div>
                                    <div class="transaction-category"><?php echo isset($categories_map[$e['category_id']]) ? $categories_map[$e['category_id']] : 'Lainnya'; ?></div>
                                    <?php if (!empty($e['description'])): ?><div class="transaction-desc"><?php echo htmlspecialchars($e['description']); ?></div><?php endif; ?>
                                    <div class="transaction-source"><?php echo $e['source'] === 'online' ? '💳 Online' : '💰 Cash'; ?></div>
                                </div>
                            </div>
                            <div class="transaction-right">
                                <div class="transaction-amount">Rp <?php echo number_format($e['amount'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="transaction-actions">
                                    <a href="?edit=<?php echo $e['id']; ?>" class="action-edit">Edit</a>
                                    <a href="receipt.php?id=<?php echo $e['id']; ?>" class="action-print" target="_blank">Struk</a>
                                    <a href="?delete=<?php echo $e['id']; ?>" class="action-delete" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="multiSelectActions" style="display: none; text-align: center;">
                <button type="submit" class="action-btn" id="cetakGabunganBtn" style="background:#3b82f6; color:white; border:none; padding:10px 24px;">Cetak Gabungan (0)</button>
                <button type="button" class="action-btn" onclick="cancelMultiSelect()" style="background:#ef4444; color:white; border:none; padding:10px 24px;">Batal</button>
            </div>
        </form>

        <?php if ($pengeluaran_terbesar > 0): ?>
        <div class="insight-card"><div class="insight-label">Pengeluaran Terbesar</div><div class="insight-value">Rp <?php echo number_format($pengeluaran_terbesar, 0, ',', '.'); ?></div></div>
        <?php endif; ?>
    </div>
</div>

<script>
let selectMode = false;
let currentSource = 'online';

function selectSource(source) {
    currentSource = source;
    document.getElementById('selectedSource').value = source;
    
    // Update UI
    document.querySelectorAll('.source-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    document.querySelector(`.source-option.${source}`).classList.add('selected');
}

function toggleMenu() {
    const menu = document.getElementById('menuDropdown');
    if (menu) menu.classList.toggle('show');
}

function showForm(type) {
    let formId = type === 'add' ? 'formAdd' : null;
    if (type === 'tambahSaldo') formId = 'formTambahSaldo';
    if (type === 'aturSaldo') formId = 'formAturSaldo';
    
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('menuDropdown')?.classList.remove('show');
    }
}

function hideForm(type) {
    let formId = type === 'add' ? 'formAdd' : (type === 'tambahSaldo' ? 'formTambahSaldo' : 'formAturSaldo');
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    if (type === 'add' && window.location.search.includes('edit')) window.location.href = 'index.php';
}

function showTambahSaldoForm(source) {
    const title = document.getElementById('tambahSaldoTitle');
    const action = document.getElementById('tambahSaldoAction');
    const info = document.getElementById('tambahSaldoInfo');
    
    if (source === 'online') {
        title.textContent = 'Tambah Saldo Online';
        action.value = 'tambah_saldo_online';
        info.textContent = 'Menambah saldo ke e-wallet / rekening bank';
    } else {
        title.textContent = 'Tambah Uang Cash';
        action.value = 'tambah_saldo_cash';
        info.textContent = 'Menambah uang tunai fisik';
    }
    
    document.getElementById('tambahSaldoForm').reset();
    showForm('tambahSaldo');
}

function showAturSaldoForm(source) {
    const title = document.getElementById('aturSaldoTitle');
    const action = document.getElementById('aturSaldoAction');
    const info = document.getElementById('aturSaldoInfo');
    
    if (source === 'online') {
        title.textContent = 'Atur Saldo Online';
        action.value = 'update_saldo_online';
        info.textContent = 'Perhatian: Ini akan mengatur ulang saldo online';
    } else {
        title.textContent = 'Atur Uang Cash';
        action.value = 'update_saldo_cash';
        info.textContent = 'Perhatian: Ini akan mengatur ulang uang cash';
    }
    
    showForm('aturSaldo');
}

function formatRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value && value !== '0') input.value = parseInt(value).toLocaleString('id-ID');
    else input.value = '';
}

function validateForm(form) {
    const amountInputs = form.querySelectorAll('input[name="amount"], input[name="saldo"]');
    amountInputs.forEach(input => { if (input && input.value) input.value = input.value.replace(/\./g, ''); });
    return true;
}

function updateCetakButton() {
    const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
    const count = checkedBoxes.length;
    const btn = document.getElementById('cetakGabunganBtn');
    if (btn) btn.textContent = `Cetak Gabungan (${count})`;
}

function toggleSelectMode() {
    selectMode = !selectMode;
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    const actions = document.getElementById('multiSelectActions');
    const btn = document.getElementById('selectModeBtn');
    
    checkboxes.forEach(cb => {
        cb.style.display = selectMode ? 'inline-block' : 'none';
        if (!selectMode) {
            cb.checked = false;
            cb.closest('.transaction-item')?.classList.remove('selected');
        }
    });
    
    actions.style.display = selectMode ? 'flex' : 'none';
    btn.textContent = selectMode ? 'Selesai' : 'Pilih';
    btn.style.background = selectMode ? '#dc2626' : '#3b82f6';
    if (!selectMode) updateCetakButton();
}

function cancelMultiSelect() {
    const checkboxes = document.querySelectorAll('.transaction-checkbox');
    const btn = document.getElementById('selectModeBtn');
    
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.style.display = 'none';
        cb.closest('.transaction-item')?.classList.remove('selected');
    });
    
    document.getElementById('multiSelectActions').style.display = 'none';
    selectMode = false;
    btn.textContent = 'Pilih';
    btn.style.background = '#3b82f6';
    updateCetakButton();
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('transaction-checkbox')) {
        const item = e.target.closest('.transaction-item');
        if (e.target.checked) item.classList.add('selected');
        else item.classList.remove('selected');
        updateCetakButton();
    }
});

document.addEventListener('click', function(event) {
    const menu = document.getElementById('menuDropdown');
    const menuBtn = document.querySelector('.menu-btn');
    if (menu && menuBtn && !menu.contains(event.target) && !menuBtn.contains(event.target)) menu.classList.remove('show');
});

document.getElementById('cetakGabunganBtn')?.addEventListener('click', function(e) {
    const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Pilih minimal satu transaksi terlebih dahulu');
        return false;
    }
    document.getElementById('multiSelectForm').submit();
});

// Set default source selection on load
document.addEventListener('DOMContentLoaded', function() {
    selectSource('online');
    <?php if ($edit_data): ?>
    showForm('add');
    <?php endif; ?>
});
</script>
</body>
</html>
