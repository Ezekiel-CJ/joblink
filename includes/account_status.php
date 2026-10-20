<?php
// =====================================================
// ACCOUNT STATUS MANAGEMENT
// Handles declined, suspended, and deleted accounts
// =====================================================

function getUserAccountStatus($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email, full_name, role, is_active, 
                          deleted_at, suspension_reason, suspended_until, 
                          declined_reason 
                          FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        return ['status' => 'not_found', 'can_login' => false];
    }
    
    // Check deleted (soft delete)
    if (!empty($user['deleted_at'])) {
        return [
            'status' => 'deleted',
            'can_login' => false,
            'message' => 'Your account has been permanently deleted. If you believe this is an error, please contact support.',
            'deleted_at' => $user['deleted_at']
        ];
    }
    
    // Check declined
    if (!empty($user['declined_reason']) && $user['is_active'] == 0) {
        return [
            'status' => 'declined',
            'can_login' => false,
            'message' => 'Your account registration has been declined. Reason: ' . $user['declined_reason'],
            'reason' => $user['declined_reason']
        ];
    }
    
    // Check suspended
    if ($user['is_active'] == 0 && !empty($user['suspended_until'])) {
        $suspended_until = strtotime($user['suspended_until']);
        $now = time();
        
        if ($now < $suspended_until) {
            $days_left = ceil(($suspended_until - $now) / 86400);
            return [
                'status' => 'suspended',
                'can_login' => false,
                'message' => 'Your account is suspended until ' . date('M d, Y', $suspended_until) . '. Reason: ' . ($user['suspension_reason'] ?? 'Violation of terms.'),
                'reason' => $user['suspension_reason'],
                'suspended_until' => $user['suspended_until'],
                'days_left' => $days_left
            ];
        } else {
            // Auto-reactivate if suspension period has passed
            $db->query("UPDATE users SET is_active = 1, suspension_reason = NULL, suspended_until = NULL WHERE id = $user_id");
            return [
                'status' => 'active',
                'can_login' => true,
                'message' => null
            ];
        }
    }
    
    // Account is active and verified
    return [
        'status' => 'active',
        'can_login' => true,
        'message' => null
    ];
}

function suspendUser($admin_id, $user_id, $reason, $days = 30) {
    $db = getDB();
    $suspended_until = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    $stmt = $db->prepare("UPDATE users SET is_active = 0, suspension_reason = ?, suspended_until = ? WHERE id = ?");
    $stmt->bind_param("ssi", $reason, $suspended_until, $user_id);
    
    if ($stmt->execute()) {
        logAudit($admin_id, 'suspend_user', 'user', $user_id, [
            'reason' => $reason,
            'duration_days' => $days,
            'suspended_until' => $suspended_until
        ]);
        
        // Send email notification
        $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user) {
            sendAccountStatusEmail($user['email'], $user['full_name'], 'suspended', $reason, $suspended_until);
        }
        
        return ['success' => true, 'message' => "User suspended for $days days."];
    }
    return ['success' => false, 'message' => "Failed to suspend user."];
}

function unsuspendUser($admin_id, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_active = 1, suspension_reason = NULL, suspended_until = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        logAudit($admin_id, 'unsuspend_user', 'user', $user_id);
        
        $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user) {
            sendAccountStatusEmail($user['email'], $user['full_name'], 'unsuspended', null, null);
        }
        
        return ['success' => true, 'message' => "User unsuspended successfully."];
    }
    return ['success' => false, 'message' => "Failed to unsuspend user."];
}

function declineUser($admin_id, $user_id, $reason) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_active = 0, declined_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $reason, $user_id);
    
    if ($stmt->execute()) {
        logAudit($admin_id, 'decline_user', 'user', $user_id, ['reason' => $reason]);
        
        $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user) {
            sendAccountStatusEmail($user['email'], $user['full_name'], 'declined', $reason, null);
        }
        
        return ['success' => true, 'message' => "User registration declined."];
    }
    return ['success' => false, 'message' => "Failed to decline user."];
}

function deleteUser($admin_id, $user_id, $permanent = false) {
    $db = getDB();
    
    if ($permanent) {
        // Hard delete - remove all related data
        $db->query("DELETE FROM applications WHERE jobseeker_id = $user_id OR reviewed_by = $user_id");
        $db->query("DELETE FROM jobs WHERE employer_id = $user_id");
        $db->query("DELETE FROM employer_profiles WHERE user_id = $user_id");
        $db->query("DELETE FROM jobseeker_profiles WHERE user_id = $user_id");
        $db->query("DELETE FROM users WHERE id = $user_id");
        
        logAudit($admin_id, 'permanent_delete_user', 'user', $user_id);
        return ['success' => true, 'message' => "User permanently deleted."];
    } else {
        // Soft delete
        $stmt = $db->prepare("UPDATE users SET is_active = 0, deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            logAudit($admin_id, 'soft_delete_user', 'user', $user_id);
            
            $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
            if ($user) {
                sendAccountStatusEmail($user['email'], $user['full_name'], 'deleted', null, null);
            }
            
            return ['success' => true, 'message' => "User soft-deleted. Will be permanently removed after 30 days."];
        }
        return ['success' => false, 'message' => "Failed to delete user."];
    }
}

function restoreUser($admin_id, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        logAudit($admin_id, 'restore_user', 'user', $user_id);
        
        $user = $db->query("SELECT email, full_name FROM users WHERE id = $user_id")->fetch_assoc();
        if ($user) {
            sendAccountStatusEmail($user['email'], $user['full_name'], 'restored', null, null);
        }
        
        return ['success' => true, 'message' => "User restored successfully."];
    }
    return ['success' => false, 'message' => "Failed to restore user."];
}

function sendAccountStatusEmail($email, $name, $status, $reason = null, $suspended_until = null) {
    $subject = "Account Status Update - " . SITE_NAME;
    
    $messages = [
        'suspended' => "Your account has been suspended until " . date('M d, Y', strtotime($suspended_until)) . ".\n\nReason: $reason",
        'unsuspended' => "Your account has been reactivated. You can now log in again.",
        'declined' => "Your registration has been declined.\n\nReason: $reason\n\nPlease contact support for more information.",
        'deleted' => "Your account has been deleted from our platform.",
        'restored' => "Your account has been restored. You can now log in again."
    ];
    
    $body = "Dear $name,\n\n" . ($messages[$status] ?? "Your account status has been updated.") . "\n\nBest regards,\n" . SITE_NAME . " Team";
    
    queueEmail($email, $subject, $body);
}