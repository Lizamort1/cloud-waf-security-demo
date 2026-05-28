<?php
/**
 * WAF Debug - Kiểm tra xem WAF hoạt động không
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Lấy IP
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    $ips = [];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips[$header] = $_SERVER[$header];
        }
    }
    return $ips;
}

// Load WAF
require_once 'includes/db.php';
require_once 'waf/LizWAF.php';

LizWAF::init($conn);

// Lấy config
$config = LizWAF::getConfig();

// Kiểm tra cache directory
$cacheDir = __DIR__ . '/waf/cache/';
$cacheWritable = is_writable($cacheDir);

// Kiểm tra file cache cho IP hiện tại
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cacheFile = $cacheDir . md5($clientIP) . '.pm';
$cacheExists = file_exists($cacheFile);
$cacheContent = $cacheExists ? file_get_contents($cacheFile) : null;

// Thử ghi file test
$testFile = $cacheDir . 'test_' . time() . '.txt';
$writeTest = @file_put_contents($testFile, 'test');
if ($writeTest) {
    @unlink($testFile);
    $canWrite = true;
} else {
    $canWrite = false;
}

// Lấy stats
$stats = LizWAF::getStats();

// Trả về debug info
echo json_encode([
    'waf_enabled' => $config['waf_enabled'] ?? 'unknown',
    'block_ddos' => $config['block_ddos'] ?? 'unknown',
    'rate_limit' => $config['rate_limit'] ?? 'unknown',
    'auto_block_ip' => $config['auto_block_ip'] ?? 'unknown',
    'client_ips' => getClientIP(),
    'cache_dir' => $cacheDir,
    'cache_dir_exists' => is_dir($cacheDir),
    'cache_dir_writable' => $cacheWritable,
    'can_write_file' => $canWrite,
    'cache_file_for_ip' => $cacheFile,
    'cache_file_exists' => $cacheExists,
    'cache_content' => $cacheContent,
    'waf_stats' => $stats,
    'timestamp' => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
?>
