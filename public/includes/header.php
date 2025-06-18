<?php
// Include authentication functions
require_once __DIR__ . '/../auth/auth.php';
// Redirect to login page if not logged in (except for login page itself and API calls)
$current_page = basename($_SERVER['PHP_SELF']);
// Allow access to login.php, signup.php, and api.php without automatic redirect
if (
    !is_logged_in() &&
    $current_page !== 'login.php' &&
    $current_page !== 'reset_password.php' &&
    $current_page !== 'signup.php' &&
    $current_page !== 'api.php'
) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Surgery Patient Management</title>

    <!-- Custom Fonts -->
    <link rel="stylesheet" href="/assets/css/fonts.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dropzone CSS -->
    <link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />

    <!-- Tom-Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Custom CSS -->

    <?php if (is_staff()): ?>
        <link rel="stylesheet" href="/assets/css/style.css">
        <link rel="stylesheet" href="/assets/css/staff-calendar.css">
    <?php else: ?>
        <link rel="stylesheet" href="/assets/css/style.css">
    <?php endif; ?>
    <!-- Dropzone JS -->
    <script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>

    <!-- API Helper for secure POST requests -->
    <script src="/assets/js/api-helper.js"></script>
</head>

<body>
    <?php
    if (is_admin() || is_editor()) {
        echo '<script>document.body.classList.add("is-admin-editor");</script>';
    }
    ?>
    <!-- Navigation -->
    <?php if (is_staff()): ?>
        <?php $current_user_id = isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : ''; ?>
        <header class="position-sticky top-0 w-100 frosted shadow-sm app-header">
            <div class="container-fluid d-flex align-items-center justify-content-between py-2 px-3">
                <h1 class="d-none d-sm-inline h5 m-0 fw-semibold">Staff Scheduler</h1>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <a href="/staff/staff-calendar.php" id="staff-calendar-btn" class="btn btn-outline p-0 me-3"
                        aria-label="Staff Calendar">
                        <i class="far fa-calendar fa-lg me-2 header-icon"></i>CALENDAR
                    </a>
                    <a href="/profile/profile.php?user_id=<?php echo $current_user_id; ?>" id="profile-btn"
                        class="btn btn-outline p-0 me-3" aria-label="Profile">
                        <i class="fas fa-user-circle fa-lg me-2 header-icon"></i>PROFILE
                    </a>
                    <a href="/logout.php" class="btn btn-outline p-0" aria-label="logout">
                        <i class="fas fa-sign-out-alt me-2 fa-lg header-icon"></i>LOGOUT
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button id="theme-btn" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </header>
    <?php else: ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
            <div class="container-fluid pe-2 ">
                <a class="navbar-brand fw-bold" href="/index.php">
                    <i class="fas fa-heartbeat me-2"></i>
                    <span class="d-none d-sm-inline">Liv Patient Management</span>
                    <span class="d-inline d-sm-none">LivPM</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse mx-auto" id="navbarNav">
                    <?php if (is_logged_in()): ?>
                        <ul class="navbar-nav mx-auto">
                            <?php if (is_staff()): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/technician/technician.php">
                                        <i class="fas fa-calendar me-1"></i>
                                        <span class="d-lg-inline">Avaliability Calendar</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (!is_staff()): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i>
                                        <span class="d-lg-inline">Dashboard</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/calendar/calendar.php">
                                        <i class="fas fa-calendar me-1"></i>
                                        <span class="d-lg-inline">Calendar</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/appointment/appointments.php">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        <span class="d-lg-inline">Appointments</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/surgery/surgeries.php">
                                        <i class="fas fa-hospital me-1"></i>
                                        <span class="d-lg-inline">Surgeries</span>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" href="/patient/patients.php">
                                        <i class="fas fa-users me-1"></i>
                                        <span class="d-lg-inline">Patients</span>
                                    </a>
                                </li>
                                <?php if (is_editor() || is_admin()): ?>
                                    <!-- HR Management - for Editor -->
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="hrDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-users-cog me-1"></i>
                                            HR
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="hrDropdown">
                                            <li>
                                                <a class="dropdown-item" href="/staff/">
                                                    <i class="fas fa-users me-1"></i>
                                                    <span class="d-lg-inline">Staff</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="/staff/staff-availability.php">
                                                    <i class="fas fa-users me-1"></i>
                                                    <span class="d-lg-inline">Staff Calendar</span>
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="/staff/interview_invitations.php">
                                                    <i class="fas fa-paper-plane me-1"></i>
                                                    Interview Invitations
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                                <?php if (is_admin()): ?>
                                    <li class="nav-item dropdown ">
                                        <a class="nav-link dropdown-toggle dark" href="#" id="navbarDropdownMenuLink" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog me-1"></i>
                                            Settings
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">

                                            <a class="dropdown-item" href="/view_log.php">
                                                <i class="fas fa-cog me-1"></i>
                                                Logs
                                            </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/settings.php">
                                            <i class="fas fa-cog me-1"></i>
                                            Settings
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/users.php">
                                            <i class="fas fa-user-cog me-1"></i>
                                            Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/procedures.php">
                                            <i class="fas fa-user-cog me-1"></i>
                                            Procedures
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/rooms.php">
                                            <i class="fas fa-door-open me-2"></i>Rooms
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/agency.php">
                                            <i class="fas fa-building me-1"></i>
                                            Agencies
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/system/auto_import.php">
                                            <i class="fas fa-building me-1"></i>
                                            Import Data
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/export_database.php">
                                            <i class="fas fa-download me-1"></i>
                                            Database Export
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/admin/import_database.php">
                                            <i class="fas fa-upload me-1"></i>
                                            Database Import
                                        </a>
                                    </li>
                        </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php
                        // Fetch username for the logged-in user
                        $user_id = get_user_id();
                        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        $username = $user['username'] ?? 'User';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false" style=" color: mediumblue;">
                            <i class="fas fa-user-circle me-1"></i>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($username); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li>
                                <a class="dropdown-item"
                                    href="/profile/profile.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                                    <i class="fas fa-user-cog me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/signup.php">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
            <ul class="navbar-nav">
                <li>
                    <a id="theme-btn" class="nav-link" href="">
                        <i class="fas fa-moon"></i>
                    </a>
                </li>
            </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Toast Container for system-wide messages -->
        <div class="toast-container position-absolute top-0 start-50 translate-middle-x p-3">
            <!-- Toasts will be appended here by JavaScript -->
        </div>