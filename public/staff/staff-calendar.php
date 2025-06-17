<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: /auth/login.php');
    exit();
}
$staff_id = $_GET['id'] ?? null;
$page_title = "Staff Management";
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/staff-calendar.css">
<!-- Scheduler Section -->
<div id="scheduler-section" class="container-fluid my-4 pb-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-6 col-md-9 col-sm-10">
            <div class="card frosted ">
                <!-- Card Header with Navigation Controls -->
                <div class="card-header frosted">
                    <!-- Bottom Row: Navigation Controls and View Toggle -->
                    <div class=" justify-content-between">
                        <!-- Navigation Buttons - Responsive Row -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center my-2">
                            <span id="current-month" class="text-center  h4 fw-semibold mb-0 ms-3"></span>
                            <div class="w-md-auto" role="group">
                                <button id="prev-month" class="btn btn-outline-secondary flex-grow-1 flex-md-grow-0">
                                    <i class="fas fa-chevron-left me-1"></i>
                                    <span class="d-none d-sm-inline">Previous</span>
                                </button>
                                <button id="today-btn" class="btn btn-primary flex-grow-1 flex-md-grow-0">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    <span class="d-none d-sm-inline">Today</span>
                                </button>
                                <button id="next-month" class="btn btn-outline-secondary flex-grow-1 flex-md-grow-0">
                                    <span class="d-none d-sm-inline">Next</span>
                                    <i class="fas fa-chevron-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-body pt-0">
                    <!-- Informational Alert Box -->
                    <div class="alert text-center align-items-center justify-content-center gap-2  mb-0" role="alert">
                        <!-- View Toggle Buttons -->
                        <div class=" align-items-center ms-2 " role="group">
                            <input type="radio" class="btn-check" name="view-mode" id="calendar-view" autocomplete="off"
                                checked>
                            <label class="btn btn-outline-secondary" for="calendar-view">
                                <i class="fas fa-calendar me-1"></i>
                                <span class="d-none d-sm-inline">Calendar</span>
                            </label>
                            <input type="radio" class="btn-check" name="view-mode" id="list-view" autocomplete="off">
                            <label class="btn btn-outline-secondary " for="list-view">
                                <i class="fas fa-list me-1"></i>
                                <span class="d-none d-sm-inline">List</span>
                            </label>
                        </div>
                        <span class="fw-small">Selected: <span id="selected-count">0</span> days <small
                                class="d-none d-sm-inline">Please select your available days.</small></span>
                    </div>

                    <!-- Calendar View -->
                    <div id="calendar-container">
                        <div class="table-responsive">
                            <table class="table table-borderless text-center">
                                <thead class="text-uppercase small">
                                    <tr>
                                        <th>Mon</th>
                                        <th>Tue</th>
                                        <th>Wed</th>
                                        <th>Thu</th>
                                        <th>Fri</th>
                                        <th class="text-danger">Sat</th>
                                        <th class="text-danger">Sun</th>
                                    </tr>
                                </thead>
                                <tbody id="calendar-body"><!-- populated by JS --></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- List View -->
                    <div id="list-container" class="d-none border p-2 rounded">
                        <div id="list-body" class=""><!-- populated by JS --></div>
                    </div>
                    <!-- Legend -->
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4 small">
                        <div class="d-flex align-items-center gap-1"><span class="legend-box"
                                style="background: var(--color-primary);"></span><span>Available</span></div>
                        <div class="d-flex align-items-center gap-1"><span class="legend-box border"
                                style="background: transparent;"></span><span>Not Available</span></div>
                        <div class="d-flex align-items-center gap-1"><span class="legend-box"
                                style="background: rgba(220,53,69,0.8);"></span><span>Weekend</span></div>
                        <div class="d-flex align-items-center gap-1"><span class="legend-box"
                                style="background: var(--color-accent);"></span><span>Today</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Loading Spinner -->
<div id="loading" class="d-none position-fixed top-50 start-50 translate-middle">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
</div>
<!-- Performance Loading Indicator -->
<div id="loading-spinner" class="position-fixed top-50 start-50 translate-middle" style="display: none; z-index: 9999;">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading
            availability...</span></div>
</div>

