<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['matric_number'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// ── Method & CSRF guard ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request. Please try again.');
    header('Location: ' . BASE_URL . '/allocation.php');
    exit;
}

$matric = $_SESSION['matric_number'];
$db     = getDB();

// ── Validate input ────────────────────────────────────────────────────────────
$validDepts = [
    'Computer Science',
    'History and International Studies',
    'Economics',
    'Law',
];
$department = trim($_POST['department'] ?? '');
$level      = (int)($_POST['level'] ?? 0);

if (!in_array($department, $validDepts, true)) {
    flash('error', 'Invalid department selected.');
    header('Location: ' . BASE_URL . '/allocation.php');
    exit;
}

// ── Verify user and level match ───────────────────────────────────────────────
$user = dbFetchOne("SELECT level FROM users WHERE matric_number = ?", [$matric]);
if (!$user || (int)$user->level !== $level) {
    flash('error', 'User verification failed. Please login again.');
    header('Location: ' . BASE_URL . '/allocation.php');
    exit;
}

// ── Already allocated? ────────────────────────────────────────────────────────
if (dbFetchOne("SELECT id FROM hostel WHERE matric_number = ?", [$matric])) {
    flash('error', 'You have already been allocated a room.');
    header('Location: ' . BASE_URL . '/allocation.php');
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// SMART ALLOCATION ALGORITHM
//
// Strategy:
//  1. Prefer rooms that are partially occupied (fill rooms evenly)
//  2. 400-level students get special rooms [1, 12, 13, 24]
//  3. Others get standard rooms [2–23]
//  4. Deterministic DB query avoids random retry loops
// ─────────────────────────────────────────────────────────────────────────────
function findAvailableRoom(PDO $db, int $level): ?array {
    $isSenior = ($level === 400);

    if ($isSenior) {
        $placeholders = implode(',', array_fill(0, count(SPECIAL_ROOMS), '?'));
        $sql = "
            SELECT b.block, b.room_no, COALESCE(h.cnt, 0) AS occupancy
            FROM (
                SELECT b.n AS block, r.n AS room_no
                FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                      UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                      UNION SELECT 16 UNION SELECT 17 UNION SELECT 18) b
                CROSS JOIN (SELECT $placeholders AS n" . str_repeat(" UNION SELECT ?", count(SPECIAL_ROOMS) - 1) . ") r
            ) b
            LEFT JOIN (SELECT block, room_no, COUNT(*) AS cnt FROM hostel GROUP BY block, room_no) h
                ON h.block = b.block AND h.room_no = b.room_no
            WHERE COALESCE(h.cnt, 0) < ?
            ORDER BY occupancy DESC, RAND()
            LIMIT 1
        ";
        // Fix: build a simpler version using the constant array
        $rooms = SPECIAL_ROOMS;
        $roomList = implode(',', $rooms);
        $sql = "
            SELECT b.n AS block, r.n AS room_no, COALESCE(h.cnt, 0) AS occupancy
            FROM (
                SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                UNION SELECT 16 UNION SELECT 17 UNION SELECT 18
            ) b
            CROSS JOIN (
                SELECT n FROM (SELECT 1 n UNION SELECT 12 UNION SELECT 13 UNION SELECT 24) rooms_list
            ) r
            LEFT JOIN (SELECT block, room_no, COUNT(*) AS cnt FROM hostel GROUP BY block, room_no) h
                ON h.block = b.n AND h.room_no = r.n
            WHERE COALESCE(h.cnt, 0) < ?
            ORDER BY occupancy DESC, RAND()
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([ROOM_CAPACITY]);
    } else {
        $sql = "
            SELECT b.n AS block, r.n AS room_no, COALESCE(h.cnt, 0) AS occupancy
            FROM (
                SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                UNION SELECT 16 UNION SELECT 17 UNION SELECT 18
            ) b
            CROSS JOIN (
                SELECT 2 n UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11
                UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16
                UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION SELECT 21
                UNION SELECT 22 UNION SELECT 23
            ) r
            LEFT JOIN (SELECT block, room_no, COUNT(*) AS cnt FROM hostel GROUP BY block, room_no) h
                ON h.block = b.n AND h.room_no = r.n
            WHERE COALESCE(h.cnt, 0) < ?
            ORDER BY occupancy DESC, RAND()
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([ROOM_CAPACITY]);
    }

    $row = $stmt->fetch();
    return $row ? ['block' => (int)$row->block, 'room_no' => (int)$row->room_no] : null;
}

// ── Run inside a transaction to prevent race conditions ───────────────────────
try {
    $db->beginTransaction();

    // Lock check (re-check inside transaction)
    $existing = $db->prepare("SELECT id FROM hostel WHERE matric_number = ? FOR UPDATE");
    $existing->execute([$matric]);
    if ($existing->fetch()) {
        $db->rollBack();
        flash('error', 'You have already been allocated a room.');
        header('Location: ' . BASE_URL . '/allocation.php');
        exit;
    }

    $room = findAvailableRoom($db, $level);

    if (!$room) {
        $db->rollBack();
        flash('error', 'Sorry, all rooms are currently fully occupied. Please contact the accommodation office.');
        header('Location: ' . BASE_URL . '/allocation.php');
        exit;
    }

    $stmt = $db->prepare(
        "INSERT INTO hostel (matric_number, department, level, block, room_no) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$matric, $department, $level, $room['block'], $room['room_no']]);

    $db->commit();

    rotateCsrf();
    flash('success', "You have been allocated to Block {$room['block']}, Room {$room['room_no']}.");

} catch (\PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }

    if ($e->getCode() === '23000') {
        flash('error', 'You have already been allocated a room.');
    } else {
        error_log('Allocation error: ' . $e->getMessage());
        flash('error', 'An error occurred during allocation. Please try again.');
    }
}

header('Location: ' . BASE_URL . '/allocation.php');
exit;
