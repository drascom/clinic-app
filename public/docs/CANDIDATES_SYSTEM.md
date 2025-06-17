# Job Candidates Management System

## Overview

The Job Candidates Management System is a comprehensive solution for tracking job applications and managing candidate information with detailed notes functionality. It integrates seamlessly with the existing HR infrastructure and follows the same authentication and authorization patterns.

## Features

### Core Functionality
- **Candidate Management**: Add, edit, view, and delete job candidates
- **Notes System**: Add chronological notes with timestamps and user attribution
- **Search & Filter**: Real-time search by name, email, phone, and position filtering
- **Status Tracking**: Track application progress through various stages
- **Statistics Dashboard**: Overview of candidate metrics and trends

### Candidate Information Fields
- **Basic Info**: Name, email, phone, position applied
- **Professional**: Experience level, current company, LinkedIn profile, salary expectations
- **Application Details**: Application date, status, source, availability date, location
- **Preferences**: Willingness to relocate

### Notes System
- **Note Types**: General, Interview, Phone Call, Email, Reference, Background Check, Offer, Rejection
- **Importance Marking**: Flag important notes for quick identification
- **User Attribution**: Track who added each note and when
- **Chronological Display**: Notes displayed in reverse chronological order

## Access Control

### User Roles
- **Admin**: Full access including delete permissions for candidates and notes
- **Editor**: Full access except delete permissions
- **Agent/Other**: No access to HR functionality

### Navigation
- **Editor Users**: HR dropdown in main navigation with Candidates and Interview Invitations
- **Admin Users**: Same HR options available in admin dropdown menu

## Database Schema

### job_candidates Table
```sql
- id: Primary key
- name: Candidate full name (required)
- email: Email address (required, unique per candidate)
- phone: Phone number
- position_applied: Position they applied for (required)
- experience_level: Entry Level, Junior, Mid Level, Senior, Expert
- current_company: Current employer
- linkedin_profile: LinkedIn profile URL
- resume_file: Resume file path (for future file upload feature)
- cover_letter_file: Cover letter file path (for future file upload feature)
- application_date: Date of application (required)
- status: Application status (Applied, Screening, Interview Scheduled, etc.)
- source: How they found the position (Website, LinkedIn, Indeed, etc.)
- salary_expectation: Expected salary range
- availability_date: When they can start
- location: Current location
- willing_to_relocate: Boolean flag
- created_at/updated_at: Timestamps
- created_by: User who added the candidate
```

### candidate_notes Table
```sql
- id: Primary key
- candidate_id: Foreign key to job_candidates
- note_text: Note content (required)
- note_type: Type of note (General, Interview, Phone Call, etc.)
- is_important: Boolean flag for important notes
- created_at: Timestamp
- created_by: User who added the note
```

## API Endpoints

### Candidates
- `POST /api.php` with `entity: 'candidates', action: 'list'` - Get candidates list with pagination
- `POST /api.php` with `entity: 'candidates', action: 'get'` - Get single candidate details
- `POST /api.php` with `entity: 'candidates', action: 'add'` - Add new candidate
- `POST /api.php` with `entity: 'candidates', action: 'edit'` - Update candidate
- `POST /api.php` with `entity: 'candidates', action: 'delete'` - Delete candidate (admin only)
- `POST /api.php` with `entity: 'candidates', action: 'stats'` - Get statistics

### Notes
- `POST /api.php` with `entity: 'candidates', action: 'get_notes'` - Get candidate notes
- `POST /api.php` with `entity: 'candidates', action: 'add_note'` - Add note to candidate
- `POST /api.php` with `entity: 'candidates', action: 'delete_note'` - Delete note (admin only)

## Usage Instructions

### Accessing the System
1. Log in as an admin or editor user
2. Navigate to HR > Job Candidates from the main menu
3. The candidates dashboard will display with statistics and candidate list

### Adding a New Candidate
1. Click "Add Candidate" button
2. Fill in the required fields (Name, Email, Position Applied)
3. Complete optional professional and application details
4. Click "Add Candidate" to save

### Managing Candidates
1. **View Details**: Click the eye icon to see full candidate information and notes
2. **Edit**: Click the edit icon to modify candidate information
3. **Delete**: Admin users can click the trash icon to delete candidates

### Adding Notes
1. Open candidate details modal
2. Click "Add Note" in the notes section
3. Select note type and enter note text
4. Optionally mark as important
5. Click "Save Note"

### Search and Filtering
- **Search**: Type in the search box to find candidates by name, email, phone, or company
- **Status Filter**: Use the status dropdown to filter by application status
- **Position Filter**: Filter by position applied for
- **Clear Filters**: Use the "Clear" button to reset all filters

## Technical Implementation

### Files Structure
```
public/
├── hr/
│   └── candidates.php              # Main candidates page
├── api_handlers/
│   └── candidates.php              # API handler for all candidate operations
├── assets/js/
│   └── candidates.js               # JavaScript functionality
└── system/migrations/
    └── create_candidates_tables.php # Database migration
```

### Key Features
- **Responsive Design**: Mobile-friendly interface with Bootstrap
- **Real-time Search**: Debounced search with 300ms delay
- **Pagination**: Efficient handling of large candidate lists
- **Form Validation**: Client-side and server-side validation
- **Error Handling**: Comprehensive error messages and logging
- **Security**: SQL injection prevention, XSS protection, role-based access

### Performance Optimizations
- Database indexes on frequently queried fields
- Efficient pagination with LIMIT/OFFSET
- Debounced search to reduce API calls
- Optimistic UI updates for better user experience

## Migration and Setup

### Database Migration
Run the migration to create the required tables:
```bash
php public/system/migrations/create_candidates_tables.php
```

### Testing
Add sample data for testing:
```bash
php public/system/test_candidates.php
```

## Integration with Existing System

The candidates system integrates seamlessly with:
- **Authentication**: Uses existing user roles and session management
- **Navigation**: Integrated into existing header navigation
- **Styling**: Follows existing Bootstrap theme and design patterns
- **API**: Uses the same API routing system as other modules
- **Database**: Uses the same SQLite database with proper foreign key relationships

## Future Enhancements

Potential future improvements:
- File upload functionality for resumes and cover letters
- Email integration for sending updates to candidates
- Calendar integration for scheduling interviews
- Advanced reporting and analytics
- Export functionality (PDF, Excel)
- Bulk operations for multiple candidates
- Integration with external job boards
- Automated status updates based on actions
