<?php
// multi_receipt.php
require_once 'includes/header.php';

$db = getDB();

// Get selected transaction IDs
$ids = isset($_GET['ids']) ? $_GET['ids'] : [];
if (!is_array($ids)) {
    $ids = explode(',', $ids);
}

// Get expenses data
$expenses = [];
$total_semua = 0;
foreach ($ids as $id) {
    $expense = $db->getExpense(intval($id));
    if ($expense) {
        $expenses[] = $expense;
        $total_semua += floatval($expense['amount']);
    }
}

$categories = $db->getCategories();
$categories_map = [];
foreach ($categories as $cat) {
    $categories_map[$cat['id']] = $cat['name'];
}

$saldo_awal = $db->getSaldo();
$total_pengeluaran = $db->getTotalPengeluaran();
$sisa_saldo = $saldo_awal - $total_pengeluaran;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Struk Gabungan</title>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .struk-container { max-width: 350px; width: 100%; margin: 0 auto; }
        .struk { background: white; padding: 16px 14px; width: 100%; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .struk-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .struk-title { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .struk-subtitle { font-size: 9px; margin-top: 2px; color: #666; }
        .info-row { display: flex; justify-content: space-between; font-size: 9px; margin-bottom: 4px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .item-header { display: flex; justify-content: space-between; font-size: 9px; font-weight: bold; margin: 8px 0 6px; border-bottom: 1px dotted #000; padding-bottom: 3px; }
        .item-name-header { flex: 2; text-align: left; }
        .item-price-header { width: 80px; text-align: right; }
        .item-row { display: flex; justify-content: space-between; font-size: 9px; margin-bottom: 4px; }
        .item-name { flex: 2; text-align: left; word-break: break-word; }
        .item-price { width: 80px; text-align: right; }
        .item-desc { font-size: 8px; color: #666; margin-left: 6px; margin-bottom: 6px; border-left: 2px solid #ccc; padding-left: 6px; }
        .total-row { display: flex; justify-content: space-between; font-size: 10px; font-weight: bold; margin: 10px 0 8px; padding-top: 6px; border-top: 1px dashed #000; }
        .grand-total { background: #f0f0f0; padding: 8px; margin: 10px 0; font-weight: bold; border: 1px solid #ccc; }
        .struk-footer { text-align: center; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #000; }
        .thankyou { font-size: 10px; font-weight: bold; margin: 8px 0; text-transform: uppercase; }
        .footer-note { font-size: 8px; color: #666; margin: 6px 0; }
        .datetime { font-size: 7px; margin: 6px 0; color: #888; }
        .watermark { font-size: 7px; color: #ccc; margin-top: 6px; }
        .button-group { display: flex; gap: 8px; margin-top: 16px; width: 100%; }
        .btn { flex: 1; padding: 10px 8px; border: 1px solid #333; background: white; font-size: 11px; font-family: monospace; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; color: #333; border-radius: 2px; }
        .btn:active { background: #f0f0f0; transform: scale(0.97); }
        .selected-info { background: #f5f5f5; padding: 8px; margin-bottom: 12px; text-align: center; font-size: 9px; border: 1px solid #ccc; }
        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; display: none; }
        .loading-content { background: white; padding: 16px 24px; border-radius: 4px; text-align: center; }
        .spinner { width: 30px; height: 30px; border: 2px solid #f3f3f3; border-top: 2px solid #333; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 8px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media print { body { background: white; } .button-group { display: none; } .selected-info { display: none; } }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content"><div class="spinner"></div><p>Mengunduh...</p></div>
</div>

<div class="struk-container">
    <div class="selected-info">
        Menampilkan <?php echo count($expenses); ?> transaksi | Total: Rp <?php echo number_format($total_semua, 0, ',', '.'); ?>
    </div>

    <div class="struk" id="strukContent">
        <div class="struk-header">
            <div class="struk-title">STRUK GABUNGAN</div>
            <div class="struk-subtitle">Aplikasi Keuangan Pribadi</div>
        </div>

        <div class="info-row"><span>Jumlah Transaksi</span><span><?php echo count($expenses); ?> transaksi</span></div>
        <div class="info-row"><span>Tanggal Cetak</span><span><?php echo date('d/m/Y H:i:s'); ?></span></div>

        <div class="divider"></div>

        <div class="item-header">
            <span class="item-name-header">Tanggal & Keterangan</span>
            <span class="item-price-header">Nominal</span>
        </div>

        <?php foreach ($expenses as $e): ?>
        <div class="item-row">
            <span class="item-name">[<?php echo date('d/m', strtotime($e['expense_date'])); ?>] <?php echo isset($categories_map[$e['category_id']]) ? $categories_map[$e['category_id']] : 'Pengeluaran'; ?></span>
            <span class="item-price">Rp<?php echo number_format($e['amount'], 0, ',', '.'); ?></span>
        </div>
        <?php if (!empty($e['description'])): ?>
        <div class="item-desc"><?php echo htmlspecialchars($e['description']); ?></div>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="total-row">
            <span>TOTAL SEMUA</span>
            <span>Rp<?php echo number_format($total_semua, 0, ',', '.'); ?></span>
        </div>

        <div class="grand-total">
            <div class="info-row"><span>Sisa Saldo</span><span>Rp<?php echo number_format($sisa_saldo, 0, ',', '.'); ?></span></div>
        </div>

        <div class="struk-footer">
            <div class="thankyou">TERIMA KASIH</div>
            <div class="footer-note">Barang yang sudah dibeli<br>tidak dapat dikembalikan</div>
            <div class="datetime"><?php echo date('d/m/Y H:i:s'); ?></div>
            <div class="watermark">@irfanandkp</div>
        </div>
    </div>

    <div class="button-group">
        <button class="btn" onclick="downloadAsPNG()">DOWNLOAD</button>
        <button class="btn" onclick="window.print()">CETAK</button>
        <a href="index.php" class="btn">KEMBALI</a>
    </div>
</div>

<script>
    async function downloadAsPNG() {
        const element = document.getElementById('strukContent');
        const loading = document.getElementById('loadingOverlay');
        loading.style.display = 'flex';
        try {
            await new Promise(resolve => setTimeout(resolve, 100));
            const canvas = await html2canvas(element, { scale: 3, backgroundColor: '#ffffff', logging: false });
            const link = document.createElement('a');
            link.download = 'struk_gabungan_<?php echo date('Ymd_His'); ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) { alert('Gagal mengunduh'); }
        finally { loading.style.display = 'none'; }
    }
</script>
</body>
</html>
