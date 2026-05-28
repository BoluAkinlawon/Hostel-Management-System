<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

$db = getDB();
$view = $_GET['view'] ?? 'dashboard';
$search = trim($_GET['search'] ?? '');
$msg = flash('admin_success') ?? flash('admin_error');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('admin_error', 'Invalid token.');
        header('Location: ' . BASE_URL . '/admin/index.php?view=' . $view);
        exit;
    }

    // Delete allocation
    if ($_POST['action'] === 'delete_allocation' && !empty($_POST['id'])) {
        $stmt = $db->prepare("DELETE FROM hostel WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        flash('admin_success', 'Allocation removed successfully.');
        header('Location: ' . BASE_URL . '/admin/index.php?view=allocations');
        exit;
    }
    
    // Delete student
    if ($_POST['action'] === 'delete_student' && !empty($_POST['id'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        flash('admin_success', 'Student record removed.');
        header('Location: ' . BASE_URL . '/admin/index.php?view=students');
        exit;
    }
}

// Stats
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_students,
        (SELECT COUNT(*) FROM hostel) as total_allocations
")->fetch();

$totalCapacity = TOTAL_BLOCKS * TOTAL_ROOMS * ROOM_CAPACITY;
$occupiedBeds = (int)$stats->total_allocations;
$availableBeds = $totalCapacity - $occupiedBeds;
$occupancyRate = $totalCapacity > 0 ? round(($occupiedBeds / $totalCapacity) * 100, 1) : 0;

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-header { margin-bottom: 1.5rem; }
    .admin-header h2 { margin-bottom: 0.25rem; color: var(--primary); }
    .admin-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid var(--border);
        flex-wrap: wrap;
    }
    .admin-tabs a {
        padding: 0.75rem 1.25rem;
        text-decoration: none;
        color: var(--text-muted);
        font-weight: 600;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
    }
    .admin-tabs a.active,
    .admin-tabs a:hover {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
        border-left: 4px solid var(--primary);
    }
    .stat-card.accent { border-left-color: var(--accent); }
    .stat-card.success { border-left-color: var(--success); }
    .stat-card.warning { border-left-color: #f9a825; }
    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    .stat-card.accent .stat-value { color: var(--accent); }
    .stat-card.success .stat-value { color: var(--success); }
    .stat-card.warning .stat-value { color: #f9a825; }
    .stat-label { font-size: 0.9rem; color: #888; font-weight: 500; }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .data-table th {
        text-align: left;
        padding: 0.875rem;
        background: var(--bg);
        color: var(--primary);
        font-weight: 600;
        border-bottom: 2px solid var(--border);
    }
    .data-table td {
        padding: 0.875rem;
        border-bottom: 1px solid var(--bg);
    }
    .data-table tr:hover { background: #f8fafc; }
    .room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .room-card {
        background: white;
        border-radius: var(--radius);
        padding: 1.25rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }
    .room-card.full { border-left: 4px solid var(--error); }
    .room-card.mid { border-left: 4px solid #f9a825; }
    .room-card.low { border-left: 4px solid var(--success); }
    .room-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    .room-id { font-weight: 700; color: var(--primary); }
    .room-badge {
        background: var(--bg);
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .room-bar {
        height: 6px;
        background: var(--bg);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    .room-fill {
        height: 100%;
        background: var(--success);
        border-radius: 3px;
        transition: width 0.3s;
    }
    .room-card.full .room-fill { background: var(--error); }
    .room-card.mid .room-fill { background: #f9a825; }
    .room-matrics {
        font-size: 0.75rem;
        color: var(--text-muted);
        word-break: break-all;
    }
    .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.85rem; width: auto; }
    .btn-danger { background: var(--error); color: white; border: none; border-radius: 5px; cursor: pointer; }
    .btn-danger:hover { background: #991b1b; }
    .mt-2 { margin-top: 1rem; }
    .text-center { text-align: center; }
</style>

<div class="admin-header">
    <h2>Admin Dashboard</h2>
    <p>Signed in as <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong> | <a href="<?= BASE_URL ?>/admin/logout.php">Logout</a></p>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="admin-tabs">
    <a href="?view=dashboard" class="<?= $view === 'dashboard' ? 'active' : '' ?>">Overview</a>
    <a href="?view=allocations" class="<?= $view === 'allocations' ? 'active' : '' ?>">Allocations</a>
    <a href="?view=students" class="<?= $view === 'students' ? 'active' : '' ?>">Students</a>
    <a href="?view=rooms" class="<?= $view === 'rooms' ? 'active' : '' ?>">Room Map</a>
</div>

<?php if ($view === 'dashboard'): ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $stats->total_students ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-value"><?= $stats->total_allocations ?></div>
        <div class="stat-label">Allocated Beds</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= $availableBeds ?></div>
        <div class="stat-label">Available Beds</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value"><?= $occupancyRate ?>%</div>
        <div class="stat-label">Occupancy Rate</div>
    </div>
</div>

<div class="card">
    <h3>Recent Allocations</h3>
    <table class="data-table">
        <thead><tr><th>Matric</th><th>Department</th><th>Level</th><th>Block</th><th>Room</th><th>Date</th></tr></thead>
        <tbody>
            <?php
            $recent = $db->query("SELECT * FROM hostel ORDER BY allocated_at DESC LIMIT 8");
            while ($row = $recent->fetch()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row->matric_number) ?></td>
                <td><?= htmlspecialchars($row->department) ?></td>
                <td><?= $row->level ?></td>
                <td><?= $row->block ?></td>
                <td><?= $row->room_no ?></td>
                <td><?= date('M j, H:i', strtotime($row->allocated_at)) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view === 'allocations'): ?>

<div class="card">
    <h3>All Allocations</h3>
    <form method="get" style="margin-bottom:1rem;">
        <input type="hidden" name="view" value="allocations">
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="padding:0.5rem; width:300px;">
        <button type="submit" class="btn-sm">Search</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Matric</th><th>Department</th><th>Level</th><th>Block</th><th>Room</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM hostel WHERE 1=1";
            $params = [];
            if ($search) {
                $sql .= " AND (matric_number LIKE ? OR department LIKE ?)";
                $like = "%$search%";
                $params = [$like, $like];
            }
            $sql .= " ORDER BY allocated_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            while ($row = $stmt->fetch()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row->matric_number) ?></td>
                <td><?= htmlspecialchars($row->department) ?></td>
                <td><?= $row->level ?></td>
                <td><?= $row->block ?></td>
                <td><?= $row->room_no ?></td>
                <td><?= date('M j, Y', strtotime($row->allocated_at)) ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Remove this allocation?');">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                        <input type="hidden" name="action" value="delete_allocation">
                        <input type="hidden" name="id" value="<?= $row->id ?>">
                        <button type="submit" class="btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view === 'students'): ?>

<div class="card">
    <h3>Registered Students</h3>
    <form method="get" style="margin-bottom:1rem;">
        <input type="hidden" name="view" value="students">
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="padding:0.5rem; width:300px;">
        <button type="submit" class="btn-sm">Search</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Name</th><th>Matric</th><th>Level</th><th>Phone</th><th>Registered</th><th>Action</th></tr></thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM users WHERE 1=1";
            $params = [];
            if ($search) {
                $sql .= " AND (firstname LIKE ? OR lastname LIKE ? OR matric_number LIKE ?)";
                $like = "%$search%";
                $params = [$like, $like, $like];
            }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            while ($row = $stmt->fetch()):
            ?>
            <tr>
                <td><?= htmlspecialchars($row->firstname . ' ' . $row->lastname) ?></td>
                <td><?= htmlspecialchars($row->matric_number) ?></td>
                <td><?= $row->level ?></td>
                <td><?= htmlspecialchars($row->student_phone) ?></td>
                <td><?= date('M j, Y', strtotime($row->created_at)) ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this student?');">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                        <input type="hidden" name="action" value="delete_student">
                        <input type="hidden" name="id" value="<?= $row->id ?>">
                        <button type="submit" class="btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php elseif ($view === 'rooms'): ?>

<div class="card">
    <h3>Room Occupancy Map</h3>
    <p>Max capacity: <strong><?= ROOM_CAPACITY ?></strong> students per room</p>

    <?php
    $roomData = $db->query("
        SELECT block, room_no, COUNT(*) as occupants, 
        GROUP_CONCAT(matric_number SEPARATOR ', ') as students
        FROM hostel 
        GROUP BY block, room_no 
        ORDER BY block ASC, room_no ASC
    ")->fetchAll();
    ?>

    <div class="room-grid">
        <?php foreach ($roomData as $room): ?>
            <?php 
            $occupancy = (int)$room->occupants;
            $pct = $occupancy / ROOM_CAPACITY;
            $statusClass = $pct >= 1 ? 'full' : ($pct >= 0.5 ? 'mid' : 'low');
            ?>
            <div class="room-card <?= $statusClass ?>">
                <div class="room-header">
                    <span class="room-id">Block <?= $room->block ?> — Room <?= $room->room_no ?></span>
                    <span class="room-badge"><?= $occupancy ?>/<?= ROOM_CAPACITY ?></span>
                </div>
                <div class="room-bar">
                    <div class="room-fill" style="width: <?= ($pct * 100) ?>%"></div>
                </div>
                <div class="room-matrics"><?= htmlspecialchars($room->students) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>