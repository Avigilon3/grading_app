# Grading System Web Application

A web-based grading system built with PHP and MySQL, featuring Admin, Teacher, and Student roles. This project allows for easy management of students, subjects, and grades, with a modern UI and dark mode support.

---

## Features

- **User Authentication** (Admin, Teacher, Student)
- **Role-based Access Control**
- **Add/Edit/Delete Students and Subjects**
- **Assign and Update Grades**
- **Student Dashboard** with GPA, pass/fail status, and analytics
- **Dark Mode** toggle (remembers your preference)
- **Responsive UI** (Bootstrap 5)
- **Secure Password Hashing**
- **User-friendly error and success messages**

---

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- XAMPP/LAMP/WAMP or any web server with PHP & MySQL
- Composer (optional, if you want to manage dependencies)

---

## Installation

1. **Clone or Download** this repository to your web server directory (e.g., `htdocs` for XAMPP).
2. **Import the Database:**
    - Open `phpMyAdmin` or your preferred MySQL tool.
    - Import the provided SQL file (e.g., `install.sql` or `grading_app.sql`).
3. **Configure Database Connection:**
    - Edit `includes/config.php` and set your MySQL username, password, and database name.
4. **Start your web server** (Apache) and MySQL.
5. **Access the Application:**
    - Go to [http://localhost/grading_app/](http://localhost/grading_app/) in your browser.

---

## Default Accounts

| Role    | Username   | Password  |
|---------|------------|-----------|
| Admin   | admin      | password  |
| Teacher | teacher1   | password  |
| Student | student1   | password  |

> You can add more users via the Admin panel or directly in the database.

---

## Usage

- **Admin**: Manage all users, subjects, and grades.
- **Teacher**: Manage students and assign grades.
- **Student**: View grades, GPA, and analytics.

---

## File Structure

- `/includes/` - Configuration and database connection
- `/teacher/`  - Admin/Teacher dashboard and management
- `/student/`  - Student dashboard
- `/login.php` - Login page
- `/logout.php` - Logout script

---

## Notes

- Passwords are securely hashed.
- Dark mode preference is saved per browser.
- All actions are protected by user roles.
- Error and success messages are displayed for user actions.

---

## Troubleshooting

- If you see a blank page, enable error reporting in `php.ini` or at the top of PHP files.
- Double-check your database credentials in `includes/config.php`.
- Make sure you have imported the SQL file before logging in.

---

## Credits

Developed by Tumamak jerick, San Pascual Phillip  for PROF. JC CODILAN
 PTC WEBPROG 2 CCS 2B , 2025.

---
ADDED BY BETHEL FOR TESTING Nov 2

## two step verification process from student to teacher 
 
  basta pogi naglagay neto maam