<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['matric_number'])) {
    header('Location: ' . BASE_URL . '/allocation.php');
    exit;
}

$errors  = [];
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please refresh and try again.';
    } else {
        $old = [
            'firstname' => trim($_POST['firstname'] ?? ''),
            'lastname'  => trim($_POST['lastname']  ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'gender'    => $_POST['gender']          ?? '',
            'matric'    => strtoupper(trim($_POST['matric'] ?? '')),
            'level'     => (int)($_POST['level']    ?? 0),
            'phone'     => trim($_POST['phonenumber'] ?? ''),
            'parent'    => trim($_POST['parentphone'] ?? ''),
        ];

        // Validation
        if (strlen($old['firstname']) < 2)
            $errors[] = 'First name must be at least 2 characters.';

        if (strlen($old['lastname']) < 2)
            $errors[] = 'Last name must be at least 2 characters.';

        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))
            $errors[] = 'Please enter a valid email address.';

        $pwd = $_POST['password'] ?? '';
        if (strlen($pwd) < 8)
            $errors[] = 'Password must be at least 8 characters.';

        if (!in_array($old['gender'], ['Male', 'Female', 'Other'], true))
            $errors[] = 'Please select a valid gender.';

        if (!preg_match('/^[A-Z0-9\-\/]+$/', $old['matric']))
            $errors[] = 'Matric number must contain only letters, numbers, hyphens or slashes.';

        if (!in_array($old['level'], [100, 200, 300, 400], true))
            $errors[] = 'Please select a valid level.';

        if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $old['phone']))
            $errors[] = 'Please enter a valid student phone number.';

        if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $old['parent']))
            $errors[] = 'Please enter a valid parent/guardian phone number.';

        if (empty($errors)) {
            $db = getDB();

            // Duplicate checks
            $existingMatric = dbFetchOne("SELECT id FROM users WHERE matric_number = ?", [$old['matric']]);
            if ($existingMatric) {
                $errors[] = 'This matric number is already registered.';
            }

            $existingEmail = dbFetchOne("SELECT id FROM users WHERE email = ?", [$old['email']]);
            if ($existingEmail) {
                $errors[] = 'This email address is already in use.';
            }

            if (empty($errors)) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);

                try {
                    // Direct INSERT instead of dbInsert() to avoid any issues
                    $sql = "INSERT INTO users (
                        firstname, 
                        lastname, 
                        email, 
                        password, 
                        gender, 
                        matric_number, 
                        level, 
                        student_phone, 
                        parent_phone
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute([
                        $old['firstname'],
                        $old['lastname'],
                        $old['email'],
                        $hash,
                        $old['gender'],
                        $old['matric'],
                        $old['level'],
                        $old['phone'],
                        $old['parent']
                    ]);
                    
                    if ($result) {
                        rotateCsrf();
                        flash('success', 'Registration successful! You can now login.');
                        header('Location: ' . BASE_URL . 'login.php');
                        exit;
                    } else {
                        $errors[] = 'Failed to create account. Please try again.';
                    }

                } catch (\PDOException $e) {
                    error_log('Registration error: ' . $e->getMessage());
                    if (APP_DEBUG) {
                        $errors[] = 'Database error: ' . $e->getMessage();
                    } else {
                        $errors[] = 'An unexpected error occurred. Please try again.';
                    }
                }
            }
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:520px; margin:0 auto;">
    <h2>📝 Student Registration</h2>
    <hr>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <div>
                <strong>Please fix the following:</strong>
                <ul style="margin:.4rem 0 0 1rem; padding:0;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 1rem;">
            <div class="form-group">
                <label for="firstname">First Name *</label>
                <input type="text" name="firstname" id="firstname" required autocomplete="given-name"
                       value="<?= htmlspecialchars($old['firstname'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="lastname">Last Name *</label>
                <input type="text" name="lastname" id="lastname" required autocomplete="family-name"
                       value="<?= htmlspecialchars($old['lastname'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" name="email" id="email" required autocomplete="email"
                   value="<?= htmlspecialchars($old['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                   minlength="8" placeholder="Minimum 8 characters">
            <span class="hint" id="password-hint"></span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 1rem;">
            <div class="form-group">
                <label for="gender">Gender *</label>
                <select name="gender" id="gender" required>
                    <option value="">-- Select --</option>
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="level">Level *</label>
                <select name="level" id="level" required>
                    <option value="">-- Select --</option>
                    <?php foreach ([100, 200, 300, 400] as $lvl): ?>
                        <option value="<?= $lvl ?>" <?= ($old['level'] ?? 0) === $lvl ? 'selected' : '' ?>><?= $lvl ?> Level</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="matric">Matric Number *</label>
            <input type="text" name="matric" id="matric" required
                   placeholder="e.g. CSC/2021/001"
                   style="text-transform:uppercase;"
                   value="<?= htmlspecialchars($old['matric'] ?? '') ?>">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 1rem;">
            <div class="form-group">
                <label for="phonenumber">Student Phone *</label>
                <input type="tel" name="phonenumber" id="phonenumber" required autocomplete="tel"
                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="parentphone">Parent/Guardian Phone *</label>
                <input type="tel" name="parentphone" id="parentphone" required
                       value="<?= htmlspecialchars($old['parent'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" name="submit">Create Account</button>

        <p class="text-center mt-2 text-muted" style="font-size:.9rem;">
            Already have an account? <a href="<?= BASE_URL ?>/login.php" style="font-weight:600;">Login here</a>
        </p>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>