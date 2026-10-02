# Internship Management System

The Internship Management System is a web-based platform developed to simplify and centralize internship management for students, supervisors, and administrators.

The system was designed to replace manual internship tracking by providing a single platform for managing internship information, student documents, logbooks, announcements, supervisor assignments, and administrative records.

## Features

### Student
- Register and log in
- View personal profile
- Manage internship information
- Upload internship documents
- Upload logbooks
- View submission status
- View announcements
- Track internship progress

### Supervisor
- Log in to the system
- View assigned students
- View student profiles
- Review internship information
- Review submitted logbooks
- Update logbook status
- Monitor student submissions

### Admin
- Separate administrator login
- Manage students
- Manage supervisors
- Assign students to supervisors
- Remove student assignments
- View student internship information
- View uploaded documents
- View logbooks
- Manage announcements
- Upload official letters and documents
- Generate reports
- Delete student and supervisor records

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- Apache

## User Roles

The system contains three main user roles:

- Student
- Supervisor
- Admin

Each role has its own dashboard and access permissions.

## Main Modules

### Authentication
Handles user registration, login, session management, and role-based access.

### Student Management
Allows administrators to view and manage student records and profiles.

### Supervisor Management
Allows administrators to manage supervisor accounts and assign students to supervisors.

### Internship Management
Allows students to submit and update information regarding their internship placement.

Information includes:

- Company name
- Company address
- Company email
- Company phone number
- Internship supervisor name
- Supervisor position
- Supervisor email
- Internship start date
- Internship end date
- Internship status

### Logbook Management
Students can upload internship logbooks while supervisors can review and update their submission status.

### Document Management
Students can upload internship-related reports and documents for record keeping.

### Announcement Management
Administrators can create announcements and specify the intended audience.

### Official Document Management
Administrators can upload and manage official internship-related letters and documents.

### Reporting
Administrators can generate reports based on system records.

## Project Structure

```text
Internship-Management-System/
│
├── includes/
│   ├── admin_dashboard.php
│   └── supervisor_dashboard.php
│
├── css/
│   └── style.css
│
├── uploads/
│
├── dashboard.php
├── student_dashboard.php
├── profile.php
├── internship.php
│
├── logbook.php
├── upload_logbook.php
│
├── document.php
├── upload_doc.php
│
├── studentList.php
├── supervisorList.php
├── supervisor_profile.php
├── student_profile_admin.php
│
├── surats.php
├── upload_surat.php
├── generate_report.php
│
├── admin_login.php
├── register.php
├── login.php
│
├── header.php
├── sidebar.php
└── footer.php
```

## Database

The system uses a MySQL relational database.

Important tables include:

### Student

```text
student
- matricNo
- stuName
- stuEmail
- stuPassword
- stuPhNO
- stuIC
```

### Supervisor

```text
supervisor
- SPmatric
- SPname
- SPgmail
- SPpassword
```

### Admin

```text
superadmin
- SAmatrix
- SAname
- SAgmail
- SApassword
```

### Internship

```text
internship
- matricNo
- companyName
- companyAddress
- companyEmail
- companyPhone
- supervisorName
- supervisorPosition
- supervisorEmail
- startDate
- endDate
- internStatus
```

### Logbook

```text
logbook
- logID
- matricNo
- fileName
- submission_date
- logbookStatus
```

### Reports

```text
report
- reportID
- matricNo
- fileName
- file_path
- upload_date
```

### Announcements

```text
announcements
- id
- title
- body
- audience
- created_at
```

### Official Documents

```text
surats
- suratID
- suratTitle
- suratType
- file_path
- upload_date
```

## Internship Status

Internship records use the following status values:

```text
Pending
Submitted
Approved
Rejected
```

## How It Works

1. A student registers an account and logs into the system.
2. The student enters their internship placement information.
3. The student uploads required documents and logbooks.
4. The administrator manages students and supervisors.
5. The administrator assigns students to supervisors.
6. Supervisors review their assigned students.
7. Supervisors review logbook submissions and update their status.
8. Administrators publish announcements and official documents.
9. Internship records can be monitored throughout the internship period.
10. Administrators can generate reports when required.

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR-USERNAME/internship-management-system.git
```

Move the project folder into the XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\internship-management-system
```

### 2. Start XAMPP

Start:

- Apache
- MySQL

### 3. Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create the
