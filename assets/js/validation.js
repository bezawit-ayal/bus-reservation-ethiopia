/**
 * Ethiopian Bus Reservation System
 * Client-Side Validation JavaScript
 */

// ==================== FORM VALIDATION ====================

/**
 * Validate Registration Form
 */
function validateRegistrationForm(event) {
    event.preventDefault();
    
    const fullName = document.getElementById('fullName').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Clear previous errors
    clearErrors();
    
    let isValid = true;
    
    // Full Name validation
    if (fullName === '') {
        showError('fullName', 'Full name is required');
        isValid = false;
    } else if (fullName.length < 3) {
        showError('fullName', 'Name must be at least 3 characters');
        isValid = false;
    }
    
    // Email validation
    if (email === '') {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    // Phone validation (Ethiopian format)
    if (phone === '') {
        showError('phone', 'Phone number is required');
        isValid = false;
    } else if (!isValidEthiopianPhone(phone)) {
        showError('phone', 'Please enter a valid Ethiopian phone number (e.g., 0911234567)');
        isValid = false;
    }
    
    // Password validation
    if (password === '') {
        showError('password', 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        showError('password', 'Password must be at least 6 characters');
        isValid = false;
    }
    
    // Confirm Password validation
    if (confirmPassword === '') {
        showError('confirmPassword', 'Please confirm your password');
        isValid = false;
    } else if (password !== confirmPassword) {
        showError('confirmPassword', 'Passwords do not match');
        isValid = false;
    }
    
    if (isValid) {
        document.getElementById('registrationForm').submit();
    }
    
    return false;
}

/**
 * Validate Login Form
 */
function validateLoginForm(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    clearErrors();
    
    let isValid = true;
    
    if (email === '') {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    if (password === '') {
        showError('password', 'Password is required');
        isValid = false;
    }
    
    if (isValid) {
        document.getElementById('loginForm').submit();
    }
    
    return false;
}

/**
 * Validate Search Form
 */
function validateSearchForm(event) {
    event.preventDefault();
    
    const origin = document.getElementById('origin').value;
    const destination = document.getElementById('destination').value;
    const travelDate = document.getElementById('travelDate').value;
    
    clearErrors();
    
    let isValid = true;
    
    if (origin === '' || origin === 'select') {
        showError('origin', 'Please select departure city');
        isValid = false;
    }
    
    if (destination === '' || destination === 'select') {
        showError('destination', 'Please select destination city');
        isValid = false;
    }
    
    if (origin !== '' && destination !== '' && origin === destination) {
        showError('destination', 'Destination must be different from origin');
        isValid = false;
    }
    
    if (travelDate === '') {
        showError('travelDate', 'Please select travel date');
        isValid = false;
    } else if (!isValidTravelDate(travelDate)) {
        showError('travelDate', 'Travel date cannot be in the past');
        isValid = false;
    }
    
    if (isValid) {
        document.getElementById('searchForm').submit();
    }
    
    return false;
}

/**
 * Validate Booking Form
 */
function validateBookingForm(event) {
    event.preventDefault();
    
    const passengerName = document.getElementById('passengerName').value.trim();
    const passengerPhone = document.getElementById('passengerPhone').value.trim();
    const selectedSeats = getSelectedSeats();
    
    clearErrors();
    
    let isValid = true;
    
    if (passengerName === '') {
        showError('passengerName', 'Passenger name is required');
        isValid = false;
    }
    
    if (passengerPhone === '') {
        showError('passengerPhone', 'Phone number is required');
        isValid = false;
    } else if (!isValidEthiopianPhone(passengerPhone)) {
        showError('passengerPhone', 'Please enter a valid Ethiopian phone number');
        isValid = false;
    }
    
    if (selectedSeats.length === 0) {
        showAlert('Please select at least one seat', 'error');
        isValid = false;
    }
    
    if (isValid) {
        document.getElementById('bookingForm').submit();
    }
    
    return false;
}

/**
 * Validate Contact Form
 */
function validateContactForm(event) {
    event.preventDefault();
    
    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const subject = document.getElementById('contactSubject').value.trim();
    const message = document.getElementById('contactMessage').value.trim();
    
    clearErrors();
    
    let isValid = true;
    
    if (name === '') {
        showError('contactName', 'Name is required');
        isValid = false;
    }
    
    if (email === '') {
        showError('contactEmail', 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email)) {
        showError('contactEmail', 'Please enter a valid email address');
        isValid = false;
    }
    
    if (subject === '') {
        showError('contactSubject', 'Subject is required');
        isValid = false;
    }
    
    if (message === '') {
        showError('contactMessage', 'Message is required');
        isValid = false;
    } else if (message.length < 10) {
        showError('contactMessage', 'Message must be at least 10 characters');
        isValid = false;
    }
    
    if (isValid) {
        document.getElementById('contactForm').submit();
    }
    
    return false;
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate Ethiopian phone number
 * Accepts: +251XXXXXXXXX, 09XXXXXXXX, 07XXXXXXXX
 */
function isValidEthiopianPhone(phone) {
    const phoneRegex = /^(\+251|0)(9|7)[0-9]{8}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

/**
 * Validate travel date (not in past)
 */
function isValidTravelDate(dateString) {
    const selectedDate = new Date(dateString);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return selectedDate >= today;
}

/**
 * Show error message for a field
 */
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('error');
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        // Insert error message after the field
        field.parentNode.appendChild(errorDiv);
    }
}

/**
 * Clear all error messages
 */
function clearErrors() {
    // Remove error class from all fields
    const errorFields = document.querySelectorAll('.error');
    errorFields.forEach(field => field.classList.remove('error'));
    
    // Remove all error messages
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(msg => msg.remove());
}

/**
 * Show alert message
 */
function showAlert(message, type = 'success') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert-js');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-js`;
    alertDiv.textContent = message;
    
    // Insert at the top of the main content
    const mainContent = document.querySelector('main') || document.querySelector('.container');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// ==================== SEAT SELECTION ====================

let selectedSeats = [];
let bookedSeats = [];

/**
 * Initialize seat selection
 */
function initSeatSelection(bookedSeatsList = []) {
    bookedSeats = bookedSeatsList;
    const seatContainer = document.getElementById('seatGrid');
    
    if (!seatContainer) return;
    
    // Mark booked seats
    bookedSeats.forEach(seatNum => {
        const seat = document.querySelector(`[data-seat="${seatNum}"]`);
        if (seat) {
            seat.classList.add('booked');
        }
    });
    
    // Add click listeners to available seats
    const availableSeats = document.querySelectorAll('.seat:not(.booked)');
    availableSeats.forEach(seat => {
        seat.addEventListener('click', function() {
            toggleSeat(this);
        });
    });
}

/**
 * Toggle seat selection
 */
function toggleSeat(seatElement) {
    const seatNumber = seatElement.dataset.seat;
    
    if (seatElement.classList.contains('booked')) {
        showAlert('This seat is already booked', 'error');
        return;
    }
    
    if (seatElement.classList.contains('selected')) {
        // Deselect seat
        seatElement.classList.remove('selected');
        selectedSeats = selectedSeats.filter(s => s !== seatNumber);
    } else {
        // Check max seats limit (e.g., 5 per booking)
        if (selectedSeats.length >= 5) {
            showAlert('You can select maximum 5 seats per booking', 'warning');
            return;
        }
        // Select seat
        seatElement.classList.add('selected');
        selectedSeats.push(seatNumber);
    }
    
    updateSelectedSeatsDisplay();
    updateTotalPrice();
}

/**
 * Get selected seats
 */
function getSelectedSeats() {
    return selectedSeats;
}

/**
 * Update selected seats display
 */
function updateSelectedSeatsDisplay() {
    const display = document.getElementById('selectedSeatsDisplay');
    const hiddenInput = document.getElementById('selectedSeatsInput');
    
    if (display) {
        if (selectedSeats.length > 0) {
            display.textContent = selectedSeats.join(', ');
        } else {
            display.textContent = 'None';
        }
    }
    
    if (hiddenInput) {
        hiddenInput.value = selectedSeats.join(',');
    }
}

/**
 * Update total price based on selected seats
 */
function updateTotalPrice() {
    const pricePerSeat = parseFloat(document.getElementById('pricePerSeat')?.value || 0);
    const totalElement = document.getElementById('totalPrice');
    
    if (totalElement) {
        const total = selectedSeats.length * pricePerSeat;
        totalElement.textContent = total.toFixed(2) + ' ETB';
    }
}

// ==================== DATE PICKER ====================

/**
 * Set minimum date for travel date picker (today)
 */
function setMinTravelDate() {
    const dateInput = document.getElementById('travelDate');
    if (dateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.min = `${yyyy}-${mm}-${dd}`;
    }
}

// ==================== MOBILE MENU ====================

/**
 * Toggle mobile menu
 * Make sure this function is accessible globally
 */
window.toggleMobileMenu = function() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
        navLinks.classList.toggle('active');
        
        // Also toggle the button's active state for visual feedback
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.classList.toggle('active');
        }
    }
};

