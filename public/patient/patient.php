<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

// Get patient ID from URL
$patient_id = $_GET['id'] ?? null;

// Validate patient ID
if (!$patient_id || !is_numeric($patient_id)) {
    // Redirect or show an error if patient ID is missing or invalid
    header('Location: /patient/patients.php'); // Redirect back to patients list
    exit();
}

$page_title = "Patient Profile";
require_once __DIR__ . '/../includes/header.php'; ?>
<div class="container  py-4 emp">

    <!-- Patient Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <a href="/patient/add_edit_patient.php?id=<?php echo htmlspecialchars($patient_id); ?>"
                        class="me-3">
                        <img id="patient-avatar" src="" alt="Patient Avatar" class="avatar"
                            style="display: none; width: 60px; height: 60px;">
                    </a>
                    <div>
                        <h2 id="page-title" class="mb-0">Patient Profile</h2>
                        <small class="text-muted">Patient ID: <?php echo htmlspecialchars($patient_id); ?></small>
                    </div>
                </div>
                <div class="btn-group" role="group">
                    <a href="/patient/add_edit_patient.php?id=<?php echo htmlspecialchars($patient_id); ?>"
                        class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>
                        <span class="d-none d-sm-inline">Edit Patient</span>
                    </a>
                    <a href="/patient/patients.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Back to Patients</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Surgeries Section -->
    <div class=" mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-hospital me-2"></i>
                    Surgeries
                </h5>
                <a href="/patient/add_edit_surgery.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>"
                    class="btn btn-success btn-sm">
                    <i class="fas fa-plus-circle me-1"></i>
                    <span class="d-none d-sm-inline">Add Surgery</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="surgeries-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Room</th>
                            <th>Status</th>
                            <th>Graft Count</th>
                            <th class="d-none d-md-table-cell">Notes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Surgeries will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Photos Section -->
    <div class=" mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-camera me-2"></i>
                    Photo Albums
                </h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"
                    data-patient-id="<?php echo htmlspecialchars($patient_id); ?>">
                    <i class="fas fa-upload me-1"></i>
                    <span class="d-none d-sm-inline">Add Photos</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="photos-list">
                <!-- Photo albums and photos will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash-alt me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-upload me-2"></i>
                        Upload Photos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="upload.php" id="photo-upload-form" enctype="multipart/form-data">
                        <input type="hidden" name="patient_id" id="upload-patient-id"
                            value="<?php echo $patient_id; ?>">
                        <div class="mb-3">
                            <label for="photo_album_type_id" class="form-label">Album Type</label>
                            <select class="form-select" id="photo_album_type_id" name="photo_album_type_id" required>
                                <option value="">Select Album Type</option>
                                <!-- Options will be loaded via JavaScript -->
                            </select>
                        </div>
                        <div id="photo-dropzone" class="dropzone" style="display: none;">
                            <div class="dz-message">
                                <span class="note needsclick">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                    Drag and drop images here or click to upload.<br>
                                    <small class="text-muted">Supports JPEG, PNG, GIF, WebP, BMP, and HEIC files<br>
                                        Maximum: 20MB per file, 20 files at once, 50MB total</small>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>

