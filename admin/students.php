<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$whereClause = '';
$params      = [];

if ($search !== '') {
    $whereClause = "WHERE u.firstname LIKE ? OR u.lastname LIKE ? OR u.matric_number LIKE ? OR u.email LIKE ?";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

$total   = (int)dbFetchOne("SELECT COUNT(*) AS n FROM users u $whereClause", $params)->n;
$pages   = (int)ceil($total / $perPage);

// FIXED: Parameterized LIMIT and OFFSET
$sql = "SELECT u.*, h.block, h.room_no, h.allocated_at
        FROM users u
        LEFT JOIN hostel h ON h.matric_number = u.matric_number
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$students = dbFetchAll($sql, $params);

$pageTitle = 'All Students';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
    <h2 style="color:var(--primary); margin:0;">👥 All Students
        <span class="badge badge-blue" style="font-size:.85rem;"><?= $total ?></span>
    </h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<!-- Search -->
<form method="get" style="margin-bottom:1rem; display:flex; gap:.5rem;">
    <input type="text" name="q" placeholder="Search by name, matric, or email…"
           value="<?= htmlspecialchars($search) ?>"
           style="flex:1; padding:.6rem .9rem; border:1.5px solid var(--border); border-radius:7px; font-size:.9rem;">
    <button type="submit" class="btn btn-sm" style="width:auto; padding:.6rem 1.2rem;">Search</button>
    <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-secondary btn-sm">Clear</a>
    <?php endif; ?>
</form>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Matric No.</th>
                <th>Level</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Room</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($students): ?>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($s->firstname . ' ' . $s->lastname) ?></strong></td>
                    <td><code><?= htmlspecialchars($s->matric_number) ?></code></td>
                    <td><span class="badge badge-blue"><?= (int)$s->level ?>L</span></td>
                    <td><?= htmlspecialchars($s->gender) ?></td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($s->email ?? '—') ?></td>
                    <td>
                        <?php if ($s->block): ?>
                            <span class="badge badge-green">B<?= (int)$s->block ?> / R<?= (int)$s->room_no ?></span>
                        <?php else: ?>
                            <span class="badge badge-yellow">Unallocated</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem; color:var(--text-muted);"><?= date('d M Y', strtotime($s->created_at)) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/student-detail.php?matric=<?= urlencode($s->matric_number) ?>"
                           class="btn btn-secondary btn-sm" style="font-size:.78rem; padding:.28rem .7rem;">View</a>
                        <a href="<?= BASE_URL ?>/admin/delete-student.php?matric=<?= urlencode($s->matric_number) ?>&csrf=<?= generateCsrf() ?>"
                           class="btn btn-danger btn-sm" style="font-size:.78rem; padding:.28rem .7rem;"
                           data-confirm="Delete <?= htmlspecialchars($s->firstname) ?>? This cannot be undone.">Del</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No students found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
    <div style="display:flex; gap:.5rem; justify-content:center; margin-top:1.25rem; flex-wrap:wrap;">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               style="display:inline-flex; align-items:center; justify-content:center;
                      width:36px; height:36px; border-radius:6px; font-size:.88rem; font-weight:600;
                      background:<?= $i === $page ? 'var(--primary)' : 'var(--card-bg)' ?>;
                      color:<?= $i === $page ? '#fff' : 'var(--text)' ?>;
                      border:1.5px solid <?= $i === $page ? 'var(--primary)' : 'var(--border)' ?>;
                      text-decoration:none;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>