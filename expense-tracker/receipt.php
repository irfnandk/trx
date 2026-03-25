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
    <title>Struk Transaksi Pribadi</title>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Courier New', 'Lucida Sans Typewriter', monospace;
            background: #e0e0e0;
            min-height: 100vh;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .struk-wrapper {
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
        }

        .struk {
            background: white;
            padding: 20px 16px;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .struk-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .struk-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .struk-subtitle {
            font-size: 11px;
            margin-top: 4px;
            color: #666;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .struk-divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .struk-divider-dotted {
            border-top: 1px dotted #ccc;
            margin: 8px 0;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 8px;
            border-bottom: 1px dotted #000;
            padding-bottom: 4px;
        }

        .item-name-header {
            flex: 2;
            text-align: left;
        }

        .item-qty-header {
            width: 40px;
            text-align: center;
        }

        .item-price-header {
            width: 90px;
            text-align: right;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .item-name {
            flex: 2;
            text-align: left;
            word-break: break-word;
        }

        .item-qty {
            width: 40px;
            text-align: center;
        }

        .item-price {
            width: 90px;
            text-align: right;
            font-weight: 500;
        }

        .item-desc {
            font-size: 9px;
            color: #666;
            margin-left: 8px;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }

        .saldo-info {
            background: #f9f9f9;
            padding: 10px;
            margin: 12px 0;
            border: 1px dashed #ccc;
        }

        .saldo-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .struk-footer {
            text-align: center;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed #000;
        }

        .note {
            font-size: 9px;
            color: #666;
            margin: 8px 0;
            line-height: 1.4;
        }

        .date-time {
            font-size: 9px;
            margin: 8px 0;
            color: #666;
        }

        .watermark {
            font-size: 8px;
            color: #999;
            margin-top: 8px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            width: 100%;
            max-width: 360px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 100px;
            padding: 14px 12px;
            border: 1px solid #000;
            background: white;
            font-size: 13px;
            font-family: monospace;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: black;
            transition: all 0.2s;
            border-radius: 4px;
        }

        .btn:active {
            background: #f0f0f0;
            transform: scale(0.97);
        }

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
            padding: 20px 30px;
            border-radius: 8px;
            text-align: center;
            font-family: monospace;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .struk-wrapper {
                max-width: 100%;
            }
            .struk {
                box-shadow: none;
                padding: 12px;
            }
            .button-group {
                display: none;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            .struk {
                padding: 16px 12px;
            }
            .btn {
                padding: 12px 10px;
                font-size: 12px;
                min-width: 90px;
            }
        }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Mengunduh struk...</p>
    </div>
</div>

<div class="struk-wrapper">
    <div class="struk" id="strukContent">
        <div class="struk-header">
            <div class="struk-title">CATATAN KEUANGAN </div>
            <div class="struk-subtitle">Transaksi Pengeluaran</div>
            <div class="struk-divider"></div>
            <div class="info-row">
                <span class="info-label">ID Transaksi</span>
                <span class="info-value">#<?php echo str_pad($expense['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Waktu</span>
                <span class="info-value"><?php echo date('H:i:s'); ?></span>
            </div>
        </div>

        <div class="item-header">
            <span class="item-name-header">Keterangan</span>
            <span class="item-qty-header"></span>
            <span class="item-price-header">Nominal</span>
        </div>
        
        <div class="item-row">
            <span class="item-name"><?php echo isset($categories_map[$expense['category_id']]) ? $categories_map[$expense['category_id']] : 'Pengeluaran'; ?></span>
            <span class="item-qty"></span>
            <span class="item-price">Rp <?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
        </div>
        
        <?php if (!empty($expense['description'])): ?>
        <div class="item-desc">
            Catatan: <?php echo htmlspecialchars($expense['description']); ?>
        </div>
        <?php endif; ?>

        <div class="total-row">
            <span>TOTAL PENGELUARAN</span>
            <span>Rp <?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
        </div>

        <div class="saldo-info">
            <div class="saldo-row">
                <span>Saldo Sebelumnya</span>
                <span>Rp <?php echo number_format($saldo_awal + $expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="saldo-row">
                <span>Pengeluaran Ini</span>
                <span style="color: #dc2626;">- Rp <?php echo number_format($expense['amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="saldo-row" style="font-weight: bold; margin-top: 6px; padding-top: 6px; border-top: 1px dotted #ccc;">
                <span>Sisa Saldo</span>
                <span>Rp <?php echo number_format($sisa_saldo, 0, ',', '.'); ?></span>
            </div>
        </div>

        <div class="struk-footer">
            <div style="margin: 10px 0;">
                <?php echo str_pad($expense['id'], 12, '0', STR_PAD_LEFT); ?>
            </div>
            <div class="note">
                Terima kasih telah mencatat pengeluaran<br>
                Tetap bijak dalam mengelola keuangan
            </div>
            <div class="date-time">
                Dicatat: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
            <div class="watermark">
                @irfanandkp
            </div>
        </div>
    </div>

    <div class="button-group">
        <button class="btn" onclick="downloadAsPNG()">DOWNLOAD</button>
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
    
    // Fungsi download sebagai PNG
    async function downloadAsPNG() {
        const element = document.getElementById('strukContent');
        const loading = document.getElementById('loadingOverlay');
        
        loading.style.display = 'flex';
        
        try {
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const canvas = await html2canvas(element, {
                scale: 3,
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: false
            });
            
            const link = document.createElement('a');
            link.download = 'catatan_keuangan_<?php echo str_pad($expense['id'], 6, '0', STR_PAD_LEFT); ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal mengunduh struk. Silakan coba lagi.');
        } finally {
            loading.style.display = 'none';
        }
    }
    
    // Touch events untuk mobile
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.97)';
        });
        btn.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
</script>
</body>
</html>