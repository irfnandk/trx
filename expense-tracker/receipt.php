<?php
// receipt.php
require_once 'includes/header.php';

// Get database instance
$db = getDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$expense = $db->getExpense($id);

if (!$expense) {
    header('Location: index.php');
    exit;
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
    <title>Struk Transaksi</title>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Lucida Sans Typewriter', monospace;
            background: #e8e8e8;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Container Struk */
        .struk-container {
            max-width: 300px;
            width: 100%;
            margin: 0 auto;
        }

        /* Struk Paper */
        .struk {
            background: white;
            padding: 16px 14px;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Header */
        .struk-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .struk-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .struk-subtitle {
            font-size: 9px;
            margin-top: 2px;
            color: #666;
        }

        .struk-address {
            font-size: 8px;
            margin-top: 4px;
            color: #888;
        }

        /* Info Row */
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 4px;
        }

        /* Divider */
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-dot {
            border-top: 1px dotted #ccc;
            margin: 6px 0;
        }

        /* Item Header */
        .item-header {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            margin: 8px 0 6px;
            border-bottom: 1px dotted #000;
            padding-bottom: 3px;
        }

        .item-name-header {
            flex: 2;
            text-align: left;
        }

        .item-price-header {
            width: 70px;
            text-align: right;
        }

        /* Item Row */
        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 4px;
        }

        .item-name {
            flex: 2;
            text-align: left;
            word-break: break-word;
        }

        .item-price {
            width: 70px;
            text-align: right;
        }

        /* Description */
        .item-desc {
            font-size: 8px;
            color: #666;
            margin-left: 6px;
            margin-bottom: 6px;
            padding-left: 6px;
            border-left: 2px solid #ccc;
        }

        /* Total */
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
            margin: 10px 0 8px;
            padding-top: 6px;
            border-top: 1px dashed #000;
        }

        /* Payment Info */
        .payment-info {
            margin: 10px 0;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 4px;
        }

        /* Saldo Info */
        .saldo-box {
            background: #f5f5f5;
            padding: 8px;
            margin: 10px 0;
            border: 1px dashed #ccc;
        }

        .saldo-row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            margin-bottom: 4px;
        }

        /* Footer */
        .struk-footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }

        .barcode {
            font-family: monospace;
            letter-spacing: 1px;
            font-size: 12px;
            margin: 8px 0;
            text-align: center;
        }

        .thankyou {
            font-size: 10px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
        }

        .footer-note {
            font-size: 8px;
            color: #666;
            margin: 6px 0;
            line-height: 1.3;
        }

        .datetime {
            font-size: 7px;
            margin: 6px 0;
            color: #888;
        }

        .watermark {
            font-size: 7px;
            color: #ccc;
            margin-top: 6px;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            max-width: 300px;
            width: 100%;
        }

        .btn {
            flex: 1;
            padding: 10px 8px;
            border: 1px solid #333;
            background: white;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
            border-radius: 2px;
        }

        .btn:active {
            background: #f0f0f0;
            transform: scale(0.97);
        }

        /* Loading */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .loading-content {
            background: white;
            padding: 16px 24px;
            border-radius: 4px;
            text-align: center;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #333;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Print */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .button-group {
                display: none;
            }
            .struk {
                box-shadow: none;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Mengunduh...</p>
    </div>
</div>

<div class="struk-container">
    <div class="struk" id="strukContent">
        <!-- Header -->
        <div class="struk-header">
            <div class="struk-title">STRUK PENGELUARAN</div>
            <div class="struk-subtitle">Aplikasi Keuangan Pribadi</div>
            <div class="struk-address">www.keuanganpribadi.com</div>
        </div>

        <!-- Transaction Info -->
        <div class="info-row">
            <span>No. Transaksi</span>
            <span>#<?php echo str_pad($expense['id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="info-row">
            <span>Tanggal</span>
            <span><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></span>
        </div>
        <div class="info-row">
            <span>Waktu</span>
            <span><?php echo date('H:i:s'); ?></span>
        </div>

        <div class="divider"></div>

        <!-- Items Header -->
        <div class="item-header">
            <span class="item-name-header">Item</span>
            <span class="item-price-header">Total</span>
        </div>

        <!-- Item -->
        <div class="item-row">
            <span class="item-name"><?php echo isset($categories_map[$expense['category_id']]) ? $categories_map[$expense['category_id']] : 'Pengeluaran'; ?></span>
            <span class="item-price">Rp<?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
        </div>

        <?php if (!empty($expense['description'])): ?>
        <div class="item-desc">
            <?php echo htmlspecialchars($expense['description']); ?>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Total -->
        <div class="total-row">
            <span>TOTAL</span>
            <span>Rp<?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
        </div>

        <!-- Payment -->
        <div class="payment-info">
            <div class="payment-row">
                <span>Total Belanja</span>
                <span>Rp<?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="payment-row">
                <span>Dibayar</span>
                <span>Rp<?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="payment-row">
                <span>Kembali</span>
                <span>Rp0</span>
            </div>
        </div>

        <div class="divider-dot"></div>

        <!-- Saldo Info -->
        <div class="saldo-box">
            <div class="saldo-row">
                <span>Saldo Sebelumnya</span>
                <span>Rp<?php echo number_format($saldo_awal + $expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="saldo-row">
                <span>Pengeluaran</span>
                <span style="color: #dc2626;">-Rp<?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="saldo-row" style="font-weight: bold; margin-top: 4px; padding-top: 4px; border-top: 1px dotted #ccc;">
                <span>Sisa Saldo</span>
                <span>Rp<?php echo number_format($sisa_saldo, 0, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="struk-footer">
            <div class="barcode">
                <?php echo str_pad($expense['id'], 10, '0', STR_PAD_LEFT); ?>
            </div>
            <div class="thankyou">
                TERIMA KASIH
            </div>
            <div class="footer-note">
                Barang yang sudah dibeli<br>
                tidak dapat dikembalikan
            </div>
            <div class="datetime">
                <?php echo date('d/m/Y H:i:s'); ?>
            </div>
            <div class="watermark">
                @irfanandkp
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="button-group">
        <button class="btn" onclick="downloadAsPNG()">DOWNLOAD</button>
        <button class="btn" onclick="window.print()">CETAK</button>
        <a href="index.php" class="btn">KEMBALI</a>
    </div>
</div>

<script>
    <?php if (isset($_GET['auto_print'])): ?>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    }
    <?php endif; ?>
    
    // Download as PNG
    async function downloadAsPNG() {
        const element = document.getElementById('strukContent');
        const loading = document.getElementById('loadingOverlay');
        
        loading.style.display = 'flex';
        
        try {
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const canvas = await html2canvas(element, {
                scale: 3,
                backgroundColor: '#ffffff',
                logging: false
            });
            
            const link = document.createElement('a');
            link.download = 'struk_<?php echo str_pad($expense['id'], 6, '0', STR_PAD_LEFT); ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            
        } catch (error) {
            alert('Gagal mengunduh struk. Silakan coba lagi.');
        } finally {
            loading.style.display = 'none';
        }
    }
</script>
</body>
</html>
