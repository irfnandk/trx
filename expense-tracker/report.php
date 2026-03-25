<?php
// report.php
require_once 'includes/header.php';

// Get database instance
$db = getDB();

// Get all expenses
$expenses = $db->selectExpenses(10000);
$categories = $db->getCategories();

// Pastikan categories adalah array
if (!is_array($categories)) {
    $categories = [];
}

$categories_map = [];
foreach ($categories as $cat) {
    if (isset($cat['id']) && isset($cat['name'])) {
        $categories_map[$cat['id']] = $cat['name'];
    }
}

// Get filter parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_week = isset($_GET['week']) ? $_GET['week'] : date('Y-\WW');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Filter expenses based on report type
$filtered_expenses = [];
$period_label = '';

switch ($report_type) {
    case 'daily':
        $filtered_expenses = array_filter($expenses, function($e) use ($selected_date) {
            return $e['expense_date'] === $selected_date;
        });
        $period_label = date('d F Y', strtotime($selected_date));
        break;
        
    case 'weekly':
        $week_start = date('Y-m-d', strtotime($selected_week . '-1'));
        $week_end = date('Y-m-d', strtotime($selected_week . '-7'));
        $filtered_expenses = array_filter($expenses, function($e) use ($week_start, $week_end) {
            return $e['expense_date'] >= $week_start && $e['expense_date'] <= $week_end;
        });
        $period_label = date('d M Y', strtotime($week_start)) . ' - ' . date('d M Y', strtotime($week_end));
        break;
        
    case 'monthly':
        $filtered_expenses = array_filter($expenses, function($e) use ($selected_month) {
            return strpos($e['expense_date'], $selected_month) === 0;
        });
        $period_label = date('F Y', strtotime($selected_month . '-01'));
        break;
}

// Calculate totals by category
$category_totals = [];
foreach ($filtered_expenses as $e) {
    $cat_id = $e['category_id'];
    if (!isset($category_totals[$cat_id])) {
        $category_totals[$cat_id] = 0;
    }
    $category_totals[$cat_id] += floatval($e['amount'] ?? 0);
}

// Sort categories by total descending
arsort($category_totals);

// Calculate summary statistics
$total_pengeluaran = array_sum($category_totals);
$transaksi_count = count($filtered_expenses);
$rata_rata = $transaksi_count > 0 ? round($total_pengeluaran / $transaksi_count) : 0;
$pengeluaran_terbesar = !empty($filtered_expenses) ? max(array_column($filtered_expenses, 'amount')) : 0;

// Get daily breakdown for weekly/monthly reports
$daily_breakdown = [];
if ($report_type === 'weekly' || $report_type === 'monthly') {
    foreach ($filtered_expenses as $e) {
        $date = $e['expense_date'];
        if (!isset($daily_breakdown[$date])) {
            $daily_breakdown[$date] = 0;
        }
        $daily_breakdown[$date] += floatval($e['amount'] ?? 0);
    }
    ksort($daily_breakdown);
}

// Saldo info
$saldo_awal = $db->getSaldo();
$total_semua_pengeluaran = $db->getTotalPengeluaran();
$sisa_saldo = $saldo_awal - $total_semua_pengeluaran;

// Generate months for dropdown
$months = [];
for ($i = 0; $i < 12; $i++) {
    $month_date = date('Y-m', strtotime("-$i months"));
    $months[$month_date] = date('F Y', strtotime($month_date));
}

// Generate weeks for dropdown
$weeks = [];
for ($i = 0; $i < 8; $i++) {
    $week_date = date('Y-\WW', strtotime("-$i weeks"));
    $week_start = date('d M', strtotime($week_date . '-1'));
    $week_end = date('d M', strtotime($week_date . '-7'));
    $weeks[$week_date] = "Minggu $week_start - $week_end";
}

