<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$page_title = "Staff Availability";
require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/staff-calendar.css">

<div class="container-fluid emp">
    <div class="d-flex justify-content-between align-items-center mb-2 pt-4 px-4 flex-wrap">
        <a href="index.php" class="d-inline-block d-sm-none btn btn-sm btn-outline-secondary mb-2 mb-md-0">
            <i class="fas fa-arrow-left me-1"></i>
        </a>
        <h4 class="mb-0 text-primary">
            <i class="fas fa-user-clock me-2"></i>Staff Availability
        </h4>
        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-outline-secondary mb-2 mb-md-0">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Staff
        </a>

        <div id="loading-spinner" class="spinner-border spinner-border-sm text-primary" role="status"
            style="display: none;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="table-container">
        <!-- Month header and controls -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <button id="todayBtn" style="display: none;" class="btn btn-sm btn-primary">Today</button>
            <div class="d-flex mb-2 mb-md-0 align-items-center">
                <button id="prevMonth" class="btn btn-sm btn-outline-primary ">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="monthLabel" class=" h5 fw-bold text-uppercase mx-2 mb-0"></span>
                <button id="nextMonth" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="d-flex align-items-center">
                <select class="form-select form-select-sm" id="staffTypeFilter" style="width: auto;">
                    <option value="all">All Staff Types</option>
                    <option value="staff">Staff</option>
                    <option value="candidate">Candidate</option>
                </select>
            </div>
        </div>

        <!-- Availability table -->
        <div class="table-responsive">
            <table id="availabilityTable" class="table table-bordered table-sm">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>


