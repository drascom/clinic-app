# Implementation Plan: Add Appointment Wizard

This document outlines the plan for creating a new "Add Appointment" page with a multi-step wizard.

## 1. Overview

The new page will be located at `public/appointment/add.php`. It will feature a Bootstrap 5 wizard to guide the user through the process of creating a new appointment. All client-side logic will be handled in `public/appointment/page.js`, and all database interactions will use the existing API.

## 2. Database and API Analysis

### Database Schema (`db/database.sql`)

The following tables and fields are relevant:

-   **`appointments`**:
    -   `patient_id` (Required)
    -   `appointment_date` (Required)
    -   `start_time` (Required)
    -   `end_time` (Required)
    -   `room_id` (Required)
    -   `procedure_id` (Optional, for treatments)
    -   `consultation_type` (Optional, defaults to 'face-to-face')
    -   `notes` (Optional)
-   **`patients`**: Used to populate the patient selection dropdown.
-   **`procedures`**: Used to populate the procedure selection dropdown.
-   **`rooms`**: Used to populate the room selection dropdown.

### API Endpoints

The following existing API actions will be used:

-   **Create Appointment**:
    -   **File**: `public/api_handlers/appointments.php`
    -   **Entity**: `appointments`
    -   **Action**: `create`
    -   **Method**: `POST`
    -   **Payload**:
        ```json
        {
          "patient_id": 123,
          "appointment_date": "2025-12-31",
          "start_time": "10:00",
          "end_time": "11:00",
          "room_id": 4,
          "procedure_id": 5, // Null if not a treatment
          "consultation_type": "Treatment", // or "Consultation"
          "notes": "Patient requires a follow-up."
        }
        ```
-   **List Patients**:
    -   **File**: `public/api_handlers/patients.php`
    -   **Entity**: `patients`
    -   **Action**: `list`
    -   **Method**: `POST`
-   **List Active Procedures**:
    -   **File**: `public/api_handlers/procedures.php`
    -   **Entity**: `procedures`
    -   **Action**: `active`
    -   **Method**: `POST`
-   **List Available Rooms**:
    -   **File**: `public/api_handlers/rooms.php`
    -   **Entity**: `rooms`
    -   **Action**: `list`
    -   **Method**: `POST`
    -   **Payload**:
        ```json
        {
          "date": "2025-12-31",
          "type": "treatment" // or "consultation"
        }
        ```

No modifications to the existing API handlers are required.

## 3. Wizard Design & Flow

The wizard will consist of 6 steps. Navigation will be handled by "Next" and "Previous" buttons. The step indicators will be Bootstrap Pills.

### Steps:

1.  **Select Patient**: A searchable dropdown (`select2`) to find and select an existing patient.
2.  **Purpose of Visit**: Two buttons: "Consultation" and "Treatment".
3.  **Select Service**:
    -   This step is conditional.
    -   If "Treatment" was selected, a dropdown of available procedures is shown.
    -   If "Consultation" was selected, this step is skipped.
4.  **Select Date & Time**: A date picker and time slot selection.
5.  **Select Room**: A dropdown of available rooms, filtered by the selected date and visit type.
6.  **Notes & Save**: A textarea for additional notes and the final "Save Appointment" button.

## 4. HTML Structure (`public/appointment/add.php`)

The page will contain the main wizard container, the navigation pills for step indication, and a `div` for each step's content.

```html
<!-- public/appointment/add.php -->
<div class="container mt-5">
    <h2>Add New Appointment</h2>

    <!-- Step Indicators (Pills) -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-patient-tab" data-bs-toggle="pill" data-bs-target="#pills-patient" type="button" role="tab" aria-controls="pills-patient" aria-selected="true">Step 1: Patient</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-purpose-tab" data-bs-toggle="pill" data-bs-target="#pills-purpose" type="button" role="tab" aria-controls="pills-purpose" aria-selected="false">Step 2: Purpose</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-service-tab" data-bs-toggle="pill" data-bs-target="#pills-service" type="button" role="tab" aria-controls="pills-service" aria-selected="false">Step 3: Service</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-datetime-tab" data-bs-toggle="pill" data-bs-target="#pills-datetime" type="button" role="tab" aria-controls="pills-datetime" aria-selected="false">Step 4: Date & Time</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-room-tab" data-bs-toggle="pill" data-bs-target="#pills-room" type="button" role="tab" aria-controls="pills-room" aria-selected="false">Step 5: Room</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-notes-tab" data-bs-toggle="pill" data-bs-target="#pills-notes" type="button" role="tab" aria-controls="pills-notes" aria-selected="false">Step 6: Notes & Save</button>
        </li>
    </ul>

    <!-- Step Content -->
    <div class="tab-content" id="pills-tabContent">
        <!-- Step 1: Patient Selection -->
        <div class="tab-pane fade show active" id="pills-patient" role="tabpanel" aria-labelledby="pills-patient-tab">
            <h3>Select Patient</h3>
            <select id="patient-select" class="form-control"></select>
        </div>

        <!-- Step 2: Purpose of Visit -->
        <div class="tab-pane fade" id="pills-purpose" role="tabpanel" aria-labelledby="pills-purpose-tab">
            <h3>Purpose of Visit</h3>
            <button id="btn-consultation" class="btn btn-primary">Consultation</button>
            <button id="btn-treatment" class="btn btn-secondary">Treatment</button>
        </div>

        <!-- Step 3: Service Selection (Conditional) -->
        <div class="tab-pane fade" id="pills-service" role="tabpanel" aria-labelledby="pills-service-tab">
            <h3>Select Service</h3>
            <select id="procedure-select" class="form-control"></select>
        </div>

        <!-- Step 4: Date and Time -->
        <div class="tab-pane fade" id="pills-datetime" role="tabpanel" aria-labelledby="pills-datetime-tab">
            <h3>Select Date and Time</h3>
            <input type="date" id="appointment-date" class="form-control">
            <!-- Time slot selection will be dynamically populated -->
            <select id="time-slot-select" class="form-control mt-2"></select>
        </div>

        <!-- Step 5: Room Selection -->
        <div class="tab-pane fade" id="pills-room" role="tabpanel" aria-labelledby="pills-room-tab">
            <h3>Select Room</h3>
            <select id="room-select" class="form-control"></select>
        </div>

        <!-- Step 6: Notes and Save -->
        <div class="tab-pane fade" id="pills-notes" role="tabpanel" aria-labelledby="pills-notes-tab">
            <h3>Additional Notes</h3>
            <textarea id="notes" class="form-control" rows="4"></textarea>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="mt-4">
        <button id="btn-prev" class="btn btn-secondary">Previous</button>
        <button id="btn-next" class="btn btn-primary">Next</button>
        <button id="btn-save" class="btn btn-success d-none">Save Appointment</button>
    </div>
</div>
```

