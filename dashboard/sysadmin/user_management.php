<?php
$page_title = "User Management";
require_once '../../includes/config.php';
require_once '../../includes/account_status.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'] ?? '';
    $reason = sanitize($_POST['reason'] ?? '');
    $duration = (int)($_POST['duration'] ?? 30);
    
    switch ($action) {
        case 'suspend':
            if (empty($reason)) {
                $error = "Please provide a reason for suspension.";
            } else {
                $result = suspendUser($_SESSION['user_id'], $user_id, $reason, $duration);
                if ($result['success']) $message = $result['message'];
                else $error = $result['message'];
            }
            break;
            
        case 'unsuspend':
            $result = unsuspendUser($_SESSION['user_id'], $user_id);
            if ($result['success']) $message = $result['message'];
            else $error = $result['message'];
            break;
            
        case 'decline':
            if (empty($reason)) {
                $error = "Please provide a reason for declining.";
            } else {
                $result = declineUser($_SESSION['user_id'], $user_id, $reason);
                if ($result['success']) $message = $result['message'];
                else $error = $result['message'];
            }
            break;
            
        case 'soft_delete':
            $result = deleteUser($_SESSION['user_id'], $user_id, false);
            if ($result['success']) $message = $result['message'];
            else $error = $result['message'];
            break;
            
        case 'permanent_delete':
            if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
                $result = deleteUser($_SESSION['user_id'], $user_id, true);
                if ($result['success']) $message = $result['message'];
                else $error = $result['message'];
            } else {
                $error = "Please confirm permanent deletion.";
            }
            break;
            
        case 'restore':
            $result = restoreUser($_SESSION['user_id'], $user_id);
            if ($result['success']) $message = $result['message'];
            else $error = $result['message'];
            break;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Build query
$query = "SELECT u.*, 
          ep.business_name,
          jp.skills,
          CASE 
              WHEN u.deleted_at IS NOT NULL THEN 'deleted'
              WHEN u.declined_reason IS NOT NULL AND u.is_active = 0 THEN 'declined'
              WHEN u.is_active = 0 AND u.suspended_until > NOW() THEN 'suspended'
              WHEN u.is_active = 1 THEN 'active'
              ELSE 'inactive'
          END as account_status,
          CASE 
              WHEN u.suspended_until > NOW() THEN u.suspended_until
              ELSE NULL
          END as suspension_end
          FROM users u
          LEFT JOIN employer_profiles ep ON u.id = ep.user_id
          LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
          WHERE 1=1";

if ($status_filter != 'all') {
    if ($status_filter == 'active') {
        $query .= " AND u.is_active = 1 AND u.deleted_at IS NULL AND (u.declined_reason IS NULL OR u.is_active = 1)";
    } elseif ($status_filter == 'suspended') {
        $query .= " AND u.is_active = 0 AND u.suspended_until > NOW()";
    } elseif ($status_filter == 'declined') {
        $query .= " AND u.declined_reason IS NOT NULL AND u.is_active = 0";
    } elseif ($status_filter == 'deleted') {
        $query .= " AND u.deleted_at IS NOT NULL";
    }
}

if (!empty($role_filter)) {
    $query .= " AND u.role = '$role_filter'";
}

$query .= " ORDER BY u.created_at DESC";
$users = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// Get counts for each status
$counts = [
    'total' => $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND deleted_at IS NULL AND (declined_reason IS NULL OR is_active = 1)")->fetch_assoc()['count'],
    'suspended' => $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 0 AND suspended_until > NOW()")->fetch_assoc()['count'],
    'declined' => $db->query("SELECT COUNT(*) as count FROM users WHERE declined_reason IS NOT NULL AND is_active = 0")->fetch_assoc()['count'],
    'deleted' => $db->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NOT NULL")->fetch_assoc()['count']
];

require_once '../../includes/header-dashboard.php';
?>

