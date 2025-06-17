# Project Documentation Index

## 📚 Documentation Overview

This folder contains comprehensive documentation for the Patient Management System, covering all major features and implementation details.

## 🏥 Core System Documentation

### [Dashboard Documentation](DASHBOARD_DOCUMENTATION.md)
Complete guide to the dashboard system including:
- Statistics cards and metrics
- Interactive yearly charts
- Quick navigation features
- Technician availability analysis
- API endpoints and usage

### [Agency Filtering Documentation](AGENCY_FILTERING_DOCUMENTATION.md)
Comprehensive guide to agency-based data filtering:
- Architecture and data flow
- Authentication system
- Frontend and backend implementation
- Security considerations
- Database schema

### [Technician Availability API](API_TECHNICIAN_AVAILABILITY.md)
Detailed documentation for technician availability system:
- Role-based endpoints
- API request patterns
- Error handling
- Security features

### [Surgery Validation Changes](SURGERY_VALIDATION_CHANGES.md)
Documentation for moving 2-technician validation from API to form level:
- Problem statement and solution
- API and form level changes
- Status logic and workflow impact
- Testing scenarios and migration notes

## 🚀 Quick References

### [Agency Filtering Quick Reference](AGENCY_FILTERING_QUICK_REFERENCE.md)
Developer quick reference for implementing agency filtering:
- Code snippets and examples
- Available functions
- Testing scenarios
- Common pitfalls and solutions
- Debug commands

## 🔧 Migration & Setup

### [Agency Filtering Migration Guide](AGENCY_FILTERING_MIGRATION_GUIDE.md)
Step-by-step guide for migrating existing installations:
- Pre-migration checklist
- Database schema updates
- Code file updates
- Testing procedures
- Troubleshooting guide
- Rollback procedures

## 📋 Feature Matrix

| Feature | Admin | Editor | Agent | Technician |
|---------|-------|--------|-------|------------|
| **Dashboard** | ✅ All Data | ✅ All Data | ✅ Agency Only | ❌ |
| **Patient Management** | ✅ All Patients | ✅ All Patients | ✅ Agency Only | ❌ |
| **Surgery Scheduling** | ✅ All Surgeries | ✅ All Surgeries | ✅ Agency Only | ❌ |
| **Appointments** | ✅ All Appointments | ✅ All Appointments | ✅ Agency Only | ❌ |
| **Technician Management** | ✅ Full Access | ✅ Full Access | ❌ | ❌ |
| **Availability Calendar** | ✅ View All | ✅ View All | ❌ | ✅ Own Only |
| **Statistics & Reports** | ✅ All Data | ✅ All Data | ✅ Agency Only | ❌ |

## 🔐 Security Features

### Data Isolation
- **Agency-based filtering** ensures agents only see their agency's data
- **Role-based access control** restricts features by user role
- **Session management** with secure authentication
- **SQL injection protection** with prepared statements

### Access Control
- **Admin**: Full system access across all agencies
- **Editor**: Full system access across all agencies  
- **Agent**: Limited to assigned agency data only
- **Technician**: Limited to availability management only

## 🏗️ System Architecture

### Frontend
- **Bootstrap 5** for responsive UI
- **Chart.js** for interactive visualizations
- **Vanilla JavaScript** with modern ES6+ features
- **PHP templating** for server-side rendering

### Backend
- **PHP 8+** with object-oriented design
- **SQLite** database with optimized queries
- **RESTful API** design with standardized responses
- **Session-based authentication** with cookie support

### Database
- **SQLite** for lightweight deployment
- **Normalized schema** with proper relationships
- **Indexed queries** for optimal performance
- **Agency-based data separation**

## 📊 API Endpoints

### Core Entities
- `patients/*` - Patient management
- `surgeries/*` - Surgery scheduling
- `appointments/*` - Appointment management
- `techAvail/*` - Technician availability
- `dashboard/*` - Dashboard statistics

### Authentication
- `users/*` - User management
- `agencies/*` - Agency management
- `invitations/*` - User invitations

### System
- `rooms/*` - Room management
- `procedures/*` - Procedure definitions
- `techs/*` - Technician management

## 🧪 Testing

### Test Users
```sql
-- Admin (sees all data)
INSERT INTO users (email, role, agency_id) VALUES ('admin@test.com', 'admin', NULL);

-- Agent for Agency 1 (sees only Agency 1 data)
INSERT INTO users (email, role, agency_id) VALUES ('agent1@test.com', 'agent', 1);

-- Agent for Agency 2 (sees only Agency 2 data)
INSERT INTO users (email, role, agency_id) VALUES ('agent2@test.com', 'agent', 2);

-- Technician (manages own availability)
INSERT INTO users (email, role, agency_id) VALUES ('tech@test.com', 'technician', 3);
```

### Verification Steps
1. **Login as different roles** and verify appropriate data access
2. **Check dashboard statistics** reflect correct filtering
3. **Test patient/surgery forms** show appropriate patient lists
4. **Verify API responses** include only authorized data

## 🔍 Troubleshooting

### Common Issues
1. **Agency filtering not working** - Check user has agency_id and role is set correctly
2. **Dashboard showing no data** - Verify patients have agency_id assigned
3. **Cookies not set** - Check login.php includes agency_id cookie
4. **API errors** - Verify all required functions exist in auth.php

### Debug Tools
```php
// Check user session
echo "Role: " . get_user_role();
echo "Agency: " . get_user_agency_id();

// Check database
SELECT COUNT(*) FROM patients WHERE agency_id = 1;
```

```javascript
// Check cookies
console.log('Role:', getCookie('user_role'));
console.log('Agency:', getCookie('agency_id'));
```

## 📞 Support

For technical support or questions about implementation:

1. **Check the relevant documentation** for your specific issue
2. **Use the Quick Reference guides** for common tasks
3. **Follow the Migration Guide** for setup issues
4. **Test with simple scenarios** before complex implementations
5. **Check error logs** for specific error messages

## 🔄 Updates

This documentation is maintained alongside the codebase. When making changes:

1. **Update relevant documentation** when modifying features
2. **Add new sections** for new functionality
3. **Update the migration guide** for breaking changes
4. **Test all examples** to ensure they work correctly

---

**Last Updated**: December 2024  
**Version**: 1.0  
**Compatibility**: PHP 8+, SQLite 3+
