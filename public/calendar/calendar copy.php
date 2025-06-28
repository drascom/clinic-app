<?php
require_once '../includes/header.php';
$page_title = "Calendar";
?>
<link rel="stylesheet" href="/assets/css/calendar.css">
<!-- Calendar Container -->
<div class="calendar-container emp">
    <!-- Calendar Header -->
    <div class="calendar-header">
        <div class="calendar-nav">
            <button type="button" id="prevMonth">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" id="nextMonth">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button type="button" id="todayBtn">Today</button>
        </div>

        <h3 class="calendar-title" id="calendarTitle">May 2025</h3>

        <div class="view-toggle">
            <button type="button" id="monthViewBtn" class="active">Month</button>
            <button type="button" id="listMonthBtn">List Month</button>
            <button type="button" style="display:none;" id="listWeekBtn">List Week</button>
            <button type="button" style="display:none;" id="listDayBtn">List Day</button>
        </div>
        <div class="filter-dropdown">
            <select id="surgeryStatusFilter" class="form-select form-select-sm">
                <option value="all">All Surgeries</option>
                <option value="scheduled">Scheduled</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
            </select>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <!-- Empty State Message -->
    <div id="emptyStateMessage" class="empty-state-message" style="display: none;">
        <div class="empty-state-content">
            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">Welcome to Your Calendar</h4>
            <p class="text-muted mb-4">No surgeries or appointments scheduled yet. Get started by adding your first
                appointment or surgery.</p>
            <div class="empty-state-actions">
                <a href="../surgery/add_edit_surgery.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-1"></i>
                    Add Surgery
                </a>
                <a href="../appointment/edit_appointment.php" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-plus me-1"></i>
                    Add Appointment
                </a>
            </div>
        </div>
    </div>

    <!-- Calendar Grid View -->
    <div id="calendarView" class="calendar-view">
        <!-- Day Headers -->
        <div class="calendar-grid" id="calendarGrid">
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
            <div class="calendar-day-header">Sun</div>
        </div>
    </div>

    <!-- List View -->
    <div id="listView" class="list-view">
        <div id="listContent" class=""></div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Detailed list will be injected here -->
            </div>
        </div>
    </div>
</div>


