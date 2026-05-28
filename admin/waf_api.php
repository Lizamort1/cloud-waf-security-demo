<?php
/**
 * LIZ-WAF API - Các endpoint điều khiển WAF
 */

require_once '../config.php';
require_once '../waf/LizWAF.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Initialize WAF
LizWAF::init($conn);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    case 'unblock_my_ip':
        // Gỡ chặn IP của người đang request
        $ip = getClientIP();
        
        if (LizWAF::isIPBlocked($ip)) {
            LizWAF::unblockIP($ip);
            
            // Xóa file cache rate limit
            $cacheFile = __DIR__ . '/../waf/cache/' . md5($ip) . '.pm';
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'IP đã được gỡ chặn',
                'ip' => $ip
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'IP không bị chặn',
                'ip' => $ip
            ]);
        }
        break;
        
    case 'unblock_ip':
        // Gỡ chặn IP cụ thể (cần auth)
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $ip = $_POST['ip'] ?? '';
        if (empty($ip)) {
            echo json_encode(['success' => false, 'message' => 'Missing IP']);
            exit;
        }
        
        LizWAF::unblockIP($ip);
        
        // Xóa file cache
        $cacheFile = __DIR__ . '/../waf/cache/' . md5($ip) . '.pm';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        
        echo json_encode(['success' => true, 'ip' => $ip]);
        break;
        
    case 'get_stats':
        echo json_encode(LizWAF::getStats());
        break;
        
    case 'get_blocked_ips':
        echo json_encode(LizWAF::getBlockedIPs());
        break;
        
    case 'get_recent_attacks':
        $limit = intval($_GET['limit'] ?? 50);
        echo json_encode(LizWAF::getRecentAttacks($limit));
        break;
        
    case 'clear_rate_limit':
        // Xóa tất cả file cache rate limit
        $cacheDir = __DIR__ . '/../waf/cache/';
        $files = glob($cacheDir . '*.pm');
        foreach ($files as $file) {
            @unlink($file);
        }
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa rate limit cache',
            'files_deleted' => count($files)
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}

/**
 * Get client IP address
 */
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            return $ip;
        }
    }
    return 'unknown';
}
?>
