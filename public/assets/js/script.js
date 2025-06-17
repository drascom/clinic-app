
// Helper function to get status color for badges
function getStatusColor(status) {
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
// Function to show a Bootstrap toast message
function showToast(message, type = 'info', delay = 1000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        console.error('Toast container not found!');
        return;
    }

    const toastId = `toast-${Date.now()}`;
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${delay}">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl);

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });

    toast.show();
}
class ThemeManager {
    constructor() {
        this.isDarkTheme = localStorage.getItem('theme') === 'dark';
        this.applyTheme();
        this.setupEventListeners();
    }

    getElement(id) {
        return document.getElementById(id);
    }

    applyTheme() {
        const html = document.documentElement;
        const themeBtn = this.getElement('theme-btn');

        if (this.isDarkTheme) {
            html.setAttribute('data-bs-theme', 'dark');
            document.body.classList.add('dark-mode');
            if (themeBtn) {
                themeBtn.innerHTML = '<i class="fas fa-sun"></i>'; // Show sun icon for dark mode
            }
        } else {
            html.setAttribute('data-bs-theme', 'light');
            document.body.classList.remove('dark-mode');
            if (themeBtn) {
                themeBtn.innerHTML = '<i class="fas fa-moon"></i>'; // Show moon icon for light mode
            }
        }
    }

    setupEventListeners() {
        const themeBtn = this.getElement('theme-btn');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                this.isDarkTheme = !this.isDarkTheme;
                localStorage.setItem('theme', this.isDarkTheme ? 'dark' : 'light');
                this.applyTheme();
            }, { passive: true });
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const loadingSpinner = document.getElementById('loading-spinner');
    const mainContent = document.getElementById('main-content');

    if (loadingSpinner && mainContent) {
        loadingSpinner.style.display = 'none'; // Hide the spinner
        mainContent.style.display = 'block'; // Show the main content
    }
});

document.addEventListener('DOMContentLoaded', function () {
    new ThemeManager();
});
// Initialize all Bootstrap toasts
document.addEventListener('DOMContentLoaded', function () {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'))
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl)
    })
});