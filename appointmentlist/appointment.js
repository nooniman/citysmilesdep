$(document).ready(function () {
    // Live Search
    $("#search-bar").on("input", function () {
        const value = $(this).val().toLowerCase();
        $("#appointment-list tr").toggle($(this).find(".patient-name").text().toLowerCase().includes(value));
    });

    // Status Filter
    $("#status_filter").on("change", function () {
        const status = $(this).val().toLowerCase();
        $("#appointment-list tr").each(function () {
            const rowStatus = $(this).find(".status-container").text().toLowerCase();
            $(this).toggle(status === "all" || rowStatus === status);
        });
    });

    // Action buttons
    $(document).on("click", ".btn-action", function () {
        const action = $(this).data("action");
        const appointmentId = $(this).data("id");

        if (action === "reschedule") {
            // Set appointment ID in the modal form
            $("#appointment-id").val(appointmentId);

            // Clear any previous date and time values
            $("#new-date").val("");
            $("#new-time").val("");

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $("#new-date").attr("min", today);

            // Show Bootstrap modal
            const rescheduleModal = new bootstrap.Modal(document.getElementById("reschedule-modal"));
            rescheduleModal.show();
        } else if (action === "completed") { // Changed from "confirm" to "completed"
            // Confirm the appointment and mark it as completed
            updateAppointmentStatus(appointmentId, action);
        } else if (action === "approve") {
            // Approve the appointment
            updateAppointmentStatus(appointmentId, action);
        } else {
            // Confirm other actions
            if (confirm(`Are you sure you want to ${action} this appointment?`)) {
                updateAppointmentStatus(appointmentId, action);
            }
        }
    });

    // Close modal
    $(".modal-close, .modal-close-btn").on("click", function () {
        const rescheduleModal = bootstrap.Modal.getInstance(document.getElementById("reschedule-modal"));
        rescheduleModal.hide();
    });

    // Confirm reschedule
    $("#confirm-reschedule").on("click", function () {
        const appointmentId = $("#appointment-id").val();
        const newDate = $("#new-date").val();
        const newTime = $("#new-time").val();

        if (!newDate || !newTime) {
            showToast("Error", "Please select both date and time", "danger");
            return;
        }

        updateAppointmentStatus(appointmentId, "reschedule", {
            new_date: newDate,
            new_time: newTime
        });

        // Hide Bootstrap modal
        const rescheduleModal = bootstrap.Modal.getInstance(document.getElementById("reschedule-modal"));
        rescheduleModal.hide();
    });

    // Update appointment status
    function updateAppointmentStatus(appointmentId, action, additionalData = {}) {
        const data = {
            appointment_id: appointmentId,
            action: action,
            ...additionalData
        };

        $.ajax({
            url: "appointment_actions.php",
            type: "POST",
            data: data,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    showToast("Success", response.message || "Appointment updated successfully", "success");
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showToast("Error", response.message || "An error occurred", "danger");
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                showToast("Error", "Something went wrong. Please try again.", "danger");
            }
        });
    }

    // Show toast notification
    function showToast(title, message, type) {
        // Ensure the toast container exists
        if ($(".toast-container").length === 0) {
            $("body").append('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
        }

        const toastId = `toast-${Date.now()}`;
        const toast = $(`
            <div id="${toastId}" class="toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong>: ${message}
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);

        $(".toast-container").append(toast);

        // Initialize and show the toast using Bootstrap's API
        const bootstrapToast = new bootstrap.Toast(document.getElementById(toastId), { delay: 3000 });
        bootstrapToast.show();

        // Automatically remove the toast after it hides
        toast.on("hidden.bs.toast", function () {
            $(this).remove();
        });
    }
});