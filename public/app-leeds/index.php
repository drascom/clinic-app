<?php
require_once '../includes/header.php';
?>
<style>
    #submissionsTable tbody tr {
        transition: all 0.2s ease-in-out;
        position: relative;
        /* Required for z-index to work */
    }

    #submissionsTable tbody tr:hover {
        outline: 2px solid var(--primary-color);
        z-index: 10;
    }

    .table-container {
        max-height: 70vh;
        overflow-y: auto;
    }

    .submission-id-col {
        width: 1%;
        white-space: nowrap;
    }

    .wrap-text {
        white-space: normal;
        word-break: break-word;
    }
</style>
<div class="container-fluid">
    <div class="card shadow-sm mb-4 ">
        <div class="card-header p-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class=""><i class="fas fa-file-alt me-2"></i>Leeds Form Submissions</h4>
                <a href="/calendar/calendar.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                    <i class="far fa-calendar"></i>
                    <span class="d-none d-sm-inline ms-1">Calendar</span>
                </a>
            </div>
            <div class="mt-3">
                <div class="position-relative">
                    <input type="text" id="lead-search-input" class="form-control ps-5" placeholder="Search by ID, name, email, phone, or treatment...">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ps-3 text-muted"></i>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive table-container">
                <table class="table table-bordered" id="submissionsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable submission-id-col" data-sort-by="submission_id" data-sort-order="desc">ID <i
                                    class="fas fa-sort-down ms-1"></i></th>
                            <th class="sortable" data-sort-by="name" data-sort-order="asc">Name <i
                                    class="fas fa-sort ms-1"></i></th>
                            <th class="sortable" data-sort-by="email" data-sort-order="asc">Email <i
                                    class="fas fa-sort ms-1"></i></th>
                            <th class="sortable" data-sort-by="phone" data-sort-order="asc">Phone <i
                                    class="fas fa-sort ms-1"></i></th>
                            <th class="sortable" data-sort-by="treatment" data-sort-order="asc">Treatment <i
                                    class="fas fa-sort ms-1"></i></th>
                            <th class="sortable" data-sort-by="status" data-sort-order="asc">Status <i
                                    class="fas fa-sort ms-1"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Lead Details Modal -->
