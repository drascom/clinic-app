<?php
require_once '../includes/header.php';
?>

<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">

                <!-- Centered card with shadow -->
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <h2 class="mb-0">Add New Appointment</h2>
                    </div>

                    <div class="card-body">

                        <!-- Step indicators -->
                        <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-patient-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-patient" type="button" role="tab"
                                    aria-controls="pills-patient" aria-selected="true">
                                    Step 1: Patient
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-purpose-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-purpose" type="button" role="tab"
                                    aria-controls="pills-purpose" aria-selected="false">
                                    Step 2: Purpose
                                </button>
                            </li>
                            <li class="nav-item d-none" role="presentation" id="step-3-pill">
                                <button class="nav-link" id="pills-service-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-service" type="button" role="tab"
                                    aria-controls="pills-service" aria-selected="false">
                                    Step 3
                                </button>
                            </li>
                            <li class="nav-item d-none" role="presentation" id="step-4-pill">
                                <button class="nav-link" id="pills-datetime-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-datetime" type="button" role="tab"
                                    aria-controls="pills-datetime" aria-selected="false">
                                    Step 4
                                </button>
                            </li>
                            <li class="nav-item d-none" role="presentation" id="step-5-pill">
                                <button class="nav-link" id="pills-notes-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-notes" type="button" role="tab" aria-controls="pills-notes"
                                    aria-selected="false">
                                    Step 5
                                </button>
                            </li>
                        </ul>

                        <!-- Step content -->
                        <div class="tab-content" id="pills-tabContent">

                            <!-- Step 1: Patient -->
                            <div class="tab-pane fade show active" id="pills-patient" role="tabpanel"
                                aria-labelledby="pills-patient-tab">
                                <fieldset class="border rounded p-3 mb-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                                        <legend class="w-auto px-3 m-0 p-0" style="font-size:1rem;">
                                            <i class="far fa-user me-2"></i>Patient Name<span
                                                class="text-danger">*</span>
                                        </legend>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 py-0 px-2 m-0"
                                            data-bs-toggle="modal" data-bs-target="#newPatientModal">
                                            <i class="far fa-plus"></i>
                                            <span class="d-none d-sm-inline">Add</span>
                                        </button>
                                    </div>
                                    <div class="input-group">
                                        <select class="form-select select2-enable" id="patient-select" required>
                                            <option value="">Select Patient</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </fieldset>
                            </div>

                            <!-- Step 2: Purpose -->
                            <div class="tab-pane fade" id="pills-purpose" role="tabpanel"
                                aria-labelledby="pills-purpose-tab">
                                <fieldset class="border rounded p-3 mb-3 shadow-sm  text-center">
                                    <legend>Purpose of Visit</legend>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                        <button id="btn-consultation"
                                            class="btn btn-outline-primary px-4">Consultation</button>
                                        <button id="btn-treatment"
                                            class="btn btn-outline-success px-4">Treatment</button>
                                    </div>
                                    <input type="hidden" id="appointment-type" name="appointment_type" value="">
                                </fieldset>
                            </div>

                            <!-- Step 3: Service (dynamic) -->
                            <div class="tab-pane fade" id="pills-service" role="tabpanel"
                                aria-labelledby="pills-service-tab">
                                <!-- populated by JS -->
                            </div>

                            <!-- Step 4: Date & Time -->
                            <div class="tab-pane fade" id="pills-datetime" role="tabpanel"
                                aria-labelledby="pills-datetime-tab">
                                <fieldset class="border rounded p-3 mb-3 shadow-sm  text-center">
                                    <legend>Select Date &amp; Time</legend>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div id="datepicker-container"></div>
                                            <input type="hidden" id="appointment-date">
                                        </div>
                                        <div class="col-md-6">
                                            <div id="time-slots-container"
                                                class="row g-1 justify-content-center mt-3 d-none">
                                                <?php
                                                $start = new DateTime('08:30');
                                                $end = new DateTime('17:00');
                                                $interval = new DateInterval('PT30M');
                                                foreach (new DatePeriod($start, $interval, $end) as $slot) {
                                                    $from = $slot->format('H:i');
                                                    $to = (clone $slot)->add($interval)->format('H:i');
                                                    echo '<div class="col-auto">';
                                                    echo '<button type="button"
                                                             class="btn btn-sm btn-outline-primary time-slot-btn"
                                                             data-start="' . $from . '"
                                                             data-end="' . $to . '">'
                                                        . $from . ' – ' . $to .
                                                        '</button></div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>

                            <!-- Step 5: Notes -->
                            <div class="tab-pane fade" id="pills-notes" role="tabpanel"
                                aria-labelledby="pills-notes-tab">
                                <fieldset class="mb-3 text-center">
                                    <legend>Additional Notes</legend>
                                    <textarea id="notes" class="form-control" rows="4"
                                        placeholder="Enter any extra information here…"></textarea>
                                </fieldset>
                            </div>

                        </div><!-- /.tab-content -->

                    </div><!-- /.card-body -->

                    <div class="card-footer d-flex justify-content-between">
                        <button id="btn-prev" class="btn btn-outline-secondary d-none">Previous</button>
                        <div class="ms-auto">
                            <button id="btn-next" class="btn btn-primary">Next</button>
                            <button id="btn-save" class="btn btn-success d-none">Save Appointment</button>
                        </div>
                    </div>
                </div><!-- /.card -->

            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container -->
    <!-- New Patient Modal -->
    <div class="modal fade" id="newPatientModal" tabindex="-1" aria-labelledby="newPatientModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPatientModalLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        Create New Patient
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-patient-form" novalidate>
                        <?php if (is_admin() || is_editor()): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="new_patient_agency_id" class="form-label">
                                            <i class="far fa-building me-1"></i>
                                            Agency<span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="new_patient_agency_id" name="agency_id" required>
                                            <option value="">Select Agency</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (is_agent()): ?>
                            <input type="hidden" id="new_patient_agency_id" name="agency_id" value="">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_patient_name" class="form-label">
                                        <i class="far fa-user me-1"></i>
                                        Patient Name *
                                    </label>
                                    <input type="text" class="form-control" id="new_patient_name" name="name"
                                        placeholder="Enter patient name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_patient_dob" class="form-label">
                                        <i class="far fa-calendar me-1"></i>
                                        Date of Birth
                                    </label>
                                    <input type="date" class="form-control" id="new_patient_dob" name="dob">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div id="new-patient-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn  btn-outline-primary" id="save-new-patient">
                        <i class="far fa-save me-1"></i>Create Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Procedure Modal -->
    <div class="modal fade" id="newProcedureModal" tabindex="-1" aria-labelledby="newProcedureModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newProcedureModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>
                        Create New Procedure
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-procedure-form">
                        <div class="mb-3">
                            <label for="new_procedure_name" class="form-label">
                                <i class="fas fa-stethoscope me-1"></i>
                                Procedure Name *
                            </label>
                            <input type="text" class="form-control" id="new_procedure_name" name="name"
                                placeholder="Enter procedure name" required>
                            <div class="form-text">Enter a unique name for the new procedure</div>
                        </div>
                    </form>
                    <div id="new-procedure-status"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn  btn-primary" id="save-new-procedure">
                        <i class="far fa-save me-1"></i>Create Procedure
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    ?>

    <!-- Progressive-enhancement helpers (load plugins only when needed) -->
    <script>
        // const scriptCache = new Map();
        // function loadScriptOnce(src) {
        //     if (scriptCache.has(src)) return scriptCache.get(src);
        //     const p = new Promise((resolve, reject) => {
        //         const s = document.createElement('script');
        //         s.src = src;
        //         s.onload = resolve;
        //         s.onerror = reject;
        //         document.head.appendChild(s);
        //     });
        //     scriptCache.set(src, p);
        //     return p;
        // }
        // window.ensureDatePicker = () => loadScriptOnce('https://cdn.jsdelivr.net/npm/@chenfengyuan/datepicker/dist/datepicker.min.js');
    </script>

    <script src="page.js"></script>
</body>