<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['matric_number'])) {
    flash('error', 'Please login to access the allocation portal.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = getDB();
$matric = $_SESSION['matric_number'];

// Fetch user details
$user = dbFetchOne("SELECT * FROM users WHERE matric_number = ?", [$matric]);
if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Fetch existing allocation
$allocation = dbFetchOne("SELECT * FROM hostel WHERE matric_number = ?", [$matric]);

// Get statistics for the dashboard
$totalAllocated = $db->query("SELECT COUNT(*) as count FROM hostel")->fetch()->count;
$totalStudents = $db->query("SELECT COUNT(*) as count FROM users")->fetch()->count;
$availableRooms = (TOTAL_BLOCKS * TOTAL_ROOMS) - $totalAllocated;

$err = flash('error');
$succ = flash('success');

$pageTitle = 'My Allocation Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        padding: 2rem;
        border-radius: var(--radius);
        margin-bottom: 2rem;
    }
    .stat-card-dashboard {
        background: white;
        padding: 1.5rem;
        border-radius: var(--radius);
        text-align: center;
        box-shadow: var(--shadow);
        border-bottom: 3px solid var(--primary);
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary);
    }
    .info-card {
        background: var(--bg);
        padding: 1rem;
        border-radius: var(--radius);
        margin: 1rem 0;
    }
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <h2>Welcome, <?= htmlspecialchars($user->firstname . ' ' . $user->lastname) ?>! 👋</h2>
    <p style="margin-top: 0.5rem; opacity: 0.9;">
        Matric Number: <strong><?= htmlspecialchars($user->matric_number) ?></strong> 
        | Level: <strong><?= $user->level ?> Level</strong>
        | Department: <strong><?= htmlspecialchars($user->department ?? 'Not set') ?></strong>
    </p>
</div>

<!-- Dashboard Stats -->
<div class="dashboard-stats">
    <div class="stat-card-dashboard">
        <div class="stat-number"><?= TOTAL_BLOCKS ?></div>
        <div>Total Blocks</div>
        <small style="color: var(--text-muted);">18 blocks available</small>
    </div>
    <div class="stat-card-dashboard">
        <div class="stat-number"><?= TOTAL_ROOMS ?></div>
        <div>Rooms per Block</div>
        <small style="color: var(--text-muted);">24 rooms each</small>
    </div>
    <div class="stat-card-dashboard">
        <div class="stat-number"><?= ROOM_CAPACITY ?></div>
        <div>Capacity per Room</div>
        <small style="color: var(--text-muted);">4 students per room</small>
    </div>
    <div class="stat-card-dashboard">
        <div class="stat-number"><?= $availableRooms ?></div>
        <div>Available Rooms</div>
        <small style="color: var(--text-muted);"><?= $totalAllocated ?> allocated</small>
    </div>
</div>

<!-- Main Content -->
<div class="card">
    <?php if ($allocation): ?>
        <!-- Already Allocated - Show Room Details -->
        <h2>🏠 Your Room Allocation</h2>
        <hr>
        
        <div class="allocation-result" style="margin-top: 1rem;">
            <h3>✅ Successfully Allocated</h3>
            <div class="block-number">Block <?= (int)$allocation->block ?></div>
            <div class="room-number">Room <?= (int)$allocation->room_no ?></div>
            <div class="allocation-badge">
                <?= htmlspecialchars($allocation->department) ?>
                &nbsp;·&nbsp;
                Allocated on <?= date('d F Y', strtotime($allocation->allocated_at)) ?>
            </div>
        </div>

        <div class="info-card">
            <h4>📋 Room Information</h4>
            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                <li>Maximum capacity: <?= ROOM_CAPACITY ?> students per room</li>
                <li>Contact hostel management for any issues</li>
                <li>Keep this allocation confirmation for reference</li>
            </ul>
        </div>

    <?php else: ?>
        <!-- Not Allocated - Show Form -->
        <h2>🎲 Request Room Allocation</h2>
        <hr>

        <?php if ($err): ?>
            <div class="alert alert-error">⚠️ <?= $err ?></div>
        <?php endif; ?>
        
        <?php if ($succ): ?>
            <div class="alert alert-success">✅ <?= $succ ?></div>
        <?php endif; ?>

        <p class="text-muted mb-2">
            Complete the form below to request your room assignment. 
            Rooms are allocated fairly based on availability.
        </p>

        <form method="post" action="<?= BASE_URL ?>/allocate.php" style="max-width: 500px;">
            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

            <div class="form-group">
                <label>📋 Matric Number</label>
                <input type="text" value="<?= htmlspecialchars($user->matric_number) ?>" disabled>
            </div>

            <div class="form-group">
                <label for="department">🏛️ Department *</label>
                <select name="department" id="department" required>
                    <option value="">-- Select Department --</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="History and International Studies">History and International Studies</option>
                    <option value="Economics">Economics</option>
                    <option value="Law">Law</option>
                </select>
            </div>

            <div class="form-group">
                <label>📚 Level</label>
                <input type="text" value="<?= (int)$user->level ?> Level" disabled>
                <input type="hidden" name="level" value="<?= (int)$user->level ?>">
                <small class="hint">
                    <?php if ($user->level == 400): ?>
                        ⭐ 400-level students get priority rooms
                    <?php else: ?>
                        Standard rooms are available for your level
                    <?php endif; ?>
                </small>
            </div>

            <button type="submit" name="submit">
                🎲 Get My Room Allocation
            </button>
        </form>

        <div class="info-card" style="margin-top: 1.5rem;">
            <h4>ℹ️ How Allocation Works</h4>
            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                <li>Rooms are filled evenly to maximize space utilization</li>
                <li><?= $user->level == 400 ? '400-level students get special rooms [1, 12, 13, 24]' : 'Standard rooms (2-23) are available for your level' ?></li>
                <li>Each room can accommodate up to <?= ROOM_CAPACITY ?> students</li>
                <li>Allocation is final and cannot be changed</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Additional Info for Allocated Students -->
<?php if ($allocation): ?>
<div class="card">
    <h3>📌 Important Information</h3>
    <hr>
    <ul style="padding-left: 1.5rem;">
        <li>Keep your allocation details for check-in</li>
        <li>Report any issues to the hostel management office</li>
        <li>Room changes are not permitted after allocation</li>
        <li>Contact: <?= ADMIN_EMAIL ?> for support</li>
    </ul>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>