<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$page_title = "Room Availability";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container  py-4 emp">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-door-open me-2 text-primary"></i>
            Room Availability
        </h2>
        <div class="btn-group" role="group">
            <?php if (is_admin()): ?>
                <a href="/admin/rooms.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-cog me-1"></i>
                    Manage Rooms
                </a>
            <?php endif; ?>

            <a href="/calendar/calendar.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-calendar me-1"></i>
                Calendar View
            </a>
        </div>
    </div>

    <!-- Date Navigation -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-outline-primary" id="prevMonth">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h5 class="mb-0" id="monthRange">Loading...</h5>
                        <button type="button" class="btn btn-outline-primary" id="nextMonth">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-primary" id="todayBtn">Today</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading room availability...</p>
    </div>

    <!-- Availability Grid -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Room Availability Grid
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="availability-table">
                    <thead class="table-dark">
                        <tr id="table-header">
                            <th>Date</th>
                            <!-- Room columns will be added here -->
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <!-- Room rows will be added here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6>Legend:</h6>
            <div class="d-flex gap-4">
                <div class="d-flex align-items-center">
                    <div class="availability-cell available me-2"
                        style="width: 20px; height: 20px; border: 1px solid #ddd;"></div>
                    <span>Available</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="availability-cell booked me-2"
                        style="width: 20px; height: 20px; border: 1px solid #ddd;"></div>
                    <span>Booked</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="availability-cell inactive me-2"
                        style="width: 20px; height: 20px; border: 1px solid #ddd;"></div>
                    <span>Room Inactive</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .availability-cell {
        min-height: 40px;
        /* Reduced height */
        padding: 4px;
        /* Reduced padding */
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        transition: all 0.2s;
    }

    .availability-cell.available {
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .availability-cell.available:hover {
        background-color: #c3e6cb;
    }

    .availability-cell.booked {
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .availability-cell.booked.completed {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        cursor: not-allowed;
    }

    .availability-cell.inactive {
        background-color: #e2e3e5;
        border-color: #d6d8db;
        cursor: not-allowed;
    }

    .patient-info {
        font-size: 0.75rem;
        /* Smaller font */
        font-weight: bold;
        color: #721c24;
    }

    .graft-info {
        font-size: 0.65rem;
        /* Smaller font */
        color: #856404;
    }

    .status-info {
        font-size: 0.6rem;
        /* Smaller font */
        color: #0c5460;
        font-weight: bold;
        text-transform: uppercase;
    }

    .agency-info {
        font-size: 0.6rem;
        /* Smaller font */
        color: #6c757d;
        font-style: italic;
    }

    #availability-table th {
        text-align: center;
        vertical-align: middle;
    }

    .date-col {
        font-weight: bold;
        background-color: #f8f9fa;
        min-width: 100px;
        /* Slightly smaller width */
        vertical-align: middle;
        font-size: 0.8rem;
        /* Smaller font */
    }

    .room-header {
        font-weight: bold;
        background-color: #f8f9fa;
        min-width: 120px;
        /* Slightly smaller width */
        font-size: 0.8rem;
        /* Smaller font */
    }
</style>

<script>
    class RoomAvailability {
        constructor() {
            this.currentDate = new Date();
            this.currentDate.setDate(1); // Start with the first day of the month
            this.rooms = [];
            this.availability = {};

            this.initializeElements();
            this.bindEvents();
            this.loadData();
        }

        initializeElements() {
            this.monthRangeEl = document.getElementById('monthRange');
            this.prevMonthBtn = document.getElementById('prevMonth');
            this.nextMonthBtn = document.getElementById('nextMonth');
            this.todayBtn = document.getElementById('todayBtn');
            this.loadingSpinner = document.getElementById('loading-spinner');
            this.tableHeader = document.getElementById('table-header');
            this.tableBody = document.getElementById('table-body');
        }

        bindEvents() {
            this.prevMonthBtn.addEventListener('click', () => this.navigateMonth(-1));
            this.nextMonthBtn.addEventListener('click', () => this.navigateMonth(1));
            this.todayBtn.addEventListener('click', () => this.goToToday());
        }

        getMonthStart(date) {
            const d = new Date(date);
            d.setDate(1);
            return d;
        }

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        navigateMonth(direction) {
            this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            this.loadData();
        }

        goToToday() {
            this.currentDate = this.getMonthStart(new Date());
            this.loadData();
        }

        updateMonthRange() {
            const options = { month: 'long', year: 'numeric' };
            this.monthRangeEl.textContent = this.currentDate.toLocaleDateString('en-US', options);
        }

        async loadData() {
            this.showLoading(true);

            try {
                // Load rooms
                const roomsData = await apiRequest('rooms', 'list');

                if (!roomsData.success) {
                    throw new Error(roomsData.error || 'Failed to load rooms');
                }

                this.rooms = roomsData.rooms;

                // Load availability for the week
                const startDate = this.formatDate(this.currentDate);
                const endDate = this.formatDate(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0));

                const availData = await apiRequest('availability', 'range', { start: startDate, end: endDate });

                if (!availData.success) {
                    throw new Error(availData.error || 'Failed to load availability');
                }

                this.availability = availData.availability || {};

                this.render();

            } catch (error) {
                console.error('Error loading data:', error);
                this.showError('Failed to load room availability data');
            } finally {
                this.showLoading(false);
            }
        }

        render() {
            this.updateMonthRange();
            this.renderTable();
        }

        renderTable() {
            // Clear existing content
            this.tableHeader.innerHTML = '<th class="date-col">Date</th>';
            this.tableBody.innerHTML = '';

            // Generate room headers
            this.rooms.forEach(room => {
                const th = document.createElement('th');
                th.className = 'room-header';
                th.innerHTML = `
                <strong>${this.escapeHtml(room.name)}</strong>
                ${room.capacity ? `<br><small>(${room.capacity} people)</small>` : ''}
            `;
                this.tableHeader.appendChild(th);
            });

            // Generate date rows for the entire month
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let i = 1; i <= daysInMonth; i++) {
                const date = new Date(year, month, i);
                const row = document.createElement('tr');
                const dateStr = this.formatDate(date);

                // Date name cell
                const dateCell = document.createElement('td');
                dateCell.className = 'date-col';
                const dayNum = date.getDate();
                const monthName = date.toLocaleDateString('en-US', { month: 'short' });
                const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
                dateCell.innerHTML = `${dayNum}, ${monthName}, <small>${dayName}</small>`;
                row.appendChild(dateCell);

                // Room cells for this date
                this.rooms.forEach(room => {
                    const cell = document.createElement('td');
                    cell.className = 'availability-cell';

                    if (!room.is_active) {
                        cell.classList.add('inactive');
                        cell.innerHTML = '<small>Inactive</small>';
                    } else {
                        const dayAvailability = this.availability[dateStr] || [];
                        const roomBooking = dayAvailability.find(a => a.room_id == room.id);

                        if (roomBooking) {
                            const isCompleted = roomBooking.status && roomBooking.status.toLowerCase() === 'completed';
                            cell.classList.add('booked');
                            if (isCompleted) {
                                cell.classList.add('completed');
                            }
                            cell.innerHTML = `
                            <div class="patient-info">${this.escapeHtml(roomBooking.patient_name || 'Unknown')}</div>
                            ${roomBooking.graft_count ? `<div class="graft-info">${roomBooking.graft_count} grafts</div>` : ''}
                            ${isCompleted ? '<div class="status-info">Completed</div>' : ''}
                        `;
                            cell.title = `Booked: ${roomBooking.patient_name}${isCompleted ? ' (Completed)' : ''}`;

                            // Only allow editing if not completed
                            if (!isCompleted) {
                                cell.addEventListener('click', () => this.handleCellClick(room.id, dateStr, roomBooking.surgery_id));
                            }
                        } else {
                            cell.classList.add('available');
                            cell.innerHTML = '<small>Available</small>';
                            cell.title = 'Available for booking';
                            cell.addEventListener('click', () => this.handleCellClick(room.id, dateStr));
                        }
                    }

                    row.appendChild(cell);
                });

                this.tableBody.appendChild(row);
            }
        }

        handleCellClick(roomId, date, surgeryId = null) {
            // Redirect to surgery form with pre-filled room and date, or edit existing surgery
            if (surgeryId) {
                window.location.href = `add_edit_surgery.php?id=${surgeryId}`;
            } else {
                window.location.href = `add_edit_surgery.php?room_id=${roomId}&date=${date}`;
            }
        }

        showLoading(show) {
            this.loadingSpinner.style.display = show ? 'block' : 'none';
        }

        showError(message) {
            // Simple error display - you can enhance this
            alert(message);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        new RoomAvailability();
    });
</script>

<?php require_once 'includes/footer.php'; ?>