# User Dashboard for City Smile Dental Clinic

This project is a user dashboard for the City Smile Dental Clinic, allowing patients to manage their appointments, view prescriptions, and update their account information.

## Project Structure

```
userdashboard
├── css
│   └── dashboard.css          # Styles for the user dashboard
├── js
│   └── fullcalendar.js        # JavaScript for FullCalendar functionality
├── userdashboard
│   ├── account.php            # Displays user's account information
│   ├── appointment.php         # Shows user's appointment details
│   ├── dashboard.php           # User dashboard with FullCalendar
│   ├── prescription.php        # Displays user's prescription information
│   ├── process_appointment.php # Processes appointment requests
│   ├── request.php             # Allows users to request an appointment
│   └── user_sidebar.php        # Sidebar navigation for the user dashboard
├── database.php                # Database connection logic
└── README.md                   # Project documentation
```

## Setup Instructions

1. **Clone the Repository**: Clone this repository to your local machine.
   
2. **Database Setup**:
   - Import the provided SQL dump into your MySQL database to create the necessary tables and data.
   - Update the `database.php` file with your database connection details.

3. **File Structure**: Ensure the file structure matches the provided layout for proper functionality.

4. **Accessing the Dashboard**:
   - Open `account.php`, `appointment.php`, `dashboard.php`, or `prescription.php` in your web browser to access the respective functionalities.

## Usage Guidelines

- **Account Management**: Users can view and edit their account information in `account.php`.
- **Appointment Management**: Users can view their upcoming and previous appointments in `appointment.php`.
- **Dashboard**: The `dashboard.php` file provides an overview of appointments using FullCalendar.
- **Prescription Information**: Users can view their prescriptions in `prescription.php`.
- **Requesting Appointments**: Users can request new appointments through `request.php`.

## Technologies Used

- PHP for server-side scripting
- MySQL for database management
- HTML/CSS for front-end layout and styling
- JavaScript for interactive features (FullCalendar)

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.