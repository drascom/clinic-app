## Analysis Report: `public/calendar/calendar.php`

**Date of Analysis:** 17/06/2025

### 1. Introduction

The `public/calendar/calendar.php` file implements a dynamic calendar interface for managing appointments and surgeries. It is part of a larger "Surgery Patient Management" system. The calendar allows users to view events by month, navigate through dates, filter events, and add new appointments or surgeries.

**Technologies Used:**
*   **Frontend:** PHP, HTML, CSS, JavaScript (ES6+)
*   **JavaScript Libraries/Frameworks:** Bootstrap 5 (for UI components like modals, buttons, layout), Font Awesome (for icons).
*   **Styling:** Custom CSS (`/assets/css/calendar.css`)
*   **API Communication:** Custom `apiRequest` function in `/assets/js/api-helper.js` for secure POST requests to `/api.php`.

### 2. Architecture Overview

*   **PHP (`calendar.php`):**
    *   Includes `header.php` and `footer.php` for common layout and dependencies.
    *   Sets the page title.
    *   Defines the basic HTML structure for the calendar container, header, views, and modals.
*   **JavaScript (`CustomCalendar` class within `calendar.php`):**
    *   Manages all client-side calendar logic.
    *   Initializes UI elements and event listeners.
    *   Fetches event data from the backend API (`calendar_events` entity).
    *   Renders the calendar grid (month view) and list view.
    *   Handles user interactions (navigation, view changes, filtering, modal operations).
*   **CSS (`/assets/css/calendar.css`):**
    *   Provides all styling for the calendar, including layout, typography, colors, responsive adjustments, and styling for different states (e.g., today, other-month, loading, empty).
*   **API Helper (`/assets/js/api-helper.js`):**
    *   Provides a global `apiRequest` function to make POST requests to `/api.php`. This is used to fetch calendar events.
*   **Includes (`/includes/header.php`, `/includes/footer.php`):**
    *   Load global dependencies: Bootstrap CSS/JS, Font Awesome, custom stylesheets (`style.css`, `staff-calendar.css`), `api-helper.js`, `script.js`, and potentially others like Dropzone, Tom-Select, Moment.js (though not all are directly used by `calendar.php`).
    *   Handle authentication and provide navigation menus (different for staff/non-staff).

### 3. Key Features

*   **Dynamic Calendar Views:**
    *   **Month Grid View:** Displays a traditional monthly calendar grid.
    *   **List View:** Displays events in a chronological list (primarily for mobile and specific list views).
*   **Navigation:**
    *   Previous/Next month.
    *   "Today" button to jump to the current month.
*   **Event Display:**
    *   Shows summaries of appointments and surgeries within day cells or list items.
    *   Clicking an event summary opens a modal with detailed information.
*   **Event Creation:**
    *   "+" icon on each day (in month view) opens a modal to choose between adding an appointment or a surgery, redirecting to the respective forms with the selected date.
*   **Filtering:**
    *   Filter surgeries by status (All, Scheduled, Confirmed, Completed, Canceled).
*   **Responsive Design:**
    *   Automatically switches from month view to a list view (`listWeek`) on smaller screens (width < 768px).
    *   CSS media queries ensure the layout adapts to different screen sizes.
*   **User Feedback:**
    *   Loading spinner displayed during data fetching.
    *   Empty state message shown when no events are available for the current view/filters, with CTAs to add events.
*   **Modals:**
    *   **Details Modal:** Shows a list of appointments or surgeries for a selected day. Items link to their respective edit pages.
    *   **Add Event Modal:** Prompts user to select event type (appointment/surgery) to add for a selected date.

### 4. Code Structure (JavaScript - `CustomCalendar` Class)

