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
                <a href="../appointment/add_appointment.php" class="btn btn-outline-primary">
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
        <div id="listContent"></div>
    </div>
</div>


<!-- Room Details Modal -->
<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-labelledby="roomDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomDetailsModalLabel">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="roomDetailsModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    class CustomCalendar {
        constructor() {
            this.currentDate = new Date();
            this.currentView = 'month';
            this.surgeries = [];
            this.isLoading = false;

            this.initializeElements();
            this.bindEvents();
            this.loadSurgeries();
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
        }

        bindEvents() {
            this.prevBtn.addEventListener('click', () => this.navigateMonth(-1));
            this.nextBtn.addEventListener('click', () => this.navigateMonth(1));
            this.todayBtn.addEventListener('click', () => this.goToToday());

            this.monthViewBtn.addEventListener('click', () => this.setView('month'));
            this.listMonthBtn.addEventListener('click', () => this.setView('listMonth'));
            this.listWeekBtn.addEventListener('click', () => this.setView('listWeek'));
            this.listDayBtn.addEventListener('click', () => this.setView('listDay'));

            // Auto-switch to list view on mobile
            this.handleResize();
            window.addEventListener('resize', () => this.handleResize());
        }

        handleResize() {
            if (window.innerWidth < 768 && this.currentView === 'month') {
                this.setView('listWeek');
            }
        }

        async loadSurgeries() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.showLoading(true);
            this.showEmptyState(false); // Hide empty state while loading

            try {
                // Load surgeries and rooms in parallel
                const [surgeriesData, roomsData] = await Promise.all([
                    apiRequest('surgeries', 'list'),
                    apiRequest('rooms', 'list')
                ]);

                if (surgeriesData.success) {
                    this.surgeries = surgeriesData.surgeries || [];
                } else {
                    console.error('Error fetching surgeries:', surgeriesData.error);
                    this.showError('Failed to load surgeries');
                }

                if (roomsData.success) {
                    this.rooms = roomsData.rooms || [];
                } else {
                    console.error('Error fetching rooms:', roomsData.error);
                    this.showError('Failed to load rooms');
                }

                // Load appointment summaries for the current month
                await this.loadMonthAppointments();

                await this.render();

                // Check if we should show empty state
                this.checkAndShowEmptyState();
            } catch (error) {
                console.error('Error fetching data:', error);
                this.showError('Failed to load data');
            } finally {
                this.isLoading = false;
                this.showLoading(false);
            }
        }

        async loadMonthAppointments() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth() + 1; // JavaScript months are 0-indexed

            try {
                const data = await apiRequest('calendar_summary', 'get', {
                    year: year,
                    month: month
                });

                if (data.success) {
                    this.monthAppointments = data.appointments || {};
                    this.monthSurgeries = data.surgeries || {};
                } else {
                    console.error('Error fetching month appointments:', data.error);
                    this.monthAppointments = {};
                    this.monthSurgeries = {};
                }
            } catch (error) {
                console.error('Error fetching month appointments:', error);
                this.monthAppointments = {};
                this.monthSurgeries = {};
            }
        }

        showLoading(show) {
            this.loadingSpinner.style.display = show ? 'flex' : 'none';
        }

        showEmptyState(show) {
            this.emptyStateMessage.style.display = show ? 'flex' : 'none';
            // Hide calendar views when showing empty state
            if (show) {
                this.calendarView.style.display = 'none';
                this.listView.style.display = 'none';
            }
        }

        checkAndShowEmptyState() {
            // Check if there are any surgeries or appointments
            const hasSurgeries = this.surgeries && this.surgeries.length > 0;
            const hasAppointments = this.monthAppointments && Object.keys(this.monthAppointments).length > 0;
            const hasMonthSurgeries = this.monthSurgeries && Object.keys(this.monthSurgeries).length > 0;

            const hasAnyData = hasSurgeries || hasAppointments || hasMonthSurgeries;

            if (!hasAnyData) {
                this.showEmptyState(true);
            } else {
                this.showEmptyState(false);
                // Show the appropriate view
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
            // You can implement error display here
            console.error(message);
        }

        getAppointmentSummary(roomId, date) {
            const key = `${roomId}-${date}`;

            // Get appointment data from cached month data
            const appointmentData = this.monthAppointments[key] || {
                consult_count: 0,
                cosmetic_count: 0
            };

            // Get surgery data from cached month data
            const surgeryData = this.monthSurgeries[key] || null;

            return {
                consult_count: appointmentData.consult_count,
                cosmetic_count: appointmentData.cosmetic_count,
                surgery: surgeryData ? true : false,
                surgery_label: surgeryData ? surgeryData.patient_name : null
            };
        }

        async navigateMonth(direction) {
            this.currentDate.setMonth(this.currentDate.getMonth() + direction);
            await this.loadMonthAppointments();
            await this.render();
            this.checkAndShowEmptyState();
        }

        async goToToday() {
            this.currentDate = new Date();
            await this.loadMonthAppointments();
            await this.render();
            this.checkAndShowEmptyState();
        }

        setView(view) {
            this.currentView = view;

            // Update button states
            document.querySelectorAll('.view-toggle button').forEach(btn => {
                btn.classList.remove('active');
            });

            if (view === 'month') {
                this.monthViewBtn.classList.add('active');
            } else {
                if (view === 'listMonth') this.listMonthBtn.classList.add('active');
                else if (view === 'listWeek') this.listWeekBtn.classList.add('active');
                else if (view === 'listDay') this.listDayBtn.classList.add('active');
            }

            this.render().then(() => {
                this.checkAndShowEmptyState();
            }).catch(console.error);
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
            // Clear existing days (keep headers)
            const existingDays = this.calendarGrid.querySelectorAll('.calendar-day');
            existingDays.forEach(day => day.remove());

            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            // Adjust starting day of week for Monday start (0=Sun, 1=Mon, ..., 6=Sat)
            const startingDayOfWeek = (firstDay.getDay() + 6) % 7;

            // Get previous month's last days
            const prevMonth = new Date(year, month, 0);
            const daysInPrevMonth = prevMonth.getDate();

            // Calculate total cells needed
            const totalCells = Math.ceil((daysInMonth + startingDayOfWeek) / 7) * 7;

            for (let i = 0; i < totalCells; i++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';

                let dayNumber, dayDate, isCurrentMonth = true;

                if (i < startingDayOfWeek) {
                    // Previous month days
                    dayNumber = daysInPrevMonth - startingDayOfWeek + i + 1;
                    dayDate = new Date(year, month - 1, dayNumber);
                    isCurrentMonth = false;
                } else if (i >= startingDayOfWeek + daysInMonth) {
                    // Next month days
                    dayNumber = i - startingDayOfWeek - daysInMonth + 1;
                    dayDate = new Date(year, month + 1, dayNumber);
                    isCurrentMonth = false;
                } else {
                    // Current month days
                    dayNumber = i - startingDayOfWeek + 1;
                    dayDate = new Date(year, month, dayNumber);
                }

                if (!isCurrentMonth) {
                    dayElement.classList.add('other-month');
                }

                // Check if it's today
                const today = new Date();
                if (dayDate.toDateString() === today.toDateString()) {
                    dayElement.classList.add('today');
                }

                // Create day content
                const dayNumberEl = document.createElement('div');
                dayNumberEl.className = 'day-number';
                dayNumberEl.textContent = dayNumber;

                // Create room slots container
                const roomSlotsEl = document.createElement('div');
                roomSlotsEl.className = 'room-slots';

                // Add room slots for current month days only
                if (isCurrentMonth && this.rooms) {
                    for (const room of this.rooms) {
                        if (room.is_active) {
                            const roomSlot = document.createElement('div');
                            roomSlot.className = 'room-slot-container';
                            roomSlot.dataset.roomId = room.id;
                            roomSlot.dataset.date = this.formatDateForAPI(dayDate);

                            // Get appointment summary for this room and date
                            const summary = this.getAppointmentSummary(room.id, this.formatDateForAPI(dayDate));

                            // Get surgery for this room and date
                            const roomSurgery = this.getSurgeryForRoomAndDate(room.id, dayDate);

                            // Determine if room has any activity
                            const hasActivity = roomSurgery || summary.consult_count > 0 || summary.cosmetic_count > 0;

                            if (hasActivity) {
                                roomSlot.classList.add('booked');

                                // Check user role for display content
                                const userRole = getCookie('user_role') || '<?php echo get_user_role(); ?>';

                                if (userRole === 'agent') {
                                    // For agents, check if surgery belongs to their agency
                                    const userAgencyId = '<?php echo get_user_agency_id(); ?>';

                                    // If there's a surgery, check if agent can view it
                                    if (roomSurgery) {
                                        const canViewSurgery = this.canAgentViewSurgery(roomSurgery, userAgencyId);

                                        if (canViewSurgery) {
                                            // Agent can see this surgery - show details
                                            roomSlot.innerHTML = this.createCombinedSlotContent(room, {
                                                consult_count: summary.consult_count,
                                                cosmetic_count: summary.cosmetic_count,
                                                surgery: roomSurgery,
                                                surgery_label: `${roomSurgery.patient_name}`
                                            });

                                            // Make it clickable to view details
                                            roomSlot.addEventListener('click', (e) => {
                                                e.preventDefault();
                                                this.openRoomDetailsModal(room.id, this.formatDateForAPI(
                                                    dayDate), room.name);
                                            });
                                        } else {
                                            // Agent cannot see this surgery - show "Not Available"
                                            roomSlot.innerHTML = this.createNotAvailableSlotContent(room);
                                            // No click event for agents on other agency's slots
                                        }
                                    } else {
                                        // No surgery but has other activities (consultations/cosmetic)
                                        // For now, show the activities (agents can see consultations/cosmetic)
                                        roomSlot.innerHTML = this.createCombinedSlotContent(room, {
                                            consult_count: summary.consult_count,
                                            cosmetic_count: summary.cosmetic_count,
                                            surgery: null,
                                            surgery_label: null
                                        });

                                        // Make it clickable to view details
                                        roomSlot.addEventListener('click', (e) => {
                                            e.preventDefault();
                                            this.openRoomDetailsModal(room.id, this.formatDateForAPI(dayDate),
                                                room.name);
                                        });
                                    }
                                } else {
                                    // For admin/editor, show full details
                                    roomSlot.innerHTML = this.createCombinedSlotContent(room, {
                                        consult_count: summary.consult_count,
                                        cosmetic_count: summary.cosmetic_count,
                                        surgery: roomSurgery,
                                        surgery_label: roomSurgery ? `${roomSurgery.patient_name}` : null
                                    });

                                    // Make it clickable to view details
                                    roomSlot.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        this.openRoomDetailsModal(room.id, this.formatDateForAPI(dayDate), room
                                            .name);
                                    });
                                }
                            } else {
                                // Room is available - show empty slot
                                roomSlot.classList.add('available');
                                roomSlot.innerHTML = this.createEmptySlotContent(room);

                                // Make it clickable to open add surgery form
                                roomSlot.addEventListener('click', (e) => {
                                    e.preventDefault();

                                    const dateForAPI = this.formatDateForAPI(dayDate);
                                    if (room.types === 'surgery') {
                                        this.openSurgeryForm(room.id, dateForAPI);
                                    } else {
                                        this.openAppointmentForm(room.id, dateForAPI, room.types);
                                    }
                                });
                            }

                            roomSlotsEl.appendChild(roomSlot);
                        }
                    }
                }

                dayElement.appendChild(dayNumberEl);
                dayElement.appendChild(roomSlotsEl);
                this.calendarGrid.appendChild(dayElement);
            }
        }

        renderListView() {
            let surgeries = [...this.surgeries];
            const now = new Date();

            // Filter surgeries based on view type
            if (this.currentView === 'listWeek') {
                const weekStart = new Date(this.currentDate);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);

                surgeries = surgeries.filter(surgery => {
                    const surgeryDate = new Date(surgery.date);
                    return surgeryDate >= weekStart && surgeryDate <= weekEnd;
                });
            } else if (this.currentView === 'listDay') {
                const dayStart = new Date(this.currentDate);
                dayStart.setHours(0, 0, 0, 0);
                const dayEnd = new Date(dayStart);
                dayEnd.setDate(dayEnd.getDate() + 1);

                surgeries = surgeries.filter(surgery => {
                    const surgeryDate = new Date(surgery.date);
                    return surgeryDate >= dayStart && surgeryDate < dayEnd;
                });
            } else if (this.currentView === 'listMonth') {
                const monthStart = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                const monthEnd = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);

                surgeries = surgeries.filter(surgery => {
                    const surgeryDate = new Date(surgery.date);
                    return surgeryDate >= monthStart && surgeryDate <= monthEnd;
                });
            }

            // Sort surgeries by date
            surgeries.sort((a, b) => new Date(a.date) - new Date(b.date));

            // Group surgeries by date
            const groupedSurgeries = {};
            surgeries.forEach(surgery => {
                const dateKey = surgery.date;
                if (!groupedSurgeries[dateKey]) {
                    groupedSurgeries[dateKey] = [];
                }
                groupedSurgeries[dateKey].push(surgery);
            });

            // Render grouped surgeries
            let html = '';

            if (Object.keys(groupedSurgeries).length === 0) {
                html = '<div class="text-center py-5 text-muted">No surgeries found for this period.</div>';
            } else {
                Object.keys(groupedSurgeries).forEach(dateKey => {
                    const date = new Date(dateKey);
                    const dayName = date.toLocaleDateString('en-US', {
                        weekday: 'long'
                    });
                    const formattedDate = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    html += `
                    <div class="list-date">
                        <span>${formattedDate}</span>
                        <span class="text-primary">${dayName}</span>
                    </div>
                `;

                    groupedSurgeries[dateKey].forEach(surgery => {
                        html += `
                        <div class="list-item">
                            <div class="list-time">8:00am - 6:00pm</div>
                            <div class="list-patient">
                                <a href="patient.php?id=${surgery.patient_id}" class="text-decoration-none text-dark">
                                    ${surgery.patient_name}
                                </a>
                            </div>
                            <div class="list-details">
                                <span class="me-3">Graft: ${surgery.graft_count || 'N/A'}</span>
                                ${surgery.room_name ? `<span class="me-3 text-primary fw-bold">Room: ${surgery.room_name}</span>` : ''}
                                ${surgery.agency_name ? `<span class="me-3">Agency: ${surgery.agency_name}</span>` : ''}
                                <span class="status-badge ${surgery.status}">${surgery.status}</span>
                            </div>
                        </div>
                    `;
                    });
                });
            }

            this.listContent.innerHTML = html;
        }

        getSurgeriesForDate(date) {
            // Use local date string to avoid timezone issues
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateString = `${year}-${month}-${day}`;
            return this.surgeries.filter(surgery => surgery.date === dateString);
        }

        getSurgeryForRoomAndDate(roomId, date) {
            const dateString = this.formatDateForAPI(date);
            return this.surgeries.find(surgery =>
                surgery.room_id == roomId && surgery.date === dateString
            );
        }

        formatDateForAPI(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        openSurgeryForm(roomId, date) {
            // Check user role and redirect accordingly
            const userRole = getCookie('user_role') || '<?php echo get_user_role(); ?>';

            if (userRole === 'agent') {
                // Agents use the quick add surgery form
                const url = `../surgery/quick_add_surgery.php?room_id=${roomId}&date=${date}`;
                window.location.href = url;
            } else {
                // Admin/Editor use the full add surgery form
                const url = `../surgery/add_edit_surgery.php?room_id=${roomId}&date=${date}`;
                window.location.href = url;
            }
        }
        openAppointmentForm(roomId, date, roomType) {
            // Open add appointment form with pre-selected room, date, and type
            let url = `../appointment/add_appointment.php?room_id=${roomId}&date=${date}`;
            if (roomType) {
                url += `&request=${encodeURIComponent(roomType)}`;
            }
            window.location.href = url;
        }

        createSurgerySlotContent(room, surgery) {
            return `
            <div class="room-badge">${room.name} ----</div>
            <div class="surgery-content">
                <div class="surgery-header">
                    <span class="status-badge ${surgery.status}"></span>
                    <span class="patient-name">${surgery.patient_name}</span>
                </div>
                ${surgery.graft_count ? `<div class="graft-count">${surgery.graft_count} grafts</div>` : ''}
                ${surgery.technician_names ? `<div class="technician-names">${surgery.technician_names}</div>` : ''}
                ${surgery.agency_name ? `<div class="agency-name">${surgery.agency_name} -  ${surgery.status}</div>` : ''}
            </div>
        `;
        }

        createEmptySlotContent(room) {
            return `
            <div class="room-badge available">${room.name}</div>
            <div class="empty-slot">
            </div>
        `;
        }

        createNotAvailableSlotContent(room) {
            return `
            <div class="room-badge booked">${room.name}</div>
            <div class="not-available-slot">
                <div class="not-available-text">Not Available</div>
            </div>
        `;
        }

        canAgentViewSurgery(surgery, userAgencyId) {
            // If no surgery, agent can't view anything
            if (!surgery) {
                return false;
            }

            // If no agency ID for user, can't determine access
            if (!userAgencyId) {
                return false;
            }

            // Check if surgery belongs to agent's agency
            // Convert both to strings for comparison to avoid type issues

            if (surgery.agency_id && String(surgery.agency_id) === String(userAgencyId)) {
                return true;
            }

            return false;
        }

        formatDate(dateString) {
            const options = {
                day: '2-digit',
                month: 'short',
                year: '2-digit'
            };
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', options).replace(/\//g, ' / ');
        }

        createCombinedSlotContent(room, summary) {
            let content = `<div class="room-badge">${room.name}</div>`;
            content += `<div class="appointment-summary">`;

            if (summary.surgery) {
                content += `<div class="appointment-type surgery">Surgery: ${summary.surgery_label}</div>`;
            }

            if (summary.consult_count > 0) {
                content += `<div class="appointment-type consult">Consultations: ${summary.consult_count}</div>`;
            }

            if (summary.cosmetic_count > 0) {
                content += `<div class="appointment-type cosmetic">Cosmetics: ${summary.cosmetic_count}</div>`;
            }

            content += `</div>`;
            return content;
        }

        async openRoomDetailsModal(roomId, date, roomName) {
            try {
                // Show loading in modal
                const modal = new bootstrap.Modal(document.getElementById('roomDetailsModal'));
                const modalTitle = document.getElementById('roomDetailsModalLabel');
                const modalBody = document.getElementById('roomDetailsModalBody');

                modalTitle.textContent = `${roomName} - ${this.formatDisplayDate(date)}`;
                modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

                modal.show();

                // Fetch detailed appointment data
                const data = await apiRequest('calendar_details', 'get', {
                    room_id: roomId,
                    date: date
                });

                if (data.success) {
                    modalBody.innerHTML = this.createModalContent(data);
                } else {
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load room details</div>';
                }
            } catch (error) {
                console.error('Error loading room details:', error);
                document.getElementById('roomDetailsModalBody').innerHTML =
                    '<div class="alert alert-danger">Error loading room details</div>';
            }
        }

        createModalContent(data) {
            let content = '';

            // Surgery section
            if (data.surgery) {
                content += `
                <div class="mb-4">
                    <h6 class="text-primary">Hair Transplant Surgery</h6>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-1"><strong>Patient:</strong> ${data.surgery.patient_name}</p>
                            <p class="mb-1"><strong>Procedure:</strong> ${data.surgery.procedure}</p>
                            <p class="mb-1"><strong>Graft Count:</strong> ${data.surgery.graft_count || 'N/A'}</p>
                            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-primary">${data.surgery.status}</span></p>
                            <p class="mb-1"><strong>Time:</strong> ${data.surgery.time}</p>
            `;

                // Add technicians if available
                if (data.surgery.technicians && data.surgery.technicians.length > 0) {
                    content += `
                    <p class="mb-0"><strong>Assigned Technicians:</strong></p>
                    <div class="mt-1">
                `;
                    data.surgery.technicians.forEach(tech => {
                        content += `<span class="badge bg-secondary me-1">${tech}</span>`;
                    });
                    content += `</div>`;
                } else {
                    content +=
                        `<p class="mb-0"><strong>Technicians:</strong> <span class="text-muted">None assigned</span></p>`;
                }

                content += `
                        </div>
                    </div>
                </div>
            `;
            }

            // Consultations section
            if (data.consult && data.consult.length > 0) {
                content += `
                <div class="mb-4">
                    <h6 class="text-info">Consultations (${data.consult.length})</h6>
                    <div class="list-group">
            `;
                data.consult.forEach(consult => {
                    content += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${consult.name}</strong>
                                ${consult.subtype ? `<br><small class="text-muted">${consult.subtype}</small>` : ''}
                            </div>
                            <span class="badge bg-info">${consult.start_time} - ${consult.end_time}</span>
                        </div>
                    </div>
                `;
                });
                content += `</div></div>`;
            }

            // Cosmetic procedures section
            if (data.cosmetic && data.cosmetic.length > 0) {
                content += `
                <div class="mb-4">
                    <h6 class="text-success">Cosmetic Procedures (${data.cosmetic.length})</h6>
                    <div class="list-group">
            `;
                data.cosmetic.forEach(cosmetic => {
                    content += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${cosmetic.name}</strong>
                                ${cosmetic.subtype ? `<br><small class="text-muted">${cosmetic.subtype}</small>` : ''}
                            </div>
                            <span class="badge bg-success">${cosmetic.start_time} - ${cosmetic.end_time}</span>
                        </div>
                    </div>
                `;
                });
                content += `</div></div>`;
            }

            if (!content) {
                content =
                    '<div class="text-center text-muted">No appointments scheduled for this room on this date.</div>';
            }

            return content;
        }

        formatDisplayDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
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