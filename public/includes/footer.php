<?php
// echo '<pre class="text-white">';
// print_r($_SESSION);
// echo '</pre>';
?>

</div> <!-- Close container-fluid -->
</main>

<!-- Footer - Hidden on mobile, visible on desktop -->
<footer class="bg-dark text-light py-4 mt-5 d-none d-xl-block">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-heartbeat me-2"></i>
                    Surgery Patient Management
                </h6>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex flex-row justify-content-md-end ">
                    <small class="text-muted text-light">
                        <a class="dropdown-item text-light" style="color: white;" href="/auth/signup.php">
                            <i class="fas fa-copyright ms-2"></i>Surgery Patient
                            Management.
                        </a>
                    </small>
                    <small class="text-muted text-light"></small>
                    <a class="dropdown-item text-light" style="color: white;" href="/auth/logout.php">
                        <?php echo date('Y'); ?> All rights reserved.
                    </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Bottom Navigation Menu - Visible on mobile/tablet, hidden on desktop -->
<?php if (!is_logged_in()): ?>
    <nav class="mobile-bottom-nav d-xl-none">
        <div class="mobile-nav-container">
            <?php if (!is_staff()): ?>
                <a href="/dashboard.php"
                    class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/calendar/calendar.php"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/calendar/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
                <a href="/technician/tech_availability.php"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/tech_availability.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user-clock"></i>
                    <span>Tech Avail</span>
                </a>
                <a href="/profile/profile.php?user_id=<?php echo $_SESSION['user_id']; ?>"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            <?php else: ?>
                <!-- Technician-specific menu -->
                <a href="/staff/dashboard.php"
                    class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'technician.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Schedule</span>
                </a>
                <a href="/staff/staff-calendar.php"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/calendar/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
                <a href="/messages/messages.php"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/tech_availability.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user-clock"></i>
                    <span>Availability</span>
                </a>
                <a href="/profile/profile.php?user_id=<?php echo $_SESSION['user_id']; ?>"
                    class="mobile-nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- jQuery (required for Select2) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/script.js"></script>
<!-- API Helper for secure POST requests -->
<script src="/assets/js/api-helper.js"></script>
<script>
    // Initialize all Bootstrap toasts
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl)
        })
    });
</script>
</body>

</html>