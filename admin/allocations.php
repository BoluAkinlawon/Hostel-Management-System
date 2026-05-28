<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

$block  = (int)($_GET['block'] ?? 0);
$params = [];
$where  = '';
if ($block > 0 && $block <= TOTAL_BLOCKS) {
    $where  = 'WHERE h.block = ?';
    $params = [$block];
}

$allocations = dbFetchAll("
    SELECT h.*, u.firstname, u.lastname, u.gender, u.email
    FROM hostel h
    JOIN users u ON u.matric_number = h.matric_number
    $where
    ORDER BY h.block ASC, h.room_no ASC, u.lastname ASC
", $params);

$pageTitle = 'Room Allocations';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
    <h2 style="color:var(--primary); margin:0;">🏠 Room Allocations
        <span class="badge badge-blue" style="font-size:.85rem;"><?= count($allocations) ?></span>
    </h2>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<!-- Filter by block -->
<form method="get" style="margin-bottom:1rem; display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
    <label style="font-weight:600; font-size:.9rem;">Filter by Block:</label>
    <select name="block" onchange="this.form.submit()"
            style="padding:.5rem .8rem; border:1.5px solid var(--border); border-radius:7px; font-size:.9rem;">
        <option value="">All Blocks</option>
        <?php for ($i = 1; $i <= TOTAL_BLOCKS; $i++): ?>
            <option value="<?= $i ?>" <?= $block === $i ? 'selected' : '' ?>>Block <?= $i ?></option>
        <?php endfor; ?>
    </select>
    <?php if ($block): ?>
        <a href="<?= BASE_URL ?>/admin/allocations.php" class="btn btn-secondary btn-sm">Clear</a>
    <?php endif; ?>
</form>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Block</th>
                <th>Room</th>
                <th>Matric No.</th>
                <th>Name</th>
                <th>Department</th>
                <th>Level</th>
                <th>Gender</th>
                <th>Allocated On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($allocations): ?>
            <?php foreach ($allocations as $a): ?>
                <tr>
                    <td><strong>Block <?= (int)$a->block ?></strong></td>
                    <td>Room <?= (int)$a->room_no ?></td>
                    <td><code><?= htmlspecialchars($a->matric_number) ?></code></td>
                    <td><?= htmlspecialchars($a->firstname . ' ' . $a->lastname) ?></td>
                    <td style="font-size:.83rem;"><?= htmlspecialchars($a->department) ?></td>
                    <td><span class="badge badge-blue"><?= (int)$a->level ?>L</span></td>
                    <td><?= htmlspecialchars($a->gender) ?></td>
                    <td style="font-size:.8rem; color:var(--text-muted);"><?= date('d M Y', strtotime($a->allocated_at)) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/deallocate.php?id=<?= (int)$a->id ?>&csrf=<?= generateCsrf() ?>"
                           class="btn btn-danger btn-sm" style="font-size:.78rem; padding:.28rem .7rem;"
                           data-confirm="Deallocate <?= htmlspecialchars($a->firstname) ?>? They will need to request a new room.">
                           Remove
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No allocations found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>