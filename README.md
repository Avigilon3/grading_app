# Online Grading and Academic Record Monitoring System

## Description
The Online Grading and Academic Record Monitoring System (or Grading App to make it simple) is a centralized, web-based platform developed specifically for Pateros Technological College (PTC). Designed to transition the institution from a semi-automated, spreadsheet-reliant workflow to a fully digital environment, this system automates grade calculations, streamlines record management, and provides real-time academic tracking. It aims to reduce administrative workloads, minimize manual encoding errors, and provide students with immediate, transparent access to their academic progress.

## Features

### Admin (Registrar / MIS Office)
* **Centralized Database Management:** Add, edit, and manage records for students, professors, courses, subjects, and sections.
* **Grading Sheet Management:** Set and manage submission deadlines for faculty grading sheets.
* **Request & Scheduling Management:** Approve or deny professors' requests for grade amendments and schedule pick-up dates for students requesting physical documents.
* **Activity Logging:** Track administrative actions for accountability and transparency.

### Professor
* **Automated Grading Sheets:** Input raw scores (quizzes, exams, attendance, etc.) and allow the system to automatically compute tentative and final grades.
* **Draft & Submit Workflow:** Save grading sheets as drafts while the semester is ongoing, and submit them once finalized.
* **Amendment Requests:** Request temporary edit access from the Admin to correct grading mistakes after submission deadlines have passed.

### Student
* **Academic Dashboard:** View current GWA, enrolled units, attendance rate, and real-time class standing (including rank and average comparisons).
* **Report of Grades:** Download official grade reports in an un-editable JPEG format to prevent tampering and ensure visual integrity.
* **Document Requests:** Request official, sealed hard copies of grade reports or certificates directly through the portal.

## Tech Stack
* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL
* **Environment:** XAMPP (Apache/MySQL local server)
* **Editor:** Visual Studio Code

## Installation & Setup
Follow these instructions to run the system locally on your machine.

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Avigilon3/grading_app.git
    ```
2.  **Move the project folder:**
    Place the cloned project folder into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\grading_app`).
3.  **Start the local server:**
    Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4.  **Database Configuration:**
    * Open your browser and navigate to `http://localhost/phpmyadmin`.
    * Create a new database (e.g., `grading_app`).
    * Import the provided `.sql` file (usually found in the `database` folder of the repo) into your newly created database.
5.  **Configure Database Connection:**
    * Locate the database connection file in the project (e.g., `db_connect.php` or `config.php`).
    * Update the credentials to match your local setup:
        ```php
        $host = "localhost";
        $username = "root";
        $password = ""; // Default XAMPP password is empty
        $database = "grading_app";
        ```
6.  **Launch the System:**
    Open your browser and go to `http://localhost/grading_app`.

## Test Accounts
Use the following credentials to explore the different portals of the system:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | ` jqtumamak@paterostechnologicalcollege.edu.ph` | `Admin123!` |
| **Professor** | `jmnaling@paterostechnologicalcollege.edu.ph` | `Prof123!` |
| **Student** | `cgbaldemor@paterostechnologicalcollege.edu.ph` | `Stud123!` |


## Project Methodology
This project was developed using the **Agile Methodology (Scrum)**, progressing through continuous iterative phases of Concept, Plan, Develop, Test, Deploy, and Review.

## Research Document
The research paper related to this system is stored in Google Drive and can be accessed using this link: `https://drive.google.com/file/d/1M-5ZfyWP_nfj1PF_IzB1aiBnaUpYnJpk/view?usp=sharing`.

## Developers / Authors
**BSIT 3OL Students - Institute of Information and Communication Technology**
* Bethel Rodriguez Albor
* Carlo Guzman Baldemor
* Jerick Quintana Tumamak
* Joshua Angel Mandia Naling