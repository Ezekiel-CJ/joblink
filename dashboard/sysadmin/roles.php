<?php
$page_title = "Role-Based Access Control";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();

// Get all users with their roles
$users = $db->query("SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY role, full_name")->fetch_all(MYSQLI_ASSOC);

// Count users by role
$role_counts = [];
$count_result = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $count_result->fetch_assoc()) {
    $role_counts[$row['role']] = $row['count'];
}

// Define role permissions
$role_permissions = [
    'jobseeker' => [
        'name' => 'Job Seeker',
        'color' => '#10b981',
        'icon' => 'fa-user',
        'permissions' => [
            'Browse jobs' => true,
            'Apply to jobs' => true,
            'View own applications' => true,
            'Edit own profile' => true,
            'Save jobs' => true,
            'Upload resume' => true,
            'View recommendations' => true,
        ]
    ],
    'employer' => [
        'name' => 'Employer',
        'color' => '#3b82f6',
        'icon' => 'fa-building',
        'permissions' => [
            'Post jobs' => true,
            'Edit own jobs' => true,
            'Delete own jobs' => true,
            'View applicants' => true,
            'Shortlist candidates' => true,
            'Accept/Reject candidates' => true,
            'Edit business profile' => true,
            'View verification status' => true,
        ]
    ],
    'lgu_admin' => [
        'name' => 'LGU Officer',
        'color' => '#06b6d4',
        'icon' => 'fa-landmark',
        'permissions' => [
            'Approve/reject jobs' => true,
            'Verify employers' => true,
            'View analytics dashboard' => true,
            'View community insights' => true,
            'Manage job categories' => true,
            'Manage skills' => true,
            'View audit logs' => true,
            'Manage users (basic)' => true,
        ]
    ],
    'sys_admin' => [
        'name' => 'System Administrator',
        'color' => '#8b5cf6',
        'icon' => 'fa-shield-alt',
        'permissions' => [
            'Full user management' => true,
            'Change user roles' => true,
            'Suspend/Activate users' => true,
            'Manage all categories' => true,
            'Manage all skills' => true,
            'Full audit trail access' => true,
            'System configuration' => true,
            'Data backup/export' => true,
            'Role-based access control' => true,
        ]
    ]
];

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $valid_roles = ['jobseeker', 'employer', 'lgu_admin', 'sys_admin'];
    if (in_array($new_role, $valid_roles)) {
        $update = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $update->bind_param("si", $new_role, $user_id);
        
        if ($update->execute()) {
            logAudit($_SESSION['user_id'], 'change_user_role', 'user', $user_id, ['new_role' => $new_role]);
            redirect('roles.php', 'User role updated successfully.', 'success');
        } else {
            $error = "Failed to update role.";
        }
    }
}

// Handle user status toggle
if (isset($_GET['toggle'])) {
    $user_id = (int)$_GET['toggle'];
    $user_check = $db->query("SELECT is_active FROM users WHERE id = $user_id")->fetch_assoc();
    if ($user_check) {
        $new_status = $user_check['is_active'] ? 0 : 1;
        $db->query("UPDATE users SET is_active = $new_status WHERE id = $user_id");
        logAudit($_SESSION['user_id'], $new_status ? 'activate_user' : 'suspend_user', 'user', $user_id);
        redirect('roles.php', 'User status updated.', 'success');
    }
}

require_once '../../includes/header-dashboard.php';
?>