<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>What would you like to add for the selected date?</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="addAppointmentBtn">
                        <i class="fas fa-calendar-plus me-1"></i> Add Appointment
                    </button>
                    <button type="button" class="btn btn-success" id="addSurgeryBtn">
                        <i class="fas fa-plus me-1"></i> Add Surgery
                    </button>
                    <button type="button" class="btn btn-danger" id="closeDayBtn">
                        <i class="fas fa-door-closed me-1"></i> Close Day
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    class CustomCalendar {
        constructor() {
            this.currentDate = new Date();
            this.currentView = 'month';
            this.events = {};
            this.closedDays = {};
            this.isLoading = false;
            this.selectedDate = null;
            this.currentSurgeryFilter = 'all';

            this.initializeElements();
            this.bindEvents();
            this.loadCalendarEvents();
        }

        initializeElements() {
            this.calendarTitle = document.getElementById('calendarTitle');
            this.calendarGrid = document.getElementById('calendarGrid');
            this.calendarView = document.getElementById('calendarView');
            this.listView = document.getElementById('listView');
            this.listContent = document.getElementById('listContent');
            this.loadingSpinner = document.getElementById('loadingSpinner');
            this.emptyStateMessage = document.getElementById('emptyStateMessage');

            // Navigation buttons
            this.prevBtn = document.getElementById('prevMonth');
            this.nextBtn = document.getElementById('nextMonth');
            this.todayBtn = document.getElementById('todayBtn');

            // View toggle buttons
            this.monthViewBtn = document.getElementById('monthViewBtn');
            this.listMonthBtn = document.getElementById('listMonthBtn');
            this.listWeekBtn = document.getElementById('listWeekBtn');
            this.listDayBtn = document.getElementById('listDayBtn');

            // Modals
            this.addEventModal = new bootstrap.Modal(document.getElementById('addEventModal'));
            this.detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            this.addAppointmentBtn = document.getElementById('addAppointmentBtn');
            this.addSurgeryBtn = document.getElementById('addSurgeryBtn');
            this.closeDayBtn = document.getElementById('closeDayBtn');

            // Filter
            this.surgeryStatusFilter = document.getElementById('surgeryStatusFilter');
        }

        bindEvents() {
            this.prevBtn.addEventListener('click', () => this.navigateMonth(-1));
            this.nextBtn.addEventListener('click', () => this.navigateMonth(1));
            this.todayBtn.addEventListener('click', () => this.goToToday());

            this.monthViewBtn.addEventListener('click', () => this.setView('month'));
            this.listMonthBtn.addEventListener('click', () => this.setView('listMonth'));
            this.listWeekBtn.addEventListener('click', () => this.setView('listWeek'));
            this.listDayBtn.addEventListener('click', () => this.setView('listDay'));

            this.addAppointmentBtn.addEventListener('click', () => this.addAppointment());
            this.addSurgeryBtn.addEventListener('click', () => this.addSurgery());
            this.closeDayBtn.addEventListener('click', () => this.closeDay());

            this.surgeryStatusFilter.addEventListener('change', (e) => {
                this.currentSurgeryFilter = e.target.value;
                this.render();
            });

            // Auto-switch to list view on mobile
            this.handleResize();
            window.addEventListener('resize', () => this.handleResize());
        }

        handleResize() {
            if (window.innerWidth < 768 && this.currentView === 'month') {
                this.setView('listWeek');
            }
        }

        async loadCalendarEvents() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.showLoading(true);
            this.showEmptyState(false);

            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth() + 1;

            try {
                await this.loadClosedDays();
                const data = await apiRequest('calendar_events', 'get', {
                    year: year,
                    month: month
                });

                if (data.success) {
                    this.events = data.events || {};
                } else {
                    console.error('Error fetching calendar events:', data.error);
                    this.showError('Failed to load calendar events');
                    this.events = {};
                }
                await this.render();
                this.checkAndShowEmptyState();
            } catch (error) {
                console.error('Error fetching data:', error);
                this.showError('Failed to load data');
            } finally {
                this.isLoading = false;
                this.showLoading(false);
            }
        }

        showLoading(show) {
            this.loadingSpinner.style.display = show ? 'flex' : 'none';
        }

        showEmptyState(show) {
            this.emptyStateMessage.style.display = show ? 'flex' : 'none';
            if (show) {
                this.calendarView.style.display = 'none';
                this.listView.style.display = 'none';
            }
        }

        checkAndShowEmptyState() {
            const hasEvents = this.events && Object.values(this.events).some(day => day.appointments.length > 0 || day
                .surgeries.length > 0);
            const year = this.currentDate.getFullYear();
            const month = String(this.currentDate.getMonth() + 1).padStart(2, '0');
            const monthPrefix = `${year}-${month}-`;
            const hasClosedDaysInMonth = Object.keys(this.closedDays).some(d => d.startsWith(monthPrefix));
            const hasAnyData = hasEvents || hasClosedDaysInMonth;

            if (!hasAnyData) {
                this.showEmptyState(true);
            } else {
                this.showEmptyState(false);
                if (this.currentView === 'month') {
                    this.calendarView.style.display = 'block';
                    this.listView.style.display = 'none';
                } else {
                    this.calendarView.style.display = 'none';
                    this.listView.style.display = 'block';
                }
            }
        }

        showError(message) {
            console.error(message);
        }

        async navigateMonth(direction) {
            this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            await this.loadCalendarEvents();
        }

        async goToToday() {
            this.currentDate = new Date();
            await this.loadCalendarEvents();
        }

        setView(view) {
            this.currentView = view;
            document.querySelectorAll('.view-toggle button').forEach(btn => btn.classList.remove('active'));
            if (view === 'month') this.monthViewBtn.classList.add('active');
            else if (view === 'listMonth') this.listMonthBtn.classList.add('active');
            else if (view === 'listWeek') this.listWeekBtn.classList.add('active');
            else if (view === 'listDay') this.listDayBtn.classList.add('active');
            this.render().then(() => this.checkAndShowEmptyState()).catch(console.error);
        }

        async render() {
            this.updateTitle();
            if (this.currentView === 'month') {
                await this.renderCalendarGrid();
            } else {
                this.renderListView();
            }
        }

        updateTitle() {
            const options = {
                year: 'numeric',
                month: 'long'
            };
            this.calendarTitle.textContent = this.currentDate.toLocaleDateString('en-GB', options);
        }

        async renderCalendarGrid() {
            const existingDays = this.calendarGrid.querySelectorAll('.calendar-day');
            existingDays.forEach(day => day.remove());

            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = (firstDay.getDay() + 6) % 7;
            const prevMonth = new Date(year, month, 0);
            const daysInPrevMonth = prevMonth.getDate();
            const totalCells = Math.ceil((daysInMonth + startingDayOfWeek) / 7) * 7;

            for (let i = 0; i < totalCells; i++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';

                let dayNumber, dayDate, isCurrentMonth = true;

                if (i < startingDayOfWeek) {
                    dayNumber = daysInPrevMonth - startingDayOfWeek + i + 1;
                    dayDate = new Date(year, month - 1, dayNumber);
                    isCurrentMonth = false;
                } else if (i >= startingDayOfWeek + daysInMonth) {
                    dayNumber = i - startingDayOfWeek - daysInMonth + 1;
                    dayDate = new Date(year, month + 1, dayNumber);
                    isCurrentMonth = false;
                } else {
                    dayNumber = i - startingDayOfWeek + 1;
                    dayDate = new Date(year, month, dayNumber);
                }

                if (!isCurrentMonth) {
                    dayElement.classList.add('other-month');
                }

                const today = new Date();
                if (dayDate.toDateString() === today.toDateString()) {
                    dayElement.classList.add('today');
                }

                const dayNumberEl = document.createElement('div');
                dayNumberEl.className = 'day-number';
                dayNumberEl.textContent = dayNumber;
                dayElement.appendChild(dayNumberEl);

                if (isCurrentMonth) {
                    const dateString = this.formatDateForAPI(dayDate);
                    const dayEvents = this.events[dateString];
                    const isClosed = this.closedDays[dateString];

                    if (isClosed) {
                        dayElement.classList.add('closed-day');

                        const reasonEl = document.createElement('div');
                        reasonEl.className = 'closed-day-reason';
                        reasonEl.textContent = isClosed.reason || 'Closed';
                        dayElement.appendChild(reasonEl);

                        const reopenIcon = document.createElement('div');
                        reopenIcon.className = 'reopen-day-icon';
                        reopenIcon.innerHTML = '<i class="fas fa-lock-open"></i>';
                        reopenIcon.title = 'Click to reopen this day';
                        reopenIcon.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.openDay(dateString);
                        });
                        dayElement.appendChild(reopenIcon);
                    } else {
                        if (dayEvents && (dayEvents.appointments.length > 0 || dayEvents.surgeries.length > 0)) {
                            const eventSummaryEl = document.createElement('div');
                            eventSummaryEl.className = 'event-summary d-flex flex-column';

                            if (dayEvents.appointments.length > 0) {
                                const appBtn = document.createElement('btn');
                                appBtn.className = 'event appointment d-flex align-items-center btn';
                                appBtn.innerHTML =
                                    `<i class="far fa-calendar me-2"></i><span class=" d-none d-sm-inline"> Appointment: </span> ${dayEvents.appointments.length} `;
                                appBtn.onclick = (e) => {
                                    e.stopPropagation();
                                    this.showDetailsModal('Appointments', dayDate, dayEvents.appointments);
                                };
                                eventSummaryEl.appendChild(appBtn);
                            }

                            if (dayEvents.surgeries.length > 0) {
                                const surgBtn = document.createElement('btn');
                                surgBtn.className = 'event surgery d-flex align-items-center btn ';
                                surgBtn.innerHTML =
                                    `<i class="fas fa-syringe me-2"></i><span class="d-none d-sm-inline"> Surgery: </span>${dayEvents.surgeries.length} `;
                                surgBtn.onclick = (e) => {
                                    e.stopPropagation();
                                    this.showDetailsModal('Surgeries', dayDate, dayEvents.surgeries);
                                };
                                eventSummaryEl.appendChild(surgBtn);
                            }

                            dayElement.appendChild(eventSummaryEl);
                        }

                        const addIcon = document.createElement('div');
                        addIcon.className = 'add-event-icon';
                        addIcon.innerHTML = '<i class="fas fa-plus"></i>';
                        addIcon.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.openAddEventModal(dateString);
                        });
                        dayElement.appendChild(addIcon);
                    }
                }

                this.calendarGrid.appendChild(dayElement);
            }
        }

        renderListView() {
            // Clear existing content
            this.listContent.innerHTML = '';
            this.listContent.className = 'p-4';

            const monthStart = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
            const monthEnd = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);

            const filteredEvents = Object.entries(this.events).filter(([dateStr, dayEvents]) => {
                const date = new Date(dateStr + 'T00:00:00');
                const hasEvents = dayEvents.appointments.length > 0 || dayEvents.surgeries.length > 0;
                const isClosed = this.closedDays[dateStr];
                return date >= monthStart && date <= monthEnd && (hasEvents || isClosed);
            });

            filteredEvents.sort(([dateA], [dateB]) => new Date(dateA) - new Date(dateB));

            if (filteredEvents.length === 0 && Object.keys(this.closedDays).length === 0) {
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'text-center py-5 text-muted';
                emptyMessage.textContent = 'No events or closed days found for this month.';
                this.listContent.appendChild(emptyMessage);
            } else {
                const allDays = {
                    ...this.events,
                    ...this.closedDays
                };
                const sortedDays = Object.keys(allDays).sort((a, b) => new Date(a) - new Date(b));

                for (const dateKey of sortedDays) {
                    const date = new Date(dateKey + 'T00:00:00');
                    if (date < monthStart || date > monthEnd) continue;

                    const dayEvents = this.events[dateKey] || {
                        appointments: [],
                        surgeries: []
                    };
                    const isClosed = this.closedDays[dateKey];

                    const dayName = date.toLocaleDateString('en-US', {
                        weekday: 'long'
                    });
                    const formattedDate = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    const dateDiv = document.createElement('div');
                    dateDiv.className = 'list-date';
                    dateDiv.innerHTML = `<span>${formattedDate}</span><span class="text-primary">${dayName}</span>`;
                    this.listContent.appendChild(dateDiv);

                    if (isClosed) {
                        const closedDiv = document.createElement('div');
                        closedDiv.className = 'list-item-summary d-flex align-items-center text-danger';
                        closedDiv.innerHTML = `<i class="fas fa-door-closed me-2"></i> Day Closed: ${isClosed.reason || ''}`;
                        this.listContent.appendChild(closedDiv);
                    }

                    const summaryContainer = document.createElement('div');
                    summaryContainer.className = 'list-item-summary d-flex';

                    if (dayEvents.appointments.length > 0) {
                        const appBtn = document.createElement('span');
                        appBtn.className = 'event appointment d-flex align-items-center mx-2';
                        appBtn.innerHTML =
                            `<i class="far fa-calendar me-1"></i>${dayEvents.appointments.length} Appointments`;
                        appBtn.onclick = (e) => {
                            e.stopPropagation();
                            this.showDetailsModal('Appointments', dateKey, dayEvents.appointments);
                        };
                        summaryContainer.appendChild(appBtn);
                    }

                    if (dayEvents.surgeries.length > 0) {
                        const surgBtn = document.createElement('span');
                        surgBtn.className = 'event surgery  d-flex align-items-center mx-2';
                        surgBtn.innerHTML =
                            `<i class="fas fa-syringe me-1"></i>${dayEvents.surgeries.length} Surgeries`;
                        surgBtn.onclick = (e) => {
                            e.stopPropagation();
                            this.showDetailsModal('Surgeries', dateKey, dayEvents.surgeries);
                        };
                        summaryContainer.appendChild(surgBtn);
                    }

                    if (summaryContainer.children.length > 0) {
                        this.listContent.appendChild(summaryContainer);
                    }
                }
            }
        }

        showDetailsModal(title, date, items) {
            const modalTitle = document.getElementById('detailsModalLabel');
            const modalBody = document.getElementById('detailsModalBody');
            const dateObject = (date instanceof Date) ? date : new Date(date + 'T00:00:00');
            const formattedDate = dateObject.toLocaleDateString('en-GB', {
                weekday: 'short',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            modalTitle.textContent = `${title} for ${formattedDate}`;

            if (!items || items.length === 0) {
                modalBody.innerHTML = '<p>No items to display.</p>';
                this.detailsModal.show();
                return;
            }

            let listHtml = '<ul class="list-group">';
            console.log("Items:", items); // Log the items array to console
            items.forEach(item => {

                const isAppointment = title.toLowerCase().includes('appointment');
                const link = isAppointment ?
                    `../appointment/edit_appointment.php?id=${item.id}` :
                    `../surgery/add_edit_surgery.php?id=${item.id}`;

                listHtml += `
                    <a href="${link}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <h6 class="mb-1">${item.patient_name}</h6>
                            ${isAppointment ? `<small class="text-muted">${item.start_time} - ${item.end_time}</small>` : `<span class="badge bg-info">${item.status}</span>`}
                        </div>
                        ${isAppointment ? `<span class="mb-1 me-2"><i class="fas fa-tag me-1"></i> ${item.procedure_type === 'treatments' ? `Procedure: ${item.procedure_name}` : 'Consultation'} </span>` : ''}
                        ${isAppointment && item.type ? `<span class="mb-1"><i class="fas fa-info-circle me-1"></i> Type: ${item.type}</span>` : ''}
                        ${!isAppointment && item.predicted_grafts_count ? `<span class="mb-1 me-2"><i class="fas fa-microscope me-1"></i> Predicted Grafts: ${item.predicted_grafts_count}</span>` : ''}
                        ${!isAppointment && item.current_grafts_count ? `<span class="mb-1 me-2"><i class="fas fa-microscope me-1"></i> Current Grafts: ${item.current_grafts_count}</span>` : ''}
                        ${item.notes ? `<p class="mb-1 text-muted">Notes: ${item.notes}</p>` : ''}
                        ${!isAppointment && item.is_recorded ? `<p class="mb-1 text-success"><i class="fas fa-copy me-1"></i> Copied from excel</p>` : ''}
                    </a>`;
            });
            listHtml += '</ul>';

            modalBody.innerHTML = listHtml;
            this.detailsModal.show();
        }

        formatDateForAPI(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        openAddEventModal(date) {
            this.selectedDate = date;
            this.addEventModal.show();
        }

        addAppointment() {
            if (this.selectedDate) {
                window.location.href = `../appointment/add_appointment.php?date=${this.selectedDate}`;
            }
        }

        addSurgery() {
            if (this.selectedDate) {
                window.location.href = `../surgery/add_edit_surgery.php?date=${this.selectedDate}`;
            }
        }

        async loadClosedDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth() + 1;
            try {
                const data = await apiRequest('closed_days', 'list', {
                    year: year,
                    month: month
                });
                if (data.success) {
                    this.closedDays = {};
                    data.closed_days.forEach(d => {
                        this.closedDays[d.date] = d;
                    });
                } else {
                    console.error('Error fetching closed days:', data.error);
                    this.showError('Failed to load closed days');
                    this.closedDays = {};
                }
            } catch (error) {
                console.error('Error fetching closed days data:', error);
                this.showError('Failed to load closed days data');
            }
        }

        async closeDay() {
            if (!this.selectedDate) return;

            const reason = prompt("Enter a reason for closing the day (optional):");
            if (reason === null) {
                return;
            }

            this.addEventModal.hide();
            this.showLoading(true);

            try {
                const response = await apiRequest('closed_days', 'add', {
                    date: this.selectedDate,
                    reason: reason
                });

                if (response.success) {
                    await this.loadCalendarEvents();
                } else {
                    this.showError(response.message || 'Failed to close day.');
                }
            } catch (error) {
                console.error('Error closing day:', error);
                this.showError('An error occurred while closing the day.');
            } finally {
                this.showLoading(false);
            }
        }

        async openDay(dateString) {
            if (!confirm(`Are you sure you want to reopen ${dateString}?`)) {
                return;
            }

            this.showLoading(true);
            try {
                const response = await apiRequest('closed_days', 'delete', {
                    date: dateString
                });

                if (response.success) {
                    await this.loadCalendarEvents();
                } else {
                    this.showError(response.message || 'Failed to reopen day.');
                }
            } catch (error) {
                console.error('Error reopening day:', error);
                this.showError('An error occurred while reopening the day.');
            } finally {
                this.showLoading(false);
            }
        }
    }

    // Helper function to get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // Initialize calendar when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        new CustomCalendar();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>