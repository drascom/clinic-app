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
                    <div class="text-muted">
                        <h3>Today's Events</h3>
                    </div>
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
                    <div class="text-muted text-sm font-weight-medium">
                        <h3>Overall Statistics</h3>
                    </div>
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
                    <div class="text-muted text-sm font-weight-medium">
                        <h3>Staff Overview</h3>
                    </div>
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
                    <div class="text-muted text-sm font-weight-medium">
                        <h3>Communications</h3>
                    </div>
                    <i class="fas fa-comments text-muted"></i>
                </div>
                <div class="card-body pt-0">
                    <p class="text-xs text-muted" id="pending-communications-details">Messages | Emails</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-1">
                        <i class="fas fa-clock me-2"></i>
                        Week's Appointment List
                    </h5>
                    <p class="card-subtitle text-muted">Upcoming 10 appointments</p>
                </div>
                <div class="card-body pt-0 space-y-3" id="week-appointment-list">
                    <!-- Schedule items will be populated by JavaScript -->
                    <div class="text-center text-muted py-4" id="no-schedule-today" style="display: none;">
                        No appointments or surgeries scheduled for today.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-1">
                        <i class="fas fa-stethoscope me-2"></i>Recent Surgeries
                    </h5>
                    <p class="card-subtitle text-muted">Recent surgeries status list</p>
                </div>
                <div class="card-body p-0 " id="recent-surgery-list">
                    <!-- Activity items will be populated by JavaScript -->
                    <div class="text-center text-muted py-4" id="no-recent-surgery" style="display: none;">
                        No recent surgery .
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="fas fa-users"></i>
                            Recent Leads
                        </h5>
                        <p class="card-subtitle text-muted"><small>Latest 10 leads</small></p>
                    </div>
                    <a href="/app-leeds/" class="btn btn-sm btn-outline-primary">Go to Leads</a>
                </div>
                <div class="card-body p-0" id="recent-leads-list">
                    <!-- Leads will be populated by JavaScript -->
                    <div class="text-center text-muted py-4" id="no-recent-leads" style="display: none;">
                        No recent leads.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-1">
                        <i class="fas fa-tasks"></i>
                        Recent Activity
                    </h5>
                    <p class="card-subtitle text-muted">Latest updates and changes across the system</p>
                </div>
                <div class="card-body p-0 " id="recent-activity-list">
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
    // Helper function to get initials for avatar
    function getInitials(name) {
        if (!name) return '';
        const parts = name.split(' ');
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
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

    function formatDate(dateString) {
        const options = { day: '2-digit', month: 'short', year: '2-digit' };
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', options).replace(/\//g, ' / ');
    }

    function getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'completed': return 'success';
            case 'scheduled': return 'primary';
            case 'confirmed': return 'info';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }

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
                renderWeekSchedule(data.week_schedule);
                renderRecentActivity(data.recent_activity);
                renderRecentSurgeries(data.recent_surgeries);
                renderRecentLeads(data.recent_leads);
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

    function renderWeekSchedule(data) {
        const scheduleList = document.getElementById('week-appointment-list');
        scheduleList.innerHTML = ''; // Clear existing content
        const allEvents = [...data.appointments].sort((a, b) => {
            const dateA = new Date(a.date + 'T' + a.time);
            const dateB = new Date(b.date + 'T' + b.time);
            return dateB - dateA;
        });

        if (allEvents.length === 0) {
            scheduleList.innerHTML = `
            <div class="text-center text-muted py-4" id="no-upcoming-schedule">
                No upcoming appointments.
            </div>`;
            return;
        }

        allEvents.forEach(event => {
            const eventDate = new Date(event.date);
            const formattedDate = eventDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            const badgeClass = event.type === 'Appointment' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
            const itemHtml = `
            <div class="d-flex align-items-center justify-content-between p-3 rounded hover-bg-light-dark">
                <div class="d-flex align-items-center space-x-3">
                    <span class="avatar avatar-sm rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center">
                        ${getInitials(event.patient_name)}
                    </span>
                    <div>
                        <p class="font-weight-medium mb-0">${event.patient_name}</p>
                        <p class="text-sm text-muted mb-0">${event.type}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-weight-medium mb-0">${event.time.substring(0, 5)}</p>
                    <p class="text-sm text-muted mb-0">${formattedDate}</p>
                </div>
            </div>`;
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
            <div class="d-flex align-items-center space-x-3 p-3 rounded  hover-bg-light-dark" >
                    <div class="flex-shrink-0 mt-1">

                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="text-sm mb-0 d-flex justify-content-between"><span> <i class="${iconClass} text-muted me-2"></i>${activity.process_type || 'N/A'}: ${activity.description}<small class="text-muted"> by ${activity.updated_by || 'N/A'}</small></span> <small class="text-xs text-muted">${timeAgo}</small></p>
                    </div>
                </div >
            `;
            activityList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }

    function renderRecentSurgeries(data) {
        const surgeryList = document.getElementById('recent-surgery-list');
        surgeryList.innerHTML = ''; // Clear existing content

        if (data.length === 0) {
            surgeryList.innerHTML = `
            <div class="text-center text-muted py-4" id="no-recent-surgery">
                No recent surgery.
            </div>`;
            return;
        }

        data.forEach(surgery => {
            const formattedDate = formatDate(surgery.date);
            let formsContent = '';
            if (surgery.forms) {
                try {
                    const forms = JSON.parse(surgery.forms);
                    for (const [formName, isCompleted] of Object.entries(forms)) {
                        const icon = isCompleted ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
                        formsContent += `<i class="fas ${icon} me-2" title="${formName}"></i>`;
                    }
                } catch (e) {
                    formsContent += '<span class="text-danger">Error</span>';
                }
            } else {
                formsContent += '<span class="text-muted">N/A</span>';
            }

            const surgeryUrl = `/surgery/add_edit_surgery.php?id=${surgery.id}`;
            const itemHtml = `
            <a href="${surgeryUrl}" class="text-decoration-none text-dark d-block">
                <div class="d-flex align-items-center space-x-3 p-3 rounded bg-light hover-bg-light-dark">
                    <div class="flex-shrink-0 mt-1">
                        <i class="fas fa-hospital text-muted me-2"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <p class="text-sm mb-0 d-flex justify-content-between">
                            <span>${surgery.patient_name}</span>
                            <small class="text-xs text-muted">${formattedDate}</small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="d-flex justify-content-start align-items-center">
                                ${formsContent}
                            </div>
                            <p class="text-xs text-muted mb-0"><span class="badge bg-${getStatusColor(surgery.status)}">${surgery.status}</span></p>
                        </div>
                    </div>
                </div>
            </a>`;
            surgeryList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }


    function renderRecentLeads(leads) {
        const leadsList = document.getElementById('recent-leads-list');
        leadsList.innerHTML = ''; // Clear existing content

        if (!leads || leads.length === 0) {
            document.getElementById('no-recent-leads').style.display = 'block';
            return;
        }

        leads.forEach(lead => {
            const leadUrl = `/app-leeds/index.php?lead_id=${lead.id}`;
            const itemHtml = `
                <a href="${leadUrl}" class="text-decoration-none text-dark">
                    <div class="d-flex align-items-center space-x-3 p-3 rounded shadow-sm hover-bg-light-dark">
                        <div class="flex-grow-1 min-w-0">
                            <p class="text-sm mb-0 d-flex justify-content-between">
                                <span><i class="fas fa-user text-muted me-2"></i>${lead.name}</span>
                                <small class="text-xs text-muted">${lead.phone || ''}</small>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <div class="d-flex justify-content-start align-items-center">
                                    ${lead.email || ''}
                                </div>
                                <span class="badge bg-${getStatusColor(lead.status)}">${lead.status}</span>
                            </div>
                        </div>
                    </div>
                </a>`;
            leadsList.insertAdjacentHTML('beforeend', itemHtml);
        });
    }
    function formatDate(dateString) {
        const options = { day: '2-digit', month: 'short', year: '2-digit' };
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', options).replace(/\//g, ' / ');
    }

    function getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'completed': return 'success';
            case 'scheduled': return 'primary';
            case 'confirmed': return 'info';
            case 'cancelled': return 'danger';
            case 'intake': return 'secondary';
            case 'not answered': return 'info';
            case 'not interested': return 'danger';
            case 'qualified': return 'primary';
            case 'converted': return 'success';
            default: return 'secondary';
        }
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