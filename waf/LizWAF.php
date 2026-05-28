<?php
// Set timezone to Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khởi động session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class LizWAF {
    const DEMO_MODE = true;
    
    // WAF Configuration
    private static $enabled = true;
    private static $block_sql_injection = true;
    private static $block_xss = true;
    private static $block_command_injection = true;
    private static $block_path_traversal = true;
    private static $block_ddos = true;
    private static $distributed_detection = true;
    private static $auto_block_ip = true;
    private static $rate_limit = 100;
    private static $global_rate_limit = 1000;
    private static $distributed_threshold = 50;
    
    // Scoring System: Block khi tổng điểm >= threshold
    private static $score_threshold = 10;
    
    // Rate limiting storage
    private static $rate_cache = [];
    
    // Database connection
    private static $conn = null;
    
    // SQL Injection patterns [pattern, score]
    private static $sql_patterns = [
        ['/(\bUNION\b.*\bSELECT\b)/i', 10],           
        ['/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i', 8],  
        ['/(\bSELECT\b.*\bFROM\b)/i', 5],              
        ['/(\bINSERT\b.*\bINTO\b)/i', 8],
        ['/(\bUPDATE\b.*\bSET\b)/i', 8],
        ['/(\bDELETE\b.*\bFROM\b)/i', 9],
        ['/(\bDROP\b.*\b(TABLE|DATABASE)\b)/i', 10],  
        ['/(\bOR\b\s+[\d\'\"]+\s*=\s*[\d\'\"])/i', 8], 
        ['/([\'\"]).*(\bOR\b|\bAND\b).*[\'\"]?\s*(=|<>|!=|<|>)/i', 6],
        ['/(--)\s/i', 3],                               
        ['/#.*$/mi', 2],                                
        ['/(\bAND\b\s+\d+\s*=\s*\d+)/i', 6],           
        ['/(;\s*DROP|;\s*DELETE|;\s*UPDATE|;\s*INSERT)/i', 10], 
        ['/(SLEEP\s*\(|BENCHMARK\s*\(|WAITFOR\s+DELAY)/i', 9], 
        ['/(\bEXEC\b|\bEXECUTE\b)\s/i', 7],
        ['/(\'\s*(OR|AND)\s+\')/i', 7],                 
        ['/(\bINFORMATION_SCHEMA\b)/i', 9],            
        ['/(\bLOAD_FILE\b|\bINTO\s+OUTFILE\b)/i', 10], 
    ];
    
    // XSS patterns [pattern, score]
    private static $xss_patterns = [
        ['/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', 10], 
        ['/<\s*script[^>]*>/i', 8],                         
        ['/javascript\s*:/i', 8],
        ['/on(error|load|click|mouseover|focus|blur|submit|change|input|keyup|keydown)\s*=/i', 7], 
        ['/<\s*iframe[^>]*>/i', 8],
        ['/<\s*object[^>]*>/i', 7],
        ['/<\s*embed[^>]*>/i', 7],
        ['/eval\s*\(/i', 8],
        ['/expression\s*\(/i', 7],
        ['/<\s*img[^>]*\s+onerror\s*=/i', 8],
        ['/<\s*svg[^>]*\s+on\w+\s*=/i', 8],
        ['/alert\s*\(|confirm\s*\(|prompt\s*\(/i', 5],      
        ['/document\s*\.\s*(cookie|location|write)/i', 7],  
        ['/\bString\s*\.\s*fromCharCode\s*\(/i', 8],       
    ];
    
    // Command Injection patterns [pattern, score]
    private static $cmd_patterns = [
        ['/[;`]\s*\w/i', 6],                                
        ['/\|\s*\w/i', 5],                                  
        ['/(\$\(|\$\{)/i', 7],                               
        ['/(\b(wget|curl|nc|ncat|bash|sh|zsh|cmd|powershell)\b)/i', 8],
        ['/(\|\s*(ls|cat|whoami|id|pwd|uname)\b)/i', 9],    
        ['/(\/bin\/|\/usr\/bin\/|\/etc\/)/i', 7],            
        ['/(\b(system|exec|passthru|shell_exec|popen|proc_open)\s*\()/i', 10], // PHP dangerous functions
    ];
    
    // Path Traversal patterns [pattern, score]
    private static $path_patterns = [
        ['/\.\.\//', 6],
        ['/\.\.\\\\/', 6],
        ['/%2e%2e(%2f|%5c)/i', 7],                          // URL encoded
        ['/\.\.(\\|\/)/i', 6],
        ['/%252e%252e%252f/i', 9],                           // Double encoded
        ['/^file:/i', 8],
        ['/file:.*\.(php|ini|conf|log|txt|sql|passwd|shadow)/i', 10],
        ['/(\/(etc|proc|var)\/)/i', 7],                    
    ];
    
    /**
     * Initialize WAF with database connection
     */
    public static function init($conn) {
        self::$conn = $conn;
        self::loadConfig();
        self::createTablesIfNotExist();
    }
    
    /**
     * Load WAF configuration from database
     */
    private static function loadConfig() {
        if (!self::$conn) return;
        
        try {
            $result = self::$conn->query("SELECT * FROM waf_config LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $config = $result->fetch_assoc();
                self::$enabled = (bool)$config['waf_enabled'];
                self::$block_sql_injection = (bool)$config['block_sql_injection'];
                self::$block_xss = (bool)$config['block_xss'];
                self::$block_command_injection = (bool)$config['block_command_injection'];
                self::$block_path_traversal = (bool)$config['block_path_traversal'];
                self::$block_ddos = (bool)($config['block_ddos'] ?? true);
                self::$distributed_detection = (bool)($config['distributed_detection'] ?? true);
                self::$auto_block_ip = (bool)$config['auto_block_ip'];
                self::$rate_limit = intval($config['rate_limit'] ?? 100);
                self::$global_rate_limit = intval($config['global_rate_limit'] ?? 1000);
                self::$distributed_threshold = intval($config['distributed_threshold'] ?? 50);
                self::$score_threshold = intval($config['score_threshold'] ?? 10);
            }
        } catch (Exception $e) {
            // Config table might not exist yet, use defaults
        }
    }
    
    /**
     * Create WAF tables if they don't exist
     */
    private static function createTablesIfNotExist() {
        if (!self::$conn) return;
        
        // WAF Config table
        self::$conn->query("
            CREATE TABLE IF NOT EXISTS waf_config (
                id INT PRIMARY KEY AUTO_INCREMENT,
                waf_enabled TINYINT(1) DEFAULT 1,
                block_sql_injection TINYINT(1) DEFAULT 1,
                block_xss TINYINT(1) DEFAULT 1,
                block_command_injection TINYINT(1) DEFAULT 1,
                block_path_traversal TINYINT(1) DEFAULT 1,
                block_ddos TINYINT(1) DEFAULT 1,
                distributed_detection TINYINT(1) DEFAULT 1,
                auto_block_ip TINYINT(1) DEFAULT 1,
                rate_limit INT DEFAULT 100,
                global_rate_limit INT DEFAULT 1000,
                distributed_threshold INT DEFAULT 50,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Add columns if not exist (for existing tables)
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS block_ddos TINYINT(1) DEFAULT 1");
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS distributed_detection TINYINT(1) DEFAULT 1");
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS rate_limit INT DEFAULT 100");
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS global_rate_limit INT DEFAULT 1000");
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS distributed_threshold INT DEFAULT 50");
        self::$conn->query("ALTER TABLE waf_config ADD COLUMN IF NOT EXISTS score_threshold INT DEFAULT 10");
        
        // Insert default config if empty
        $result = self::$conn->query("SELECT COUNT(*) as cnt FROM waf_config");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['cnt'] == 0) {
                self::$conn->query("INSERT INTO waf_config (waf_enabled) VALUES (1)");
            }
        }
        
        // Attack Logs table
        self::$conn->query("
            CREATE TABLE IF NOT EXISTS waf_attack_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                attack_type VARCHAR(50) NOT NULL,
                payload TEXT,
                request_uri VARCHAR(500),
                request_method VARCHAR(10),
                user_agent VARCHAR(500),
                action VARCHAR(20) DEFAULT 'Blocked',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_type (attack_type),
                INDEX idx_created (created_at)
            )
        ");
        
        // Blocked IPs table
        self::$conn->query("
            CREATE TABLE IF NOT EXISTS waf_blocked_ips (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason VARCHAR(200),
                attack_count INT DEFAULT 1,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Whitelist Table
        self::$conn->query("
            CREATE TABLE IF NOT EXISTS waf_whitelist (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                description VARCHAR(200),
                created_by VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // DDoS Metrics Table
        self::$conn->query("
            CREATE TABLE IF NOT EXISTS waf_ddos_metrics (
                id INT PRIMARY KEY AUTO_INCREMENT,
                minute_bucket INT NOT NULL,
                total_requests INT DEFAULT 0,
                unique_ips INT DEFAULT 0,
                blocked_requests INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_minute (minute_bucket)
            )
        ");
    }
    
    /**
     * Main protection function - call this at the start of every page
     */
    public static function protect() {
        if (!self::$enabled) {
            return true; // WAF disabled, allow request
        }
        
        $ip = self::getClientIP();
        
        // 1. Whitelist Check (Priority 1)
        if (self::isWhitelisted($ip)) {
            return true; // Allow whitelisted IP
        }
        
        // 2. Block List Check (Priority 2)
        if (self::isIPBlocked($ip)) {
            // Log blocked IP attempt for dashboard visibility
            self::logAttack($ip, 'IP Blocked', 'Request from blocked IP');
            self::showBlockedPage('IP Blocked', $ip);
            exit;
        }
        
        // 3. Track & Check Global Metrics (Distributed DDoS)
        $globalStatus = self::trackAndCheckGlobal($ip);
        
        if ($globalStatus['blocked']) {
            self::logAttack($ip, $globalStatus['reason'], 'Server under high load');
            // Optional: Auto block simple rate limiters don't auto-block on global limit usually 
            // to avoid blocking legit users during flash crowds, but for DDoS demo we show it.
            self::showBlockedPage($globalStatus['reason'], $ip);
            exit;
        }
        
        // 4. Individual Rate Limit
        if (self::$block_ddos && self::isRateLimitExceeded($ip)) {
            self::logAttack($ip, 'DDoS', 'Rate limit exceeded: ' . self::$rate_limit . ' req/min');
            if (self::$auto_block_ip) {
                self::blockIP($ip, "Auto-blocked: DDoS Rate Limit");
            }
            self::showBlockedPage('DDoS', $ip);
            exit;
        }
        
        // 5. Analyze request for attacks (Scoring System)
        $attack = self::analyzeRequest();
        
        if ($attack !== false) {
            // Log attack with score info
            $scoreInfo = '[Score: ' . $attack['score'] . '/' . self::$score_threshold . '] ';
            self::logAttack($ip, $attack['type'], $scoreInfo . $attack['payload']);
            
            // Auto-block IP
            if (self::$auto_block_ip) {
                self::blockIP($ip, "Auto-blocked: " . $attack['type']);
            }
            
            // Show blocked page
            self::showBlockedPage($attack['type'], $ip);
            exit;
        }
        
        return true;
    }

    /**
     * Track global metrics and check for distributed attacks
     * Returns ['blocked' => bool, 'reason' => string]
     */
    private static function trackAndCheckGlobal($ip) {
        $cacheDir = __DIR__ . '/cache/';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
            if (!file_exists($cacheDir . '.htaccess')) @file_put_contents($cacheDir . '.htaccess', "Deny from all");
        }

        $bucket = floor(time() / 60); // Current minute bucket
        $file = $cacheDir . 'global_' . $bucket . '.pm';
        
        $blocked = false;
        $reason = '';
        
        $fp = @fopen($file, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $data = fread($fp, 1024);
            $stats = $data ? json_decode($data, true) : ['req' => 0, 'ips' => []];
            
            if (!$stats) $stats = ['req' => 0, 'ips' => []];
            
            // Update stats
            $stats['req']++;
            if (!in_array($ip, $stats['ips'])) {
                $stats['ips'][] = $ip;
            }
            
            // Trim IP list if too large to save space/time (just keep recent for count)
            if (count($stats['ips']) > 1000) array_shift($stats['ips']);
            
            $unique_ips = count($stats['ips']);
            $total_req = $stats['req'];
            
            // Check Global Limit
            if ($total_req > self::$global_rate_limit) {
                $blocked = true;
                $reason = 'Global Rate Limit Exceeded';
            }
            
            // Check Distributed Attack
            if (self::$distributed_detection && $unique_ips > self::$distributed_threshold) {
                 // Heuristic: If we have too many unique IPs AND high load
                 if ($total_req > (self::$global_rate_limit * 0.5)) {
                     $blocked = true;
                     $reason = 'Distributed DDoS Detected';
                 }
            }
            
            // Write back
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($stats));
            
            flock($fp, LOCK_UN);
            fclose($fp);
            
            // Async update to DB (Probability sampling to reduce DB load)
            if (rand(1, 100) <= 5) {
                self::updateDBMetrics($bucket, $total_req, $unique_ips);
            }
        }
        
        return ['blocked' => $blocked, 'reason' => $reason];
    }
    
    /**
     * Update Metrics to DB
     */
    private static function updateDBMetrics($minute, $total, $unique) {
        if (!self::$conn) return;
        $stmt = self::$conn->prepare("
            INSERT INTO waf_ddos_metrics (minute_bucket, total_requests, unique_ips) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE total_requests = ?, unique_ips = ?
        ");
        $stmt->bind_param("iiiii", $minute, $total, $unique, $total, $unique);
        $stmt->execute();
    }
    
    /**
     * Check if rate limit is exceeded for an IP 
     */
    private static function isRateLimitExceeded($ip) {
        $cacheDir = __DIR__ . '/cache/';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
            // Tạo file .htaccess để bảo vệ thư mục cache
            if (!file_exists($cacheDir . '.htaccess')) {
                @file_put_contents($cacheDir . '.htaccess', "Deny from all");
            }
        }

        $file = $cacheDir . md5($ip) . '.pm'; // .pm = protect me
        $now = time();
        
        // Mở file với lock để tránh race condition
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            // Không mở được file -> bỏ qua rate limit
            return false;
        }
        
        // Khóa file độc quyền (blocking) - đảm bảo chỉ 1 process xử lý tại 1 thời điểm
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }
        
        // Đọc dữ liệu
        $data = '';
        $size = filesize($file);
        if ($size > 0) {
            $data = fread($fp, $size);
        }
        
        $shouldBlock = false;
        
        if ($data && strpos($data, '|') !== false) {
            list($logTime, $count) = explode('|', $data);
            $logTime = intval($logTime);
            $count = intval($count);
            
            // Nếu trong cùng 1 phút -> tăng đếm
            if ($now - $logTime < 60) {
                $count++;
                
                // Chặn nếu vượt quá rate limit
                if ($count > self::$rate_limit) { 
                    $shouldBlock = true;
                }
                
                // Ghi lại counter mới
                fseek($fp, 0);
                ftruncate($fp, 0);
                fwrite($fp, "$logTime|$count");
            } else {
                // Đã qua 1 phút -> Reset counter
                fseek($fp, 0);
                ftruncate($fp, 0);
                fwrite($fp, "$now|1");
            }
        } else {
            // File mới hoặc rỗng -> Khởi tạo
            fseek($fp, 0);
            ftruncate($fp, 0);
            fwrite($fp, "$now|1");
        }
        
        // Giải phóng lock và đóng file
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return $shouldBlock;
    }
    
    /**
     * Ensure rate limit table exists (Deprecated)
     */
    private static function ensureRateLimitTable() {
        return; // Không dùng DB nữa
    }
    

    
    /**
     * Normalize payload — Multi-layer decoding để chống bypass
     * Decode URL (loop), xóa SQL comments, decode HTML entities
     */
    private static function normalizePayload($value) {
        $payload = $value;
        
        // 1. Multi-layer URL decode (chống double/triple encoding)
        $maxIterations = 5; // Giới hạn để tránh infinite loop
        for ($i = 0; $i < $maxIterations; $i++) {
            $decoded = urldecode($payload);
            if ($decoded === $payload) break;
            $payload = $decoded;
        }
        
        // 2. Decode HTML entities (chống &#x6A;avascript: bypass)
        $payload = html_entity_decode($payload, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 3. Xóa SQL inline comments (chống UN/**/ION SE/**/LECT bypass)
        $payload = preg_replace('/\/\*.*?\*\//s', '', $payload);
        
        // 4. Normalize whitespace (chống tab/newline obfuscation)
        $payload = preg_replace('/\s+/', ' ', $payload);
        
        // 5. Xóa null bytes
        $payload = str_replace(chr(0), '', $payload);
        
        return trim($payload);
    }
    
    /**
     * Analyze incoming request for attacks — SCORING SYSTEM
     */
    private static function analyzeRequest() {
        // Combine all input sources
        $inputs = [
            'GET' => $_GET,
            'POST' => $_POST,
            'COOKIE' => $_COOKIE,
            'URI' => [$_SERVER['REQUEST_URI'] ?? ''],
        ];
        
        // Track tổng score cao nhất và loại tấn công
        $maxScore = 0;
        $detectedType = '';
        $detectedPayload = '';
        
        foreach ($inputs as $source => $data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                
                // Multi-layer normalization (chống bypass encoding)
                $payload = self::normalizePayload($value);
                
                // Tính score cho mỗi input field
                $fieldScore = 0;
                $fieldType = '';
                
                // Check SQL Injection
                if (self::$block_sql_injection) {
                    $sqlScore = 0;
                    foreach (self::$sql_patterns as $rule) {
                        if (preg_match($rule[0], $payload)) {
                            $sqlScore += $rule[1];
                        }
                    }
                    if ($sqlScore > $fieldScore) {
                        $fieldScore = $sqlScore;
                        $fieldType = 'SQL Injection';
                    }
                }
                
                // Check XSS
                if (self::$block_xss) {
                    $xssScore = 0;
                    foreach (self::$xss_patterns as $rule) {
                        if (preg_match($rule[0], $payload)) {
                            $xssScore += $rule[1];
                        }
                    }
                    if ($xssScore > $fieldScore) {
                        $fieldScore = $xssScore;
                        $fieldType = 'XSS';
                    }
                }
                
                // Check Path Traversal (trước Command Injection)
                if (self::$block_path_traversal) {
                    $pathScore = 0;
                    foreach (self::$path_patterns as $rule) {
                        if (preg_match($rule[0], $payload)) {
                            $pathScore += $rule[1];
                        }
                    }
                    if ($pathScore > $fieldScore) {
                        $fieldScore = $pathScore;
                        $fieldType = 'Path Traversal';
                    }
                }
                
                // Check Command Injection
                if (self::$block_command_injection) {
                    $cmdScore = 0;
                    foreach (self::$cmd_patterns as $rule) {
                        if (preg_match($rule[0], $payload)) {
                            $cmdScore += $rule[1];
                        }
                    }
                    if ($cmdScore > $fieldScore) {
                        $fieldScore = $cmdScore;
                        $fieldType = 'Command Injection';
                    }
                }
                
                // Cập nhật max score nếu field này có score cao hơn
                if ($fieldScore > $maxScore) {
                    $maxScore = $fieldScore;
                    $detectedType = $fieldType;
                    $detectedPayload = $payload;
                }
            }
        }
        
        // Chỉ block khi score >= threshold
        if ($maxScore >= self::$score_threshold) {
            return [
                'type' => $detectedType, 
                'payload' => $detectedPayload,
                'score' => $maxScore
            ];
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     * Supports proxy headers for demo/testing purposes
     */
    private static function getClientIP() {
        // This enables testing distributed DDoS without real multiple IPs
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $isLocalhost = in_array($remote, ['127.0.0.1', '::1', 'localhost']);
        
        if ($isLocalhost && !empty($_GET['_demo_ip'])) {
            $demoIP = $_GET['_demo_ip'];
            if (filter_var($demoIP, FILTER_VALIDATE_IP)) {
                return $demoIP;
            }
        }
        
        // Priority: Cloudflare > X-Forwarded-For > X-Real-IP > Client-IP > REMOTE_ADDR
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',   // Most proxies
            'HTTP_X_REAL_IP',         // Nginx proxy
            'HTTP_CLIENT_IP',         // Some proxies
            'HTTP_X_CLIENT_IP',       // Alternative
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs, get the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Check if IP is blocked
     */
    public static function isIPBlocked($ip) {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("SELECT id FROM waf_blocked_ips WHERE ip_address = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    /**
     * Block an IP address
     */
    public static function blockIP($ip, $reason = 'Manual block') {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("
            INSERT INTO waf_blocked_ips (ip_address, reason) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE attack_count = attack_count + 1, reason = ?
        ");
        $stmt->bind_param("sss", $ip, $reason, $reason);
        return $stmt->execute();
    }
    
    /**
     * Unblock an IP address
     */
    public static function unblockIP($ip) {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("DELETE FROM waf_blocked_ips WHERE ip_address = ?");
        $stmt->bind_param("s", $ip);
        return $stmt->execute();
    }
    
    /**
     * Log attack to database
     */
    private static function logAttack($ip, $type, $payload) {
        if (!self::$conn) return;
        
        $stmt = self::$conn->prepare("
            INSERT INTO waf_attack_logs (ip_address, attack_type, payload, request_uri, request_method, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $payload = substr($payload, 0, 500); // Truncate
        
        $stmt->bind_param("ssssss", $ip, $type, $payload, $uri, $method, $ua);
        $stmt->execute();
    }
    
    /**
     * Show blocked page
     */
    private static function showBlockedPage($attackType, $ip) {
        http_response_code(403);
        
        $incidentId = 'INC-' . time() . '-' . rand(1000, 9999);
        
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - LIZ-WAF</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #1a0000 0%, #330000 50%, #1a0000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { background: linear-gradient(135deg, #1a0000 0%, #330000 50%, #1a0000 100%); }
            50% { background: linear-gradient(135deg, #2a0000 0%, #550000 50%, #2a0000 100%); }
        }
        .container {
            max-width: 500px;
            padding: 2rem;
        }
        .card {
            background: rgba(0, 0, 0, 0.8);
            border: 3px solid #ff3333;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 0 50px rgba(255, 0, 0, 0.5);
        }
        .icon { font-size: 4rem; margin-bottom: 1rem; animation: bounce 1s infinite; }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        h1 { color: #ff3333; font-size: 2rem; margin-bottom: 0.5rem; }
        .status { color: #ff6666; font-size: 0.9rem; margin-bottom: 1rem; }
        .details {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 51, 51, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: left;
        }
        .details p { color: #ccc; margin: 0.5rem 0; font-size: 0.9rem; }
        .label { color: #ff3333; font-weight: bold; display: inline-block; min-width: 100px; }
        .value { color: #ffaaaa; }
        .message {
            background: rgba(255, 0, 0, 0.2);
            border-left: 4px solid #ff0000;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            color: #ff9999;
            font-size: 0.9rem;
        }
        .footer { color: #666; font-size: 0.8rem; margin-top: 1.5rem; border-top: 1px solid #333; padding-top: 1rem; }
        .incident { color: #ff6666; font-family: monospace; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>ACCESS DENIED</h1>
            <div class="status">HTTP 403 FORBIDDEN</div>
            
            <div class="details">
                <p><span class="label">Attack Type:</span> <span class="value">' . htmlspecialchars($attackType) . '</span></p>
                <p><span class="label">Your IP:</span> <span class="value" style="color:#ff4444;font-weight:bold;">' . htmlspecialchars($ip) . '</span></p>
                <p><span class="label">IP Status:</span> <span class="value" style="color:#ff4444;">BLOCKED</span></p>
                <p><span class="label">Time:</span> <span class="value">' . date('Y-m-d H:i:s') . '</span></p>
            </div>
            
            <div class="message">
                <strong>IP ĐÃ BỊ CHẶN!</strong><br>
                Địa chỉ IP của bạn đã bị chặn do phát hiện tấn công <strong>' . htmlspecialchars($attackType) . '</strong>.<br>
                Tất cả request từ IP này sẽ bị từ chối.
            </div>
            
            <div class="footer">
                <p>LIZ-WAF Security System</p>
                <p class="incident">Incident ID: ' . $incidentId . '</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Get WAF statistics
     */
    public static function getStats() {
        if (!self::$conn) return [];
        
        $stats = [
            'total_attacks' => 0,
            'blocked_ips' => 0,
            'sql_injection' => 0,
            'xss' => 0,
            'command_injection' => 0,
            'path_traversal' => 0,
            'ddos' => 0,
        ];
        
        // Total attacks
        $result = self::$conn->query("SELECT COUNT(*) as cnt FROM waf_attack_logs");
        if ($result) $stats['total_attacks'] = $result->fetch_assoc()['cnt'];
        
        // Blocked IPs
        $result = self::$conn->query("SELECT COUNT(*) as cnt FROM waf_blocked_ips");
        if ($result) $stats['blocked_ips'] = $result->fetch_assoc()['cnt'];
        
        // By type
        $result = self::$conn->query("SELECT attack_type, COUNT(*) as cnt FROM waf_attack_logs GROUP BY attack_type");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = strtolower(str_replace(' ', '_', $row['attack_type']));
                if (isset($stats[$key])) {
                    $stats[$key] = $row['cnt'];
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get recent attacks
     */
    public static function getRecentAttacks($limit = 50) {
        if (!self::$conn) return [];
        
        $stmt = self::$conn->prepare("
            SELECT * FROM waf_attack_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get blocked IPs
     */
    public static function getBlockedIPs() {
        if (!self::$conn) return [];
        
        $result = self::$conn->query("SELECT * FROM waf_blocked_ips ORDER BY blocked_at DESC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Update WAF configuration
     */
    public static function updateConfig($config) {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("
            UPDATE waf_config SET 
                waf_enabled = ?,
                block_sql_injection = ?,
                block_xss = ?,
                block_command_injection = ?,
                block_path_traversal = ?,
                block_ddos = ?,
                distributed_detection = ?,
                auto_block_ip = ?,
                rate_limit = ?,
                global_rate_limit = ?,
                distributed_threshold = ?,
                score_threshold = ?
            WHERE id = 1
        ");
        
        $scoreThreshold = intval($config['score_threshold'] ?? self::$score_threshold);
        $stmt->bind_param("iiiiiiiiiiii",
            $config['waf_enabled'],
            $config['block_sql_injection'],
            $config['block_xss'],
            $config['block_command_injection'],
            $config['block_path_traversal'],
            $config['block_ddos'],
            $config['distributed_detection'],
            $config['auto_block_ip'],
            $config['rate_limit'],
            $config['global_rate_limit'],
            $config['distributed_threshold'],
            $scoreThreshold
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get current config
     */
    public static function getConfig() {
        if (!self::$conn) return [];
        
        $result = self::$conn->query("SELECT * FROM waf_config LIMIT 1");
        return $result ? $result->fetch_assoc() : [];
    }
    
    /**
     * Clear all attack logs
     */
    public static function clearLogs() {
        if (!self::$conn) return false;
        return self::$conn->query("DELETE FROM waf_attack_logs");
    }
    
    /**
     * Clear all blocked IPs
     */
    public static function clearBlockedIPs() {
        if (!self::$conn) return false;
        return self::$conn->query("DELETE FROM waf_blocked_ips");
    }

    /**
     * Whitelist Management Methods
     */
    public static function isWhitelisted($ip) {
        if (!self::$conn) return false;
        $stmt = self::$conn->prepare("SELECT id FROM waf_whitelist WHERE ip_address = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public static function getWhitelist() {
        if (!self::$conn) return [];
        return self::$conn->query("SELECT * FROM waf_whitelist ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    }

    public static function addToWhitelist($ip, $desc = '') {
        if (!self::$conn) return false;
        $stmt = self::$conn->prepare("INSERT IGNORE INTO waf_whitelist (ip_address, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $ip, $desc);
        return $stmt->execute();
    }

    public static function removeFromWhitelist($ip) {
        if (!self::$conn) return false;
        $stmt = self::$conn->prepare("DELETE FROM waf_whitelist WHERE ip_address = ?");
        $stmt->bind_param("s", $ip);
        return $stmt->execute();
    }
}
?>