// Also define it without window for backward compatibility
function toggleMobileMenu() {
    window.toggleMobileMenu();
}

// ==================== MODAL FUNCTIONS ====================

/**
 * Open modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ==================== ADMIN FUNCTIONS ====================

/**
 * Confirm delete action
 */
function confirmDelete(itemType, itemId) {
    if (confirm(`Are you sure you want to delete this ${itemType}?`)) {
        window.location.href = `delete_${itemType}.php?id=${itemId}`;
    }
}

/**
 * Toggle sidebar on mobile
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    // Set minimum travel date
    setMinTravelDate();
    
    // Initialize mobile menu button
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    // Initialize seat selection if on seat page
    const seatGrid = document.getElementById('seatGrid');
    if (seatGrid) {
        const bookedSeatsData = document.getElementById('bookedSeatsData');
        if (bookedSeatsData) {
            const bookedList = bookedSeatsData.value ? bookedSeatsData.value.split(',') : [];
            initSeatSelection(bookedList);
        }
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// ==================== UTILITY FUNCTIONS ====================

/**
 * Format currency (Ethiopian Birr)
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount) + ' ETB';
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Validate form fields in real-time
 */
function addRealTimeValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Remove error when user starts typing
            this.classList.remove('error');
            const errorMsg = this.parentNode.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
        });
    });
}

/**
 * Validate individual field
 */
function validateField(field) {
    const value = field.value.trim();
    const fieldId = field.id;
    
    // Remove existing error
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) existingError.remove();
    
    // Check if required and empty
    if (field.required && value === '') {
        showError(fieldId, 'This field is required');
        return false;
    }
    
    // Email validation
    if (field.type === 'email' && value !== '' && !isValidEmail(value)) {
        showError(fieldId, 'Please enter a valid email');
        return false;
    }
    
    // Phone validation
    if (fieldId.toLowerCase().includes('phone') && value !== '' && !isValidEthiopianPhone(value)) {
        showError(fieldId, 'Please enter a valid Ethiopian phone number');
        return false;
    }
    
    return true;
}
