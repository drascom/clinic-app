<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/auth.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$page_title = "Room Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container emp">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card">
        <div class="card-header">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center p-2">
                <h4 class="mb-0">
                    <i class="fas fa-door-open me-2 text-primary"></i>
                    Room Management
                </h4>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                        data-bs-target="#roomModal" onclick="openRoomModal()">
                        <i class="fas fa-plus me-1"></i>
                        <span class="d-none d-sm-inline">Add Room</span>
                    </button>
                    <a href="/calendar/room_availability.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-1"></i>
                        <span class="d-none d-sm-inline">Availability</span>
                    </a>
                </div>
            </div>
            <!-- Search Bar -->
            <fieldset class="p-4 frosted">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="search-input"
                        placeholder="Search rooms by name or type...">
                    <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="text-muted small ms-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <span id="records-count">Loading...</span> records found
                </div>
            </fieldset>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="rooms-table" style="table-layout: fixed; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rooms-tbody">
                        <!-- Rooms will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalLabel">Add Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="room-form">
                <div class="modal-body">
                    <input type="hidden" id="room-id" name="id">

                    <div class="mb-3">
                        <label for="room-name" class="form-label">Room Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="room-name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="room-types" class="form-label">Status</label>
                        <select class="form-select" id="room-type" name="type">
                            <option value="">Select Type</option>
                            <option value="surgery">Surgery</option>
                            <option value="consultation">Consultation</option>
                            <option value="treatment">Treatment</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="room-submit-btn">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let isEditing = false;

    document.addEventListener('DOMContentLoaded', function () {
        loadRooms();

        // Room form submission
        document.getElementById('room-form').addEventListener('submit', function (e) {
            e.preventDefault();
            saveRoom();
        });

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#rooms-tbody tr');
                rows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const type = row.cells[1].textContent.toLowerCase();
                    if (name.includes(searchTerm) || type.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                loadRooms();
            });
        }
    });

    function loadRooms() {
        showLoading(true);

        apiRequest('rooms', 'list')
            .then(data => {
                if (data.success) {
                    renderRoomsTable(data.rooms);
                } else {
                    showToast('Error loading rooms: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading rooms:', error);
                showToast('Failed to load rooms. Please try again.', 'danger');
            })
            .finally(() => {
                showLoading(false);
            });
    }

    function renderRoomsTable(rooms) {
        const tbody = document.getElementById('rooms-tbody');

        if (rooms.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-door-open fa-2x mb-2"></i><br>
                    No rooms found. <a href="#" onclick="openRoomModal()">Add your first room</a>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = rooms.map(room => `
        <tr>
            <td>
                <strong>${escapeHtml(room.name)}</strong>
            </td>
            <td>
                ${room.types ? escapeHtml(room.types) : '<span class="text-muted">No types</span>'}
            </td>
            <td>
                <span class="badge ${room.is_active ? 'bg-success' : 'bg-secondary'}">
                    ${room.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm justify-space-between" role="group">
                    <button type="button" class="btn btn-text text-primary" onclick="editRoom(${room.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                        <span class="d-none d-lg-inline ms-1">Edit</span>
                    </button>
                    ${room.is_active ? `
                        <button type="button" class="btn btn-text text-info" onclick="toggleRoomStatus(${room.id}, '${escapeHtml(room.name)}',0)" title="deactivate">
                            <i class="fas fa-check"></i>
                             <span class="d-none d-lg-inline ms-1">Suspend</span>
                        </button>
                    ` : `
                        <button type="button" class="btn btn-text text-warning" onclick="toggleRoomStatus(${room.id}, '${escapeHtml(room.name)}',1)" title="activate">
                            <i class="fas fa-hand-paper"></i>
                             <span class="d-none d-lg-inline ms-1">Activate</span>
                        </button>
                    `}
                     <button type="button" class="btn btn-text text-danger" onclick="deleteRoom(${room.id}, '${escapeHtml(room.name)}')" title="activate">
                            <i class="fas fa-trash"></i><span class="d-none d-lg-inline ms-1">Delete</span>
                        </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function openRoomModal(roomId = null) {
        isEditing = !!roomId;
        const modal = document.getElementById('roomModal');
        const modalTitle = document.getElementById('roomModalLabel');
        const submitBtn = document.getElementById('room-submit-btn');

        // Reset form
        const form = document.getElementById('room-form');
        form.reset();
        form.classList.remove('was-validated');
        document.getElementById('room-id').value = '';

        if (isEditing) {
            modalTitle.textContent = 'Edit Room';
            submitBtn.textContent = 'Update Room';
            loadRoomData(roomId);
        } else {
            modalTitle.textContent = 'Add Room';
            submitBtn.textContent = 'Save Room';
        }

        new bootstrap.Modal(modal).show();
    }

    function loadRoomData(roomId) {
        apiRequest('rooms', 'get', {
            id: roomId
        })
            .then(data => {
                if (data.success) {
                    const room = data.room;
                    document.getElementById('room-id').value = room.id;
                    document.getElementById('room-name').value = room.name;
                    document.getElementById('room-types').value = room.types || '';
                } else {
                    showToast('Error loading room data: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error loading room data:', error);
                showToast('Failed to load room data.', 'danger');
            });
    }

    function saveRoom() {
        const form = document.getElementById('room-form');

        // Add Bootstrap validation classes
        form.classList.add('was-validated');

        // Check form validity
        if (!form.checkValidity()) {
            showToast('Please fill in all required fields correctly.', 'danger');
            return;
        }

        const formData = new FormData(form);
        formData.append('entity', 'rooms');
        formData.append('action', isEditing ? 'update' : 'create');

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    loadRooms(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error saving room:', error);
                showToast('Failed to save room. Please try again.', 'danger');
            });
    }

    function editRoom(roomId) {
        openRoomModal(roomId);
    }

    function deleteRoom(roomId, roomName) {
        if (!confirm(`Are you sure you want to archive "${roomName}"? This will make it unavailable for new bookings.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('entity', 'rooms');
        formData.append('action', 'delete');
        formData.append('id', roomId);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRooms(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error archiving room:', error);
                showToast('Failed to archive room. Please try again.', 'danger');
            });
    }

    function toggleRoomStatus(roomId, roomName, status) {
        if (!confirm(`Are you sure you want to archive "${roomName}"? This will make it unavailable for new bookings.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('entity', 'rooms');
        formData.append('action', 'toggle');
        formData.append('id', roomId);
        formData.append('status', status);

        fetch('/api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadRooms(); // Reload the table
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error archiving room:', error);
                showToast('Failed to archive room. Please try again.', 'danger');
            });
    }

    function showLoading(show) {
        const spinner = document.getElementById('loading-spinner');
        if (show) {
            spinner.style.display = 'block';
        } else {
            spinner.style.display = 'none';
        }
    }


    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>