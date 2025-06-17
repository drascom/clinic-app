Create a responsive web application for technicians to manage their work availability using the following specifications:

Requirements:
1. Build a dual-view (calendar/list) availability management system
2. Implement responsive design (list view for mobile, calendar for desktop)
3. Create real-time availability updates with optimistic UI
4. Follow the provided database schema and API structure
5. Incorporate all specified visual states and performance optimizations

Technical Specifications:

Database:
- Use the provided 3-table schema (users, technicians, technician_availability)
- Implement proper foreign key relationships
- Ensure data integrity with appropriate constraints

Frontend:
- Implement the TechnicianScheduler class with all specified methods
- Use the provided HTML structure for layout components
- Apply the CSS performance optimizations
- Handle responsive breakpoints (< 768px: List, ≥ 768px: Calendar)
- Implement weekend restrictions (disabled, red background)

Visual Requirements:
- Available Days: #198754 (green) with white text
- Not Available: #f8f9fa (light gray) with muted text
- Today: #fff3cd (yellow) with dark text
- Weekends: #f8d7da (red) - disabled
- Loading State: Blurred backdrop with spinner

Performance Optimization Requirements:
- Implement API response caching
- Use DOM caching for frequent elements
- Apply debouncing (150ms for interactions, 50ms for updates)
- Utilize document fragments for batch updates
- Enable GPU acceleration for animations

Accessibility Requirements:
- Include proper focus states
- Support reduced motion preferences
- Use semantic HTML structure

Deliverable: A fully functional, responsive web application following all specified requirements and best practices for performance, accessibility, and user experience.



# Technician Availability Page - Complete Recreation Guide

## Purpose & Overview
A responsive web application for technicians to manage their work availability calendar. Features dual view modes (calendar/list), weekend restrictions, real-time data persistence, and optimistic UI updates for seamless user experience.

## User Workflow
1. **Login** → Technician accesses their availability page
2. **View Selection** → Auto-selects list view on mobile, calendar on desktop
3. **Month Navigation** → Browse different months using prev/next/today buttons
4. **Day Selection** → Click weekdays to toggle availability (weekends disabled)
5. **Real-time Feedback** → Immediate UI updates with server synchronization
6. **Responsive Adaptation** → View automatically adjusts to screen size changes

## Design Concepts

### Visual States & Colors
- **Available Days**: Green background (#198754) with white text
- **Not Available**: Light gray (#f8f9fa) with muted text
- **Today**: Yellow highlight (#fff3cd) with dark text
- **Weekends**: Red background (#f8d7da) - disabled/non-selectable
- **Loading**: Blurred backdrop with spinner overlay

### Layout Philosophy
- **Mobile-First**: List view optimized for touch interaction
- **Desktop-Enhanced**: Calendar view leverages screen real estate
- **Performance-Focused**: GPU acceleration, debouncing, DOM caching
- **Accessibility-Aware**: Focus states, reduced motion support

## Core Architecture

### Database Schema (3 Tables)
```sql
-- User accounts with role-based access
users (id, email, password, name, surname, username, phone, role, is_active)

-- Technician records for availability tracking
technicians (id, name, phone, is_active)

-- Availability data linked to technicians
technician_availability (id, tech_id, date, created_at)
```

### Key Backend Functions
```php
get_technician_id()     // Maps logged user to technician record
is_technician()         // Role-based access control
API: byRange           // Fetch availability for date range
API: toggleDay         // Toggle single day availability
```

### Frontend JavaScript Class
```javascript
class TechnicianScheduler {
    // Performance optimizations
    cache: Map              // API response caching
    domCache: Map          // Element reference caching
    debounceTimers: Map    // User interaction debouncing

    // Core methods
    loadAvailability()     // Cached API requests
    toggleDayAvailability() // Optimistic updates
    renderCalendarView()   // Document fragment rendering
    renderListView()       // Efficient list generation
    setInitialViewMode()   // Responsive view selection
}
```

## Layout Components

### HTML Structure
```html
<!-- Control Panel -->
<div class="card-header">
    <!-- Month Navigation + View Toggle -->
    <!-- Responsive: Desktop horizontal, Mobile stacked -->
</div>

<!-- Status Alert -->
<div class="alert alert-info">
    Selected Days: <span id="selected-count">0</span>
</div>

<!-- Calendar View -->
<table class="calendar-table">
    <thead>Mon|Tue|Wed|Thu|Fri|Sat|Sun</thead>
    <tbody id="calendar-body">
        <!-- Dynamic day buttons with data-date attributes -->
    </tbody>
</table>

<!-- List View -->
<div id="list-body">
    <!-- Dynamic day items with status indicators -->
</div>

<!-- Legend -->
<div class="legend">Available|Not Available|Weekend|Today</div>
```

### CSS Performance Features
```css
.calendar-day {
    will-change: background-color, color;
    contain: layout style;
    transition: all 0.15s ease;
}

.calendar-day:hover {
    transform: translateZ(0); /* GPU acceleration */
}

@media (prefers-reduced-motion: reduce) {
    .calendar-day { transition: none; }
}
```

## Key Implementation Details

### Responsive Breakpoints
- **< 768px**: Auto-select List View (mobile-optimized)
- **≥ 768px**: Auto-select Calendar View (desktop-optimized)
- **Dynamic**: Switches view on window resize with 150ms debounce

### Performance Optimizations
- **API Caching**: Month-based response caching prevents redundant requests
- **Optimistic Updates**: Immediate UI feedback with server verification
- **DOM Caching**: Pre-cache frequently accessed elements
- **Debouncing**: 150ms for user interactions, 50ms for count updates
- **Document Fragments**: Batch DOM updates for smooth rendering

### Weekend Logic
```javascript
isWeekend(date) {
    const dayOfWeek = date.getDay();
    return dayOfWeek === 0 || dayOfWeek === 6; // Sunday/Saturday
}
// Weekends: Red styling, disabled state, no click events
```

### Data Flow
1. **User clicks day** → Optimistic UI update
2. **Debounced API call** → Server synchronization
3. **Cache update** → Local state management
4. **Error handling** → Revert on failure with user notification

## File Structure
```
public/
├── technician/technician.php    # Main page with embedded JS/CSS
├── api_handlers/techAvail.php   # API endpoints (byRange, toggleDay)
├── auth/auth.php               # Authentication & user mapping
└── includes/header.php         # Common navigation

db/
└── database.sqlite            # SQLite with 3 core tables
```

## Critical Success Factors
1. **User-Technician Mapping**: Robust `get_technician_id()` function with multiple fallback strategies
2. **Performance**: Caching, debouncing, and optimistic updates for responsive feel
3. **Responsive Design**: Automatic view selection based on screen size
4. **Data Integrity**: Proper foreign key relationships and error handling
5. **Accessibility**: Focus states, reduced motion, and semantic HTML

This architecture delivers a professional, performant technician availability management system with excellent user experience across all devices.
