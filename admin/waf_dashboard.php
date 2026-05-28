<?php
/**
 * LIZ-WAF Admin Dashboard
 * Improved UI V2 - Vietnamese, Analytics, DDoS
 */

// Set timezone to Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once '../includes/db.php';
require_once '../waf/LizWAF.php';

// Initialize WAF (không protect trang này)
LizWAF::init($conn);

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Bạn cần đăng nhập với tài khoản admin.'];
    header("Location: ../login.php");
    exit();
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_config':
            $config = [
                'waf_enabled' => isset($_POST['waf_enabled']) ? 1 : 0,
                'block_sql_injection' => isset($_POST['block_sql_injection']) ? 1 : 0,
                'block_xss' => isset($_POST['block_xss']) ? 1 : 0,
                'block_command_injection' => isset($_POST['block_command_injection']) ? 1 : 0,
                'block_path_traversal' => isset($_POST['block_path_traversal']) ? 1 : 0,
                'block_ddos' => isset($_POST['block_ddos']) ? 1 : 0,
                'distributed_detection' => isset($_POST['distributed_detection']) ? 1 : 0,
                'auto_block_ip' => isset($_POST['auto_block_ip']) ? 1 : 0,
                'rate_limit' => intval($_POST['rate_limit'] ?? 100),
                'global_rate_limit' => intval($_POST['global_rate_limit'] ?? 1000),
                'distributed_threshold' => intval($_POST['distributed_threshold'] ?? 50),
            ];
            LizWAF::updateConfig($config);
            echo json_encode(['success' => true]);
            exit;
            
        case 'unblock_ip':
            LizWAF::unblockIP($_POST['ip'] ?? '');
            echo json_encode(['success' => true]);
            exit;
            
        case 'block_ip':
            LizWAF::blockIP($_POST['ip'] ?? '', $_POST['reason'] ?? 'Chặn thủ công');
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_logs':
            LizWAF::clearLogs();
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_blocked':
            LizWAF::clearBlockedIPs();
            echo json_encode(['success' => true]);
            exit;

        case 'add_whitelist':
            LizWAF::addToWhitelist($_POST['ip'] ?? '', $_POST['desc'] ?? '');
            echo json_encode(['success' => true]);
            exit;

        case 'remove_whitelist':
            LizWAF::removeFromWhitelist($_POST['ip'] ?? '');
            echo json_encode(['success' => true]);
            exit;
    }
}

// Get data
$stats = LizWAF::getStats();
$config = LizWAF::getConfig();
$attacks = LizWAF::getRecentAttacks(500);
$blocked_ips = LizWAF::getBlockedIPs();
$whitelist = LizWAF::getWhitelist();

// Helper function to display timestamp in Vietnam timezone
// InfinityFree MySQL uses America/Los_Angeles (UTC-8)
// Vietnam is UTC+7, so need to add 15 hours total
function convertToVietnamTime($dbTimestamp) {
    if (empty($dbTimestamp)) return '';
    try {
        // Create DateTime from database timestamp
        $dt = new DateTime($dbTimestamp);
        // Add 15 hours to convert from LA (UTC-8) to Vietnam (UTC+7)
        $dt->modify('+15 hours');
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $dbTimestamp;
    }
}

// Calculate analytics
$attack_by_type = [];
$attack_by_hour = array_fill(0, 24, 0);

// Get current time in Vietnam for comparison
$now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
$today = $now->format('Y-m-d');

