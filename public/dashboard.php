<?php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container emp mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1">Dashboard</h2>
            <p class="text-muted">Welcome back! Here's what's happening at your clinic today.</p>
        </div>
        <a href="/calendar/calendar.php" class="btn btn-primary"><i class="fas fa-calendar-alt me-2"></i>Open
            Calendar</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-lg h-100 position-relative">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary badge-lg"
                    id="today-events-badge"></span>
                <div class="card-body d-flex flex-row align-items-center justify-content-between pb-2">
                    <div class="text-muted  font-weight-medium">Today's Events</div>
                    <i class="fas fa-calendar-day text-muted"></i>
                </div>
                <div class="card-body pt-0">
                    <p class="text-xs text-muted" id="today-events-details">Appointments & Surgeries</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-lg h-100 position-relative">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success badge-lg"
                    id="overall-stats-badge"></span>
                <div class="card-body d-flex flex-row align-items-center justify-content-between pb-2">
                    <div class="text-muted text-sm font-weight-medium">Overall Statistics</div>
                    <i class="fas fa-chart-bar text-muted"></i>
                </div>
                <div class="card-body pt-0">
                    <p class="text-xs text-muted" id="overall-stats-details"></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-lg h-100 position-relative">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark badge-lg"
                    id="total-staff-badge"></span>
                <div class="card-body d-flex flex-row align-items-center justify-content-between pb-2">
                    <div class="text-muted text-sm font-weight-medium">Staff Overview</div>
                    <i class="fas fa-users text-muted"></i>
                </div>
                <div class="card-body pt-0">
                    <p class="text-xs text-muted" id="staff-overview-details">Total Staff | Available this month</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-lg h-100 position-relative">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-lg"
                    id="pending-communications-badge"></span>
                <div class="card-body d-flex flex-row align-items-center justify-content-between pb-2">
                    <div class="text-muted text-sm font-weight-medium">Pending Communications</div>
                    <i class="fas fa-comments text-muted"></i>
                </div>
                <div class="card-body pt-0">
                    <p class="text-xs text-muted" id="pending-communications-details">Messages | Emails</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Today's Schedule</h5>
                    <p class="card-subtitle text-muted">Upcoming appointments and surgeries for today</p>
                </div>
                <div class="card-body pt-0 space-y-3" id="today-schedule-list">
                    <!-- Schedule items will be populated by JavaScript -->
                    <div class="text-center text-muted py-4" id="no-schedule-today" style="display: none;">
                        No appointments or surgeries scheduled for today.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                    <p class="card-subtitle text-muted">Latest updates and changes across the system</p>
                </div>
                <div class="card-body pt-0 space-y-3" id="recent-activity-list">
                    <!-- Activity items will be populated by JavaScript -->
                    <div class="text-center text-muted py-4" id="no-recent-activity" style="display: none;">
                        No recent activity today.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadDashboardData();
    });

    async function loadDashboardData() {
        // Show loading indicators if any
        try {
            const response = await apiRequest('dashboard', 'get_all_data', {});

            if (response.success) {
                const data = response.data;
                renderTodayOverview(data.today_overview);
                renderOverallStats(data.overall_stats);
                renderStaffAvailability(data.staff_availability);
                renderPendingTasks(data.pending_tasks);
                renderTodaySchedule(data.today_schedule);
                renderRecentActivity(data.recent_activity);
            } else {
                console.error('Error fetching dashboard data:', response.error);
                alert('Failed to load dashboard data: ' + (response.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Network or API error:', error);
            alert('An error occurred while fetching dashboard data.');
        } finally {
            // Hide loading indicators
        }
    }

    function renderTodayOverview(data) {
        const totalTodayEvents = data.appointments_today + data.surgeries_today;
        document.getElementById('today-events-details').innerHTML = `<a href="/appointment/appointments.php" class="text-primary">Appointments</a>: ${data.appointments_today} | <a href="/surgery/surgeries.php" class="text-success">Surgeries</a>: ${data.surgeries_today} `;
        document.getElementById('today-events-badge').textContent = totalTodayEvents;
    }

    function renderOverallStats(data) {
        document.getElementById('overall-stats-details').innerHTML = `<a href="/patient/patients.php" class="text-primary">Patients</a>: ${data.total_patients} </br> Procedures: ${data.total_procedures} | <a href="/surgery/surgeries.php" class="text-success">Surgeries</a>: ${data.total_surgeries} `;
        document.getElementById('overall-stats-badge').textContent = data.total_patients;
    }

    function renderStaffAvailability(data) {
        document.getElementById('staff-overview-details').innerHTML = `<a href="staff/" class="text-primary">Total</a>: ${data.total_staff} | <a href="/staff/staff-availability.php" class="text-success">Available</a>: ${data.available_staff_this_month}`;
        document.getElementById('total-staff-badge').textContent = data.total_staff;
    }

    function renderPendingTasks(data) {
        const totalPending = data.unread_messages + data.unread_emails;
        document.getElementById('pending-communications-details').innerHTML = `<a href="/app-msg/" class="text-primary">Messages</a>:  ${data.unread_messages}/${data.read_messages} | <a href="/app-email/" class=" text-success">Emails</a>: ${data.unread_emails}/${data.read_emails}`;
        document.getElementById('pending-communications-badge').textContent = totalPending;
    }

    function renderTodaySchedule(data) {
        const scheduleList = document.getElementById('today-schedule-list');
        scheduleList.innerHTML = ''; // Clear existing content
        const allEvents = [...data.appointments, ...data.surgeries].sort((a, b) => a.time.localeCompare(b.time));

        if (allEvents.length === 0) {
            scheduleList.innerHTML = `
            <div class="text-center text-muted py-4" id = "no-schedule-today" >
                No appointments or surgeries scheduled for today.
                </div > `;
            return;
        }

        allEvents.forEach(event => {
            const icon = event.type === 'Appointment' ? 'fas fa-calendar-check' : 'fas fa-hospital';
            const badgeClass = event.type === 'Appointment' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
            const itemHtml = `
            <div class="d-flex align-items-center justify-content-between p-3 rounded  hover-bg-light-dark">
                    <div class="d-flex align-items-center space-x-3">
                        <span class="avatar avatar-sm rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center">
                            ${event.patient_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)}
                        </span>
                        <div>
                            <p class="font-weight-medium mb-0">${event.patient_name}</p>
                            <p class="text-sm text-muted mb-0">${event.type}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-weight-medium mb-0">${event.time.substring(0, 5)}</p>
                        <span class="badge ${badgeClass}">${event.type}</span>
                    </div>
                </div >
            `;
            scheduleList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }

    function renderRecentActivity(data) {
        const activityList = document.getElementById('recent-activity-list');
        activityList.innerHTML = ''; // Clear existing content

        if (data.length === 0) {
            activityList.innerHTML = `
            <div class="text-center text-muted py-4" id = "no-recent-activity" >
                No recent activity today.
                </div > `;
            return;
        }

        data.forEach(activity => {
            let iconClass = '';
            switch (activity.type) {
                case 'Patient':
                    iconClass = 'fas fa-user';
                    break;
                case 'Appointment':
                    iconClass = 'fas fa-calendar-alt';
                    break;
                case 'Surgery':
                    iconClass = 'fas fa-hospital';
                    break;
                case 'Staff':
                    iconClass = 'fas fa-user-tie';
                    break;
                case 'Staff Detail':
                    iconClass = 'fas fa-user-edit'; // Icon for staff details
                    break;
                case 'Room':
                    iconClass = 'fas fa-door-open';
                    break;
                default:
                    iconClass = 'fas fa-info-circle';
            }

            const timeAgo = formatTimeAgo(activity.activity_timestamp);

            const itemHtml = `
            <div class="d-flex align-items-center space-x-3 p-3 rounded bg-light hover-bg-light-dark" >
                    <div class="flex-shrink-0 mt-1">
                        <i class="${iconClass} text-muted me-2"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="text-sm mb-0 d-flex justify-content-between"><span>${activity.process_type || 'N/A'}: ${activity.description} by ${activity.updated_by || 'N/A'}</span> <small class="text-xs text-muted">${timeAgo}</small></p>
                    </div>
                </div >
            `;
            activityList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }

    function formatTimeAgo(timestamp) {
        const now = new Date();
        const updatedDate = new Date(timestamp.replace(' ', 'T') + 'Z'); // Assume UTC for consistency
        const seconds = Math.floor((now - updatedDate) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " years ago";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " months ago";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " days ago";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " hours ago";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " minutes ago";
        return Math.floor(seconds) + " seconds ago";
    }

    // Helper function to get initials for avatar
    function getInitials(name) {
        if (!name) return '';
        const parts = name.split(' ');
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>