<!-- GLightbox CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===== GLOBAL VARIABLES =====
        const patientId = <?php echo json_encode($patient_id); ?>;
        const surgeriesTableBody = document.querySelector('#surgeries-table tbody');
        const photosListDiv = document.getElementById('photos-list');
        const pageTitle = document.getElementById('page-title');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const uploadModal = document.getElementById('uploadModal');
        const photoAlbumTypeSelect = document.getElementById('photo_album_type_id');
        const photoDropzoneDiv = document.getElementById('photo-dropzone');

        let itemToDeleteId = null;
        let itemToDeleteType = null; // 'surgery' or 'photo'

        // ===== UTILITY FUNCTIONS =====

        /**
         * Sanitize HTML to prevent XSS attacks
         * @param {string} str - String to sanitize
         * @returns {string} - Sanitized string
         */
        function sanitizeHTML(str) {
            if (!str) return '';
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }


        /**
         * Format date as DD, MMM / YY
         * @param {string} dateString - Date string to format
         * @returns {string} - Formatted date string
         */
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = {
                day: '2-digit',
                month: 'short',
                year: '2-digit'
            };
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB', options).replace(/\//g, ' / ');
        }

        /**
         * Get Bootstrap color class for status badges
         * @param {string} status - Status string
         * @returns {string} - Bootstrap color class
         */
        function getStatusColor(status) {
            if (!status) return 'secondary';
            switch (status.toLowerCase()) {
                case 'completed':
                    return 'success';
                case 'booked':
                    return 'primary';
                case 'cancelled':
                    return 'danger';
                case 'in-progress':
                    return 'warning';
                default:
                    return 'secondary';
            }
        }

        /**
         * Reset page elements on error
         */
        function resetPageOnError() {
            pageTitle.textContent = 'Patient Profile';
            document.getElementById('patient-avatar').style.display = 'none';
            surgeriesTableBody.innerHTML = '<tr><td colspan="6">Error loading surgeries.</td></tr>';
            photosListDiv.innerHTML = '<p>Error loading photos.</p>';
        }

        // ===== MAIN DATA LOADING FUNCTION =====

        /**
         * Load and render all patient profile data (patient info, surgeries, photos)
         * @param {number} patientId - Patient ID to load data for
         */
        async function loadPatientProfileData(patientId) {
            if (!patientId) {
                showToast('Patient ID not provided.', 'danger');
                return;
            }

            try {
                const data = await apiRequest('patients', 'get', { id: patientId });

                if (data && data.success) {
                    renderPatientInfo(data.patient);
                    renderSurgeries(data.surgeries);
                    renderPhotos(data.photos);
                } else {
                    showToast(`Error loading patient data: ${data ? data.error : 'Unknown error'}`, 'danger');
                    resetPageOnError();
                }
            } catch (error) {
                console.error('Error loading patient data:', error);
                showToast('An error occurred while loading patient data.', 'danger');
                resetPageOnError();
            }
        }

        // ===== RENDERING FUNCTIONS =====

        /**
         * Render patient information in the header
         * @param {Object} patient - Patient data object
         */
        function renderPatientInfo(patient) {
            const patientAvatarImg = document.getElementById('patient-avatar');

            if (patient) {
                // Update page title with patient name and agency
                if (patient.name) {
                    let titleText = sanitizeHTML(patient.name);
                    if (patient.agency_name) {
                        titleText += ` - ${sanitizeHTML(patient.agency_name)}`;
                    }
                    pageTitle.textContent = titleText;
                } else {
                    pageTitle.textContent = `Profile for Patient ID ${patientId}`;
                }

                // Update avatar
                if (patient.avatar) {
                    patientAvatarImg.src = sanitizeHTML(patient.avatar);
                } else {
                    patientAvatarImg.src = '/assets/avatar.png';
                }
                patientAvatarImg.style.display = 'block';
            } else {
                pageTitle.textContent = 'Patient Profile';
                patientAvatarImg.style.display = 'none';
            }
        }

        /**
         * Render surgeries table
         * @param {Array} surgeries - Array of surgery objects
         */
        function renderSurgeries(surgeries) {
            surgeriesTableBody.innerHTML = ''; // Clear existing rows

            if (surgeries && surgeries.length > 0) {
                surgeries.forEach(surgery => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-surgery-id', surgery.id);
                    row.innerHTML = `
                        <td><span class="fw-medium">${formatDate(surgery.date)}</span></td>
                        <td>${sanitizeHTML(surgery.room_name || 'N/A')}</td>
                        <td><span class="badge bg-${getStatusColor(surgery.status)} status-${sanitizeHTML(surgery.status)}">${sanitizeHTML(surgery.status)}</span></td>
                        <td>${sanitizeHTML(surgery.graft_count || '0')}</td>
                        <td class="d-none d-md-table-cell">${sanitizeHTML(surgery.notes || '')}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="/patient/add_edit_surgery.php?id=${surgery.id}" class="btn btn-sm btn-outline-warning" title="Edit Surgery">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-lg-inline ms-1">Edit</span>
                                </a>
                                <?php if (is_admin()): ?>
                                <button class="btn btn-sm btn-outline-danger delete-item-btn" data-id="${surgery.id}" data-type="surgery" title="Delete Surgery">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="d-none d-lg-inline ms-1">Delete</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    `;
                    surgeriesTableBody.appendChild(row);
                });
            } else {
                surgeriesTableBody.innerHTML = '<tr><td colspan="6">No surgeries found.</td></tr>';
            }
        }

        /**
         * Render photos grouped by album type
         * @param {Array} photos - Array of photo objects
         */
        function renderPhotos(photos) {
            photosListDiv.innerHTML = ''; // Clear existing content

            if (photos && photos.length > 0) {
                // Group photos by album type name
                const photosByAlbumType = photos.reduce((acc, photo) => {
                    const albumTypeName = photo.album_type || 'Uncategorized';
                    if (!acc[albumTypeName]) {
                        acc[albumTypeName] = {
                            id: photo.photo_album_type_id,
                            name: albumTypeName,
                            photos: []
                        };
                    }
                    acc[albumTypeName].photos.push(photo);
                    return acc;
                }, {});

                for (const albumTypeName in photosByAlbumType) {
                    const albumType = photosByAlbumType[albumTypeName];
                    const albumDiv = document.createElement('div');
                    albumDiv.classList.add('album-type-section', 'mb-4');
                    albumDiv.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <h4>${sanitizeHTML(albumType.name)}</h4>
                        </div>
                        <div class="row photo-gallery">
                            <!-- Photos will be added here -->
                        </div>
                    `;
                    const photoGalleryDiv = albumDiv.querySelector('.photo-gallery');

                    albumType.photos.forEach(photo => {
                        const photoCol = document.createElement('div');
                        photoCol.classList.add('col-lg-3', 'col-md-4', 'col-sm-6', 'col-6', 'mb-4');
                        photoCol.innerHTML = `
                            <div class="card h-100">
                                <a href="${sanitizeHTML(photo.file_path)}" class="glightbox" data-gallery="${sanitizeHTML(albumType.name)}">
                                    <img src="${sanitizeHTML(photo.file_path)}" class="card-img-top" alt="Patient Photo" style="height: 200px; object-fit: cover;">
                                </a>
                                <div class="card-body text-center p-2">
                                    <button class="btn btn-outline-danger btn-sm delete-item-btn" data-id="${photo.id}" data-type="photo" title="Delete Photo">
                                        <i class="fas fa-trash-alt"></i>
                                        <span class="d-none d-lg-inline ms-1">Delete</span>
                                    </button>
                                </div>
                            </div>
                        `;
                        photoGalleryDiv.appendChild(photoCol);
                    });
                    photosListDiv.appendChild(albumDiv);
                }

                // Initialize GLightbox after photos are rendered
                GLightbox({
                    selector: '.glightbox'
                });

            } else {
                photosListDiv.innerHTML = '<p>No photos found for this patient.</p>';
            }
        }

        // ===== EVENT HANDLERS =====

        /**
         * Setup delete button event handlers
         */
        function setupDeleteHandlers() {
            // Event listener for delete buttons (delegation)
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('delete-item-btn')) {
                    itemToDeleteId = event.target.dataset.id;
                    itemToDeleteType = event.target.dataset.type;
                    const modalBody = deleteConfirmModal.querySelector('.modal-body');

                    if (itemToDeleteType === 'surgery') {
                        modalBody.textContent = 'Are you sure you want to delete this surgery?';
                    } else if (itemToDeleteType === 'photo') {
                        modalBody.textContent = 'Are you sure you want to delete this photo?';
                    }

                    const modal = new bootstrap.Modal(deleteConfirmModal);
                    modal.show();
                }
            });

            // When the confirm delete button in the modal is clicked
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', async function () {
                    if (itemToDeleteId && itemToDeleteType) {
                        try {
                            const data = await apiRequest(
                                itemToDeleteType === 'surgery' ? 'surgeries' : 'photos',
                                'delete',
                                { id: itemToDeleteId }
                            );

                            if (data.success) {
                                showToast(
                                    `${itemToDeleteType.charAt(0).toUpperCase() + itemToDeleteType.slice(1)} deleted successfully.`,
                                    'success'
                                );
                                loadPatientProfileData(patientId); // Refresh data
                            } else {
                                showToast(`Error deleting ${itemToDeleteType}: ${data.error}`, 'danger');
                            }
                        } catch (error) {
                            console.error(`Error deleting ${itemToDeleteType}:`, error);
                            showToast(`An error occurred while deleting the ${itemToDeleteType}.`, 'danger');
                        } finally {
                            const modal = bootstrap.Modal.getInstance(deleteConfirmModal);
                            modal.hide();
                            itemToDeleteId = null;
                            itemToDeleteType = null;
                        }
                    }
                });
            }
        }

        // ===== UPLOAD FUNCTIONALITY =====

        /**
         * Fetch and populate photo album types
         */
        async function fetchPhotoAlbumTypes() {
            try {
                const data = await apiRequest('photo_album_types', 'list');
                photoAlbumTypeSelect.innerHTML = '<option value="">Select Album Type</option>';

                if (data.success && Array.isArray(data.photo_album_types)) {
                    data.photo_album_types.forEach(albumType => {
                        const option = document.createElement('option');
                        option.value = albumType.id;
                        option.textContent = albumType.name;
                        photoAlbumTypeSelect.appendChild(option);
                    });
                } else {
                    throw new Error('Invalid data format or success is false');
                }
            } catch (error) {
                console.error('Error fetching photo album types:', error);
                photoAlbumTypeSelect.innerHTML = '<option value="">Error loading types</option>';
                photoAlbumTypeSelect.disabled = true;
            }
        }

        /**
         * Setup upload modal functionality
         */
        function setupUploadModal() {
            if (uploadModal) {
                uploadModal.addEventListener('show.bs.modal', function () {
                    fetchPhotoAlbumTypes();
                    photoDropzoneDiv.style.display = 'none';
                });
            }

            // Show dropzone when an album type is selected
            photoAlbumTypeSelect.addEventListener('change', function () {
                if (this.value) {
                    photoDropzoneDiv.style.display = 'block';
                } else {
                    photoDropzoneDiv.style.display = 'none';
                }
            });
        }

        /**
         * Initialize Dropzone for photo uploads
         */
        function initializeDropzone() {
            if (typeof Dropzone === 'undefined') {
                console.error("Dropzone.js not loaded.");
                return;
            }

            Dropzone.autoDiscover = false;

            const photoDropzone = new Dropzone("#photo-dropzone", {
                url: "../system/upload.php",
                paramName: "file",
                maxFilesize: 20, // MB - Individual file size limit
                maxFiles: 20, // Maximum number of files that can be uploaded at once
                acceptedFiles: "image/*,.heic,.heif", // Explicitly include HEIC/HEIF files
                addRemoveLinks: true,
                autoProcessQueue: true,
                uploadMultiple: true,
                parallelUploads: 5, // Reduced to prevent overwhelming the server
                timeout: 300000, // 5 minutes timeout for large files and HEIC conversion
                params: function () {
                    return {
                        patient_id: document.getElementById('upload-patient-id').value,
                        photo_album_type_id: photoAlbumTypeSelect.value
                    };
                },
                init: function () {
                    const myDropzone = this;

                    myDropzone.on("successmultiple", function (files, response) {
                        console.log("Upload successful:", response);

                        // Check if any HEIC conversions occurred
                        let message = 'Photos uploaded successfully!';
                        if (response.results && response.results.some(result => result.converted_from_heic)) {
                            message = 'Photos uploaded successfully! HEIC files were automatically converted to JPEG.';
                        }

                        showToast(message, 'success');
                        myDropzone.removeAllFiles();
                        const modal = bootstrap.Modal.getInstance(uploadModal);
                        modal.hide();
                        loadPatientProfileData(patientId);
                    });

                    myDropzone.on("errormultiple", function (files, response, xhr) {
                        console.error("Upload error:", response);
                        let errorMessage = 'An error occurred during upload.';

                        if (response && response.error) {
                            errorMessage = 'Upload failed: ' + response.error;
                        } else if (xhr && xhr.responseText) {
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                errorMessage = 'Upload failed: ' + (errorData.error || xhr.responseText);
                            } catch (e) {
                                errorMessage = 'Upload failed: ' + xhr.responseText;
                            }
                        }
                        showToast(errorMessage, 'danger');
                    });

                    // Enhanced error handling for individual files
                    myDropzone.on("error", function (file, message) {
                        console.error("Individual file error:", file.name, message);

                        let errorMessage = message;

                        // Check for specific error types and provide better messages
                        if (typeof message === 'string') {
                            if (message.includes('File is too big')) {
                                errorMessage = `File "${file.name}" is too large. Maximum size is 20MB. Your file is ${Math.round(file.size / 1024 / 1024 * 100) / 100}MB.`;
                            } else if (message.includes('too many files')) {
                                errorMessage = `Too many files selected. Maximum 20 files allowed at once.`;
                            } else if (message.includes('Invalid file type')) {
                                errorMessage = `File "${file.name}" is not a supported image format. Please use JPEG, PNG, GIF, WebP, BMP, or HEIC files.`;
                            }
                        }

                        showToast(errorMessage, 'warning');
                    });

                    // Add validation before upload starts
                    myDropzone.on("addedfiles", function (files) {
                        const maxTotalSize = 50 * 1024 * 1024; // 50MB in bytes
                        let totalSize = 0;
                        let oversizedFiles = [];

                        files.forEach(file => {
                            totalSize += file.size;
                            if (file.size > 20 * 1024 * 1024) { // 20MB in bytes
                                oversizedFiles.push(file);
                            }
                        });

                        // Remove oversized files
                        oversizedFiles.forEach(file => {
                            myDropzone.removeFile(file);
                            showToast(`File "${file.name}" removed: exceeds 20MB limit (${Math.round(file.size / 1024 / 1024 * 100) / 100}MB)`, 'warning');
                        });

                        // Check total size
                        if (totalSize > maxTotalSize) {
                            showToast(`Total file size (${Math.round(totalSize / 1024 / 1024 * 100) / 100}MB) exceeds 50MB limit. Please remove some files.`, 'warning');
                        }

                        // Update UI to show current status
                        const remainingFiles = myDropzone.getAcceptedFiles();
                        if (remainingFiles.length > 0) {
                            const currentTotalSize = remainingFiles.reduce((sum, file) => sum + file.size, 0);
                            console.log(`Ready to upload ${remainingFiles.length} files (${Math.round(currentTotalSize / 1024 / 1024 * 100) / 100}MB total)`);
                        }
                    });
                }
            });
        }

        // ===== INITIALIZATION =====

        /**
         * Initialize all functionality
         */
        function init() {
            setupDeleteHandlers();
            setupUploadModal();
            initializeDropzone();
            loadPatientProfileData(patientId);
        }

        // Initialize when DOM is ready
        init();
    });
</script>