foreach ($attacks as $attack) {
    $type = $attack['attack_type'];
    // Exclude 'IP Blocked' from attack types chart since it's not an attack type
    if ($type !== 'IP Blocked') {
        $attack_by_type[$type] = ($attack_by_type[$type] ?? 0) + 1;
    }
    
    // Convert to Vietnam time for hourly chart
    $vnTime = convertToVietnamTime($attack['created_at']);
    $vnDate = date('Y-m-d', strtotime($vnTime));
    
    // Only count attacks from TODAY for the hourly chart
    if ($vnDate === $today) {
        $hour = (int)date('G', strtotime($vnTime));
        $attack_by_hour[$hour]++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAF Dashboard - Cấu Hình Bảo Mật</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 22px; font-weight: 600; }
        .header-nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            transition: 0.3s;
            font-size: 14px;
        }
        .header-nav a:hover { background: rgba(255,255,255,0.15); }
        
        /* Container */
        .container { max-width: 1400px; margin: 0 auto; padding: 25px; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #3498db;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card.danger { border-left-color: #e74c3c; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-card.success { border-left-color: #27ae60; }
        .stat-card.info { border-left-color: #9b59b6; }
        .stat-card.purple { border-left-color: #8e44ad; }
        .stat-label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-value { font-size: 28px; font-weight: 700; color: #2c3e50; margin: 6px 0; }
        .stat-desc { font-size: 11px; color: #95a5a6; }
        .status-on { color: #27ae60; }
        .status-off { color: #e74c3c; }
        
        /* Panel */
        .panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .panel-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-header h2 { font-size: 15px; font-weight: 600; color: #2c3e50; }
        .panel-body { padding: 20px; }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }
        
        .chart-panel {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .chart-panel h3 { font-size: 14px; color: #2c3e50; margin-bottom: 15px; font-weight: 600; }
        .chart-container { position: relative; height: 280px; }
        
        /* Config Grid */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: 0.2s;
        }
        .config-item:hover { border-color: #3498db; }
        .config-item.main {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #e8f4fd, #f0f7ff);
            border: 2px solid #3498db;
            padding: 18px 20px;
        }
        .config-item.disabled { opacity: 0.5; pointer-events: none; }
        .config-info h4 { font-size: 13px; color: #2c3e50; margin-bottom: 3px; font-weight: 600; }
        .config-info p { font-size: 11px; color: #7f8c8d; }
        
        /* Rate Limit Input */
        .rate-input {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rate-input input {
            width: 80px;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }
        .rate-input input:focus { outline: none; border-color: #3498db; }
        .rate-input span { font-size: 12px; color: #7f8c8d; }
        
        /* Toggle */
        .toggle { position: relative; width: 48px; height: 24px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #ccc;
            border-radius: 24px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .toggle input:checked + .toggle-slider { background: #27ae60; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(24px); }
        
        /* Table */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; font-size: 11px; text-transform: uppercase; color: #7f8c8d; letter-spacing: 0.3px; }
        td { font-size: 13px; color: #2c3e50; }
        tr:hover { background: #fafbfc; }
        .empty-row { text-align: center; color: #95a5a6; padding: 40px; font-size: 14px; }
        
        /* Buttons */
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.85; }
        .btn-sm { padding: 6px 10px; font-size: 11px; }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-danger { background: #fde8e8; color: #c0392b; }
        .badge-warning { background: #fef3e2; color: #d35400; }
        .badge-info { background: #e8f4fd; color: #2980b9; }
        .badge-success { background: #e8f8f0; color: #27ae60; }
        .badge-purple { background: #f3e8fd; color: #8e44ad; }
        
        /* Actions bar */
        .actions-bar { display: flex; gap: 8px; flex-wrap: wrap; }
        
        /* Input group */
        .input-group { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .input-group input {
            flex: 1;
            min-width: 150px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        .input-group input:focus { outline: none; border-color: #3498db; }
        
        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 22px;
            background: #27ae60;
            color: white;
            border-radius: 6px;
            font-weight: 500;
            font-size: 13px;
            z-index: 10000;
            display: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .toast.show { display: block; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body>
    <header class="header">
        <h1>WAF Dashboard - Cấu Hình Bảo Mật</h1>
        <nav class="header-nav">
            <a href="index.php">Quay Lại Admin</a>
            <a href="../index.php">Trang Chủ</a>
        </nav>
    </header>
    
    <div class="container">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-label">Trạng Thái WAF</div>
                <div class="stat-value <?= $config['waf_enabled'] ? 'status-on' : 'status-off' ?>" id="wafStatusText">
                    <?= $config['waf_enabled'] ? 'BẬT' : 'TẮT' ?>
                </div>
                <div class="stat-desc">Web Application Firewall</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-label">Tổng Tấn Công</div>
                <div class="stat-value"><?= number_format($stats['total_attacks']) ?></div>
                <div class="stat-desc">Đã phát hiện</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">IP Bị Chặn</div>
                <div class="stat-value"><?= number_format($stats['blocked_ips']) ?></div>
                <div class="stat-desc">Đang bị khóa</div>
            </div>
            <div class="stat-card info">
                <div class="stat-label">SQL Injection</div>
                <div class="stat-value"><?= number_format($stats['sql_injection']) ?></div>
                <div class="stat-desc">Cuộc tấn công</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-label">XSS</div>
                <div class="stat-value"><?= number_format($stats['xss']) ?></div>
                <div class="stat-desc">Cuộc tấn công</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-label">DDoS</div>
                <div class="stat-value"><?= number_format($stats['ddos'] ?? 0) ?></div>
                <div class="stat-desc">Vượt Rate Limit</div>
            </div>
        </div>
        
        <!-- Analytics Charts -->
        <div class="charts-grid">
            <div class="chart-panel">
                <h3>Phân Loại Tấn Công</h3>
                <div class="chart-container">
                    <canvas id="attackTypeChart"></canvas>
                </div>
            </div>
            <div class="chart-panel">
                <h3>Tấn Công Theo Giờ (Hôm Nay - <?= date('d/m/Y') ?>)</h3>
                <div class="chart-container">
                    <canvas id="attackHourChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Configuration -->
        <div class="panel">
            <div class="panel-header">
                <h2>Cấu Hình WAF</h2>
            </div>
            <div class="panel-body">
                <form id="configForm">
                    <div class="config-grid">
                        <!-- Master Switch -->
                        <div class="config-item main">
                            <div class="config-info">
                                <h4>WAF Master Switch</h4>
                                <p>Bật/Tắt toàn bộ hệ thống WAF (tắt sẽ tắt tất cả bảo vệ)</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="waf_enabled" id="masterSwitch" <?= $config['waf_enabled'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <!-- Protection toggles -->
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>SQL Injection</h4>
                                <p>Chặn các cuộc tấn công SQL Injection</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="block_sql_injection" class="child-toggle" <?= $config['block_sql_injection'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>XSS Protection</h4>
                                <p>Chặn Cross-Site Scripting</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="block_xss" class="child-toggle" <?= $config['block_xss'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Command Injection</h4>
                                <p>Chặn OS Command Injection</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="block_command_injection" class="child-toggle" <?= $config['block_command_injection'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Path Traversal</h4>
                                <p>Chặn Directory Traversal</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="block_path_traversal" class="child-toggle" <?= $config['block_path_traversal'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Chống DDoS</h4>
                                <p>Giới hạn số request mỗi phút</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="block_ddos" class="child-toggle" <?= ($config['block_ddos'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Distributed Detection</h4>
                                <p>Phát hiện tấn công phân tán (Botnet)</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="distributed_detection" class="child-toggle" <?= ($config['distributed_detection'] ?? 1) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Tự Động Chặn IP</h4>
                                <p>Tự động chặn IP khi phát hiện tấn công</p>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" name="auto_block_ip" class="child-toggle" <?= $config['auto_block_ip'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <!-- Rate Limit Config -->
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Rate Limit (DDoS)</h4>
                                <p>Số request tối đa mỗi phút cho mỗi IP</p>
                            </div>
                            <div class="rate-input">
                                <input type="number" name="rate_limit" id="rateLimit" value="<?= $config['rate_limit'] ?? 100 ?>" min="5" max="1000">
                                <span>req/phút</span>
                            </div>
                        </div>

                        <!-- Global Rate Limit -->
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>Global Rate Limit</h4>
                                <p>Tổng request toàn server/phút</p>
                            </div>
                            <div class="rate-input">
                                <input type="number" name="global_rate_limit" id="globalRateLimit" value="<?= $config['global_rate_limit'] ?? 1000 ?>" min="100" max="10000">
                                <span>req/phút</span>
                            </div>
                        </div>

                        <!-- Distributed Threshold -->
                        <div class="config-item protection-toggle">
                            <div class="config-info">
                                <h4>DDos IP Threshold</h4>
                                <p>Ngưỡng IP phân tán (Unique IPs/min)</p>
                            </div>
                            <div class="rate-input">
                                <input type="number" name="distributed_threshold" id="distThreshold" value="<?= $config['distributed_threshold'] ?? 50 ?>" min="5" max="500">
                                <span>IPs</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Blocked IPs -->
        <div class="panel">
            <div class="panel-header">
                <h2>Danh Sách IP Bị Chặn</h2>
                <div class="actions-bar">
                    <button class="btn btn-danger btn-sm" onclick="clearBlockedIPs()">Xóa Tất Cả</button>
                    <button class="btn btn-secondary btn-sm" onclick="location.reload()">Làm Mới</button>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Địa Chỉ IP</th>
                                <th>Lý Do</th>
                                <th>Số Lần Tấn Công</th>
                                <th>Thời Gian Chặn</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($blocked_ips)): ?>
                                <tr><td colspan="5" class="empty-row">Chưa có IP nào bị chặn</td></tr>
                            <?php else: ?>
                                <?php foreach ($blocked_ips as $ip): ?>
                                <tr>
                                    <td><strong style="color: #e74c3c;"><?= htmlspecialchars($ip['ip_address']) ?></strong></td>
                                    <td><?= htmlspecialchars($ip['reason']) ?></td>
                                    <td style="text-align: center;"><?= $ip['attack_count'] ?></td>
                                    <td style="font-size: 12px; color: #7f8c8d;"><?= convertToVietnamTime($ip['blocked_at']) ?></td>
                                    <td>
                                        <button class="btn btn-success btn-sm" onclick="unblockIP('<?= $ip['ip_address'] ?>')">Mở Chặn</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="input-group">
                    <input type="text" id="manualIP" placeholder="Nhập địa chỉ IP (vd: 192.168.1.100)">
                    <input type="text" id="blockReason" placeholder="Lý do (không bắt buộc)">
                    <button class="btn btn-danger" onclick="blockIP()">Chặn IP</button>
                </div>
            </div>
        </div>

        <!-- Whitelist Management -->
        <div class="panel">
            <div class="panel-header">
                <h2>Danh Sách Tin Cậy (Whitelist)</h2>
                <div class="actions-bar">
                    <button class="btn btn-secondary btn-sm" onclick="location.reload()">Làm Mới</button>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Địa Chỉ IP</th>
                                <th>Mô Tả</th>
                                <th>Người Tạo</th>
                                <th>Ngày Tạo</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($whitelist)): ?>
                                <tr><td colspan="5" class="empty-row">Chưa có IP nào trong whitelist</td></tr>
                            <?php else: ?>
                                <?php foreach ($whitelist as $w_ip): ?>
                                <tr>
                                    <td><strong style="color: #27ae60;"><?= htmlspecialchars($w_ip['ip_address']) ?></strong></td>
                                    <td><?= htmlspecialchars($w_ip['description']) ?></td>
                                    <td><?= htmlspecialchars($w_ip['created_by'] ?? 'admin') ?></td>
                                    <td style="font-size: 12px; color: #7f8c8d;"><?= convertToVietnamTime($w_ip['created_at']) ?></td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="removeWhitelist('<?= $w_ip['ip_address'] ?>')">Xóa</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="input-group">
                    <input type="text" id="whitelistIP" placeholder="Nhập IP tin cậy (vd: 127.0.0.1)">
                    <input type="text" id="whitelistDesc" placeholder="Mô tả (vd: Localhost, Monitoring)">
                    <button class="btn btn-success" onclick="addWhitelist()">Thêm Whitelist</button>
                </div>
            </div>
        </div>
        
        <!-- Attack Logs -->
        <div class="panel">
            <div class="panel-header">
                <h2>Nhật Ký Tấn Công</h2>
                <button class="btn btn-danger btn-sm" onclick="clearLogs()">Xóa Nhật Ký</button>
            </div>
            <div class="panel-body">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Loại</th>
                                <th>Địa Chỉ IP</th>
                                <th>Payload</th>
                                <th>Đường Dẫn</th>
                                <th>Thời Gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attacks)): ?>
                                <tr><td colspan="5" class="empty-row">Chưa phát hiện tấn công nào</td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice($attacks, 0, 50) as $attack): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-danger';
                                        if ($attack['attack_type'] == 'XSS') $badgeClass = 'badge-warning';
                                        if ($attack['attack_type'] == 'Command Injection') $badgeClass = 'badge-info';
                                        if ($attack['attack_type'] == 'Path Traversal') $badgeClass = 'badge-purple';
                                        if ($attack['attack_type'] == 'DDoS') $badgeClass = 'badge-success';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($attack['attack_type']) ?></span>
                                    </td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($attack['ip_address']) ?></td>
                                    <td><code style="font-size: 11px; background: #f5f5f5; padding: 3px 8px; border-radius: 3px;"><?= htmlspecialchars(substr($attack['payload'] ?? '', 0, 35)) ?>...</code></td>
                                    <td style="font-size: 12px; color: #7f8c8d;"><?= htmlspecialchars(substr($attack['request_uri'] ?? '', 0, 25)) ?></td>
                                    <td style="font-size: 12px; color: #95a5a6;"><?= convertToVietnamTime($attack['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast">Đã lưu thành công!</div>
    
    <script>
        // Chart data
        const attackByType = <?= json_encode($attack_by_type) ?>;
        const attackByHour = <?= json_encode(array_values($attack_by_hour)) ?>;
        
        // Attack Type Doughnut Chart
        const typeCtx = document.getElementById('attackTypeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(attackByType).length ? Object.keys(attackByType) : ['Chưa có dữ liệu'],
                datasets: [{
                    data: Object.keys(attackByType).length ? Object.values(attackByType) : [1],
                    backgroundColor: Object.keys(attackByType).length 
                        ? ['#e74c3c', '#f39c12', '#3498db', '#9b59b6', '#27ae60', '#1abc9c']
                        : ['#ecf0f1'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { 
                            padding: 15,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        
        // Hourly Bar Chart - Improved
        const hourCtx = document.getElementById('attackHourChart').getContext('2d');
        const gradient = hourCtx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(52, 152, 219, 0.8)');
        gradient.addColorStop(1, 'rgba(52, 152, 219, 0.2)');
        
        new Chart(hourCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Số tấn công',
                    data: attackByHour,
                    backgroundColor: gradient,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barThickness: 'flex',
                    maxBarThickness: 25
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            stepSize: 1,
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { 
                            font: { size: 9 },
                            maxRotation: 45
                        },
                        grid: { display: false }
                    }
                }
            }
        });
        
        // Master Switch - controls all child toggles ONLY when clicked
        const masterSwitch = document.getElementById('masterSwitch');
        const childToggles = document.querySelectorAll('.child-toggle');
        const protectionItems = document.querySelectorAll('.protection-toggle');
        
        // Function to update UI state (disable/enable toggles) without changing their values
        function updateToggleAccessibility(masterEnabled) {
            protectionItems.forEach(item => {
                if (masterEnabled) {
                    item.classList.remove('disabled');
                } else {
                    item.classList.add('disabled');
                }
            });
            childToggles.forEach(toggle => {
                toggle.disabled = !masterEnabled;
            });
            document.getElementById('rateLimit').disabled = !masterEnabled;
        }
        
        // Function called ONLY when master switch is toggled by user
        function onMasterSwitchChange(enabled) {
            // Sync all child toggles with master state
            childToggles.forEach(toggle => {
                toggle.checked = enabled;
            });
            updateToggleAccessibility(enabled);
            saveConfig();
        }
        
        masterSwitch.addEventListener('change', function() {
            onMasterSwitchChange(this.checked);
        });
        
        // Initialize: only update accessibility, DON'T change toggle values
        updateToggleAccessibility(masterSwitch.checked);
        
        // Auto-save config on any toggle change
        document.querySelectorAll('#configForm input').forEach(input => {
            if (input.id !== 'masterSwitch') {
                input.addEventListener('change', saveConfig);
            }
        });
        
        // Save on rate limit change (with debounce)
        let rateLimitTimeout;
        document.getElementById('rateLimit').addEventListener('input', function() {
            clearTimeout(rateLimitTimeout);
            rateLimitTimeout = setTimeout(saveConfig, 500);
        });
        
        async function saveConfig() {
            const form = document.getElementById('configForm');
            const formData = new FormData(form);
            formData.append('action', 'update_config');
            
            // Ensure checkboxes are sent correctly
            document.querySelectorAll('#configForm input[type="checkbox"]').forEach(input => {
                if (input.checked) {
                    formData.set(input.name, '1');
                }
            });
            
            await fetch('', { method: 'POST', body: formData });
            
            // Update status display
            const wafEnabled = masterSwitch.checked;
            const statusText = document.getElementById('wafStatusText');
            statusText.textContent = wafEnabled ? 'BẬT' : 'TẮT';
            statusText.className = 'stat-value ' + (wafEnabled ? 'status-on' : 'status-off');
            
            showToast('Đã lưu cấu hình!');
        }
        
        async function unblockIP(ip) {
            if (!confirm('Mở chặn IP: ' + ip + '?')) return;
            const formData = new FormData();
            formData.append('action', 'unblock_ip');
            formData.append('ip', ip);
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã mở chặn IP!');
            setTimeout(() => location.reload(), 500);
        }
        
        async function blockIP() {
            const ip = document.getElementById('manualIP').value.trim();
            const reason = document.getElementById('blockReason').value.trim() || 'Chặn thủ công bởi admin';
            if (!ip) { alert('Vui lòng nhập địa chỉ IP'); return; }
            
            const formData = new FormData();
            formData.append('action', 'block_ip');
            formData.append('ip', ip);
            formData.append('reason', reason);
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã chặn IP!');
            setTimeout(() => location.reload(), 500);
        }

        async function addWhitelist() {
            const ip = document.getElementById('whitelistIP').value.trim();
            const desc = document.getElementById('whitelistDesc').value.trim();
            if (!ip) { alert('Vui lòng nhập địa chỉ IP'); return; }
            
            const formData = new FormData();
            formData.append('action', 'add_whitelist');
            formData.append('ip', ip);
            formData.append('desc', desc);
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã thêm whitelist!');
            setTimeout(() => location.reload(), 500);
        }

        async function removeWhitelist(ip) {
            if (!confirm('Xóa IP khỏi whitelist: ' + ip + '?')) return;
            const formData = new FormData();
            formData.append('action', 'remove_whitelist');
            formData.append('ip', ip);
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã xóa khỏi whitelist!');
            setTimeout(() => location.reload(), 500);
        }
        
        async function clearLogs() {
            if (!confirm('Xóa tất cả nhật ký tấn công?')) return;
            const formData = new FormData();
            formData.append('action', 'clear_logs');
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã xóa nhật ký!');
            setTimeout(() => location.reload(), 500);
        }
        
        async function clearBlockedIPs() {
            if (!confirm('Mở chặn TẤT CẢ IP?')) return;
            const formData = new FormData();
            formData.append('action', 'clear_blocked');
            await fetch('', { method: 'POST', body: formData });
            showToast('Đã mở chặn tất cả IP!');
            setTimeout(() => location.reload(), 500);
        }
        
        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
    </script>
</body>
</html>
