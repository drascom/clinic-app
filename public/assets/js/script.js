// Helper function to get status color for badges
function getStatusColor(status) {
  switch (status.toLowerCase()) {
    case "completed":
      return "success";
    case "booked":
      return "primary";
    case "cancelled":
      return "danger";
    case "in-progress":
      return "warning";
    default:
      return "secondary";
  }
}

 // Initialize tooltips globally
 document.addEventListener('DOMContentLoaded', function() {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
  })
});
// Initialize all Bootstrap toasts
document.addEventListener("DOMContentLoaded", function () {
  var toastElList = [].slice.call(document.querySelectorAll(".toast"));
  var toastList = toastElList.map(function (toastEl) {
    return new bootstrap.Toast(toastEl);
  });
});
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const html = document.documentElement;
        html.dataset.bsTheme =
            html.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', html.dataset.bsTheme);
    });
}

// on load
document.documentElement.dataset.bsTheme =
    localStorage.getItem('theme') ?? 'light';
// Function to show a Bootstrap toast message
function showToast(message, type = "info", delay = 1000) {
  const toastContainer = document.querySelector(".toast-container");
  if (!toastContainer) {
    console.error("Toast container not found!");
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

  toastContainer.insertAdjacentHTML("beforeend", toastHtml);
  const toastEl = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastEl);

  toastEl.addEventListener("hidden.bs.toast", function () {
    toastEl.remove();
  });

  toast.show();
}
class ThemeManager {
  constructor() {
    this.isDarkTheme = localStorage.getItem("theme") === "dark";
    this.applyTheme();
    this.setupEventListeners();
  }

  getElement(id) {
    return document.getElementById(id);
  }

  applyTheme() {
    const html = document.documentElement;
    const themeBtn = this.getElement("theme-btn");

    if (this.isDarkTheme) {
      html.setAttribute("data-bs-theme", "dark");
      document.body.classList.add("dark-mode");
      if (themeBtn) {
        const icon = themeBtn.querySelector("i");
        if (icon) {
          icon.classList.remove("fas", "fa-moon");
          icon.classList.add("fas", "fa-sun");
        }
      }
    } else {
      html.setAttribute("data-bs-theme", "light");
      document.body.classList.remove("dark-mode");
      if (themeBtn) {
        const icon = themeBtn.querySelector("i");
        if (icon) {
          icon.classList.remove("fas", "fa-sun");
          icon.classList.add("fas", "fa-moon");
        }
      }
    }
  }

  setupEventListeners() {
    const themeBtn = this.getElement("theme-btn");
    if (themeBtn) {
      themeBtn.addEventListener(
        "click",
        () => {
          this.isDarkTheme = !this.isDarkTheme;
          localStorage.setItem("theme", this.isDarkTheme ? "dark" : "light");
          this.applyTheme();
        },
        { passive: true }
      );
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const loadingSpinner = document.getElementById("loading-spinner");
  const mainContent = document.getElementById("main-content");

  if (loadingSpinner && mainContent) {
    loadingSpinner.style.display = "none"; // Hide the spinner
    mainContent.style.display = "block"; // Show the main content
  }
});

document.addEventListener("DOMContentLoaded", function () {
  new ThemeManager();
});
// Initialize all Bootstrap toasts
document.addEventListener("DOMContentLoaded", function () {
  var toastElList = [].slice.call(document.querySelectorAll(".toast"));
  var toastList = toastElList.map(function (toastEl) {
    return new bootstrap.Toast(toastEl);
  });
});

// The Select2 initialization has been moved to the specific pages that use it.

// Function to show a general form error alert
function showFormError(message) {
  const errorAlert = document.getElementById("form-error-alert");
  const errorMessageSpan = document.getElementById("form-error-message");
  if (errorAlert && errorMessageSpan) {
    errorMessageSpan.textContent = message;
    errorAlert.classList.remove("d-none");
  }
}

// Function to hide the general form error alert
function hideFormError() {
  const errorAlert = document.getElementById("form-error-alert");
  if (errorAlert) {
    errorAlert.classList.add("d-none");
  }
}
