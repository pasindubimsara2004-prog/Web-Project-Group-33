# NSBM ArtCraft - Student Arts & Handcraft Showcasing System

**NSBM Green University - Faculty of Computing**  
**Module:** Web and Mobile Application Development | **Batch:** 26.1 IFSL | **Group:** 33  

---

## Project Overview

NSBM ArtCraft is a web-based arts and handcraft showcasing platform built for students at NSBM Green University. It allows student creators across all faculties to upload and showcase their paintings, sketches, pottery, photography, digital art, and sculptures, receive peer feedback and ratings, and register for campus exhibitions.

---

## Technology Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript (AJAX / Fetch API)
- **Backend:** PHP 8.x (PDO with prepared statements, BCrypt password hashing, session-based access control)
- **Database:** MySQL / MariaDB

---

## Main Features

### Public Gallery & Community
- Browse approved student artworks with keyword search, category filters, and sorting options
- View artwork details, medium, dimensions, and LKR pricing/display status
- Interactive AJAX likes, 1-5 star ratings, and comment threads
- Student artist directory with faculty filtering, artist portfolios, and follow/unfollow support
- Campus exhibition listings and event details

### Student Portal (`/student`)
- Student registration and secure login
- Upload new artworks with category, medium, dimensions, price, and live image preview (JPG, PNG, WEBP, SVG up to 5MB)
- Track submission status (`Pending`, `Approved`, `Rejected`) and view admin review notes
- Edit or delete personal artwork submissions
- Register artworks for upcoming campus exhibitions and manage student profile

### Admin Panel (`/admin`)
- Artwork moderation queue (approve, reject with feedback notes, toggle homepage featured items)
- Category management (Add, Edit, Delete)
- Campus exhibition management (create and schedule events, view registered participants)
- Student user management
- Submission and faculty engagement reports with printable layout and CSV export

---

## Setup Instructions (XAMPP)

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy or clone the project folder into `C:\xampp\htdocs\nsbm-artcraft`.
3. Open your browser and run the database installer:
   ```
   http://localhost/nsbm-artcraft/install.php
   ```
4. Click **Install Database & Seed Data** to create the `nsbm_artcraft` database and populate sample records.
5. Visit the homepage at:
   ```
   http://localhost/nsbm-artcraft/
   ```

---

## Default Test Accounts

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@nsbm.ac.lk` | `admin123` |
| **Student 1** | `student1@nsbm.ac.lk` | `student123` |
| **Student 2** | `student2@nsbm.ac.lk` | `student123` |
| **Student 3** | `student3@nsbm.ac.lk` | `student123` |
| **Student 4** | `student4@nsbm.ac.lk` | `student123` |

---

## Project Structure

```
nsbm-artcraft/
├── admin/               # Admin moderation, categories, exhibitions, users & CSV reports
├── api/                 # AJAX JSON endpoints (like, rate, follow)
├── assets/              # Custom CSS, JS, and university/banner images
├── config/              # PDO database connection & SITE_URL setup
├── includes/            # Shared header, footer, helper functions & auth guards
├── student/             # Student dashboard, artwork CRUD, profile & exhibitions
├── uploads/             # Artwork images, avatars & exhibition banners
├── database.sql         # MySQL schema & seed data
├── install.php          # One-click database setup script
├── index.php            # Homepage
├── gallery.php          # Searchable public gallery
├── artwork_detail.php   # Single artwork view, ratings & comments
├── artists.php          # Student creators directory
├── artist_profile.php   # Individual artist portfolio
├── exhibitions.php      # Exhibitions listing
├── exhibition_detail.php# Exhibition details & registration
├── login.php            # Login page
├── register.php         # Student registration page
└── logout.php           # Session logout
```
