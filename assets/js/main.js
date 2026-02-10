// FUTURE AUTOMOTIVE - Main JavaScript File
// Interactive Features and AJAX Functionality

// Global variables
let sidebarOpen = false;
let currentLanguage = 'fr';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize modals
    initializeModals();

    try {
        const params = new URLSearchParams(window.location.search);
        const modalToOpen = params.get('openModal');
        if (modalToOpen) {
            openModal(modalToOpen);
        }
    } catch (e) {
    }
    
    // Initialize animations
    initializeAnimations();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize auto-refresh
    initializeAutoRefresh();
    
    // Initialize keyboard shortcuts
    initializeKeyboardShortcuts();

    // Initialize header date/time display
    initializeHeaderDateTime();
    
    console.log('🚀 FUTURE AUTOMOTIVE initialized successfully');
}

function pad2(n) {
    return String(n).padStart(2, '0');
}

function formatDateTimeNumeric(date) {
    const hh = pad2(date.getHours());
    const mm = pad2(date.getMinutes());
    const ss = pad2(date.getSeconds());
    return `${hh}:${mm}:${ss}`;
}

function initializeHeaderDateTime() {
    const el = document.getElementById('currentDateTime');
    if (!el) return;

    const render = () => {
        el.textContent = formatDateTimeNumeric(new Date());
    };

    render();
    setInterval(render, 1000);
}

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebarOpen = !sidebarOpen;
    
    if (sidebarOpen) {
        sidebar.classList.add('active');
        mainContent.style.marginLeft = '0';
    } else {
        sidebar.classList.remove('active');
        mainContent.style.marginLeft = '280px';
    }
}

// Language switching
function changeLanguage(lang) {
    currentLanguage = lang;
    document.documentElement.lang = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    
    // Store preference
    localStorage.setItem('language', lang);
    
    // Reload page to apply language
    location.reload();
}

// Dashboard refresh
function refreshDashboard() {
    showLoadingSpinner();
    
    // Fetch fresh stats from API
    refreshDashboardStats();
    
    setTimeout(() => {
        hideLoadingSpinner();
        showNotification('Dashboard refreshed successfully', 'success');
    }, 1000);
}

// Animate numeric values
function animateValue(element, start, end, duration) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            element.textContent = formatNumber(end);
            clearInterval(timer);
        } else {
            element.textContent = formatNumber(Math.floor(current));
        }
    }, 16);
}

// Format numbers with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Currency formatting
function formatCurrency(amount, currency = null) {
    // Always use MAD (Moroccan Dirham)
    const curr = currency || window.CURRENCY || 'MAD';
    const locale = document.documentElement.lang === 'ar' ? 'ar-SA' : 'fr-FR';
    
    // Format with Moroccan Dirham
    return new Intl.NumberFormat(locale, {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount) + ' DH';
}

// Date formatting
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString(currentLanguage === 'ar' ? 'ar-SA' : 'fr-FR');
}

// Show loading spinner
function showLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = `
        <div class="spinner"></div>
        <p>Loading...</p>
    `;
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideLoadingSpinner() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="btn-close">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Get notification icon based on type
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

// Show tooltip
function showTooltip(element) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-popup';
    tooltip.textContent = element.dataset.tooltip;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
}

// Hide tooltip
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip-popup');
    if (tooltip) {
        tooltip.remove();
    }
}

// Initialize modals
function initializeModals() {
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// Open modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Prefer Bootstrap modal when markup uses .modal.fade
        if (window.bootstrap && modal.classList.contains('modal')) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Focus first input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (window.bootstrap && modal.classList.contains('modal')) {
            const instance = bootstrap.Modal.getInstance(modal);
            if (instance) {
                instance.hide();
            } else {
                modal.style.display = 'none';
            }
        } else {
            modal.style.display = 'none';
        }
        document.body.style.overflow = 'auto';
    }
}

// Initialize animations
function initializeAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe all fade-in elements
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });
}

// Initialize form validations
function initializeFormValidations() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'error');
            }
        });
    });
}

