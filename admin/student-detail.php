<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth_guard.php';

$matric = trim($_GET['matric'] ?? '');
if ($matric === '') {
    header('Location: /admin/students.php');
    exit;
}

$student    = dbFetchOne("SELECT * FROM users  WHERE matric_number = ?", [$matric]);
$allocation = dbFetchOne("SELECT * FROM hostel WHERE matric_number = ?", [$matric]);

if (!$student) {
    flash('error', 'Student not found.');
    header('Location: /admin/students.php');
    exit;
}

$pageTitle = 'Student: ' . $student->firstname . ' ' . $student->lastname;
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
    <a href="/admin/students.php" class="btn btn-secondary btn-sm">← Back</a>
    <h2 style="color:var(--primary); margin:0;">
        👤 <?= htmlspecialchars($student->firstname . ' ' . $student->lastname) ?>
    </h2>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

    <div class="card" style="margin-bottom:0;">
        <h3 style="color:var(--primary-dark); font-size:1rem; margin-bottom:1rem;">Personal Details</h3>
        <table style="width:100%; font-size:.9rem; border-collapse:collapse;">
            <?php
            $rows = [
                'Matric Number' => $student->matric_number,
                'First Name'    => $student->firstname,
                'Last Name'     => $student->lastname,
                'Email'         => $student->email ?? '—',
                'Gender'        => $student->gender,
                'Level'         => $student->level . ' Level',
                'Student Phone' => $student->student_phone,
                'Parent Phone'  => $student->parent_phone,
                'Registered'    => date('d M Y H:i', strtotime($student->created_at)),
            ];
            foreach ($rows as $label => $val): ?>
                <tr>
                    <td style="padding:.5rem .75rem; font-weight:600; color:var(--text-muted);
                               background:var(--bg); border-radius:4px; width:40%;"><?= $label ?></td>
                    <td style="padding:.5rem .75rem;"><?= htmlspecialchars((string)$val) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card" style="margin-bottom:0;">
        <h3 style="color:var(--primary-dark); font-size:1rem; margin-bottom:1rem;">Room Allocation</h3>
        <?php if ($allocation): ?>
            <div class="allocation-result" style="padding:1.5rem 1rem;">
                <h3>✅ Allocated</h3>
                <div class="block-number">Block <?= (int)$allocation->block ?></div>
                <div class="room-number">Room <?= (int)$allocation->room_no ?></div>
                <div class="allocation-badge">
                    <?= htmlspecialchars($allocation->department) ?> ·
                    <?= date('d M Y', strtotime($allocation->allocated_at)) ?>
                </div>
            </div>
            <div class="mt-2">
                <a href="/admin/deallocate.php?id=<?= (int)$allocation->id ?>&csrf=<?= generateCsrf() ?>"
                   class="btn btn-danger btn-sm"
                   data-confirm="Remove this student's allocation? They will need to re-apply.">
                   Remove Allocation
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">⚠️ This student has not been allocated a room yet.</div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
