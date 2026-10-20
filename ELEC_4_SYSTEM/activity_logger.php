<?php
// ============================================
// ACTIVITY LOGGER
// ============================================
// Usage:
//   log_activity($pdo, $user_id, 'LOGIN', 'User logged in', 'SUCCESS');
//   log_activity($pdo, null, 'LOGIN_FAILED', 'Invalid password', 'FAILED');

if (!function_exists('log_activity')) {
    function log_activity($pdo, $user_id, $activity_type, $description = '', $result = 'SUCCESS', $division = null) {
        if (!$pdo) return false;

        // Auto-detect IP
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;
        if ($ip && strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ip = $ip ? substr($ip, 0, 50) : null;

        // User agent
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ua = $ua ? substr($ua, 0, 255) : null;

        // Auto-fetch division kung walang ibinigay
        if (!$division && $user_id) {
            try {
                $s = $pdo->prepare("SELECT college FROM users WHERE id = :id");
                $s->execute([':id' => $user_id]);
                $row = $s->fetch();
                if ($row && !empty($row['college'])) {
                    $division = $row['college'];
                }
            } catch (PDOException $e) {
                // silent fail
            }
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_activities 
                (user_id, activity_type, description, division, result, ip_address, user_agent, created_at)
                VALUES (:uid, :atype, :desc, :div, :res, :ip, :ua, NOW())
            ");
            $stmt->execute([
                ':uid'   => $user_id,
                ':atype' => $activity_type,
                ':desc'  => $description,
                ':div'   => $division,
                ':res'   => $result,
                ':ip'    => $ip,
                ':ua'    => $ua,
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('Activity log failed: ' . $e->getMessage());
            return false;
        }
    }
}