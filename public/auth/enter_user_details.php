<?php
require_once '../includes/db.php';

session_start();

$page_title = "Complete Registration";
$errors = [];
$success_message = '';

$token = $_GET['token'] ?? '';
$token = trim($token);

// Validate token and get invitation
if (empty($token)) {
    $errors[] = 'Invalid or missing invitation link.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM invitations WHERE token = :token AND status = 'pending'");
    $stmt->execute([':token' => $token]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invite) {
        $errors[] = 'This invitation link is invalid, already used, or expired.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name || !$phone) {
        $errors[] = 'Name and phone number are required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users 
                (email, password, role, agency_id, name, surname, phone, is_active, created_at, updated_at)
                VALUES (:email, :password, :role, :agency_id, :name, :surname, :phone, 1, datetime('now'), datetime('now'))");

            $stmt->execute([
                ':email' => $invite['email'],
                ':password' => $hashed,
                ':role' => $invite['role'],
                ':agency_id' => $invite['agency_id'],
                ':name' => $name,
                ':surname' => $surname,
                ':phone' => $phone
            ]);

            $user_id = $pdo->lastInsertId();

            // If technician, insert into technicians table
            if ($invite['role'] === 'technician') {
                $stmt = $pdo->prepare("INSERT INTO technicians (name, phone, is_active, created_at)
                                      VALUES (:name, :phone, 1, datetime('now'))");
                $stmt->execute([':name' => $name, ':phone' => $phone]);
            }

            // Mark invitation as used
            $stmt = $pdo->prepare("UPDATE invitations SET status = 'used', used_at = datetime('now') WHERE id = :id");
            $stmt->execute([':id' => $invite['id']]);

            // Auto-login the user
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_role'] = $invite['role'];
            $_SESSION['user_email'] = $invite['email'];

            // Redirect
            if ($invite['role'] === 'technician') {
                header('Location: ../technician/tech_availability.php');
            } else {
                header('Location: ../dashboard.php');
            }
            exit;
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>
<div class="container emp frosted">
    <div class="card frosted">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center py-2">
                <a href="login.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Login</span>
                </a>
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Complete Your Registration
                </h4>
                <div style="width: 80px;"></div> <!-- Spacer -->
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif (!empty($success_message)): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if (empty($success_message) && isset($invite)): ?>
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">User Details</legend>
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($invite['email']) ?>"
                                disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Surname</label>
                            <input type="text" class="form-control" name="surname">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </fieldset>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>