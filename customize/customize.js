$(document).ready(function () {
    // Initialize Select2 for open days
    $('#days').select2({
        placeholder: "Select open days",
        allowClear: true,
        width: '100%'
    });

    // Initialize timepicker for opening and closing times in 12-hour format
    $('.timepicker').timepicker({
        timeFormat: 'h:mm p',  // 12-hour format
        interval: 30,  // 30-minute intervals
        minTime: '6:00am',  // earliest time available
        maxTime: '11:30pm',  // latest time available
        dynamic: false,  // prevent dynamic change
        dropdown: true,  // allow dropdown
        scrollbar: true  // show scrollbar if needed
    });

    // Assuming the time data comes in 24-hour format
function convertTo12HourFormat(time24) {
    const [hours, minutes] = time24.split(':');
    const period = hours < 12 ? 'AM' : 'PM';
    const hour12 = (hours % 12) || 12; // Converts hour 0 to 12 for AM
    return `${hour12}:${minutes} ${period}`;
}

// Example usage (fetching from PHP response)
const openingTime24 = '14:30'; // Example 24-hour time
const closingTime24 = '22:00'; // Example 24-hour time

const openingTime12 = convertTo12HourFormat(openingTime24); // "2:30 PM"
const closingTime12 = convertTo12HourFormat(closingTime24); // "10:00 PM"

console.log(openingTime12, closingTime12);


    // Handle success and error messages from URL
    const urlParams = new URLSearchParams(window.location.search);
    const successMessage = urlParams.get('success');
    const errorMessage = urlParams.get('error');

    // Show toast notification if there's a success or error message in the URL
    if (successMessage) {
        localStorage.setItem('toastMessage', JSON.stringify({ title: 'Success', message: successMessage, type: 'success' }));
        clearQueryParams(); // Clear query parameters after storing the message
    }

    if (errorMessage) {
        localStorage.setItem('toastMessage', JSON.stringify({ title: 'Error', message: errorMessage, type: 'error' }));
        clearQueryParams(); // Clear query parameters after storing the message
    }

    // Display toast from localStorage if present
    const storedToast = localStorage.getItem('toastMessage');
    if (storedToast) {
        const { title, message, type } = JSON.parse(storedToast);
        showToast(title, message, type);
        localStorage.removeItem('toastMessage'); // Remove the message after displaying it
    }

    // Function to display toast notifications
    function showToast(title, message, type) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        document.querySelector('.toast-container').appendChild(toast);
        const bootstrapToast = new bootstrap.Toast(toast);
        bootstrapToast.show();
    }

    // Function to clear query parameters from the URL
    function clearQueryParams() {
        const url = new URL(window.location.href);
        url.searchParams.delete('success'); 
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.toString());
    }

    // Handle clinic details form submission
    const clinicDetailsForm = document.getElementById('clinic-details-form');
    clinicDetailsForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(clinicDetailsForm);

        fetch('process_clinic.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    showToast('Success', result.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showToast('Error', result.message, 'error');
                }
            })
            .catch(() => {
                showToast('Error', 'An error occurred while updating clinic details.', 'error');
            });
    });

    // Handle additional clinic info form submission
    const additionalClinicInfoForm = document.getElementById('additional-clinic-info-form');
    additionalClinicInfoForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(additionalClinicInfoForm);

        fetch('update_clinic_info.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(result => {
                if (result.includes('updated successfully')) {
                    showToast('Success', 'Additional clinic information updated successfully.', 'success');
                } else {
                    showToast('Error', 'Failed to update additional clinic information.', 'error');
                }
            })
            .catch(() => {
                showToast('Error', 'An error occurred while updating additional clinic information.', 'error');
            });
    });

    // Handle slider image deletion
    document.querySelectorAll('.btn-delete-slider').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this slider image?')) {
                fetch('delete_slider_image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        localStorage.setItem('toastMessage', JSON.stringify({ title: 'Success', message: result.message, type: 'success' }));
                        location.reload();
                    } else {
                        showToast('Error', result.message, 'error');
                    }
                })
                .catch(() => showToast('Error', 'An error occurred while deleting the slider image.', 'error'));
            }
        });
    });
    

    // Handle service deletion
    document.querySelectorAll('.btn-delete-service').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const id = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this service?')) {
                fetch('delete_service.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.status === 'success') {
                            showToast('Success', result.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showToast('Error', result.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error', 'An unexpected error occurred.', 'error');
                    });
            }
        });
    });

    // Phone number validation (only numeric input)
    document.getElementById('phone').addEventListener('input', function (e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});

// Handle dropdown toggle for services
$('.services-dropdown-toggle').click(function () {
    $('.services-dropdown-content').toggleClass('show');
});