<style>
    .stats-grid-user {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 30px;
    }
    
    .stat-card-user {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .stat-card-user:hover {
        transform: translateY(-2px);
        background: rgba(37,99,235,0.05);
    }
    
    .stat-card-user.active {
        border-color: #3b82f6;
        background: rgba(59,130,246,0.1);
    }
    
    .stat-number-user {
        font-size: 28px;
        font-weight: 700;
    }
    
    .stat-label-user {
        font-size: 12px;
        color: var(--white-muted);
        margin-top: 4px;
    }
    
    .status-badge-user {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .status-active { background: rgba(16,185,129,0.15); color: #10b981; }
    .status-suspended { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .status-declined { background: rgba(239,68,68,0.15); color: #ef4444; }
    .status-deleted { background: rgba(100,116,139,0.15); color: #94a3b8; }
    
    .filter-bar-user {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        padding: 16px;
        background: rgba(255,255,255,0.03);
        border-radius: 20px;
    }
    
    .filter-select {
        padding: 8px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
    }
    
    .users-table-manage {
        width: 100%;
        border-collapse: collapse;
    }
    
    .users-table-manage th,
    .users-table-manage td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }
    
    .users-table-manage th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .action-buttons-user {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-action-user {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
    }
    
    .btn-suspend { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .btn-unsuspend { background: rgba(16,185,129,0.15); color: #10b981; }
    .btn-decline { background: rgba(239,68,68,0.15); color: #ef4444; }
    .btn-delete { background: rgba(100,116,139,0.15); color: #94a3b8; }
    .btn-restore { background: rgba(59,130,246,0.15); color: #3b82f6; }
    
    .modal-overlay-user {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        display: none;
    }
    
    .modal-content-user {
        background: #0f172a;
        border-radius: 24px;
        padding: 32px;
        max-width: 450px;
        width: 90%;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .modal-content-user textarea,
    .modal-content-user select,
    .modal-content-user input {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
        margin: 12px 0;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    .role-jobseeker { background: rgba(16,185,129,0.15); color: #10b981; }
    .role-employer { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .role-lgu_admin { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .role-sys_admin { background: rgba(139,92,246,0.15); color: #8b5cf6; }
    
    .user-avatar-small {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }
    
    @media (max-width: 900px) {
        .stats-grid-user {
            grid-template-columns: repeat(3, 1fr);
        }
        .users-table-manage {
            font-size: 12px;
        }
        .users-table-manage th,
        .users-table-manage td {
            padding: 8px;
        }
    }
    
    @media (max-width: 640px) {
        .stats-grid-user {
            grid-template-columns: repeat(2, 1fr);
        }
        .action-buttons-user {
            flex-direction: column;
        }
    }
    .dashboard-main {
        margin-left: -60px !important;
        padding-left: 200px !important;
    }
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>User Management</h1>
        <p class="text-muted-light">Manage user accounts including suspension, decline, and deletion.</p>
    </div>
    
    <?php if ($message): ?>
        <div style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #10b981;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 14px; border-radius: 16px; margin-bottom: 20px; color: #ef4444;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid-user">
        <div class="stat-card-user <?php echo $status_filter == 'all' ? 'active' : ''; ?>" onclick="location.href='?status=all&role=<?php echo $role_filter; ?>'">
            <div class="stat-number-user"><?php echo $counts['total']; ?></div>
            <div class="stat-label-user">Total Users</div>
        </div>
        <div class="stat-card-user <?php echo $status_filter == 'active' ? 'active' : ''; ?>" onclick="location.href='?status=active&role=<?php echo $role_filter; ?>'">
            <div class="stat-number-user" style="color: #10b981;"><?php echo $counts['active']; ?></div>
            <div class="stat-label-user">Active</div>
        </div>
        <div class="stat-card-user <?php echo $status_filter == 'suspended' ? 'active' : ''; ?>" onclick="location.href='?status=suspended&role=<?php echo $role_filter; ?>'">
            <div class="stat-number-user" style="color: #f59e0b;"><?php echo $counts['suspended']; ?></div>
            <div class="stat-label-user">Suspended</div>
        </div>
        <div class="stat-card-user <?php echo $status_filter == 'declined' ? 'active' : ''; ?>" onclick="location.href='?status=declined&role=<?php echo $role_filter; ?>'">
            <div class="stat-number-user" style="color: #ef4444;"><?php echo $counts['declined']; ?></div>
            <div class="stat-label-user">Declined</div>
        </div>
        <div class="stat-card-user <?php echo $status_filter == 'deleted' ? 'active' : ''; ?>" onclick="location.href='?status=deleted&role=<?php echo $role_filter; ?>'">
            <div class="stat-number-user" style="color: #94a3b8;"><?php echo $counts['deleted']; ?></div>
            <div class="stat-label-user">Deleted</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar-user">
        <select id="roleFilter" class="filter-select" onchange="applyFilters()">
            <option value="">All Roles</option>
            <option value="jobseeker" <?php echo $role_filter == 'jobseeker' ? 'selected' : ''; ?>>Job Seeker</option>
            <option value="employer" <?php echo $role_filter == 'employer' ? 'selected' : ''; ?>>Employer</option>
            <option value="lgu_admin" <?php echo $role_filter == 'lgu_admin' ? 'selected' : ''; ?>>LGU Admin</option>
            <option value="sys_admin" <?php echo $role_filter == 'sys_admin' ? 'selected' : ''; ?>>System Admin</option>
        </select>
        <button class="btn-outline-light" onclick="resetFilters()">Clear Filters</button>
    </div>
    
    <!-- Users Table -->
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; overflow-x: auto;">
        <table class="users-table-manage">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Details</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): 
                        $status_class = '';
                        $status_text = '';
                        
                        if ($user['account_status'] == 'active') {
                            $status_class = 'status-active';
                            $status_text = 'Active';
                        } elseif ($user['account_status'] == 'suspended') {
                            $status_class = 'status-suspended';
                            $status_text = 'Suspended';
                        } elseif ($user['account_status'] == 'declined') {
                            $status_class = 'status-declined';
                            $status_text = 'Declined';
                        } else {
                            $status_class = 'status-deleted';
                            $status_text = 'Deleted';
                        }
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <div class="text-dim" style="font-size: 11px;"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo getRoleName($user['role']); ?></span></td>
                            <td><span class="status-badge-user <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <?php if ($user['role'] == 'employer' && !empty($user['business_name'])): ?>
                                    <div class="text-dim" style="font-size: 11px;"><?php echo htmlspecialchars($user['business_name']); ?></div>
                                <?php elseif ($user['role'] == 'jobseeker' && !empty($user['skills'])): ?>
                                    <div class="text-dim" style="font-size: 11px;">Skills: <?php echo htmlspecialchars(substr($user['skills'], 0, 50)); ?></div>
                                <?php endif; ?>
                                <?php if ($user['account_status'] == 'suspended' && $user['suspension_end']): ?>
                                    <div class="text-dim" style="font-size: 10px;">Until: <?php echo date('M d, Y', strtotime($user['suspension_end'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons-user">
                                <?php if ($user['account_status'] == 'active'): ?>
                                    <button class="btn-action-user btn-suspend" onclick="openActionModal(<?php echo $user['id']; ?>, 'suspend', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-pause"></i> Suspend
                                    </button>
                                    <button class="btn-action-user btn-decline" onclick="openActionModal(<?php echo $user['id']; ?>, 'decline', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                    <button class="btn-action-user btn-delete" onclick="openActionModal(<?php echo $user['id']; ?>, 'soft_delete', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    
                                <?php elseif ($user['account_status'] == 'suspended'): ?>
                                    <button class="btn-action-user btn-unsuspend" onclick="openActionModal(<?php echo $user['id']; ?>, 'unsuspend', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-play"></i> Unsuspend
                                    </button>
                                    <button class="btn-action-user btn-decline" onclick="openActionModal(<?php echo $user['id']; ?>, 'decline', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                    <button class="btn-action-user btn-delete" onclick="openActionModal(<?php echo $user['id']; ?>, 'soft_delete', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    
                                <?php elseif ($user['account_status'] == 'declined'): ?>
                                    <button class="btn-action-user btn-unsuspend" onclick="openActionModal(<?php echo $user['id']; ?>, 'restore', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <button class="btn-action-user btn-delete" onclick="openActionModal(<?php echo $user['id']; ?>, 'permanent_delete', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash-alt"></i> Permanent Delete
                                    </button>
                                    
                                <?php elseif ($user['account_status'] == 'deleted'): ?>
                                    <button class="btn-action-user btn-restore" onclick="openActionModal(<?php echo $user['id']; ?>, 'restore', '<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <button class="btn-action-user btn-delete" onclick="openActionModal(<?php echo $user['id']; ?>, 'permanent_delete', '<?php echo addslashes($user['full_name']); ?>', true)">
                                        <i class="fas fa-trash-alt"></i> Permanent Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px;">
                            <i class="fas fa-users" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px; display: block;"></i>
                            <h3>No users found</h3>
                            <p class="text-muted-light">Try changing your filter criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Action Modal -->
<div id="actionModal" class="modal-overlay-user">
    <div class="modal-content-user">
        <h3 id="modalTitle">Action</h3>
        <p id="modalMessage" class="text-muted-light"></p>
        
        <form method="POST" action="" id="actionForm">
            <input type="hidden" name="user_id" id="modalUserId">
            <input type="hidden" name="action" id="modalAction">
            
            <div id="reasonField" style="display: none;">
                <label>Reason / Remarks *</label>
                <textarea name="reason" rows="3" placeholder="Please provide a detailed reason for this action..."></textarea>
            </div>
            
            <div id="durationField" style="display: none;">
                <label>Suspension Duration</label>
                <select name="duration">
                    <option value="7">7 days</option>
                    <option value="14">14 days</option>
                    <option value="30" selected>30 days</option>
                    <option value="60">60 days</option>
                    <option value="90">90 days</option>
                </select>
            </div>
            
            <div id="confirmField" style="display: none;">
                <label>
                    <input type="checkbox" name="confirm" value="yes">
                    I confirm this action is correct and understand it may be irreversible.
                </label>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn-primary-gradient" style="flex: 1;">Confirm Action</button>
                <button type="button" class="btn-outline-light" onclick="closeModal()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function applyFilters() {
        const role = document.getElementById('roleFilter').value;
        const status = '<?php echo $status_filter; ?>';
        let url = '?status=' + status;
        if (role) url += '&role=' + role;
        window.location.href = url;
    }
    
    function resetFilters() {
        window.location.href = 'user_management.php';
    }
    
    function openActionModal(userId, action, userName, requireConfirm = false) {
        const modal = document.getElementById('actionModal');
        const title = document.getElementById('modalTitle');
        const message = document.getElementById('modalMessage');
        const reasonField = document.getElementById('reasonField');
        const durationField = document.getElementById('durationField');
        const confirmField = document.getElementById('confirmField');
        
        // Reset fields
        reasonField.style.display = 'none';
        durationField.style.display = 'none';
        confirmField.style.display = 'none';
        
        // Set modal based on action
        switch(action) {
            case 'suspend':
                title.innerHTML = 'Suspend User';
                message.innerHTML = `You are about to suspend <strong>${userName}</strong>. The user will not be able to login during the suspension period.`;
                reasonField.style.display = 'block';
                durationField.style.display = 'block';
                break;
            case 'unsuspend':
                title.innerHTML = 'Unsuspend User';
                message.innerHTML = `You are about to unsuspend <strong>${userName}</strong>. The user will be able to login again.`;
                break;
            case 'decline':
                title.innerHTML = 'Decline User Registration';
                message.innerHTML = `You are about to decline <strong>${userName}</strong>'s registration. The user will not be able to login.`;
                reasonField.style.display = 'block';
                break;
            case 'soft_delete':
                title.innerHTML = 'Delete User (Soft Delete)';
                message.innerHTML = `You are about to delete <strong>${userName}</strong>. The account will be recoverable for 30 days.`;
                confirmField.style.display = 'block';
                break;
            case 'permanent_delete':
                title.innerHTML = 'Permanently Delete User';
                message.innerHTML = `<strong style="color: #ef4444;">⚠️ WARNING: This action is irreversible!</strong><br><br>You are about to permanently delete <strong>${userName}</strong>. All data associated with this user will be removed.`;
                confirmField.style.display = 'block';
                break;
            case 'restore':
                title.innerHTML = 'Restore User';
                message.innerHTML = `You are about to restore <strong>${userName}</strong>. The user will be able to login again.`;
                break;
        }
        
        document.getElementById('modalUserId').value = userId;
        document.getElementById('modalAction').value = action;
        
        modal.style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('actionModal').style.display = 'none';
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('actionModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>