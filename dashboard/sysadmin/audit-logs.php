<?php
$page_title = "Audit Logs";
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== 'sys_admin') {
    redirect(SITE_URL . 'pages/lgu-login.php', 'Unauthorized access.', 'error');
}

$db = getDB();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT al.*, u.full_name, u.email, u.role 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($action_filter)) {
    $query .= " AND al.action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= "s";
}

if (!empty($user_filter)) {
    $query .= " AND u.full_name LIKE ?";
    $params[] = "%$user_filter%";
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Get total count
$count_query = str_replace("SELECT al.*, u.full_name, u.email, u.role", "SELECT COUNT(*) as total", $query);
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total / $limit);

$query .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$audit_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique actions
$actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);

require_once '../../includes/header-dashboard.php';
?>

<style>
    .filter-bar {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    
    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 6px;
    }
    
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 10px 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: white;
        font-family: inherit;
    }
    
    .filter-actions {
        display: flex;
        gap: 12px;
    }
    
    .reset-btn {
        background: rgba(239,68,68,0.15);
        border: 1px solid #ef4444;
        color: #ef4444;
        padding: 10px 24px;
        border-radius: 40px;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .reset-btn:hover {
        background: rgba(239,68,68,0.25);
        transform: translateY(-2px);
    }
    
    .audit-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .audit-table th,
    .audit-table td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .audit-table th {
        background: rgba(37,99,235,0.1);
        font-weight: 600;
    }
    
    .action-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .action-create { background: rgba(16,185,129,0.15); color: #10b981; }
    .action-update { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .action-delete { background: rgba(239,68,68,0.15); color: #ef4444; }
    .action-login { background: rgba(6,182,212,0.15); color: #06b6d4; }
    .action-approve { background: rgba(16,185,129,0.15); color: #10b981; }
    .action-reject { background: rgba(239,68,68,0.15); color: #ef4444; }
    .action-default { background: rgba(139,92,246,0.15); color: #8b5cf6; }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    
    .pagination a {
        padding: 8px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .pagination a:hover,
    .pagination a.active {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
    }
    
    .stats-badge {
        background: rgba(37,99,235,0.1);
        border-radius: 12px;
        padding: 8px 16px;
        display: inline-block;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .filter-bar { flex-direction: column; }
        .filter-group { width: 100%; }
        .audit-table { font-size: 12px; }
        .audit-table th, .audit-table td { padding: 10px; }
    }
    .dashboard-main {
    margin-left: -60px !important;
    padding-left: 200px !important;
}
</style>

<div class="dashboard-main">
    <div style="margin-bottom: 24px;">
        <h1>Audit Logs</h1>
        <p class="text-muted-light">Complete history of all system activities.</p>
    </div>
    
    <div class="stats-badge">
        <i class="fas fa-chart-line"></i> Total Records: <strong><?php echo number_format($total); ?></strong>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Action</label>
            <select id="filterAction">
                <option value="">All Actions</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $action_filter === $act['action'] ? 'selected' : ''; ?>>
                        <? echo str_replace('_', ' ', ucfirst($act['action'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label><i class="fas fa-user"></i> User</label>
            <input type="text" id="filterUser" placeholder="Search by user name" value="<?php echo htmlspecialchars($user_filter); ?>">
        </div>
        
        <div class="filter-group">
            <label><i class="fas fa-calendar"></i> Date From</label>
            <input type="date" id="filterDateFrom" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        
        <div class="filter-group">
            <label><i class="fas fa-calendar"></i> Date To</label>
            <input type="date" id="filterDateTo" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        
        <div class="filter-actions">
            <button class="btn-primary-gradient" id="applyFilters" style="padding: 10px 24px;">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="?reset=1" class="reset-btn">
                <i class="fas fa-trash-alt"></i> Reset Filters
            </a>
        </div>
    </div>
    
    <!-- Audit Logs Table -->
    <?php if (!empty($audit_logs)): ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; overflow-x: auto;">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_logs as $log): 
                        $action_class = 'action-default';
                        if (strpos($log['action'], 'create') !== false || strpos($log['action'], 'register') !== false) $action_class = 'action-create';
                        elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) $action_class = 'action-update';
                        elseif (strpos($log['action'], 'delete') !== false) $action_class = 'action-delete';
                        elseif (strpos($log['action'], 'login') !== false || strpos($log['action'], 'logout') !== false) $action_class = 'action-login';
                        elseif (strpos($log['action'], 'approve') !== false) $action_class = 'action-approve';
                        elseif (strpos($log['action'], 'reject') !== false) $action_class = 'action-reject';
                    ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong>
                                <?php if (!empty($log['email'])): ?>
                                    <div class="text-dim" style="font-size: 11px;"><?php echo htmlspecialchars($log['email']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getRoleName($log['role']); ?></td>
                            <td><span class="action-badge <?php echo $action_class; ?>"><?php echo str_replace('_', ' ', ucfirst($log['action'])); ?></span></td>
                            <td>
                                <?php echo htmlspecialchars($log['entity'] ?? '—'); ?>
                                <?php if ($log['entity_id']): ?>
                                    <div class="text-dim" style="font-size: 11px;">ID: <?php echo $log['entity_id']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 250px;">
                                <?php 
                                if (!empty($log['details'])) {
                                    $details = json_decode($log['details'], true);
                                    if ($details) {
                                        foreach ($details as $key => $value) {
                                            echo '<span class="text-dim" style="font-size: 11px; display: block;">';
                                            echo htmlspecialchars($key) . ': ' . htmlspecialchars(is_array($value) ? json_encode($value) : $value);
                                            echo '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-dim">' . htmlspecialchars(substr($log['details'], 0, 100)) . '</span>';
                                    }
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($action_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($action_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($action_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: rgba(255,255,255,0.03); border-radius: 20px;">
            <i class="fas fa-history" style="font-size: 48px; color: var(--white-dim); margin-bottom: 16px;"></i>
            <h3>No audit logs found</h3>
            <a href="?reset=1" class="reset-btn" style="display: inline-block; margin-top: 16px;">Reset Filters</a>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('applyFilters')?.addEventListener('click', function() {
        const action = document.getElementById('filterAction').value;
        const user = document.getElementById('filterUser').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        
        let url = '?';
        if (action) url += 'action=' + encodeURIComponent(action) + '&';
        if (user) url += 'user=' + encodeURIComponent(user) + '&';
        if (dateFrom) url += 'date_from=' + dateFrom + '&';
        if (dateTo) url += 'date_to=' + dateTo + '&';
        
        window.location.href = url;
    });
</script>

<?php require_once '../../includes/footer-dashboard.php'; ?>