// Validate form
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Initialize auto-refresh
function initializeAutoRefresh() {
    // Auto-refresh dashboard every 30 seconds
    if (document.querySelector('.stats-grid')) {
        setInterval(refreshDashboard, 30000);
    }
}

// Initialize keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for quick search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openQuickSearch();
        }
        
        // Ctrl/Cmd + N for new item
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openNewItemModal();
        }
    });
}

// Quick search
function openQuickSearch() {
    const searchModal = document.createElement('div');
    searchModal.className = 'modal';
    searchModal.id = 'quickSearchModal';
    searchModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Quick Search</h3>
                <button class="modal-close" onclick="closeModal('quickSearchModal')">×</button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" placeholder="Search customers, vehicles, work orders..." 
                       id="quickSearchInput" autofocus>
                <div id="searchResults" class="mt-3"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(searchModal);
    openModal('quickSearchModal');
    
    // Focus search input
    setTimeout(() => {
        document.getElementById('quickSearchInput').focus();
    }, 100);
}

// Open new item modal
function openNewItemModal() {
    // Determine which modal to open based on current page
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('customers')) {
        openModal('customerModal');
    } else if (currentPage.includes('cars')) {
        openModal('carModal');
    } else if (currentPage.includes('work_orders')) {
        openModal('workOrderModal');
    } else if (currentPage.includes('inventory')) {
        openModal('inventoryModal');
    } else {
        // Default to customer modal on dashboard
        openModal('customerModal');
    }
}

// AJAX form submission
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    showLoadingSpinner();
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        
        if (data.success) {
            showNotification(data.message || 'Operation completed successfully', 'success');
            closeModal(formId.replace('Form', 'Modal'));
            
            if (successCallback) {
                successCallback(data);
            }
        } else {
            showNotification(data.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showNotification('An error occurred. Please try again.', 'error');
        console.error('AJAX Error:', error);
    });
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    
    let csv = [];
    
    // Add headers
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
    csv.push(headers.join(','));
    
    // Add data rows
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td')).map(td => td.textContent);
        if (cells.length > 0) {
            csv.push(cells.join(','));
        }
    });
    
    // Create and download CSV file
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    
    window.URL.revokeObjectURL(url);
    showNotification('Table exported successfully', 'success');
}

// Print functionality
function printElement(elementId) {
    const element = document.getElementById(elementId);
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Print</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    @media print { .no-print { display: none; } }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Add custom styles for dynamic elements
const customStyles = `
    .spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
    }
    
    .notification-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    }
    
    .tooltip-popup {
        position: absolute;
        background: var(--darker-bg);
        color: var(--text-primary);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        white-space: nowrap;
        z-index: 1000;
        animation: fadeIn 0.2s ease;
    }
    
    .animate-in {
        opacity: 1;
        transform: translateY(0);
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
`;

// Add custom styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = customStyles;
document.head.appendChild(styleSheet);

// Export global functions
window.toggleSidebar = toggleSidebar;
window.changeLanguage = changeLanguage;
window.refreshDashboard = refreshDashboard;
window.openModal = openModal;
window.closeModal = closeModal;
window.showNotification = showNotification;
window.submitFormAjax = submitFormAjax;
window.exportTableToCSV = exportTableToCSV;
window.printElement = printElement;

// Auto-refresh dashboard statistics
function refreshDashboardStats() {
    fetch('api/dashboard/stats.php')
        .then(response => response.json())
        .then(data => {
            updateStatCards(data);
        })
        .catch(error => console.error('Error fetching stats:', error));
}

// Update statistics cards
function updateStatCards(response) {
    // Handle case when response is undefined or null
    if (!response) {
        console.log('No stats data provided, skipping update');
        return;
    }
    
    // Handle both direct stats and wrapped data
    const stats = response.data || response;
    
    // Check if stats is valid
    if (!stats || typeof stats !== 'object') {
        console.error('Invalid stats data:', stats);
        return;
    }
    
    const elements = {
        'total_customers': stats.total_customers || 0,
        'cars_in_repair': stats.cars_in_repair || 0,
        'total_revenue': formatCurrency(stats.total_revenue || 0),
        'monthly_orders': stats.monthly_orders || 0,
        'today_appointments': stats.today_appointments || 0,
        'active_employees': stats.active_employees || 0
    };
    
    Object.keys(elements).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = elements[key];
        }
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Confirm delete action
function confirmDelete(message, callback) {
    if (confirm(message || 'Êtes-vous sûr de vouloir supprimer cet élément ?')) {
        callback();
    }
}

// Show loading spinner
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.style.margin = '20px auto';
    element.innerHTML = '';
    element.appendChild(spinner);
}