## 5. JavaScript Logic (`public/appointment/page.js`)

The JavaScript will manage the state of the wizard, handle user input, perform validation, and make API calls.

```javascript
// public/appointment/page.js

document.addEventListener('DOMContentLoaded', function() {
    // State object to hold appointment data
    const appointmentData = {
        patient_id: null,
        consultation_type: null,
        procedure_id: null,
        appointment_date: null,
        start_time: null,
        end_time: null,
        room_id: null,
        notes: ''
    };

    let currentStep = 0;
    const steps = document.querySelectorAll('.tab-pane');
    const stepPills = document.querySelectorAll('.nav-pills .nav-link');

    // Initialize page (fetch patients, etc.)
    function initPage() {
        // Initialize Select2 for patient selection
        // Fetch patients and populate the dropdown
        // Add event listeners to buttons and inputs
    }

    // Navigation functions
    function nextStep() {
        // Validation logic for the current step
        // ...

        // Handle conditional skip of step 3
        if (currentStep === 1 && appointmentData.consultation_type === 'Consultation') {
            currentStep++; // Skip service selection
        }

        if (currentStep < steps.length - 1) {
            currentStep++;
            updateWizardState();
        }
    }

    function prevStep() {
        // Handle conditional skip of step 3
        if (currentStep === 3 && appointmentData.consultation_type === 'Consultation') {
            currentStep--; // Skip service selection
        }

        if (currentStep > 0) {
            currentStep--;
            updateWizardState();
        }
    }

    // Update UI based on the current step
    function updateWizardState() {
        // Show/hide tab panes
        // Update active pill
        // Show/hide nav buttons (prev, next, save)
    }

    // Data fetching functions
    async function fetchPatients() {
        const response = await apiRequest('patients', 'list', {});
        // Populate #patient-select
    }

    async function fetchProcedures() {
        const response = await apiRequest('procedures', 'active', {});
        // Populate #procedure-select
    }

    async function fetchRooms() {
        const response = await apiRequest('rooms', 'list', {
            date: appointmentData.appointment_date,
            type: appointmentData.consultation_type.toLowerCase()
        });
        // Populate #room-select
    }

    // Final save function
    async function saveAppointment() {
        // Collect all data from appointmentData
        const response = await apiRequest('appointments', 'create', appointmentData);
        if (response.success) {
            // Show success message and redirect
        } else {
            // Show error message
        }
    }

    initPage();
});
```

## 6. Implementation Steps

1.  **Create Files**: Create `public/appointment/add.php` and `public/appointment/page.js`.
2.  **HTML**: Add the HTML structure to `add.php`. Include necessary CSS and JS files (`select2`, `page.js`, etc.).
3.  **JavaScript - Initialization**:
    -   Implement `initPage()`.
    -   Fetch the list of patients using `apiRequest('patients', 'list', {})` and populate the `#patient-select` dropdown. Initialize `select2` on it.
    -   Add click listeners to the "Next", "Previous", and "Save" buttons.
4.  **JavaScript - Step Logic**:
    -   **Step 1 (Patient)**: On "Next", store the selected `patient_id`.
    -   **Step 2 (Purpose)**: Add click listeners to `#btn-consultation` and `#btn-treatment`. Store the selection in `appointmentData.consultation_type`.
    -   **Step 3 (Service)**: If `consultation_type` is 'Treatment', fetch and populate procedures. On "Next", store the `procedure_id`.
    -   **Step 4 (Date/Time)**: On date change, fetch available time slots (this might require a new API action or be handled client-side for simplicity). Store `appointment_date`, `start_time`, and `end_time`.
    -   **Step 5 (Room)**: On entering this step, call `fetchRooms()` with the selected date and type. Populate the dropdown. Store `room_id` on "Next".
    -   **Step 6 (Notes)**: Update `appointmentData.notes` as the user types.
5.  **JavaScript - Finalization**:
    -   Implement the `saveAppointment` function to send the final `appointmentData` object to the API.
    -   Handle the API response (success or error).
6.  **Styling and Refinement**: Ensure the wizard is responsive and visually polished. Add loading indicators for API calls.