<script>
    class StaffScheduler {
        constructor() {
            this.currentDate = new Date();
            this.availableDays = {};
            this.viewMode = 'calendar';

            // Performance optimizations
            this.cache = new Map(); // Cache for API responses
            this.domCache = new Map(); // Cache for DOM elements
            this.isLoading = false; // Prevent concurrent API calls
            this.pendingUpdates = new Set(); // Track pending UI updates
            this.debounceTimers = new Map(); // Debounce timers
            this.requestQueue = []; // Queue for batched requests

            // Pre-cache frequently used DOM elements
            this.cacheElements();
            this.init();
        }

        cacheElements() {
            const elements = [
                'current-month', 'calendar-body', 'list-body', 'selected-count',
                'calendar-container', 'list-container', 'prev-month', 'next-month',
                'today-btn', 'calendar-view', 'list-view', 'theme-btn'
            ];

            elements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    this.domCache.set(id, element);
                }
            });
        }

        getElement(id) {
            return this.domCache.get(id) || document.getElementById(id);
        }

        init() {
            this.setupEventListeners();
            this.setInitialViewMode();
            this.updateMonthDisplay();
            this.renderView();
            this.loadAvailability();
        }

        setInitialViewMode() {
            // Set view mode based on screen size
            // Small screens (< 768px): List view
            // Medium and larger screens (>= 768px): Calendar view
            const isSmallScreen = window.innerWidth < 768;
            this.viewMode = isSmallScreen ? 'list' : 'calendar';

            // Update radio buttons to reflect the selected view
            this.updateViewModeRadios();
        }

        updateViewModeRadios() {
            const calendarRadio = this.getElement('calendar-view');
            const listRadio = this.getElement('list-view');

            if (calendarRadio && listRadio) {
                if (this.viewMode === 'calendar') {
                    calendarRadio.checked = true;
                    listRadio.checked = false;
                } else {
                    calendarRadio.checked = false;
                    listRadio.checked = true;
                }
            }
        }

        setupEventListeners() {

            // Navigation buttons with cached elements
            const prevBtn = this.getElement('prev-month');
            const nextBtn = this.getElement('next-month');
            const todayBtn = this.getElement('today-btn');

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    this.goToPreviousMonth();
                }, { passive: true });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    this.goToNextMonth();
                }, { passive: true });
            }

            if (todayBtn) {
                todayBtn.addEventListener('click', () => {
                    this.goToToday();
                }, { passive: true });
            }

            // View mode toggle with cached elements
            const calendarView = this.getElement('calendar-view');
            const listView = this.getElement('list-view');

            if (calendarView) {
                calendarView.addEventListener('change', () => {
                    this.setViewMode('calendar');
                }, { passive: true });
            }

            if (listView) {
                listView.addEventListener('change', () => {
                    this.setViewMode('list');
                }, { passive: true });
            }

            // Handle window resize for responsive view switching
            let resizeTimer;
            window.addEventListener('resize', () => {
                // Debounce resize events
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    this.handleResponsiveViewChange();
                }, 150);
            }, { passive: true });
        }

        handleResponsiveViewChange() {
            const isSmallScreen = window.innerWidth < 768;
            const shouldBeListView = isSmallScreen;
            const shouldBeCalendarView = !isSmallScreen;

            // Only change view if it doesn't match the screen size preference
            // and user hasn't manually selected a different view recently
            if (shouldBeListView && this.viewMode === 'calendar') {
                this.setViewMode('list');
            } else if (shouldBeCalendarView && this.viewMode === 'list') {
                this.setViewMode('calendar');
            }
        }


        async loadAvailability() {
            // Prevent concurrent API calls
            if (this.isLoading) {
                return;
            }

            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const cacheKey = `${year}-${month}`;

            // Check cache first
            if (this.cache.has(cacheKey)) {
                const cachedData = this.cache.get(cacheKey);
                this.availableDays = { ...this.availableDays, ...cachedData };
                this.scheduleRender();
                return;
            }

            this.isLoading = true;
            this.showLoadingState(true);

            const startDate = new Date(year, month, 1);
            const endDate = new Date(year, month + 1, 0);
            const start = this.formatDate(startDate);
            const end = this.formatDate(endDate);

            try {
                const response = await apiRequest('staff_availability', 'byRange', {
                    start: start,
                    end: end,
                    id: <?php echo json_encode($staff_id); ?>
                });

                if (!response.success) {
                    throw new Error(response.error || 'Failed to load availability');
                }

                // Process and cache the response
                const monthData = {};
                if (response.availability && Array.isArray(response.availability)) {
                    response.availability.forEach(item => {
                        if (item.date) {
                            monthData[item.date] = true;
                        }
                    });
                }

                // Cache the data for this month
                this.cache.set(cacheKey, monthData);

                // Merge with existing data
                this.availableDays = { ...this.availableDays, ...monthData };

                this.scheduleRender();

            } catch (error) {
                console.error('Failed to load availability:', error);
                this.showError('Failed to load availability data. Please try again.');
            } finally {
                this.isLoading = false;
                this.showLoadingState(false);
            }
        }

        async toggleDayAvailability(dateStr) {
            // Debounce rapid clicks
            if (this.debounceTimers.has(dateStr)) {
                clearTimeout(this.debounceTimers.get(dateStr));
            }

            // Optimistic update - immediately update UI
            const previousState = this.availableDays[dateStr] || false;
            const newState = !previousState;
            this.availableDays[dateStr] = newState;

            // Update UI immediately for better perceived performance
            this.updateDayElement(dateStr, newState);
            this.updateSelectedCount();

            var payload = {
                date: dateStr
            };

            <?php if ($staff_id !== null): ?>
                payload.id = <?= json_encode($staff_id) ?>;
            <?php endif; ?>

            // Debounce the API call
            const timer = setTimeout(async () => {
                try {
                    const response = await apiRequest('staff_availability', 'toggleDay', payload);

                    if (!response.success) {
                        throw new Error(response.error || 'Failed to toggle availability');
                    }

                    // Verify the server state matches our optimistic update
                    if (this.availableDays[dateStr] !== response.isAvailable) {
                        this.availableDays[dateStr] = response.isAvailable;
                        this.updateDayElement(dateStr, response.isAvailable);
                        this.updateSelectedCount();
                    }

                    // Update cache
                    const year = this.currentDate.getFullYear();
                    const month = this.currentDate.getMonth();
                    const cacheKey = `${year}-${month}`;
                    if (this.cache.has(cacheKey)) {
                        const cachedData = this.cache.get(cacheKey);
                        cachedData[dateStr] = response.isAvailable;
                    }

                } catch (error) {
                    console.error('Failed to toggle day:', error);
                    // Revert optimistic update on error
                    this.availableDays[dateStr] = previousState;
                    this.updateDayElement(dateStr, previousState);
                    this.updateSelectedCount();
                    this.showError('Failed to update availability. Please try again.');
                } finally {
                    this.debounceTimers.delete(dateStr);
                }
            }, 150); // 150ms debounce

            this.debounceTimers.set(dateStr, timer);
        }

        showLoadingState(show) {
            const loadingElement = document.getElementById('loading-spinner');
            if (loadingElement) {
                loadingElement.style.display = show ? 'block' : 'none';
            }
        }

        scheduleRender() {
            // Use requestAnimationFrame for smooth rendering
            if (!this.pendingUpdates.has('render')) {
                this.pendingUpdates.add('render');
                requestAnimationFrame(() => {
                    this.renderView();
                    this.pendingUpdates.delete('render');
                });
            }
        }

        updateDayElement(dateStr, isAvailable) {
            // Fast update of individual day element without full re-render
            const dayElements = document.querySelectorAll(`[data-date="${dateStr}"]`);
            dayElements.forEach(element => {
                if (isAvailable) {
                    element.classList.add('available');
                } else {
                    element.classList.remove('available');
                }
            });
        }

        goToPreviousMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
            this.updateMonthDisplay();
            this.loadAvailability();
        }

        goToNextMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
            this.updateMonthDisplay();
            this.loadAvailability();
        }

        goToToday() {
            this.currentDate = new Date();
            this.updateMonthDisplay();
            this.loadAvailability();
        }

        setViewMode(mode) {
            this.viewMode = mode;
            this.updateViewModeRadios();
            this.renderView();
        }

        updateMonthDisplay() {
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            const monthText = `${monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
            const monthElement = this.getElement('current-month');
            if (monthElement) {
                monthElement.textContent = monthText;
            }
        }

        renderView() {
            const calendarContainer = this.getElement('calendar-container');
            const listContainer = this.getElement('list-container');

            if (this.viewMode === 'calendar') {
                this.renderCalendarView();
                calendarContainer?.classList.remove('d-none');
                listContainer?.classList.add('d-none');
            } else {
                this.renderListView();
                calendarContainer?.classList.add('d-none');
                listContainer?.classList.remove('d-none');
            }
            this.updateSelectedCount();
        }

        updateSelectedCount() {
            // Debounce count updates for better performance
            if (this.debounceTimers.has('count-update')) {
                clearTimeout(this.debounceTimers.get('count-update'));
            }

            const timer = setTimeout(() => {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                let selectedCount = 0;

                // Optimized counting with early exit for non-matching years
                for (const dateStr in this.availableDays) {
                    if (this.availableDays[dateStr]) {
                        // Quick year check before creating Date object
                        if (dateStr.startsWith(year.toString())) {
                            const date = new Date(dateStr);
                            if (date.getFullYear() === year && date.getMonth() === month) {
                                selectedCount++;
                            }
                        }
                    }
                }

                const countElement = this.getElement('selected-count');
                if (countElement) {
                    countElement.textContent = selectedCount;
                }

                this.debounceTimers.delete('count-update');
            }, 50); // 50ms debounce for count updates

            this.debounceTimers.set('count-update', timer);
        }

        renderCalendarView() {
            const calendarBody = this.getElement('calendar-body');
            if (!calendarBody) return;

            const days = this.getDaysInMonth();

            // Use DocumentFragment for better performance
            const fragment = document.createDocumentFragment();
            let week = document.createElement('tr');

            days.forEach((day, index) => {
                const cell = document.createElement('td');

                if (day === null) {
                    cell.innerHTML = '<button class="calendar-day empty"></button>';
                } else {
                    const dateStr = this.formatDate(day);
                    const isAvailable = this.availableDays[dateStr] || false;
                    const isToday = this.isToday(day);
                    const isWeekend = this.isWeekend(day);

                    const button = document.createElement('button');

                    // Build class list efficiently
                    const classes = ['calendar-day'];
                    if (isWeekend) classes.push('weekend');
                    else if (isAvailable) classes.push('available');
                    if (isToday) classes.push('today');

                    button.className = classes.join(' ');
                    button.textContent = day.getDate();
                    button.setAttribute('data-date', dateStr); // For fast updates

                    // Only add click event for non-weekend days
                    if (!isWeekend) {
                        button.addEventListener('click', () => this.toggleDayAvailability(dateStr), { passive: true });
                    } else {
                        button.disabled = true;
                        button.title = 'Weekend - Not available for work';
                    }

                    cell.appendChild(button);
                }

                week.appendChild(cell);

                if ((index + 1) % 7 === 0) {
                    fragment.appendChild(week);
                    week = document.createElement('tr');
                }
            });

            if (week.children.length > 0) {
                fragment.appendChild(week);
            }

            // Single DOM update
            calendarBody.innerHTML = '';
            calendarBody.appendChild(fragment);
        }

        renderListView() {
            const listBody = this.getElement('list-body');
            if (!listBody) return;

            const days = this.getDaysInMonth().filter(day => day !== null);

            // Use DocumentFragment for better performance
            const fragment = document.createDocumentFragment();

            // Pre-define constants to avoid repeated array access
            const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            days.forEach(day => {
                const dateStr = this.formatDate(day);
                const isAvailable = this.availableDays[dateStr] || false;
                const isToday = this.isToday(day);
                const isWeekend = this.isWeekend(day);

                const dayItem = document.createElement('div');

                // Build class list efficiently
                const classes = ['list-day-item'];
                if (isWeekend) classes.push('weekend');
                else if (isAvailable) classes.push('available');
                if (isToday) classes.push('today');

                dayItem.className = classes.join(' ');
                dayItem.setAttribute('data-date', dateStr); // For fast updates

                // Only add click event for non-weekend days
                if (!isWeekend) {
                    dayItem.addEventListener('click', () => this.toggleDayAvailability(dateStr), { passive: true });
                } else {
                    dayItem.style.cursor = 'not-allowed';
                    dayItem.title = 'Weekend - Not available for work';
                }

                const dayName = weekDays[day.getDay()];
                let statusIcon, statusColor;

                if (isWeekend) {
                    statusIcon = ' ✕';
                    statusColor = 'rgba(220,53,69,0.8)';
                } else {
                    statusIcon = isAvailable ? ' -' : ' +';
                    statusColor = isAvailable ? 'rgba(255,255,255,0.8)' : 'rgba(108,117,125,0.5)';
                }

                // Use template literal for better performance than innerHTML
                dayItem.innerHTML = `
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle mx-2" style="width: 12px; height: 12px; background-color: ${statusColor}"></div>
                            <div class="fw-semibold">
                                ${dayName}, ${day.toLocaleDateString('en-GB', { month: 'long', day: 'numeric' })} ${isToday ? '<div class="text-sm opacity-75">Today</div>' : ''}
                            </div>
                            <div class="text-lg fw-medium">
                                ${statusIcon}
                            </div>
                        </div>
                    </div>
                `;

                fragment.appendChild(dayItem);
            });

            // Single DOM update
            listBody.innerHTML = '';
            listBody.appendChild(fragment);
        }

        getDaysInMonth() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();

            // Convert Sunday-based day (0=Sunday, 1=Monday, ..., 6=Saturday)
            // to Monday-based day (0=Monday, 1=Tuesday, ..., 6=Sunday)
            let startingDayOfWeek = firstDay.getDay();
            startingDayOfWeek = startingDayOfWeek === 0 ? 6 : startingDayOfWeek - 1;

            const days = [];

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < startingDayOfWeek; i++) {
                days.push(null);
            }

            // Add all days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                days.push(new Date(year, month, day));
            }

            return days;
        }

        formatDate(date) {
            return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
        }

        formatMonth(date) {
            return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
        }

        isToday(date) {
            const today = new Date();
            return date.toDateString() === today.toDateString();
        }

        isWeekend(date) {
            const dayOfWeek = date.getDay();
            return dayOfWeek === 0 || dayOfWeek === 6; // Sunday (0) or Saturday (6)
        }

        showError(message) {
            // Simple error display - you can enhance this with a toast or modal
            alert(message);
        }
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', () => {
        new StaffScheduler();
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>