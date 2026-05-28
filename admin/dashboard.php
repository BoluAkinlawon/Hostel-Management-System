<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

// ── Stats ──────────────────────────────────────────────────────────────────
$totalStudents  = (int)dbFetchOne("SELECT COUNT(*) AS n FROM users")->n;
$totalAllocated = (int)dbFetchOne("SELECT COUNT(*) AS n FROM hostel")->n;
$totalCapacity  = TOTAL_BLOCKS * TOTAL_ROOMS * ROOM_CAPACITY;
$occupancyPct   = $totalCapacity > 0 ? round(($totalAllocated / $totalCapacity) * 100, 1) : 0;
$unallocated    = $totalStudents - $totalAllocated;

// Block occupancy breakdown
$blockStats = dbFetchAll("
    SELECT block, COUNT(*) AS count, COUNT(DISTINCT room_no) AS rooms_used
    FROM hostel
    GROUP BY block
    ORDER BY block ASC
");

// Recent registrations
$recentStudents = dbFetchAll("
    SELECT firstname, lastname, matric_number, level, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 8
");

// Department breakdown
$deptStats = dbFetchAll("
    SELECT department, COUNT(*) AS count
    FROM hostel
    GROUP BY department
    ORDER BY count DESC
");

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem;">
    <h2 style="color:var(--primary); margin:0;">📊 Admin Dashboard</h2>
    <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">👥 All Students</a>
        <a href="<?= BASE_URL ?>/admin/allocations.php" class="btn btn-secondary btn-sm">🏠 Allocations</a>
        <a href="<?= BASE_URL ?>/admin/reset-password.php" class="btn btn-secondary btn-sm">🔑 Reset Password</a>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</div>

<!-- ── Stats ─────────────────────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= $totalAllocated ?></div>
        <div class="stat-label">Allocated</div>
    </div>
    <div class="stat-card error">
        <div class="stat-value"><?= $unallocated ?></div>
        <div class="stat-label">Unallocated</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-value"><?= $occupancyPct ?>%</div>
        <div class="stat-label">Hostel Occupancy</div>
    </div>
</div>

<!-- ── Occupancy bar ──────────────────────────────────────────────────────── -->
<div class="card mb-2">
    <h3 style="margin-bottom:.75rem; color:var(--primary-dark); font-size:1rem;">Overall Hostel Occupancy</h3>
    <div style="background:var(--bg); border-radius:999px; height:18px; overflow:hidden;">
        <div style="background:linear-gradient(90deg,var(--primary),var(--primary-light));
                    width:<?= $occupancyPct ?>%; height:100%; border-radius:999px;
                    transition:width .8s ease; display:flex; align-items:center; justify-content:flex-end; padding-right:6px;">
            <?php if ($occupancyPct > 10): ?>
                <span style="font-size:.7rem; color:#fff; font-weight:700;"><?= $occupancyPct ?>%</span>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-muted mt-1" style="font-size:.82rem;">
        <?= $totalAllocated ?> / <?= $totalCapacity ?> beds occupied
    </p>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">

    <!-- ── Department breakdown ─────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:1rem; color:var(--primary-dark); font-size:1rem;">Allocations by Department</h3>
        <?php if ($deptStats): ?>
            <?php foreach ($deptStats as $row): ?>
                <div style="margin-bottom:.75rem;">
                    <div style="display:flex; justify-content:space-between; font-size:.85rem; margin-bottom:.2rem;">
                        <span><?= htmlspecialchars($row->department) ?></span>
                        <strong><?= $row->count ?></strong>
                    </div>
                    <div style="background:var(--bg); border-radius:999px; height:8px; overflow:hidden;">
                        <div style="background:var(--primary-light);
                                    width:<?= $totalAllocated > 0 ? round(($row->count / $totalAllocated) * 100) : 0 ?>%;
                                    height:100%; border-radius:999px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No allocations yet.</p>
        <?php endif; ?>
    </div>

    <!-- ── Block summary ────────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:0;">
        <h3 style="margin-bottom:1rem; color:var(--primary-dark); font-size:1rem;">Block Activity</h3>
        <?php if ($blockStats): ?>
            <div class="table-wrapper" style="box-shadow:none;">
                <table style="font-size:.82rem;">
                    <thead><tr><th>Block</th><th>Students</th><th>Rooms Used</th></tr></thead>
                    <tbody>
                    <?php foreach ($blockStats as $b): ?>
                        <tr>
                            <td><strong>Block <?= (int)$b->block ?></strong></td>
                            <td><?= (int)$b->count ?></td>
                            <td><?= (int)$b->rooms_used ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No allocations yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ── Recent Registrations ──────────────────────────────────────────────── -->
<div class="card">
    <h3 style="margin-bottom:1rem; color:var(--primary-dark); font-size:1rem;">Recent Registrations</h3>
    <?php if ($recentStudents): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Matric No.</th>
                        <th>Level</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentStudents as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s->firstname . ' ' . $s->lastname) ?></td>
                            <td><code><?= htmlspecialchars($s->matric_number) ?></code></td>
                            <td><span class="badge badge-blue"><?= (int)$s->level ?> L</span></td>
                            <td class="text-muted" style="font-size:.82rem;"><?= date('d M Y H:i', strtotime($s->created_at)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-1"><a href="<?= BASE_URL ?>/admin/students.php" style="font-size:.88rem;">View all students →</a></p>
    <?php else: ?>
        <p class="text-muted">No students registered yet.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>