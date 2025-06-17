<?php
require_once 'includes/db.php';
require_once 'auth/auth.php';

$page_title = "Dashboard";
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="container-fluid emp">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                Dashboard
            </h2>
            <p class="text-muted mb-0">Overview of your clinic operations</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="monthSelect" class="form-label mb-0 me-2">Month:</label>
            <input type="month" id="monthSelect" class="form-control form-control-sm" style="width: auto;">
            <button id="refreshBtn" class="btn btn-primary btn-sm">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading dashboard data...</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4" id="stats-cards">
        <!-- Cards will be populated by JavaScript -->
    </div>
    <?php if (is_admin() && is_editor()): ?>

        <!-- Quick Navigation -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Quick Navigation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="calendar/calendar.php"
                                    class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none">
                                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                    <span class="fw-semibold">Calendar</span>
                                    <small class="text-muted">Surgery Schedule</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="technician/tech_availability.php"
                                    class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none">
                                    <i class="fas fa-user-clock fa-2x mb-2"></i>
                                    <span class="fw-semibold">Tech Availability</span>
                                    <small class="text-muted">Manage Technicians</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="patient/patients.php"
                                    class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <span class="fw-semibold">Patients</span>
                                    <small class="text-muted">Patient Management</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="appointment/appointments.php"
                                    class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 text-decoration-none">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <span class="fw-semibold">Appointments</span>
                                    <small class="text-muted">Schedule & Manage</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Charts Section -->
    <div class="row g-3">
        <!-- Yearly Surgery Chart -->
        <div class="col-lg-<?php echo is_admin() && is_editor() ? '8' : '12'; ?>">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Yearly Surgery Overview
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="yearSelect" class="form-label mb-0 me-2">Year:</label>
                        <select id="yearSelect" class="form-select form-select-sm" style="width: auto;">
                            <!-- Years will be populated by JavaScript -->
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="surgeryChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <?php if (is_admin() && is_editor()): ?>
            <!-- Technician Availability Analysis -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-cog me-2"></i>
                            Technician Analysis
                        </h5>
                    </div>
                    <div class="card-body" id="tech-analysis">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    class Dashboard {
        constructor() {
            this.currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM format
            this.currentYear = new Date().getFullYear();
            this.surgeryChart = null;

            this.initializeElements();
            this.setupEventListeners();
            this.loadDashboardData();
        }

        initializeElements() {
            this.monthSelect = document.getElementById('monthSelect');
            this.yearSelect = document.getElementById('yearSelect');
            this.refreshBtn = document.getElementById('refreshBtn');
            this.loadingSpinner = document.getElementById('loading-spinner');
            this.statsCards = document.getElementById('stats-cards');
            this.techAnalysis = document.getElementById('tech-analysis'); // May be null for non-admin users

            // Set current month
            this.monthSelect.value = this.currentMonth;

            // Populate year select
            const currentYear = new Date().getFullYear();
            for (let year = currentYear - 2; year <= currentYear + 1; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true;
                this.yearSelect.appendChild(option);
            }
        }

        setupEventListeners() {
            this.monthSelect.addEventListener('change', () => {
                this.currentMonth = this.monthSelect.value;
                this.loadDashboardData();
            });

            this.yearSelect.addEventListener('change', () => {
                this.currentYear = parseInt(this.yearSelect.value);
                this.loadYearlyChart();
            });

            this.refreshBtn.addEventListener('click', () => {
                this.loadDashboardData();
            });
        }

        showLoading(show) {
            this.loadingSpinner.style.display = show ? 'block' : 'none';
        }

        async loadDashboardData() {
            this.showLoading(true);

            try {
                // Load dashboard data - only load tech analysis if element exists
                const promises = [
                    this.loadStats(),
                    this.loadYearlyChart()
                ];

                // Only load tech analysis if the element exists (admin/editor users)
                if (this.techAnalysis) {
                    promises.push(this.loadTechAnalysis());
                }

                await Promise.all(promises);
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                this.showError('Failed to load dashboard data');
            } finally {
                this.showLoading(false);
            }
        }

        async loadStats() {
            try {
                const response = await apiRequest('dashboard', 'stats', {
                    month: this.currentMonth
                });

                if (!response.success) {
                    throw new Error(response.error || 'Failed to load statistics');
                }

                this.renderStatsCards(response.stats);
            } catch (error) {
                console.error('Error loading stats:', error);
                throw error;
            }
        }

        renderStatsCards(stats) {
            // Individual cards for patients
            const individualCards = [
                {
                    title: 'Total Patients',
                    value: stats.total_patients,
                    icon: 'fas fa-users',
                    color: 'primary',
                    description: 'New patients this month'
                },
                {
                    title: 'Patients with Surgeries',
                    value: stats.patients_with_surgeries,
                    icon: 'fas fa-user-md',
                    color: 'success',
                    description: 'Patients who had surgery'
                },
                {
                    title: 'Patients with Appointments',
                    value: stats.patients_with_appointments,
                    icon: 'fas fa-calendar-check',
                    color: 'info',
                    description: 'Patients with appointments'
                }
            ];

            // Combined operations card
            const combinedCard = `
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2 text-warning"></i>
                                Operations Overview
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <div class="text-warning">
                                        <i class="fas fa-hospital fa-2x mb-2"></i>
                                        <h4 class="fw-bold mb-1">${stats.total_surgeries}</h4>
                                        <small class="text-muted">Surgeries</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-secondary">
                                        <i class="fas fa-seedling fa-2x mb-2"></i>
                                        <h4 class="fw-bold mb-1">${stats.total_grafts}</h4>
                                        <small class="text-muted">Grafts</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-dark">
                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                        <h4 class="fw-bold mb-1">${stats.total_appointments}</h4>
                                        <small class="text-muted">Appointments</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Surgery status card
            const surgeryStatusCard = `
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clipboard-check me-2 text-info"></i>
                                Surgery Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center mb-3">
                                <div class="col-6">
                                    <div class="text-warning">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <h4 class="fw-bold mb-1">${stats.reserved_surgeries}</h4>
                                        <small class="text-muted">Reserved</small>
                                        <div class="text-warning fw-semibold">${stats.reserved_percentage}%</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-success">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <h4 class="fw-bold mb-1">${stats.confirmed_surgeries}</h4>
                                        <small class="text-muted">Confirmed</small>
                                        <div class="text-success fw-semibold">${stats.confirmed_percentage}%</div>
                                    </div>
                                </div>
                            </div>
                            ${stats.total_status_surgeries > 0 ? `
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar"
                                     style="width: ${stats.reserved_percentage}%"
                                     title="Reserved: ${stats.reserved_percentage}%"></div>
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: ${stats.confirmed_percentage}%"
                                     title="Confirmed: ${stats.confirmed_percentage}%"></div>
                            </div>
                            <small class="text-muted d-block text-center mt-2">
                                Total: ${stats.total_status_surgeries} surgeries
                            </small>
                            ` : `
                            <div class="text-center text-muted">
                                <small>No reserved or confirmed surgeries this month</small>
                            </div>
                            `}
                        </div>
                    </div>
                </div>
            `;

            // Render individual cards + combined cards
            const individualCardsHtml = individualCards.map(card => `
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <div class="rounded-circle bg-${card.color} bg-opacity-10 p-3">
                                    <i class="${card.icon} fa-2x text-${card.color}"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-${card.color} mb-1">${card.value}</h3>
                            <h6 class="card-title mb-1">${card.title}</h6>
                            <small class="text-muted">${card.description}</small>
                        </div>
                    </div>
                </div>
            `).join('');

            this.statsCards.innerHTML = individualCardsHtml + combinedCard + surgeryStatusCard;
        }

        async loadYearlyChart() {
            try {
                const response = await apiRequest('dashboard', 'yearlyChart', {
                    year: this.currentYear
                });

                if (!response.success) {
                    throw new Error(response.error || 'Failed to load yearly chart data');
                }

                this.renderSurgeryChart(response.chartData);
            } catch (error) {
                console.error('Error loading yearly chart:', error);
                throw error;
            }
        }

        renderSurgeryChart(data) {
            const ctx = document.getElementById('surgeryChart').getContext('2d');

            // Destroy existing chart if it exists
            if (this.surgeryChart) {
                this.surgeryChart.destroy();
            }

            this.surgeryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.month_name),
                    datasets: [
                        {
                            label: 'Surgeries',
                            data: data.map(item => item.surgery_count),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Grafts',
                            data: data.map(item => item.graft_count),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Number of Surgeries'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Number of Grafts'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `Surgery and Graft Overview - ${this.currentYear}`
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        async loadTechAnalysis() {
            // Skip if tech analysis element doesn't exist (non-admin users)
            if (!this.techAnalysis) {
                return;
            }

            try {
                const response = await apiRequest('dashboard', 'techAvailability', {
                    month: this.currentMonth
                });

                if (!response.success) {
                    throw new Error(response.error || 'Failed to load technician analysis');
                }

                this.renderTechAnalysis(response.data);
            } catch (error) {
                console.error('Error loading tech analysis:', error);
                throw error;
            }
        }

        renderTechAnalysis(data) {
            // Skip if tech analysis element doesn't exist (non-admin users)
            if (!this.techAnalysis) {
                return;
            }

            const statusColor = data.status === 'surplus' ? 'success' : 'danger';
            const statusIcon = data.status === 'surplus' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            const statusText = data.status === 'surplus' ? 'Surplus' : 'Deficit';

            this.techAnalysis.innerHTML = `
            <div class="row g-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Current Month Analysis</h6>
                        <span class="badge bg-${statusColor}">
                            <i class="fas ${statusIcon} me-1"></i>
                            ${statusText}
                        </span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border rounded p-3 mb-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary">
                                    <i class="fas fa-hospital fa-2x mb-2"></i>
                                    <h4 class="mb-0">${data.surgery_count}</h4>
                                    <small class="text-muted">Surgeries</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-warning">
                                    <i class="fas fa-user-clock fa-2x mb-2"></i>
                                    <h4 class="mb-0">${data.required_tech_days}</h4>
                                    <small class="text-muted">Required Days</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-success">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <h4 class="mb-0">${data.available_tech_days}</h4>
                                    <small class="text-muted">Available Days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-${statusColor} mb-0">
                        <div class="d-flex align-items-center">
                            <i class="fas ${statusIcon} fa-2x me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">
                                    ${Math.abs(data.difference)} Day ${statusText}
                                </h6>
                                <p class="mb-0">
                                    ${data.status === 'surplus'
                    ? `You have ${data.difference} extra technician days available.`
                    : `You need ${Math.abs(data.difference)} more technician days.`
                }
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Each surgery requires minimum 2 technicians
                    </small>
                </div>
            </div>
        `;
        }

        showError(message) {
            // Simple error display - you can enhance this with toast notifications
            alert(message);
        }
    }

    // Initialize dashboard when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        new Dashboard();
    });
</script>

<?php require_once 'includes/footer.php'; ?>