<div class="modal fade" id="leadDetailsModal" tabindex="-1" aria-labelledby="leadDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadDetailsModalLabel">Lead Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Lead details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editLeadBtn">Edit</button>
                <button type="button" class="btn btn-success" id="saveLeadChangesBtn" style="display: none;">Save
                    Changes</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let leads = [];
        let currentSortColumn = 'submission_id';
        let currentSortOrder = 'desc';
        let searchTimeout = null;

        function displayLeads() {
            const searchInput = document.getElementById('lead-search-input');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

            const filteredLeads = leads.filter(lead => {
                const searchFields = [
                    lead.submission_id,
                    lead.name,
                    lead.email,
                    lead.phone,
                    lead.treatment,
                    lead.status
                ];
                return searchFields.some(field => field && field.toString().toLowerCase().includes(searchTerm));
            });

            sortAndDisplayLeads(filteredLeads, currentSortColumn, currentSortOrder);
        }

        async function fetchLeads() {
            const tableBody = document.querySelector('#submissionsTable tbody');
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

            try {
                const response = await apiRequest('leeds', 'list_leeds');
                if (response.success && Array.isArray(response.leeds)) {
                    leads = response.leeds;
                    displayLeads();
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${response.message || 'Failed to load data.'}</td></tr>`;
                }
            } catch (error) {
                console.error('Failed to fetch leads:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">An unexpected error occurred.</td></tr>';
            }
        }

        function getStatusBadgeClass(status) {
            switch (status) {
                case 'intake':
                    return 'bg-info';
                case 'not answered':
                    return 'bg-warning text-dark';
                case 'not interested':
                    return 'bg-danger';
                case 'qualified':
                    return 'bg-success';
                case 'converted':
                    return 'bg-primary';
                default:
                    return 'bg-secondary';
            }
        }

        function renderLeadsTable(leadsToRender) {
            const tableBody = document.querySelector('#submissionsTable tbody');
            tableBody.innerHTML = '';
            if (leadsToRender.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No leads found.</td></tr>';
                return;
            }
            leadsToRender.forEach(lead => {
                const badgeClass = getStatusBadgeClass(lead.status);
                const row = `
                <tr data-lead-id="${lead.id}" style="cursor: pointer;">
                    <td class="submission-id-col">${lead.submission_id}</td>
                    <td class="wrap-text">${lead.name || ''}</td>
                    <td class="">${lead.email || ''}</td>
                    <td>${lead.phone || ''}</td>
                    <td>${lead.treatment || ''}</td>
                    <td><span class="badge ${badgeClass}">${lead.status}</span></td>
                </tr>
            `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        }

        function sortAndDisplayLeads(leadsToSort, sortColumn, sortOrder) {
            const sortedLeads = [...leadsToSort].sort((a, b) => {
                let valA = a[sortColumn];
                let valB = b[sortColumn];

                if (sortColumn === 'submission_id') {
                    valA = parseInt(valA, 10) || 0;
                    valB = parseInt(valB, 10) || 0;
                } else if (valA === null || valA === undefined) {
                    valA = '';
                } else if (valB === null || valB === undefined) {
                    valB = '';
                }

                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();

                if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
                if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
                return 0;
            });

            renderLeadsTable(sortedLeads);
            attachRowClickListeners();

            currentSortColumn = sortColumn;
            currentSortOrder = sortOrder;

            document.querySelectorAll('.sortable').forEach(header => {
                const icon = header.querySelector('i');
                if (icon) {
                    icon.className = 'fas ms-1'; // Reset classes
                    if (header.dataset.sortBy === currentSortColumn) {
                        icon.classList.add(currentSortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
                    } else {
                        icon.classList.add('fa-sort');
                    }
                }
            });
        }

        fetchLeads().then(() => {
            const urlParams = new URLSearchParams(window.location.search);
            const leadId = urlParams.get('lead_id');
            if (leadId) {
                showLeadDetailsModal(leadId);
            }
        });

        const searchInput = document.getElementById('lead-search-input');
        searchInput.addEventListener('input', () => {
            if (searchTimeout) clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                displayLeads();
            }, 300);
        });

        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function () {
                const sortColumn = this.dataset.sortBy;
                let newSortOrder;

                if (currentSortColumn === sortColumn) {
                    newSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    newSortOrder = this.dataset.sortOrder || 'asc';
                }
                sortAndDisplayLeads(leads, sortColumn, newSortOrder);
            });
        });

        const leadDetailsModalEl = document.getElementById('leadDetailsModal');
        const leadDetailsModal = new bootstrap.Modal(leadDetailsModalEl);

        function attachRowClickListeners() {
            document.querySelectorAll('#submissionsTable tbody tr').forEach(row => {
                row.addEventListener('click', function () {
                    const leadId = this.dataset.leadId;
                    if (leadId) {
                        showLeadDetailsModal(leadId);
                    }
                });
            });
        }

        async function showLeadDetailsModal(leadId) {
            const modalBody = document.querySelector('#leadDetailsModal .modal-body');
            const modalFooter = document.querySelector('#leadDetailsModal .modal-footer');
            modalBody.innerHTML = '<p class="text-center">Loading...</p>';

            const editForm = document.getElementById('editLeadForm');
            if (editForm) {
                editForm.reset();
                editForm.style.display = 'none';
            }
            const displayView = document.getElementById('lead-display-view');
            if (displayView) {
                displayView.style.display = 'block';
            }

            modalFooter.querySelector('#editLeadBtn').style.display = 'inline-block';
            modalFooter.querySelector('#saveLeadChangesBtn').style.display = 'none';

            leadDetailsModal.show();

            try {
                const response = await apiRequest('leeds', 'get_lead_details', { id: leadId });
                if (response.success) {
                    const { lead, notes } = response;
                    renderModalContent(lead, notes);
                } else {
                    modalBody.innerHTML = `<p class="text-danger">Error: ${response.message || 'Failed to load details.'}</p>`;
                }
            } catch (error) {
                console.error('Failed to fetch lead details:', error);
                modalBody.innerHTML = '<p class="text-danger">An unexpected error occurred.</p>';
            }
        }

        function renderModalContent(lead, notes) {
            const modalBody = document.querySelector('#leadDetailsModal .modal-body');
            const statuses = ['intake', 'not answered', 'not interested', 'qualified', 'converted'];

            let notesHtml = '';
            if (notes && notes.length > 0) {
                notesHtml += '<ul class="list-group list-group-flush notes-list">';
                notes.forEach(note => {
                    notesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">${note.note}</p>
                            <small class="text-muted">By User ID: ${note.user_id} at ${new Date(note.created_at).toLocaleString()}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger delete-note-btn" data-note-id="${note.id}" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </li>`;
                });
                notesHtml += '</ul>';
            } else {
                notesHtml += '<p>No notes for this lead.</p>';
            }

            const content = `
                <div id="lead-display-view">
                    <h5 class="mb-3">${lead.name}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> ${lead.email || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${lead.phone || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Treatment:</strong> ${lead.treatment || 'N/A'}</p>
                            <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(lead.status)}">${lead.status}</span></p>
                        </div>
                    </div>
                </div>
                <form id="editLeadForm" style="display: none;">
                    <input type="hidden" id="editLeadId" value="${lead.id}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editLeadName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editLeadName" value="${lead.name || ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editLeadEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editLeadEmail" value="${lead.email || ''}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editLeadPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="editLeadPhone" value="${lead.phone || ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editLeadTreatment" class="form-label">Treatment</label>
                            <input type="text" class="form-control" id="editLeadTreatment" value="${lead.treatment || ''}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Status</label>
                        ${statuses.map(status => `
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="leadStatusRadio" id="status_${status}" value="${status}" ${lead.status === status ? 'checked' : ''}>
                                <label class="form-check-label" for="status_${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</label>
                            </div>
                        `).join('')}
                    </div>
                </form>
               
                 <div class="card p-2 mt-3 border align-content-center shadow-sm">
                   <div id="add-note-container" >
                   <h6>Add a new note</h6>
                    <div class="input-group mb-3">
                        <input type="text" id="newLeadNote" class="form-control" placeholder="Type your note here...">
                        <button class="btn btn-outline-secondary" type="button" id="addLeadNoteBtn">Add Note</button>
                    </div>
                     </div>
                <div id="notes-section">
                    ${notesHtml}
                </div>
                </div>
              
               
               
            `;
            modalBody.innerHTML = content;
        }

        const editLeadBtn = document.getElementById('editLeadBtn');
        const saveLeadChangesBtn = document.getElementById('saveLeadChangesBtn');

        editLeadBtn.addEventListener('click', function () {
            document.getElementById('lead-display-view').style.display = 'none';
            document.getElementById('editLeadForm').style.display = 'block';
            document.getElementById('add-note-container').style.display = 'none';
            this.style.display = 'none';
            saveLeadChangesBtn.style.display = 'inline-block';

            // Show delete buttons
            document.querySelectorAll('.delete-note-btn').forEach(btn => {
                btn.style.display = 'inline-block';
            });
        });

        saveLeadChangesBtn.addEventListener('click', async function () {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            const leadId = document.getElementById('editLeadId').value;
            const name = document.getElementById('editLeadName').value;
            const email = document.getElementById('editLeadEmail').value;
            const phone = document.getElementById('editLeadPhone').value;
            const treatment = document.getElementById('editLeadTreatment').value;
            const status = document.querySelector('input[name="leadStatusRadio"]:checked').value;

            const payload = { id: leadId, name, email, phone, treatment, status };

            try {
                const response = await apiRequest('leeds', 'update_lead', payload);
                if (response.success) {
                    leadDetailsModal.hide();
                    fetchLeads(); // Refresh the table
                } else {
                    alert('Error updating lead: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to update lead:', error);
                alert('An unexpected error occurred while updating the lead.');
            } finally {
                this.disabled = false;
                this.innerHTML = 'Save Changes';
            }
        });
        document.querySelector('#leadDetailsModal .modal-body').addEventListener('click', async function (e) {
            // Handle delete note button click
            if (e.target.closest('.delete-note-btn')) {
                const deleteBtn = e.target.closest('.delete-note-btn');
                const noteId = deleteBtn.dataset.noteId;

                if (confirm('Are you sure you want to delete this note?')) {
                    deleteBtn.disabled = true;
                    try {
                        const response = await apiRequest('leeds', 'delete_note', { id: noteId });
                        if (response.success) {
                            // Refresh the notes section
                            const leadId = document.getElementById('editLeadId').value;
                            const detailsResponse = await apiRequest('leeds', 'get_lead_details', { id: leadId });
                            if (detailsResponse.success) {
                                const { lead, notes } = detailsResponse;
                                let notesHtml = '';
                                if (notes && notes.length > 0) {
                                    notesHtml += '<ul class="list-group list-group-flush notes-list">';
                                    notes.forEach(note => {
                                        notesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-1">${note.note}</p>
                                                <small class="text-muted">By User ID: ${note.user_id} at ${new Date(note.created_at).toLocaleString()}</small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger delete-note-btn" data-note-id="${note.id}" style="display: inline-block;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </li>`;
                                    });
                                    notesHtml += '</ul>';
                                } else {
                                    notesHtml += '<p>No notes for this lead.</p>';
                                }
                                document.getElementById('notes-section').innerHTML = notesHtml;
                            }
                        } else {
                            alert('Error deleting note: ' + (response.message || 'Unknown error'));
                            deleteBtn.disabled = false;
                        }
                    } catch (error) {
                        console.error('Failed to delete note:', error);
                        alert('An unexpected error occurred while deleting the note.');
                        deleteBtn.disabled = false;
                    }
                }
                return; // Stop further execution
            }

            if (e.target && e.target.id === 'addLeadNoteBtn') {
                const addNoteBtn = e.target;
                const noteInput = document.getElementById('newLeadNote');
                const noteText = noteInput.value.trim();
                const leadId = document.getElementById('editLeadId').value;

                if (!noteText) {
                    alert('Please enter a note.');
                    return;
                }

                addNoteBtn.disabled = true;
                addNoteBtn.innerHTML = '&lt;span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"&gt;&lt;/span&gt; Adding...';

                try {
                    const response = await apiRequest('leeds', 'add_note', {
                        lead_id: leadId,
                        note: noteText,
                        user_id: currentUserId
                    });

                    if (response.success) {
                        noteInput.value = '';
                        // Refresh notes
                        const detailsResponse = await apiRequest('leeds', 'get_lead_details', { id: leadId });
                        if (detailsResponse.success) {
                            const { lead, notes } = detailsResponse;

                            let notesHtml = '';
                            if (notes && notes.length > 0) {
                                notesHtml += '<ul class="list-group list-group-flush">';
                                notes.forEach(note => {
                                    notesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-1">${note.note}</p>
                                            <small class="text-muted">By User ID: ${note.user_id} at ${new Date(note.created_at).toLocaleString()}</small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger delete-note-btn" data-note-id="${note.id}" style="display: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </li>`;
                                });
                                notesHtml += '</ul>';
                            } else {
                                notesHtml += '<ul> No notes for this lead.</ul>';
                            }
                            document.getElementById('notes-section').innerHTML = notesHtml;
                        }

                    } else {
                        alert('Error adding note: ' + (response.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Failed to add note:', error);
                    alert('An unexpected error occurred while adding the note.');
                } finally {
                    addNoteBtn.disabled = false;
                    addNoteBtn.innerHTML = 'Add Note';
                }
            }
        });
    });
</script>