<style>
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 40px;
    }
    
    .role-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        padding: 24px;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .role-card:hover {
        transform: translateY(-4px);
        background: rgba(37,99,235,0.05);
        border-color: rgba(37,99,235,0.2);
    }
    
    .role-card.active {
        border-color: #2563eb;
        background: rgba(37,99,235,0.1);
    }
    
    .role-icon {
        width: 56px;
        height: 56px;
        background: rgba(37,99,235,0.1);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .role-icon i {
        font-size: 28px;
        color: var(--azure-light);
    }
    
    .role-count {
        font-size: 28px;
        font-weight: 700;
        color: var(--azure-light);
    }
    
    .permissions-list {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        font-size: 13px;
        color: var(--white-muted);
    }
    
    .permission-item i {
        width: 20px;
        color: #10b981;
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .users-table th,
    .users-table td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .users-table th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .role-badge {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .role-jobseeker { background: rgba(16,185,129,0.15); color: #10b981; }
    .role-employer { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .role-lgu_admin { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .role-sys_admin { background: rgba(139,92,246,0.15); color: #8b5cf6; }
    
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        display: none;
    }
    
    .role-modal {
        background: #0f172a;
        border-radius: 28px;
        padding: 32px;
        max-width: 400px;
        width: 90%;
    }
    
    .role-modal select {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
        margin: 16px 0;
    }
    
    .alert-success {
        background: rgba(16,185,129,0.15);
        border: 1px solid #10b981;
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        color: #10b981;
    }
    
    @media (max-width: 1024px) {
        .roles-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 640px) {
        .roles-grid {
            grid-template-columns: 1fr;
        }
    }
    .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Role-Based Access Control (RBAC)</h1>
        <p class="text-muted-light">Manage user roles and their permissions across the platform.</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    
    <!-- Role Cards -->
    <div class="roles-grid">
        <?php foreach ($role_permissions as $role => $info): 
            $count = $role_counts[$role] ?? 0;
        ?>
            <div class="role-card" onclick="showRoleDetails('<?php echo $role; ?>')">
                <div class="role-icon">
                    <i class="fas <?php echo $info['icon']; ?>"></i>
                </div>
                <h3><?php echo $info['name']; ?></h3>
                <div class="role-count"><?php echo $count; ?> users</div>
                <div class="permissions-list">
                    <div class="permission-item">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo count($info['permissions']); ?> platform permissions</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Role Details Section -->
    <div id="roleDetails" style="display: none;">
        <?php foreach ($role_permissions as $role => $info): ?>
            <div id="role-detail-<?php echo $role; ?>" class="role-detail-panel" style="display: none; background: rgba(255,255,255,0.03); border-radius: 24px; padding: 24px; margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <h3><i class="fas <?php echo $info['icon']; ?>"></i> <?php echo $info['name']; ?> - Permissions</h3>
                    <button onclick="closeRoleDetails()" class="btn-outline-light" style="padding: 6px 16px;">Close</button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;">
                    <?php foreach ($info['permissions'] as $permission => $allowed): ?>
                        <div class="permission-item">
                            <i class="fas fa-<?php echo $allowed ? 'check-circle' : 'times-circle'; ?>" style="color: <?php echo $allowed ? '#10b981' : '#ef4444'; ?>"></i>
                            <span><?php echo $permission; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <h4 style="margin: 24px 0 16px;">Users with this role</h4>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(37,99,235,0.1);">
                                <th style="padding: 12px; text-align: left;">Name</th>
                                <th style="padding: 12px; text-align: left;">Email</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $role_users = array_filter($users, function($u) use ($role) {
                                return $u['role'] === $role;
                            });
                            ?>
                            <?php foreach ($role_users as $ru): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 12px;"><strong><?php echo htmlspecialchars($ru['full_name']); ?></strong></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($ru['email']); ?></td>
                                    <td style="padding: 12px;"><?php echo $ru['is_active'] ? '<span style="color: #10b981;">Active</span>' : '<span style="color: #ef4444;">Suspended</span>'; ?></td>
                                    <td style="padding: 12px;">
                                        <button onclick="openRoleModal(<?php echo $ru['id']; ?>, '<?php echo $ru['role']; ?>')" class="btn-outline-light" style="padding: 4px 12px;">Change Role</button>
                                        <a href="?toggle=<?php echo $ru['id']; ?>" class="btn-outline-light" style="padding: 4px 12px;" onclick="return confirm('<?php echo $ru['is_active'] ? 'Suspend' : 'Activate'; ?> this user?')"><?php echo $ru['is_active'] ? 'Suspend' : 'Activate'; ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($role_users)): ?>
                                <tr><td colspan="4" style="padding: 30px; text-align: center;">No users with this role</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Users Table -->
    <div style="margin-top: 40px;">
        <h3 style="margin-bottom: 20px;">All Users</h3>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; overflow-x: auto;">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo getRoleName($user['role']); ?></span></td>
                            <td><?php echo $user['is_active'] ? '<span style="color: #10b981;">Active</span>' : '<span style="color: #ef4444;">Suspended</span>'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button onclick="openRoleModal(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')" class="btn-outline-light" style="padding: 4px 12px;">
                                    <i class="fas fa-edit"></i> Change Role
                                </button>
                                <a href="?toggle=<?php echo $user['id']; ?>" class="btn-outline-light" style="padding: 4px 12px; <?php echo $user['is_active'] ? 'color: #ef4444;' : 'color: #10b981;'; ?>" onclick="return confirm('<?php echo $user['is_active'] ? 'Suspend' : 'Activate'; ?> this user?')">
                                    <i class="fas fa-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i> <?php echo $user['is_active'] ? 'Suspend' : 'Activate'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div id="roleModal" class="modal-overlay">
    <div class="role-modal">
        <h3>Change User Role</h3>
        <form method="POST" action="">
            <input type="hidden" name="user_id" id="modalUserId">
            <select name="new_role" id="modalNewRole" required>
                <option value="jobseeker">Job Seeker</option>
                <option value="employer">Employer</option>
                <option value="lgu_admin">LGU Admin</option>
                <option value="sys_admin">System Admin</option>
            </select>
            <div style="display: flex; gap: 12px;">
                <button type="submit" name="change_role" class="btn-primary-gradient" style="flex: 1;">Apply Changes</button>
                <button type="button" class="btn-outline-light" onclick="closeRoleModal()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showRoleDetails(role) {
        // Hide all role detail divs
        document.querySelectorAll('.role-detail-panel').forEach(div => {
            div.style.display = 'none';
        });
        // Show selected role detail
        const detailDiv = document.getElementById('role-detail-' + role);
        if (detailDiv) {
            detailDiv.style.display = 'block';
        }
        document.getElementById('roleDetails').style.display = 'block';
        document.getElementById('roleDetails').scrollIntoView({ behavior: 'smooth' });
    }
    
    function closeRoleDetails() {
        document.getElementById('roleDetails').style.display = 'none';
    }
    
    function openRoleModal(userId, currentRole) {
        document.getElementById('modalUserId').value = userId;
        const select = document.getElementById('modalNewRole');
        for(let i = 0; i < select.options.length; i++) {
            if(select.options[i].value === currentRole) {
                select.options[i].selected = true;
                break;
            }
        }
        document.getElementById('roleModal').style.display = 'flex';
    }
    
    function closeRoleModal() {
        document.getElementById('roleModal').style.display = 'none';
    }
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>