<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$patient_id = $_GET['id'] ?? null;
if (!$patient_id || !is_numeric($patient_id)) {
    header('Location: patients.php');
    exit();
}

$page_title = "Patient Details";
require_once __DIR__ . '/../includes/header.php';

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />

<style>
    /* Enhanced Typography for Patient Details */
    .nav-tabs .nav-link {
        padding: 0.75rem 1.5rem;
        border-radius: 0.375rem 0.375rem 0 0;
        transition: all 0.2s ease-in-out;
    }

    .nav-tabs .nav-link:hover {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
    }

    .table th {
        border-top: none;
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .table td {
        padding: 0.875rem 0.75rem;
        vertical-align: middle;
    }

    .profile-img img {
        border: 3px solid #e9ecef;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .btn {
        font-weight: 500;
        letter-spacing: 0.025em;
    }

    .modal-title {
        font-weight: 600;
    }

    .form-label {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .badge {
        font-weight: 500;
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }

    /* Responsive typography adjustments */
    @media (max-width: 768px) {
        .nav-tabs .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .table th,
        .table td {
            padding: 0.5rem 0.375rem;
            font-size: 0.875rem;
        }
    }
</style>

<!-- Status Messages -->

<div class="container emp">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2 text-primary"></i>
                    Patient Details
                </h4>
                <a href="/patient/patients.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Back to Patients</span>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row my-4">
                <div class="col-md-3 text-center">
                    <div class="profile-img">
                        <div class="avatar-container">
                            <img id="patient-avatar"
                                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS52y5aInsxSm31CvHOFHWujqUx_wWTS9iM6s7BAm21oEN_RiGoog"
                                alt="Patient Avatar" />
                            <div class="avatar-overlay">
                                <div class="avatar-controls">
                                    <button class="avatar-control-btn change-btn" id="change-avatar-btn"
                                        title="Change Avatar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="avatar-control-btn delete-btn" id="delete-avatar-btn"
                                        title="Delete Avatar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="patient-info-left" class="text-center mt-3">
                        <!-- Patient info will be loaded here -->
                    </div>
                </div>
                <div class="col-md-9">
                    <fieldset>
                        <div id="patient-info-right">
                            <!-- Patient name, phone, email will be loaded here -->
                        </div>
                    </fieldset>
                    <div class="profile-tab mt-4">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold fs-5" id="appointments-tab"
                                    data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab"
                                    aria-controls="appointments" aria-selected="true">Appointments</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold fs-5" id="surgeries-tab" data-bs-toggle="tab"
                                    data-bs-target="#surgeries" type="button" role="tab" aria-controls="surgeries"
                                    aria-selected="false">Surgeries</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold fs-5" id="images-tab" data-bs-toggle="tab"
                                    data-bs-target="#images" type="button" role="tab" aria-controls="images"
                                    aria-selected="false">Images</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="appointments" role="tabpanel"
                                aria-labelledby="appointments-tab">
                                <table class="table table-striped mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-semibold fs-6">Date</th>
                                            <th class="fw-semibold fs-6">Time</th>
                                            <th class="fw-semibold fs-6">Reason</th>
                                            <th class="fw-semibold fs-6">Type</th>
                                            <th class="fw-semibold fs-6 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointments-table-body">
                                        <!-- Appointments will be loaded here -->
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No appointments found.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="tab-pane fade" id="surgeries" role="tabpanel" aria-labelledby="surgeries-tab">
                                <table class="table table-striped mt-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-semibold fs-6">Date</th>
                                            <th class="fw-semibold fs-6">Room</th>
                                            <th class="fw-semibold fs-6">Status</th>
                                            <th class="fw-semibold fs-6">Graft Count</th>
                                            <th class="fw-semibold fs-6 d-none d-md-table-cell">Notes</th>
                                            <th class="fw-semibold fs-6 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="surgeries-table-body">
                                        <!-- Surgeries will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="tab-pane fade" id="images" role="tabpanel" aria-labelledby="images-tab">
                                <button class="btn btn-outline-primary mt-3 fw-semibold" data-bs-toggle="modal"
                                    data-bs-target="#uploadModal">
                                    <i class="fas fa-plus me-2"></i>Add Photos</button>
                                <div id="photos-list" class="mt-3">
                                    <!-- Photos will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>


<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Photos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../system/upload.php" id="photo-upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                    <div class="form-group mb-3">
                        <label for="photo_album_type_id" class="form-label fw-semibold">Album Type</label>
                        <select class="form-select" id="photo_album_type_id" name="photo_album_type_id" required>
                            <option value="">Select Album Type</option>
                            <!-- Options loaded dynamically -->
                        </select>
                    </div>
                    <div id="photo-dropzone" class="dropzone" style="display: none;">
                        <div class="dz-message">
                            <span class="note needsclick">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i><br>
                                <span class="fs-6">Drag and drop images here or click to upload.</span><br>
                                <small class="text-muted">Supports JPEG, PNG, GIF, WebP, BMP, and HEIC files<br>
                                    Maximum file size: 20MB per file</small>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Upload Modal -->
<div class="modal fade" id="avatarUploadModal" tabindex="-1" aria-labelledby="avatarUploadModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarUploadModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Change Avatar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="current-avatar-preview" src="" alt="Current Avatar"
                        style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                    <p class="text-muted mt-2 mb-0">Current Avatar</p>
                </div>
                <form id="avatar-upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                    <div id="avatar-dropzone" class="dropzone">
                        <div class="dz-message">
                            <span class="note needsclick">
                                <i class="fas fa-user-circle fa-2x mb-2"></i><br>
                                <span class="fs-6">Drag and drop avatar image here or click to upload.</span><br>
                                <small class="text-muted">Only one image allowed. Max size: 20MB<br>
                                    Supports JPEG, PNG, GIF, WebP, BMP, and HEIC files</small>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
            </div>
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
                <p class="mb-0 fs-6">Are you sure you want to delete this item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger fw-semibold" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

<script>
    // Disable Dropzone auto-discovery to prevent conflicts
    if (typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const patientId = <?php echo json_encode($patient_id); ?>;
        const patientAvatarImg = document.getElementById('patient-avatar');
        const patientInfoLeft = document.getElementById('patient-info-left');
        const patientInfoRight = document.getElementById('patient-info-right');
        const surgeriesTableBody = document.getElementById('surgeries-table-body');
        const appointmentsTableBody = document.getElementById('appointments-table-body');
        const photosListDiv = document.getElementById('photos-list');
        const photoAlbumTypeSelect = document.getElementById('photo_album_type_id');
        const photoDropzoneDiv = document.getElementById('photo-dropzone');
        const uploadModal = document.getElementById('uploadModal');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let itemToDeleteId = null;
        let itemToDeleteType = null; // 'surgery' or 'photo'

        function getStatusColor(status) {
            const statusColors = {
                'reserved': 'warning',
                'scheduled': 'info',
                'confirmed': 'primary',
                'completed': 'success',
                'cancelled': 'danger'
            };
            return statusColors[status] || 'secondary';
        }

        function getConsultationTypeClass(type) {
            switch (type) {
                case 'face-to-face':
                    return 'bg-info text-dark';
                case 'video-to-video':
                    return 'bg-success';
                default:
                    return 'bg-secondary';
            }
        }

        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }


        async function loadPatientData() {
            const data = await apiRequest('patients', 'get', {
                id: patientId
            });
            if (data && data.success && data.patient) {
                const patient = data.patient;
                if (patient.avatar) {
                    // Remove 'uploads/' prefix for serve-file.php
                    patientAvatarImg.src = patient.avatar.replace('uploads/', '');
                } else {
                    patientAvatarImg.src = '../assets/avatar.png';
                }

                // Fetch agency name separately if agency_id exists
                let agencyName = 'N/A';
                if (patient.agency_id) {
                    try {
                        const agencyData = await apiRequest('agencies', 'get', {
                            id: patient.agency_id
                        });
                        if (agencyData && agencyData.success && agencyData.agency) {
                            agencyName = agencyData.agency.name;
                        }
                    } catch (error) {
                        console.error('Error fetching agency:', error);
                        agencyName = 'N/A';
                    }
                }

                let leftInfo = `
                <div class="text-start">
                    <p class="mb-2"><span class="fw-semibold text-muted">DOB:</span> <span class="fs-6">${patient.dob || 'N/A'}</span></p>
                    <p class="mb-2"><span class="fw-semibold text-muted">City:</span> <span class="fs-6">${patient.city || 'N/A'}</span></p>
                    <p class="mb-2"><span class="fw-semibold text-muted">Gender:</span> <span class="fs-6">${patient.gender || 'N/A'}</span></p>
                    <p class="mb-2"><span class="fw-semibold text-muted">Occupation:</span> <span class="fs-6">${patient.occupation || 'N/A'}</span></p>
                    <p class="mb-2"><span class="fw-semibold text-muted">Agency:</span> <span class="fs-6">${agencyName}</span></p>
                </div>
            `;
                patientInfoLeft.innerHTML = leftInfo;

                let rightInfo = `
                 <legend class="w-auto px-2 mb-3">
                    <h3 class="mb-0 text-primary"> <i class="fas fa-user me-2"></i>${patient.name}</h3>
                </legend>
                <div class="mt-3">
                    <p class="mb-2 fs-6"><span class="fw-semibold text-muted">Phone:</span> <span class="ms-2">${patient.phone || 'N/A'}</span></p>
                    <p class="mb-2 fs-6"><span class="fw-semibold text-muted">Email:</span> <span class="ms-2">${patient.email || 'N/A'}</span></p>
                </div>
            `;
                patientInfoRight.innerHTML = rightInfo;

                renderSurgeries(data.surgeries);
                renderAppointments(data.appointments);
                renderPhotos(data.photos);
            }
        }

        function fetchPhotoAlbumTypes() {
            apiRequest('photo_album_types', 'list').then(data => {
                if (data.success && Array.isArray(data.photo_album_types)) {
                    data.photo_album_types.forEach(albumType => {
                        const option = document.createElement('option');
                        option.value = albumType.id;
                        option.textContent = albumType.name;
                        photoAlbumTypeSelect.appendChild(option);
                    });
                }
            });
        }

        function renderSurgeries(surgeries) {
            surgeriesTableBody.innerHTML = '';
            if (surgeries && surgeries.length > 0) {
                surgeries.forEach(surgery => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                    <td>${surgery.date}</td>
                    <td>${surgery.room_name}</td>
                    <td><span class="badge bg-${getStatusColor(surgery.status)} ms-1">${surgery.status}</span></td>
                    <td>${surgery.graft_count}</td>
                    <td>${surgery.notes}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="../surgery/add_edit_surgery.php?id=${surgery.id}" class="btn btn-sm btn-outline-warning" title="Edit Surgery">
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
                surgeriesTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No surgeries found.</td></tr>';
            }
        }

        function renderAppointments(appointments) {
            appointmentsTableBody.innerHTML = '';
            if (appointments && appointments.length > 0) {
                appointments.forEach(appointment => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                    <td>${appointment.appointment_date}</td>
                    <td>${appointment.start_time}</td>
                    <td>${appointment.procedure_name}</td>
                    <td>
                        <span class="badge ${getConsultationTypeClass(appointment.consultation_type)}">
                            <i class="fas ${appointment.consultation_type === 'video-to-video' ? 'fa-video' : 'fa-user-friends'} me-1"></i>
                            ${appointment.consultation_type}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="../appointment/add_appointment.php?id=${appointment.id}" class="btn btn-sm btn-outline-warning" title="Edit Appointment">
                                <i class="fas fa-edit"></i>
                                <span class="d-none d-lg-inline ms-1">Edit</span>
                            </a>
                            <?php if (is_admin()): ?>
                            <button class="btn btn-sm btn-outline-danger delete-item-btn" data-id="${appointment.id}" data-type="appointment" title="Delete Surgery">
                                <i class="fas fa-trash-alt"></i>
                                <span class="d-none d-lg-inline ms-1">Delete</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                   
                `;
                    appointmentsTableBody.appendChild(row);
                });
            } else {
                appointmentsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No appointments found.</td></tr>';
            }
        }

        function renderPhotos(photos) {
            photosListDiv.innerHTML = '';
            if (photos && photos.length > 0) {
                const photosByAlbum = photos.reduce((acc, photo) => {
                    const albumName = photo.album_type || 'Uncategorized';
                    if (!acc[albumName]) {
                        acc[albumName] = [];
                    }
                    acc[albumName].push(photo);
                    return acc;
                }, {});

                for (const albumName in photosByAlbum) {
                    const albumDiv = document.createElement('div');
                    albumDiv.classList.add('album-section', 'mb-4');
                    albumDiv.innerHTML = `<h5 class="fw-semibold text-secondary border-bottom pb-2">${sanitizeHTML(albumName)}</h5>`;
                    const photoGalleryDiv = document.createElement('div');
                    photoGalleryDiv.classList.add('row');

                    photosByAlbum[albumName].forEach(photo => {
                        const photoCol = document.createElement('div');
                        photoCol.classList.add('col-md-3', 'mb-3');

                        photoCol.innerHTML = `
                    <div class="card h-100">
                        <a href="${sanitizeHTML(photo.file_path.replace('uploads/', ''))}" class="glightbox" data-gallery="${sanitizeHTML(albumName)}">
                            <img src="${sanitizeHTML(photo.file_path.replace('uploads/', ''))}" class="card-img-top" alt="Patient Photo" style="height: 200px; object-fit: cover;">
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

                    albumDiv.appendChild(photoGalleryDiv);
                    photosListDiv.appendChild(albumDiv);
                }

                GLightbox({
                    selector: '.glightbox'
                });
            } else {
                photosListDiv.innerHTML = '<p class="text-muted fs-6 text-center py-4">No photos found.</p>';
            }
        }


        // Show dropzone when an album type is selected
        photoAlbumTypeSelect.addEventListener('change', function() {
            if (this.value) {
                photoDropzoneDiv.style.display = 'block';
            } else {
                photoDropzoneDiv.style.display = 'none';
            }
        });

        // Event listener for delete buttons (delegation)
        document.addEventListener('click', function(event) {
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
            confirmDeleteBtn.addEventListener('click', async function() {
                if (itemToDeleteId && itemToDeleteType) {
                    try {
                        const entity = itemToDeleteType === 'surgery' ? 'surgeries' : `${itemToDeleteType}s`;
                        const data = await apiRequest(entity, 'delete', {
                            id: itemToDeleteId
                        });

                        if (data.success) {
                            showToast(
                                `${itemToDeleteType.charAt(0).toUpperCase() + itemToDeleteType.slice(1)} deleted successfully.`,
                                'success');
                            loadPatientData(); // Refresh data
                        } else {
                            showToast(`Error deleting ${itemToDeleteType}: ${data.message || data.error}`,
                                'danger');
                        }
                    } catch (error) {
                        console.error(`Error deleting ${itemToDeleteType}:`, error);
                        showToast(`An error occurred while deleting the ${itemToDeleteType}.`,
                            'danger');
                    } finally {
                        const modal = bootstrap.Modal.getInstance(deleteConfirmModal);
                        modal.hide();
                        itemToDeleteId = null;
                        itemToDeleteType = null;
                    }
                }
            });
        }

        const photoDropzone = new Dropzone("#photo-dropzone", {
            url: "../system/upload.php", // Corrected upload script path
            paramName: "file", // The name that will be used to transfer the files
            maxFilesize: 20, // MB - Increased for HEIC files
            acceptedFiles: "image/*,.heic,.heif", // Explicitly include HEIC/HEIF files
            addRemoveLinks: true,
            autoProcessQueue: true, // Process the queue automatically
            uploadMultiple: true, // Allow multiple file uploads
            parallelUploads: 10, // How many files to upload in parallel
            createImageThumbnails: false, // Disable thumbnail creation to prevent Event object issues
            params: function() {
                return {
                    patient_id: patientId,
                    photo_album_type_id: photoAlbumTypeSelect.value
                };
            },
            init: function() {
                const myDropzone = this;

                // Listen to the "addedfile" event
                myDropzone.on("addedfile", function(file) {
                    // You can add custom logic here when a file is added
                    // If autoProcessQueue is true, the upload starts here
                });

                // Prevent thumbnail errors by handling the thumbnail event
                myDropzone.on("thumbnail", function(file, dataUrl) {
                    // Since we disabled createImageThumbnails, this shouldn't fire
                    // But if it does, ensure we have valid data
                    if (typeof dataUrl !== 'string' || dataUrl.includes('[object')) {
                        console.warn('Invalid thumbnail dataUrl detected, skipping:', dataUrl);
                        return false;
                    }
                });

                // Listen to the "successmultiple" event
                myDropzone.on("successmultiple", function(files, response) {
                    // Handle successful uploads
                    console.log("Upload successful:", response);

                    // Check if any HEIC conversions occurred
                    let message = 'Photos uploaded successfully!';
                    if (response.results && response.results.some(result => result.converted_from_heic)) {
                        message = 'Photos uploaded successfully! HEIC files were automatically converted to JPEG.';
                    }

                    showToast(message, 'success');

                    // Clear the dropzone with a small delay to prevent thumbnail race conditions
                    setTimeout(() => {
                        myDropzone.removeAllFiles();
                    }, 100);

                    const modal = bootstrap.Modal.getOrCreateInstance(uploadModal);
                    modal.hide();
                    loadPatientData(); // Refresh the photo list
                });

                // Listen to the "errormultiple" event
                myDropzone.on("errormultiple", function(files, response, xhr) {
                    // Handle errors
                    console.error("Upload error:", response);
                    let errorMessage = 'An error occurred during upload.';
                    if (response && response.error) {
                        errorMessage = 'Upload failed: ' + response.error;
                    } else if (xhr && xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.error) {
                                errorMessage = 'Upload failed: ' + errorData.error;
                            } else {
                                errorMessage = 'Upload failed: ' + xhr.responseText;
                            }
                        } catch (e) {
                            errorMessage = 'Upload failed: ' + xhr.responseText;
                        }
                    }
                    showToast(errorMessage, 'danger');
                });
            }
        });

        uploadModal.addEventListener('shown.bs.modal', function(event) {
            fetchPhotoAlbumTypes();
        });

        // Function to handle tab activation from URL parameters
        function activateTabFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const hash = window.location.hash;
            let targetTab = null;

            // Check for tab parameter in URL (e.g., ?tab=surgeries)
            if (urlParams.has('tab')) {
                targetTab = urlParams.get('tab');
            }
            // Check for hash in URL (e.g., #surgeries)
            else if (hash) {
                targetTab = hash.substring(1); // Remove the # symbol
            }

            // Activate the specified tab if it exists
            if (targetTab) {
                const tabElement = document.querySelector(`#${targetTab}-tab`);
                const tabContent = document.querySelector(`#${targetTab}`);

                if (tabElement && tabContent) {
                    // Remove active class from all tabs and content
                    document.querySelectorAll('.nav-link').forEach(tab => {
                        tab.classList.remove('active');
                        tab.setAttribute('aria-selected', 'false');
                    });
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });

                    // Activate the target tab
                    tabElement.classList.add('active');
                    tabElement.setAttribute('aria-selected', 'true');
                    tabContent.classList.add('show', 'active');

                    // Update the URL without reloading the page
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('tab', targetTab);
                    window.history.replaceState({}, '', newUrl);
                }
            }
        }

        // Function to update URL when tab is clicked
        function setupTabNavigation() {
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tabElement => {
                tabElement.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('data-bs-target').substring(1); // Remove #
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('tab', targetId);
                    window.history.replaceState({}, '', newUrl);
                });
            });
        }

        // ===== AVATAR FUNCTIONALITY =====

        const avatarUploadModal = document.getElementById('avatarUploadModal');
        const changeAvatarBtn = document.getElementById('change-avatar-btn');
        const deleteAvatarBtn = document.getElementById('delete-avatar-btn');
        const currentAvatarPreview = document.getElementById('current-avatar-preview');
        let avatarDropzone = null;

        // Initialize Avatar Dropzone
        function initializeAvatarDropzone() {
            if (typeof Dropzone === 'undefined') {
                console.error("Dropzone.js not loaded for avatar upload.");
                return;
            }

            // Destroy existing dropzone if it exists
            if (avatarDropzone) {
                avatarDropzone.destroy();
            }

            avatarDropzone = new Dropzone("#avatar-dropzone", {
                url: "../api.php",
                paramName: "avatar",
                maxFilesize: 20, // MB - Increased for HEIC files
                acceptedFiles: "image/*,.heic,.heif", // Explicitly include HEIC/HEIF files
                addRemoveLinks: true,
                maxFiles: 1,
                autoProcessQueue: true,
                createImageThumbnails: false, // Disable thumbnail creation to prevent Event object issues
                params: function() {
                    return {
                        entity: 'patients',
                        action: 'upload_avatar',
                        id: patientId
                    };
                },
                init: function() {
                    const myDropzone = this;

                    // Prevent thumbnail errors by handling the thumbnail event
                    myDropzone.on("thumbnail", function(file, dataUrl) {
                        // Since we disabled createImageThumbnails, this shouldn't fire
                        // But if it does, ensure we have valid data
                        if (typeof dataUrl !== 'string' || dataUrl.includes('[object')) {
                            console.warn('Invalid avatar thumbnail dataUrl detected, skipping:', dataUrl);
                            return false;
                        }
                    });

                    // Handle successful upload
                    myDropzone.on("success", function(file, response) {
                        console.log("Avatar upload successful:", response);

                        if (response.success) {
                            // Update the avatar image immediately
                            patientAvatarImg.src = response.avatar_url;

                            // Check if HEIC conversion occurred and show appropriate message
                            let message = response.message || 'Avatar updated successfully!';
                            showToast(message, 'success');

                            // Close the modal
                            const modal = bootstrap.Modal.getInstance(avatarUploadModal);
                            modal.hide();

                            // Clear the dropzone with a small delay to prevent race conditions
                            setTimeout(() => {
                                myDropzone.removeAllFiles();
                            }, 100);
                        } else {
                            showToast(`Avatar upload failed: ${response.error}`, 'danger');
                        }
                    });

                    // Handle upload errors
                    myDropzone.on("error", function(file, response, xhr) {
                        console.error("Avatar upload error:", response);
                        let errorMessage = 'Avatar upload failed.';

                        if (typeof response === 'string') {
                            errorMessage = response;
                        } else if (response && response.error) {
                            errorMessage = response.error;
                        }

                        showToast(errorMessage, 'danger');
                    });

                    // Handle file added
                    myDropzone.on("addedfile", function(file) {
                        // Remove any existing files (since maxFiles is 1)
                        if (myDropzone.files.length > 1) {
                            myDropzone.removeFile(myDropzone.files[0]);
                        }
                    });
                }
            });
        }

        // Handle Change Avatar Button Click
        if (changeAvatarBtn) {
            changeAvatarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Update the current avatar preview in modal
                currentAvatarPreview.src = patientAvatarImg.src;

                // Initialize dropzone if not already done
                if (!avatarDropzone) {
                    initializeAvatarDropzone();
                }

                // Show the modal
                const modal = new bootstrap.Modal(avatarUploadModal);
                modal.show();
            });
        }

        // Handle Delete Avatar Button Click
        if (deleteAvatarBtn) {
            deleteAvatarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Check if patient has a custom avatar (not the default)
                const currentSrc = patientAvatarImg.src;
                if (currentSrc.includes('avatar.png') || currentSrc.includes('default')) {
                    showToast('No custom avatar to delete.', 'warning');
                    return;
                }

                // Show confirmation dialog
                const confirmDelete = confirm('Are you sure you want to delete the current avatar? This action cannot be undone.');

                if (confirmDelete) {
                    deleteAvatar();
                }
            });
        }

        // Delete Avatar Function
        async function deleteAvatar() {
            try {
                const data = await apiRequest('patients', 'delete_avatar', {
                    patient_id: patientId,
                    avatar_url: patientAvatarImg.src
                });

                if (data.success) {
                    // Update avatar to default
                    patientAvatarImg.src = '../assets/avatar.png';
                    showToast('Avatar deleted successfully!', 'success');
                } else {
                    showToast(`Failed to delete avatar: ${data.error}`, 'danger');
                }
            } catch (error) {
                console.error('Error deleting avatar:', error);
                showToast('An error occurred while deleting the avatar.', 'danger');
            }
        }

        // Initialize everything
        loadPatientData();
        activateTabFromURL();
        setupTabNavigation();

        // Initialize avatar dropzone when modal is shown
        avatarUploadModal.addEventListener('shown.bs.modal', function() {
            if (!avatarDropzone) {
                initializeAvatarDropzone();
            }
        });
    });
</script>