// Hide loading spinner
function hideLoading(element) {
    const spinner = element.querySelector('.spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// AJAX form submission
function submitForm(formId, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
            showAlert(data.message || 'Opération réussie');
        } else {
            if (errorCallback) errorCallback(data);
            showAlert(data.message || 'Une erreur est survenue', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Une erreur de connexion est survenue', 'danger');
        if (errorCallback) errorCallback(error);
    });
}

// Search functionality
function setupSearch(searchInputId, tableId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize modals
function initModals() {
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalEl => {
        new bootstrap.Modal(modalEl);
    });
}

// Print functionality
function printElement(elementId) {
    const element = document.getElementById(elementId);
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${document.title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .mb-3 { margin-bottom: 15px; }
                .mt-3 { margin-top: 15px; }
            </style>
        </head>
        <body>
            ${element.innerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.trim() + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Date picker initialization
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set max date to today for date of birth fields
        if (input.classList.contains('date-of-birth')) {
            input.max = new Date().toISOString().split('T')[0];
        }
    });
}

// Number formatting
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Validate phone number
function validatePhoneNumber(phone) {
    const phoneRegex = /^[\d\s\+\-\(\)]+$/;
    return phoneRegex.test(phone);
}

// Validate email
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        // Special validations
        if (input.type === 'email' && input.value) {
            if (!validateEmail(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
        
        if (input.classList.contains('phone') && input.value) {
            if (!validatePhoneNumber(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Auto-calculate totals
function setupAutoCalculation() {
    const quantityInputs = document.querySelectorAll('input[data-calc-quantity]');
    const priceInputs = document.querySelectorAll('input[data-calc-price]');
    const totalInputs = document.querySelectorAll('input[data-calc-total]');
    
    quantityInputs.forEach((qtyInput, index) => {
        qtyInput.addEventListener('input', () => calculateLineTotal(index));
    });
    
    priceInputs.forEach((priceInput, index) => {
        priceInput.addEventListener('input', () => calculateLineTotal(index));
    });
}

function calculateLineTotal(index) {
    const quantity = parseFloat(document.querySelectorAll('input[data-calc-quantity]')[index]?.value || 0);
    const price = parseFloat(document.querySelectorAll('input[data-calc-price]')[index]?.value || 0);
    const totalInput = document.querySelectorAll('input[data-calc-total]')[index];
    
    if (totalInput) {
        const total = quantity * price;
        totalInput.value = total.toFixed(2);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initTooltips();
    initModals();
    initDatePickers();
    setupAutoCalculation();
    
    // Setup search if elements exist
    setupSearch('searchInput', 'dataTable');
    
    // Auto-refresh dashboard every 30 seconds
    if (window.location.pathname.includes('index.php')) {
        setInterval(refreshDashboardStats, 30000);
    }
    
    // Handle form submissions
    const forms = document.querySelectorAll('form[data-ajax]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(form.id)) {
                submitForm(form.id);
            }
        });
    });
});

// Handle keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N: New item (context dependent)
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const newBtn = document.querySelector('[data-action="new"]');
        if (newBtn) newBtn.click();
    }
    
    // Ctrl+S: Save form
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const saveBtn = document.querySelector('[data-action="save"]');
        if (saveBtn) saveBtn.click();
    }
    
    // Ctrl+P: Print
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        const printBtn = document.querySelector('[data-action="print"]');
        if (printBtn) printBtn.click();
    }
    
    // Escape: Close modal
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            bootstrap.Modal.getInstance(openModal).hide();
        }
    }
});