```javascript
class CustomCalendar {
  // Properties:
  // - currentDate: Date object for the currently displayed month/year
  // - currentView: String ('month', 'listMonth', 'listWeek', 'listDay')
  // - events: Object storing fetched events, keyed by date string
  // - isLoading: Boolean flag for loading state
  // - selectedDate: String, stores date for adding new event
  // - currentSurgeryFilter: String, current filter for surgery status
  // - DOM element references (calendarTitle, calendarGrid, etc.)

  constructor() {
    // Initializes properties, DOM elements, binds events, loads initial events
  }

  initializeElements() { /* Gets references to DOM elements */ }
  bindEvents() { /* Adds event listeners to buttons, filter, window resize */ }

  async loadCalendarEvents() {
    // Fetches events for current month/year using apiRequest
    // Updates this.events, then calls render() and checkAndShowEmptyState()
  }

  showLoading(show) { /* Toggles visibility of loading spinner */ }
  showEmptyState(show) { /* Toggles visibility of empty state message and hides/shows views */ }
  checkAndShowEmptyState() { /* Checks if events exist and calls showEmptyState accordingly */ }
  showError(message) { /* Logs error to console (could be improved) */ }

  async navigateMonth(direction) { /* Changes month, reloads events */ }
  async goToToday() { /* Sets to current month, reloads events */ }

  setView(view) {
    // Updates currentView, active button styles, calls render() and checkAndShowEmptyState()
  }

  async render() {
    // Calls updateTitle()
    // Calls renderCalendarGrid() or renderListView() based on currentView
  }

  updateTitle() { /* Sets the calendar header title (e.g., "June 2025") */ }

  async renderCalendarGrid() {
    // Clears and rebuilds the month grid (day cells, day numbers)
    // Populates cells with event summaries (appointment/surgery counts)
    // Adds '+' icon for adding events to each day
  }

  renderListView() {
    // Clears and rebuilds the list view
    // Filters events for the current month
    // Displays events grouped by date with summaries
    // Shows "No events found" if applicable
  }

  showDetailsModal(title, date, items) {
    // Populates and shows the details modal with a list of event items
    // Items link to their respective add/edit pages
  }

  formatDateForAPI(date) { /* Formats Date object to 'YYYY-MM-DD' string */ }

  openAddEventModal(date) { /* Stores selectedDate and shows the add event modal */ }
  addAppointment() { /* Redirects to add appointment page with selectedDate */ }
  addSurgery() { /* Redirects to add surgery page with selectedDate */ }

  handleResize() {
    // Switches to 'listWeek' view if on mobile and current view is 'month'
  }
}

// Helper: getCookie (not directly used by CustomCalendar but present in the script tag)
// Initialization: new CustomCalendar() on DOMContentLoaded
```

### 5. Dependencies

*   **External Libraries:**
    *   Bootstrap 5.3.2 (CSS & JS): For UI components, grid, modals.
    *   Font Awesome 6.4.0: For icons.
    *   Moment.js 2.29.1: Included in `footer.php`, but not directly used within `calendar.php`'s `CustomCalendar` class (native `Date` object is used).
*   **Internal Files:**
    *   `public/includes/header.php`: Provides page structure, common CSS/JS.
    *   `public/includes/footer.php`: Closes page structure, common JS.
    *   `public/auth/auth.php`: For authentication checks in `header.php`.
    *   `public/assets/css/calendar.css`: Specific styles for the calendar.
    *   `public/assets/css/style.css`: General site styles.
    *   `public/assets/css/fonts.css`: Custom fonts.
    *   `public/assets/js/api-helper.js`: Provides `apiRequest` for backend communication.
    *   `public/assets/js/script.js`: General site-wide JavaScript.
    *   `/api.php`: Backend endpoint for all API interactions.

### 6. Potential Issues & Areas for Improvement

1.  **Hidden/Unimplemented Views:**
    *   The "List Week" (`listWeekBtn`) and "List Day" (`listDayBtn`) buttons are present in HTML but hidden with `style="display:none;"`.
    *   The `renderListView()` method currently only implements a full month list. If week/day list views are desired, this method needs to be extended to filter and display events accordingly based on `this.currentView`.