// Generate days for dropdown (last 30 days)
$days = [];
for ($i = 0; $i < 30; $i++) {
    $day_date = date('Y-m-d', strtotime("-$i days"));
    $days[$day_date] = date('d F Y', strtotime($day_date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Laporan Keuangan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fb;
            color: #1e293b;
            line-height: 1.5;
        }

        .app {
            max-width: 480px;
            margin: 0 auto;
            padding: 20px 16px 32px;
            min-height: 100vh;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #1e293b, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 12px;
            text-decoration: none;
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-btn:active {
            transform: scale(0.95);
            background: #f1f5f9;
        }

        /* Report Type Tabs */
        .report-tabs {
            display: flex;
            gap: 8px;
            background: white;
            padding: 6px;
            border-radius: 60px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: #64748b;
        }

        .tab-btn.active {
            background: #3b82f6;
            color: white;
        }

        .tab-btn:active {
            transform: scale(0.97);
        }

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .filter-select {
            width: 100%;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            font-size: 15px;
            background: #f8fafc;
            cursor: pointer;
            font-weight: 500;
            color: #1e293b;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-value.large {
            font-size: 28px;
            color: #3b82f6;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0 16px 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            position: relative;
            padding-left: 12px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #3b82f6;
            border-radius: 2px;
        }

        /* Category List */
        .category-list {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info {
            flex: 1;
        }

        .category-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .category-percentage {
            font-size: 12px;
            color: #64748b;
        }

        .category-amount {
            font-weight: 700;
            color: #ef4444;
            text-align: right;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #3b82f6;
            border-radius: 3px;
        }

        /* Daily List */
        .daily-list {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .daily-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .daily-item:last-child {
            border-bottom: none;
        }

        .daily-date {
            font-weight: 500;
            color: #1e293b;
        }

        .daily-amount {
            font-weight: 600;
            color: #ef4444;
        }

        /* Transaction List */
        .transaction-list {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-info {
            flex: 1;
        }

        .transaction-category {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .transaction-desc {
            font-size: 12px;
            color: #64748b;
        }

        .transaction-date {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .transaction-amount {
            font-weight: 700;
            color: #ef4444;
            text-align: right;
            font-size: 15px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 8px;
        }

        /* Print Button */
        .print-btn {
            width: 100%;
            padding: 16px;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.2s;
        }

        .print-btn:active {
            transform: scale(0.97);
            background: #0f172a;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: rgba(0, 0, 0, 0.2);
            font-family: monospace;
            z-index: 999;
            pointer-events: none;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .report-tabs, 
            .filter-card, 
            .back-btn, 
            .print-btn,
            .watermark {
                display: none;
            }
            
            .app {
                padding: 0;
                max-width: 100%;
                background: white;
            }
            
            .stat-card {
                border: 1px solid #ddd;
            }
            
            .category-list,
            .daily-list,
            .transaction-list {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<div class="watermark">@irfanandkp</div>

<div class="app">
    <div class="header">
        <h1>Laporan Keuangan</h1>
        <a href="index.php" class="back-btn">← Kembali</a>
    </div>

    <!-- Report Type Tabs -->
    <div class="report-tabs">
        <button class="tab-btn <?php echo $report_type === 'daily' ? 'active' : ''; ?>" onclick="setReportType('daily')">Harian</button>
        <button class="tab-btn <?php echo $report_type === 'weekly' ? 'active' : ''; ?>" onclick="setReportType('weekly')">Mingguan</button>
        <button class="tab-btn <?php echo $report_type === 'monthly' ? 'active' : ''; ?>" onclick="setReportType('monthly')">Bulanan</button>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="" id="filterForm">
            <input type="hidden" name="type" id="reportType" value="<?php echo $report_type; ?>">
            
            <?php if ($report_type === 'daily'): ?>
                <select name="date" class="filter-select" onchange="this.form.submit()">
                    <?php foreach ($days as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected_date == $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
            <?php elseif ($report_type === 'weekly'): ?>
                <select name="week" class="filter-select" onchange="this.form.submit()">
                    <?php foreach ($weeks as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected_week == $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
            <?php else: ?>
                <select name="month" class="filter-select" onchange="this.form.submit()">
                    <?php foreach ($months as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected_month == $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($filtered_expenses)): ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <p>Belum ada data pengeluaran</p>
            <p style="font-size: 13px;">Pada periode <?php echo $period_label; ?></p>
        </div>
    <?php else: ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value large">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Jumlah Transaksi</div>
                <div class="stat-value"><?php echo $transaksi_count; ?> transaksi</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Rata-rata</div>
                <div class="stat-value">Rp <?php echo number_format($rata_rata, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Terbesar</div>
                <div class="stat-value">Rp <?php echo number_format($pengeluaran_terbesar, 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Rekap per Kategori -->
        <div class="section-header">
            <div class="section-title">Rekap per Kategori</div>
            <div style="font-size: 12px; color: #64748b;"><?php echo $period_label; ?></div>
        </div>
        <div class="category-list">
            <?php 
            $total = $total_pengeluaran;
            foreach ($category_totals as $cat_id => $amount): 
                $percentage = $total > 0 ? round(($amount / $total) * 100) : 0;
                $cat_name = isset($categories_map[$cat_id]) ? $categories_map[$cat_id] : 'Lainnya';
            ?>
                <div class="category-item">
                    <div class="category-info">
                        <div class="category-name"><?php echo htmlspecialchars($cat_name); ?></div>
                        <div class="category-percentage"><?php echo $percentage; ?>%</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                    <div class="category-amount">Rp <?php echo number_format($amount, 0, ',', '.'); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Rincian Harian -->
        <?php if (($report_type === 'weekly' || $report_type === 'monthly') && !empty($daily_breakdown)): ?>
            <div class="section-header">
                <div class="section-title">Rincian Harian</div>
            </div>
            <div class="daily-list">
                <?php foreach ($daily_breakdown as $date => $amount): ?>
                    <div class="daily-item">
                        <span class="daily-date"><?php echo date('d F Y', strtotime($date)); ?></span>
                        <span class="daily-amount">Rp <?php echo number_format($amount, 0, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Detail Transaksi -->
        <div class="section-header">
            <div class="section-title">Detail Transaksi</div>
            <div style="font-size: 12px; color: #64748b;"><?php echo $transaksi_count; ?> item</div>
        </div>
        <div class="transaction-list">
            <?php 
            $sorted_expenses = $filtered_expenses;
            usort($sorted_expenses, function($a, $b) {
                return strtotime($b['expense_date']) - strtotime($a['expense_date']);
            });
            foreach ($sorted_expenses as $e): 
            ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-category">
                            <?php echo isset($categories_map[$e['category_id']]) ? $categories_map[$e['category_id']] : 'Lainnya'; ?>
                        </div>
                        <?php if (!empty($e['description'])): ?>
                            <div class="transaction-desc"><?php echo htmlspecialchars($e['description']); ?></div>
                        <?php endif; ?>
                        <div class="transaction-date"><?php echo date('d M Y', strtotime($e['expense_date'])); ?></div>
                    </div>
                    <div class="transaction-amount">
                        Rp <?php echo number_format($e['amount'], 0, ',', '.'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Print Button -->
        <button class="print-btn" onclick="window.print()">🖨️ Cetak Laporan</button>
        
    <?php endif; ?>

    <div class="footer">
        Dicetak: <?php echo date('d/m/Y H:i'); ?> | @irfanandkp
    </div>
</div>

<script>
function setReportType(type) {
    document.getElementById('reportType').value = type;
    document.getElementById('filterForm').submit();
}

// Handle date change
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

</body>
</html>