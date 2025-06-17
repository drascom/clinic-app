<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$patient = null;
$errors = [];
$is_editing = false;

// Fetch patient data if ID is provided (for editing)
$patient_id = $_GET['id'] ?? null;
$is_editing = $patient_id !== null;

$page_title = $is_editing ? 'Edit Patient' : 'Add New Patient';
require_once '../includes/header.php';
?>


<div class="container  py-4 emp">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-user-edit me-2 text-primary"></i>
            <?php echo $is_editing ? 'Edit Patient' : 'Add New Patient'; ?>
        </h2>
        <a href="/patient/patients.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            <span class="d-none d-sm-inline">Back to Patients</span>
        </a>
    </div>
    <!-- Patient Form -->
    <fieldset class="border rounded p-3 mb-3 shadow-sm">
        <form id="patient-form" novalidate>
            <?php if ($is_editing): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($patient_id); ?>">
            <?php endif; ?>
            <input type="hidden" name="entity" value="patients">
            <input type="hidden" name="action" value="<?php echo $is_editing ? 'update' : 'add'; ?>">

            <!-- General Error Alert -->
            <div class="alert alert-danger d-none" id="form-error-alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span id="form-error-message"></span>
            </div>


            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <fieldset class="border rounded p-3 mb-3">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                Patient Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Enter patient name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Enter patient email address">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="dob" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Date of Birth <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                Phone
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                placeholder="Enter patient phone number">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                City
                            </label>
                            <input type="text" class="form-control" id="city" name="city"
                                placeholder="Enter patient city">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Avatar Upload Section -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-image me-1"></i>
                                Patient Avatar
                            </label>
                            <div id="avatar-dropzone" class="dropzone">
                                <div class="dz-message">
                                    <span class="note needsclick">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                        Drag and drop avatar here or click to upload.<br>
                                        <small class="text-muted">Supports JPEG, PNG, GIF, WebP, BMP, and HEIC files<br>
                                            Maximum file size: 20MB</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <fieldset class="border rounded p-3 mb-3">
                        <legend class="w-auto px-2 mb-3" style="font-size: 1rem;">
                            <i class="fas fa-info-circle me-2"></i>Optional Info
                        </legend>

                        <div class="mb-3">
                            <label for="gender" class="form-label">
                                <i class="fas fa-venus-mars me-1"></i>
                                Gender
                            </label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Transgender">Transgender</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="occupation" class="form-label">
                                <i class="fas fa-briefcase me-1"></i>
                                Occupation
                            </label>
                            <input type="text" class="form-control" id="occupation" name="occupation"
                                placeholder="Enter patient occupation">
                            <div class="invalid-feedback"></div>
                        </div>

                        <?php if (is_admin() || is_editor()): ?>
                            <div class="mb-3">
                                <label for="agency_id" class="form-label">
                                    <i class="fas fa-building me-1"></i>
                                    Agency <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="agency_id" name="agency_id" required>
                                    <option value="">Select Agency</option>
                                    <!-- Agency options will be loaded dynamically -->
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        <?php elseif (is_agent()): ?>
                            <!-- Hidden field for agents - their agency_id will be set via JavaScript -->
                            <input type="hidden" id="agency_id" name="agency_id" value="">
                        <?php endif; ?>
                    </fieldset>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-end gap-2">
                <a href="patients.php" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    <span id="save-button-text"><?php echo $is_editing ? 'Update Patient' : 'Add Patient'; ?></span>
                </button>

            </div>

        </form>
    </fieldset>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const patientForm = document.getElementById('patient-form');
        const patientIdInput = document.querySelector('#patient-form input[name="id"]');
        const isEditing = patientIdInput !== null;
        let patientId = isEditing ? patientIdInput.value : null; // Make patientId mutable
        let allAgencies = []; // Store all agencies for dropdown
        let uploadedAvatarUrl = null; // Store the uploaded avatar URL


        // Function to fetch agencies from the API
        function fetchAgencies() {
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            if (userRole === 'agent') {
                // For agents, don't fetch agencies - just set their agency_id
                populateAgencyDropdown();
            } else {
                // For admin and editor, fetch all agencies
                apiRequest('agencies', 'list')
                    .then(data => {
                        if (data.success) {
                            allAgencies = data.agencies;
                            populateAgencyDropdown();
                        } else {
                            console.error('Error fetching agencies:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching agencies:', error);
                    });
            }
        }

        // Function to populate agency dropdown
        function populateAgencyDropdown() {
            const agencySelect = document.getElementById('agency_id');
            const userRole = '<?php echo get_user_role(); ?>';
            const userAgencyId = '<?php echo get_user_agency_id(); ?>';

            // For agents, set their agency_id in the hidden field
            if (userRole === 'agent' && userAgencyId) {
                agencySelect.value = userAgencyId;
            } else {
                // agencySelect.innerHTML = '<option value="">Select Agency </option>';
                allAgencies.forEach(agency => {
                    const option = document.createElement('option');
                    option.value = agency.id;
                    option.textContent = agency.name;
                    agencySelect.appendChild(option);
                });
            }

            // For editors, add edit protection for agency field
            if (userRole === 'editor' && isEditing) {
                agencySelect.disabled = true;

                // Add edit button next to agency field
                const agencyContainer = agencySelect.closest('.mb-3');
                const editButton = document.createElement('button');
                editButton.type = 'button';
                editButton.className = 'btn btn-sm btn-outline-warning ms-2';
                editButton.innerHTML = '<i class="fas fa-edit"></i>';
                editButton.title = 'Enable agency editing';
                editButton.onclick = function () {
                    agencySelect.disabled = false;
                    editButton.style.display = 'none';
                    warningText.style.display = 'block';
                };

                // Add warning text
                const warningText = document.createElement('small');
                warningText.className = 'form-text text-muted';
                warningText.style.display = 'none';
                warningText.innerHTML =
                    '<i class="fas fa-exclamation-triangle me-1"></i>Don\'t change agency if you are not sure!';

                agencyContainer.appendChild(editButton);
                agencyContainer.appendChild(warningText);
            }
        }

        // Fetch agencies first
        fetchAgencies();

        // Fetch patient data if editing
        if (isEditing) {
            apiRequest('patients', 'get', { id: patientId })
                .then(data => {
                    if (data.success) {
                        const patient = data.patient;
                        // Populate all form fields with patient data
                        document.getElementById('name').value = patient.name || '';
                        document.getElementById('dob').value = patient.dob || '';
                        document.getElementById('phone').value = patient.phone || '';
                        document.getElementById('email').value = patient.email || '';
                        document.getElementById('city').value = patient.city || '';
                        document.getElementById('gender').value = patient.gender || '';
                        document.getElementById('occupation').value = patient.occupation || '';

                        // Set agency if available
                        if (patient.agency_id) {
                            document.getElementById('agency_id').value = patient.agency_id;
                        }

                        // Log successful data population
                        console.log('Patient data loaded successfully:', patient);
                    } else {
                        showToast(`Error loading patient: ${data.error || 'Unknown error'}`, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error fetching patient:', error);
                    showToast('An error occurred while loading patient data.', 'danger');
                });
        }


        // Setup form validation
        setupFormValidation();

        /**
         * Setup form validation
         */
        function setupFormValidation() {
            const form = patientForm;
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                // Real-time validation on blur
                input.addEventListener('blur', function () {
                    validateField(this);
                });

                // Clear validation on input
                input.addEventListener('input', function () {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                        const feedback = this.parentNode.querySelector('.invalid-feedback');
                        if (feedback) feedback.textContent = '';
                    }
                });
            });
        }

        /**
         * Validate individual field
         */
        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';

            // Required field validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'This field is required.';
            }

            // Email validation
            else if (field.type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    message = 'Please enter a valid email address.';
                }
            }

            // Date validation (not in the future for DOB)
            else if (field.type === 'date' && value && field.name === 'dob') {
                const selectedDate = new Date(value);
                const today = new Date();
                today.setHours(23, 59, 59, 999); // Set to end of today

                if (selectedDate > today) {
                    isValid = false;
                    message = 'Date of birth cannot be in the future.';
                }
            }

            // Phone validation (basic format check)
            else if (field.type === 'tel' && value) {
                const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    message = 'Please enter a valid phone number.';
                }
            }

            // Update field validation state
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = message;
            }

            return isValid;
        }

        /**
         * Validate entire form
         */
        function validateForm() {
            const form = patientForm;
            const inputs = form.querySelectorAll('input[required], select[required]');
            let isValid = true;
            let firstInvalidField = null;

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = input;
                    }
                }
            });

            if (!isValid && firstInvalidField) {
                firstInvalidField.focus();
                showFormError('Please correct the highlighted errors before submitting.');
            }

            return isValid;
        }

        /**
         * Show form error
         */
        function showFormError(message) {
            const errorAlert = document.getElementById('form-error-alert');
            const errorMessage = document.getElementById('form-error-message');
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
        }

        /**
         * Hide form error
         */
        function hideFormError() {
            const errorAlert = document.getElementById('form-error-alert');
            errorAlert.classList.add('d-none');
        }

        /**
         * Reset form validation
         */
        function resetForm() {
            patientForm.reset();
            patientForm.classList.remove('was-validated');

            // Clear validation states
            const inputs = patientForm.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });

            hideFormError();
        }

        // Initialize Dropzone for avatar upload
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false; // Prevent Dropzone from automatically attaching

            const avatarDropzone = new Dropzone("#avatar-dropzone", {
                url: "/api.php", // API endpoint
                paramName: "avatar", // The name that will be used to transfer the file
                maxFilesize: 20, // MB - Increased for HEIC files
                acceptedFiles: "image/*,.heic,.heif", // Explicitly include HEIC/HEIF files
                addRemoveLinks: true, // Add remove links
                maxFiles: 1, // Only allow one file
                autoProcessQueue: false, // Do not auto-process, we'll handle it on form submit (or separately)
                headers: {
                    // Add any necessary headers, e.g., for authentication if your API requires it
                },
                init: function () {
                    const myDropzone = this;
                    // let currentPatientId = patientId; // Capture the initial patientId

                    // Override the params function to use the potentially updated currentPatientId
                    // myDropzone.options.params = function() {
                    //     return {
                    //         entity: 'patients',
                    //         action: 'upload_avatar',
                    //         id: currentPatientId // Use the captured/updated variable
                    //     };
                    // };

                    // Listen for successful patient creation to update currentPatientId
                    // This part relies on the logic in your form submission handling
                    // which is outside the init, but this structure makes the intent clear.
                    // The form submission success handler already updates the outer patientId variable,
                    // and this closure should capture that update when processQueue is called.

                    // Log when a file is about to be sent
                    myDropzone.on("sending", function (file, xhr, formData) {
                        console.log("Dropzone 'sending' event triggered.");
                        // Manually append parameters to the formData
                        formData.append('entity', 'patients');
                        formData.append('action', 'upload_avatar');
                        // Ensure patientId is available. The outer patientId variable should be updated by now.
                        if (patientId) {
                            formData.append('id', patientId);
                            console.log("Appending patientId to FormData:", patientId);
                        } else {
                            console.warn(
                                "patientId is null when sending avatar upload request.");
                        }
                    });

                    // Handle successful upload
                    myDropzone.on("success", function (file, response) {
                        console.log("Avatar upload successful:", response);
                        if (response.success) {
                            // Check if HEIC conversion occurred and show appropriate message
                            let message = response.message || 'Avatar uploaded successfully!';
                            showToast(message, 'success');

                            // Store the uploaded avatar URL if available
                            if (response.avatar_url) {
                                uploadedAvatarUrl = response.avatar_url;
                                console.log("Stored uploadedAvatarUrl:", uploadedAvatarUrl);
                            }
                            // Remove the file from Dropzone's preview after successful upload
                            myDropzone.removeFile(file);
                        } else {
                            showToast(
                                `Avatar upload failed: ${response.error || response.message}`,
                                'danger');
                            // Remove the file from Dropzone's preview on failure
                            myDropzone.removeFile(file);
                        }
                    });

                    // Handle upload error
                    myDropzone.on("error", function (file, message) {
                        console.error("Avatar upload error:", message);

                        let errorMessage = message;

                        // Check for file size errors
                        if (file.size > myDropzone.options.maxFilesize * 1024 * 1024) {
                            errorMessage = `File too large. Maximum size is ${myDropzone.options.maxFilesize}MB. Your file is ${Math.round(file.size / 1024 / 1024 * 100) / 100}MB.`;
                        }

                        showToast(`Avatar upload failed: ${errorMessage}`, 'danger');
                        // Remove the file from Dropzone's preview on error
                        myDropzone.removeFile(file);
                    });

                    // Handle file removal (for existing avatars or newly added ones before upload)
                    myDropzone.on("removedfile", function (file) {
                        console.log("File removed:", file);
                        // If this is an existing avatar (mock file), trigger deletion via API
                        if (file.isMockFile && file.avatarId) {
                            console.log(`Deleting avatar with ID: ${file.avatarId}`);
                            // Perform API call to delete the avatar
                            const deleteFormData = new FormData();
                            deleteFormData.append('entity', 'patients');
                            deleteFormData.append('action',
                                'delete_avatar'); // New action for avatar deletion
                            deleteFormData.append('patient_id', patientId); // Patient ID
                            // You might need to send the avatar ID or path depending on your API
                            deleteFormData.append('avatar_url', file
                                .dataURL); // Send avatar URL as identifier

                            fetch('/api.php', {
                                method: 'POST',
                                body: deleteFormData
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        showToast('Avatar deleted successfully!',
                                            'success');
                                        // No need to hide the current avatar preview div anymore
                                    } else {
                                        showToast(
                                            `Avatar deletion failed: ${data.error || data.message}`,
                                            'danger');
                                        // Optionally re-add the mock file if deletion failed
                                        // myDropzone.emit("addedfile", file);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error deleting avatar:', error);
                                    showToast(
                                        'An error occurred while deleting the avatar.',
                                        'danger');
                                    // Optionally re-add the mock file if deletion failed
                                    // myDropzone.emit("addedfile", file);
                                });
                        }
                        // If it's a new file added by the user, no action needed on remove
                    });


                    // When in editing mode, add existing avatar as a mock file
                    if (isEditing && patientId) {
                        apiRequest('patients', 'get', { id: patientId })
                            .then(data => {
                                if (data.success && data.patient && data.patient.avatar) {
                                    const avatarUrl = data.patient.avatar;
                                    const avatarFileName = avatarUrl.substring(avatarUrl
                                        .lastIndexOf('/') + 1);

                                    // Create a mock file object
                                    const mockFile = {
                                        name: avatarFileName,
                                        size: 12345, // Dummy size, replace if you can get actual size
                                        accepted: true,
                                        kind: 'image',
                                        dataURL: avatarUrl, // Use dataURL for preview
                                        isMockFile: true, // Custom property to identify mock files
                                        avatarId: data.patient
                                            .id // Store patient ID or avatar ID if available
                                    };

                                    // Call the addedfile event handler
                                    myDropzone.emit("addedfile", mockFile);

                                    // Call the thumbnail event handler to display the preview
                                    // Dropzone expects the dataUrl to be set on the mock file
                                    myDropzone.emit("thumbnail", mockFile, avatarUrl);

                                    // Call the complete event handler
                                    myDropzone.emit("complete", mockFile);

                                    // Add the file to the files array
                                    myDropzone.files.push(mockFile);

                                    // No need to display the current avatar preview image separately anymore

                                }
                            })
                            .catch(error => {
                                console.error('Error fetching patient avatar for Dropzone:', error);
                            });
                    }
                }
            });

            // Handle form submission - Trigger Dropzone upload if files are added
            patientForm.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevent default form submission

                // Clear previous messages
                hideFormError();

                // Validate form before submission
                if (!validateForm()) {
                    return;
                }

                // Determine if we are adding or editing
                const action = isEditing ? 'update' : 'add';
                const hasQueuedFiles = avatarDropzone.getQueuedFiles().length > 0;

                if (action === 'add' && hasQueuedFiles) {
                    console.log("Adding new patient with avatar. Submitting main form first...");
                    // Submit the main form data first to create the patient record
                    submitPatientForm()
                        .then(data => {
                            if (data.success && data.patient && data.patient.id) {
                                // Update the patientId variable
                                patientId = data.patient.id;

                                // Listen for Dropzone completion after processing the queue
                                const queueCompleteHandler = function () {
                                    // Redirect after a short delay on success
                                    setTimeout(() => {
                                        window.location.href = 'patients.php';
                                    }, 500);
                                    // Remove the event listener after it has been triggered
                                    avatarDropzone.off("queuecomplete", queueCompleteHandler);
                                };
                                avatarDropzone.on("queuecomplete", queueCompleteHandler);

                                // Process the Dropzone queue to upload the avatar
                                avatarDropzone.processQueue();

                            } else if (data.success) {
                                // Patient created but no patient object returned (unexpected but handle)
                                showToast(data.message +
                                    ' (Avatar upload skipped due to missing patient data).',
                                    'warning'
                                );
                                // Redirect anyway
                                setTimeout(() => {
                                    window.location.href = 'patients.php';
                                }, 500);
                            } else {
                                // Handle main form submission error
                                showFormError(data.error || data.message || 'An error occurred while creating the patient.');
                            }
                        })
                        .catch(error => {
                            console.error('Error submitting main form for new patient:', error);
                            showFormError('An error occurred while creating the patient.');
                        });

                } else if (action === 'update' && hasQueuedFiles) {
                    // If editing and a new avatar is uploaded, process it first
                    console.log("Processing Dropzone queue for existing patient update...");
                    // Listen for Dropzone completion before submitting the main form
                    const queueCompleteHandler = function () {
                        console.log("Dropzone queue complete for update. Submitting main form.");
                        // Submit the main form data (avatar update is handled by upload_avatar action)
                        submitPatientForm();
                        // Remove the event listener after it has been triggered
                        avatarDropzone.off("queuecomplete", queueCompleteHandler);
                    };
                    avatarDropzone.on("queuecomplete", queueCompleteHandler);
                    console.log("Calling avatarDropzone.processQueue() for existing patient update.");
                    // Process the Dropzone queue
                    avatarDropzone.processQueue();
                } else {
                    console.log(
                        "No files in Dropzone queue or editing without new avatar. Submitting main form directly."
                    );
                    // If no files in Dropzone or editing without a new avatar, just submit the main form
                    submitPatientForm();
                }
            });

            // Function to submit the main patient form data
            function submitPatientForm() { // Removed avatarUrl parameter as it's no longer passed here for 'add'
                const formData = new FormData();
                const userRole = '<?php echo get_user_role(); ?>';
                const userAgencyId = '<?php echo get_user_agency_id(); ?>';

                const action = isEditing ? 'update' : 'add';

                formData.append('entity', 'patients');
                formData.append('action', action);
                if (isEditing) {
                    formData.append('id', patientIdInput.value);
                }
                formData.append('name', document.getElementById('name').value);
                formData.append('dob', document.getElementById('dob').value);
                formData.append('phone', document.getElementById('phone').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('city', document.getElementById('city').value);
                formData.append('gender', document.getElementById('gender').value);
                formData.append('occupation', document.getElementById('occupation').value);

                // Agency ID is handled by the form field (hidden for agents, select for admin/editor)
                formData.append('agency_id', document.getElementById('agency_id').value);

                // Do NOT append avatar here for 'add' action, it's handled by Dropzone after patient creation
                // For 'update', the avatar is handled by the 'upload_avatar' action triggered by Dropzone

                return fetch('/api.php', { // Return the fetch promise
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        // Handle success/error messages for the main form submission
                        if (action !== 'add' || !avatarDropzone.getQueuedFiles().length > 0) {
                            // Only display message and redirect immediately if no avatar upload is pending for 'add'
                            if (data.success) {
                                showToast(data.message, 'success');
                                setTimeout(() => {
                                    window.location.href = 'patients.php';
                                }, 500);
                            } else {
                                showFormError(data.error || data.message || 'An error occurred while saving patient data.');
                            }
                        }
                        return data; // Return data for chaining
                    })
                    .catch(error => {
                        console.error('Error submitting form:', error);
                        showFormError('An error occurred while saving patient data.');
                        throw error; // Re-throw error for chaining
                    });
            }


        } else {
            console.error("Dropzone.js not loaded.");
            showToast('File upload functionality is not available because Dropzone.js failed to load.',
                'danger');
        }
    });
</script>