<script>
    class StaffAvailability {
        constructor() {
            this.currentMonthStart = this.getMonthStart(new Date());
            this.allStaffMembers = [];
            this.filteredStaffMembers = [];
            this.availability = {};

            this.initializeElements();
            this.bindEvents();
            this.loadData();
        }

        initializeElements() {
            this.monthLabel = document.getElementById('monthLabel');
            this.prevMonthBtn = document.getElementById('prevMonth');
            this.nextMonthBtn = document.getElementById('nextMonth');
            this.todayBtn = document.getElementById('todayBtn');
            this.loadingSpinner = document.getElementById('loading-spinner');
            this.availabilityTable = document.getElementById('availabilityTable');
            this.staffTypeFilter = document.getElementById('staffTypeFilter');
        }

        bindEvents() {
            this.prevMonthBtn.addEventListener('click', () => this.navigateMonth(-1));
            this.nextMonthBtn.addEventListener('click', () => this.navigateMonth(1));
            this.todayBtn.addEventListener('click', () => this.goToToday());
            this.staffTypeFilter.addEventListener('change', () => this.filterAndRender());
            document.addEventListener('keydown', e => {
                if (e.key.toLowerCase() === 'd') {
                    document.body.classList.toggle('dark-mode');
                }
            });
        }

        getMonthStart(date) {
            const d = new Date(date);
            return new Date(d.getFullYear(), d.getMonth(), 1);
        }

        getMonthEnd(date) {
            const d = new Date(date);
            return new Date(d.getFullYear(), d.getMonth() + 1, 0);
        }

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        navigateMonth(direction) {
            this.currentMonthStart.setMonth(this.currentMonthStart.getMonth() + direction);
            this.loadData();
        }

        goToToday() {
            this.currentMonthStart = this.getMonthStart(new Date());
            this.loadData();
        }

        async loadData() {
            this.showLoading(true);
            try {
                const staffData = await apiRequest('staff', 'list', {
                    limit: 9999
                });
                if (!staffData.success) throw new Error(staffData.error || 'Failed to load staff members');
                this.allStaffMembers = staffData.staff;

                const startDate = this.formatDate(this.currentMonthStart);
                const endDate = this.formatDate(this.getMonthEnd(this.currentMonthStart));
                const availData = await apiRequest('staff_availability', 'byRangeAll', {
                    start: startDate,
                    end: endDate
                });
                if (!availData.success) throw new Error(availData.error || 'Failed to load availability');

                this.availability = {};
                availData.availability.forEach(avail => {
                    const key = `${avail.staff_id}-${avail.date}`;
                    this.availability[key] = avail.status;
                });

                this.filterAndRender();
            } catch (error) {
                console.error('Error loading data:', error);
                showToast('Failed to load staff availability data', 'danger');
            } finally {
                this.showLoading(false);
            }
        }

        filterAndRender() {
            const selectedType = this.staffTypeFilter.value;
            if (selectedType === 'all') {
                this.filteredStaffMembers = this.allStaffMembers;
            } else {
                this.filteredStaffMembers = this.allStaffMembers.filter(staff => staff.staff_type == selectedType);
            }
            this.renderTable();
        }

        renderTable() {
            const table = this.availabilityTable;
            const thead = table.querySelector('thead');
            const tbody = table.querySelector('tbody');
            thead.innerHTML = '';
            tbody.innerHTML = '';

            // Header
            const headRow = document.createElement('tr');
            const nameTh = document.createElement('th');
            nameTh.textContent = 'Name';
            nameTh.className = 'staff-name-cell';
            headRow.appendChild(nameTh);

            const monthEnd = this.getMonthEnd(this.currentMonthStart);
            const daysInMonth = monthEnd.getDate();
            for (let d = 1; d <= daysInMonth; d++) {
                const th = document.createElement('th');
                th.textContent = d;
                const dayOfWeek = new Date(this.currentMonthStart.getFullYear(), this.currentMonthStart.getMonth(), d)
                    .getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    th.classList.add('weekend');
                }
                headRow.appendChild(th);
            }
            thead.appendChild(headRow);

            // Body
            this.filteredStaffMembers.filter(staff => staff.is_active).forEach(staff => {
                const row = document.createElement('tr');
                const nameTd = document.createElement('td');
                nameTd.className = 'staff-name-cell';
                nameTd.innerHTML = `
                    <strong>${this.escapeHtml(staff.name)}</strong>
                    ${staff.speciality ? `<br><span class="staff-specialty">${this.escapeHtml(staff.speciality)}</span>` : ''}
                `;
                row.appendChild(nameTd);

                for (let d = 1; d <= daysInMonth; d++) {
                    const td = document.createElement('td');
                    const dateObj = new Date(this.currentMonthStart.getFullYear(), this.currentMonthStart
                        .getMonth(), d);
                    const dateStr = this.formatDate(dateObj);
                    const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;

                    if (isWeekend) {
                        td.className = 'weekend';
                        td.innerHTML = '<i class="fas fa-lock text-secondary"></i>';
                    } else {
                        const availKey = `${staff.id}-${dateStr}`;
                        const status = this.availability[availKey] || 'unselected';

                        td.className = status; // 'available', 'not_available', 'unselected'
                        if (status === 'available') {
                            td.innerHTML = '<i class="fas fa-check text-success"></i>';
                        } else if (status === 'not_available') {
                            td.innerHTML = '<i class="fas fa-times text-danger"></i>';
                        }
                        // No icon for unselected to reduce visual clutter
                        td.addEventListener('click', () => this.toggleAvailability(staff.id, dateStr));
                    }
                    row.appendChild(td);
                }
                tbody.appendChild(row);
            });

            // Month Label
            const label = this.currentMonthStart.toLocaleString('default', {
                month: 'long',
                year: 'numeric'
            });
            this.monthLabel.textContent = label;
        }

        async toggleAvailability(staffId, date) {
            this.showLoading(true);
            try {
                const response = await apiRequest('staff_availability', 'toggleDayAdmin', {
                    staff_id: staffId,
                    date: date
                });
                if (!response.success) throw new Error(response.error || 'Failed to toggle availability');

                // Update local state and re-render for immediate feedback
                const availKey = `${staffId}-${date}`;
                if (response.newStatus === 'unselected') {
                    delete this.availability[availKey];
                } else {
                    this.availability[availKey] = response.newStatus;
                }
                this.renderTable();

            } catch (error) {
                console.error('Error toggling availability:', error);
                showToast('Failed to update availability. Please try again.', 'danger');
            } finally {
                this.showLoading(false);
            }
        }

        showLoading(show) {
            this.loadingSpinner.style.display = show ? 'inline-block' : 'none';
        }

        escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        new StaffAvailability();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>