2.  **Responsive View Reversion:**
    *   The `handleResize()` function switches to `listWeek` on mobile if the view is `month`. However, there's no logic to switch back to `month` view if the window is resized back to a larger width. The user would remain in `listWeek` view.
3.  **Error Handling:**
    *   API errors in `loadCalendarEvents()` are caught and logged to the console (`this.showError()`). For a better user experience, these errors should be displayed to the user (e.g., via a toast notification, for which a container exists in `header.php`).
4.  **Date/Time Handling:**
    *   The calendar uses native JavaScript `Date` objects. While functional, for applications requiring robust timezone handling or complex date manipulations, a dedicated library like Luxon or date-fns might be more reliable than Moment.js (which is included but not used here, and is now a legacy project).
5.  **Hardcoded Links in Modals:**
    *   Links in `showDetailsModal` (e.g., `../appointment/add_appointment.php?id=${item.id}`) are relative. While common, using dynamically generated base URLs or route helpers (if available in the PHP framework) can make these more robust to changes in directory structure.
6.  **XSS Vulnerability in Details Modal:**
    *   In `showDetailsModal`, `item.patient_name` and `item.summary` are directly injected into HTML using template literals:
        ```html
        <h6 class="mb-1">${item.patient_name}</h6>
        ...
        <p class="mb-1">${item.summary}</p>
        ```
    *   If `patient_name` or `summary` can contain user-supplied HTML or script tags, this is a potential XSS vulnerability. These values should be properly escaped before rendering, or preferably, set using `textContent` on dynamically created elements.
7.  **Performance for Large Datasets:**
    *   Fetching and rendering events for an entire month at once might become slow if a very large number of events exist per day/month. Consider optimizations if performance issues arise (e.g., more granular data fetching for list views, virtual scrolling for long lists).
8.  **Accessibility (A11y):**
    *   While Bootstrap provides some accessible components, a thorough A11y review would be beneficial. Ensure all interactive elements are keyboard navigable, have appropriate ARIA attributes (e.g., for grid cells, buttons, modal states), and sufficient color contrast. The current code does not explicitly add many ARIA attributes beyond what Bootstrap provides.
9.  **Filter Persistence:**
    *   The `currentSurgeryFilter` is reset when navigating months because `loadCalendarEvents` is called, which re-renders everything. If the filter should persist across month navigations, its state needs to be maintained and reapplied.

### 7. Recommendations

1.  **Implement or Remove Hidden Views:** Decide if "List Week" and "List Day" views are needed. If so, implement the logic in `renderListView()` and make the buttons visible. If not, remove the button elements.
2.  **Improve Responsive View Switching:** Add logic to `handleResize()` or a separate listener to switch back to `month` view from `listWeek` when the screen becomes larger.
3.  **Enhance User Error Display:** Modify `showError()` to display errors in a user-facing manner (e.g., using the toast notification system set up in `header.php`).
4.  **Sanitize Data for Modals:** In `showDetailsModal`, ensure `item.patient_name` and `item.summary` are sanitized before being rendered to prevent XSS. Use `document.createElement` and `element.textContent` for safer rendering of dynamic text.
5.  **Review Date Handling:** Evaluate if the current native `Date` object usage is sufficient or if a more robust date library is needed for timezone accuracy and easier manipulation.
6.  **Conduct Accessibility Audit:** Perform an audit and add necessary ARIA attributes and keyboard navigation enhancements.
7.  **Consider Filter Persistence:** If desired, modify the logic to retain the selected surgery filter when navigating between months.

### 8. Conclusion

The `public/calendar/calendar.php` page provides a functional and well-structured calendar component. The use of a JavaScript class (`CustomCalendar`) encapsulates the client-side logic effectively. The styling is comprehensive and responsive. The primary areas for improvement revolve around completing partially implemented features (week/day list views), enhancing error handling and security (XSS prevention), and ensuring robust responsive behavior and accessibility.