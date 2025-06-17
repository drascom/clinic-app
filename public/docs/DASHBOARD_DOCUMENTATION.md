# Dashboard Documentation

## Overview

The dashboard provides a comprehensive overview of clinic operations with real-time statistics, charts, and quick navigation to key areas of the system. The dashboard includes agency-based filtering to ensure agents see only data relevant to their assigned agency.

## Related Documentation

- [Agency Filtering Documentation](AGENCY_FILTERING_DOCUMENTATION.md) - Complete guide to agency-based data filtering
- [Agency Filtering Quick Reference](AGENCY_FILTERING_QUICK_REFERENCE.md) - Developer quick reference
- [Technician Availability API](API_TECHNICIAN_AVAILABILITY.md) - Technician availability system

## Features

### 1. Statistics Cards
- **Total Patients**: New patients registered in the current month
- **Patients with Surgeries**: Patients who had surgery in the current month
- **Patients with Appointments**: Patients with appointments in the current month
- **Total Surgeries**: Number of surgeries performed in the current month
- **Total Grafts**: Total number of grafts transplanted in the current month
- **Total Appointments**: Number of appointments scheduled in the current month

### 2. Quick Navigation
Four quick access buttons to main system areas:
- **Calendar**: Surgery schedule management
- **Tech Availability**: Technician availability management
- **Patients**: Patient management system
- **Appointments**: Appointment scheduling and management

### 3. Yearly Surgery Chart
- Interactive line chart showing monthly surgery and graft counts
- Dual Y-axis for surgeries and grafts
- Year selector to view different years
- Responsive design for all screen sizes

### 4. Technician Availability Analysis
- **Current Month Analysis**: Shows technician availability vs requirements
- **Surgery Count**: Number of surgeries scheduled
- **Required Days**: Calculated based on 2 technicians per surgery minimum
- **Available Days**: Total technician availability days
- **Surplus/Deficit Indicator**: Visual status of technician availability

## API Endpoints

### Dashboard Statistics
- **Endpoint**: `dashboard/stats`
- **Method**: POST
- **Parameters**: `month` (YYYY-MM format, optional - defaults to current month)
- **Returns**: Object with all monthly statistics

### Yearly Chart Data
- **Endpoint**: `dashboard/yearlyChart`
- **Method**: POST
- **Parameters**: `year` (YYYY format, optional - defaults to current year)
- **Returns**: Array of monthly data with surgery and graft counts

### Technician Analysis
- **Endpoint**: `dashboard/techAvailability`
- **Method**: POST
- **Parameters**: `month` (YYYY-MM format, optional - defaults to current month)
- **Returns**: Analysis of technician availability vs requirements

## File Structure

```
dashboard.php                 # Main dashboard page
api_handlers/dashboard.php    # API handler for dashboard endpoints
assets/css/dashboard.css      # Dashboard-specific styling
docs/DASHBOARD_DOCUMENTATION.md # This documentation
```

## Security

- **Access Control**: Dashboard is restricted to admin and editor users
- **Technician Redirect**: Technicians are automatically redirected to their availability page
- **Session Validation**: All API endpoints validate user login status
- **Permission Checks**: API endpoints verify user permissions before returning data

## Technical Implementation

### Frontend
- **Framework**: Vanilla JavaScript with Bootstrap 5
- **Charts**: Chart.js for interactive visualizations
- **API Communication**: Uses the standardized `apiRequest()` helper
- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox

### Backend
- **Database**: SQLite with optimized queries
- **Caching**: No caching implemented (can be added for performance)
- **Error Handling**: Comprehensive error handling with user-friendly messages

## Usage

### For Administrators
1. Access dashboard from main navigation
2. View current month statistics at a glance
3. Use month selector to view historical data
4. Analyze technician availability vs surgery requirements
5. Use quick navigation to access specific areas

### For Editors
- Same functionality as administrators
- Can view all statistics and charts
- Can navigate to all linked pages

### For Technicians
- Automatically redirected to their availability calendar
- Cannot access dashboard (insufficient permissions)

## Customization

### Adding New Statistics
1. Add database query to `api_handlers/dashboard.php` in the `stats` action
2. Update the frontend cards array in `dashboard.php`
3. Add appropriate styling in `assets/css/dashboard.css`

### Modifying Charts
1. Update the `yearlyChart` action in the API handler
2. Modify the `renderSurgeryChart()` method in the frontend
3. Customize Chart.js options as needed

### Styling Changes
- Modify `assets/css/dashboard.css` for visual customizations
- Update Bootstrap classes in `dashboard.php` for layout changes
- Add dark theme support by extending existing dark theme selectors

## Performance Considerations

- **Database Queries**: Optimized with proper indexes
- **Chart Rendering**: Charts are destroyed and recreated to prevent memory leaks
- **API Calls**: Parallel loading of dashboard components
- **Responsive Images**: Icons use Font Awesome for scalability

## Browser Support

- **Modern Browsers**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **Mobile**: iOS Safari 13+, Chrome Mobile 80+
- **Features Used**: ES6+ JavaScript, CSS Grid, Flexbox, Chart.js

## Future Enhancements

1. **Real-time Updates**: WebSocket integration for live data
2. **Export Functionality**: PDF/Excel export of statistics
3. **Custom Date Ranges**: Allow custom date range selection
4. **More Chart Types**: Pie charts, bar charts for different metrics
5. **Notifications**: Alert system for low technician availability
6. **Caching**: Redis/Memcached for improved performance
7. **Analytics**: Google Analytics integration for usage tracking
