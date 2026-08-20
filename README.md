# College Toilet Cleanliness Monitoring System

A PHP + MySQL web app for tracking toilet check-in/check-out with photo
evidence and comments, for accountability and cleanliness auditing.

## Features

**Admin**
- Add / edit / delete student & admin accounts
- Add / edit / delete toilets (code, name, location)
- Assign one or many toilets to each student (many-to-many)
- View complete history for all toilets, with filters (toilet, student, status, date range)
- View full detail of any session: check-in/out time, photos, comments

**Student / User**
- Logs in with the account created by Admin
- Must set their own password on first login; can change it anytime
- Sees only the toilet(s) assigned to them (auto-shown if only one)
- Check-In: time auto-recorded, upload multiple "before" photos, add a comment
- Check-Out: time auto-recorded, upload multiple "after" photos, add a comment
- Cannot check out unless they have an active check-in for that toilet
- Can view shared history for toilets they're assigned to (sees other
  students' check-in/check-out records for the same toilet)

**Data integrity rules enforced by the app**
- A user cannot Check Out without an active (not-yet-checked-out) Check-In
  for that toilet. Verified both on page load and again inside the DB
  transaction on submit (race-condition safe).
- A user cannot start a new Check-In while another session of theirs is
  still active — they must check out first.
- Once a session is checked out (`status = 'completed'`), the application
  never edits it again. There are no "edit history" forms anywhere in the
  code — history is append-only, preserving accountability.
- Deleting a user or toilet that already has session history is blocked by
  a foreign key constraint; the admin is told to set it to "Inactive"
  instead, so history is never lost.

## Database design

```
users            (id, username, password_hash, full_name, role[admin/user], status, must_change_password)
toilets          (id, code, name, location, status)
user_toilets     (id, user_id, toilet_id)              -- many-to-many
toilet_sessions  (id, toilet_id, user_id, checkin_time, checkin_comment,
                   checkout_time, checkout_comment, status[active/completed])
session_photos   (id, session_id, photo_path, photo_type[checkin/checkout])
```

`users ↔ user_toilets ↔ toilets` is the many-to-many assignment.
Each visit is one row in `toilet_sessions`. Each session can have many
rows in `session_photos` (before photos tagged `checkin`, after photos
tagged `checkout`).

## Installation

1. **Requirements**: PHP 8.0+, MySQL 5.7+/MariaDB, a web server (Apache/Nginx)
   with the `gd`/`fileinfo` extension enabled (for image validation).

2. **Create the database**:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   This creates the `toilet_monitor` database, all tables, and a default
   admin account.

3. **Configure DB credentials** in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'toilet_monitor');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Set folder permissions** so PHP can write uploaded photos:
   ```bash
   chmod -R 755 uploads/
   ```

5. **Point your web server's document root** at this project folder
   (or place the folder inside `htdocs` / `www` for XAMPP/WAMP/MAMP users),
   then visit `index.php` in your browser.

6. **Default admin login**:
   - Username: `admin`
   - Password: `admin123`

   Log in and change this password immediately (Password menu in the navbar).

## Creating students & toilets

1. Log in as admin → **Users** → Add New User (role = Student). An initial
   password is set (default `welcome123` if left blank) — tell the student
   this password; they'll be forced to change it at first login.
2. Go to **Toilets** → Add New Toilet (code + name, e.g. `T01` /
   "Block A Level 1 Male Toilet").
3. Go to **Assignments** → pick a student → tick the toilet(s) to assign →
   Save. A student can be assigned multiple toilets; a toilet can have
   multiple students.

## Folder structure

```
config/db.php              DB connection + upload settings
includes/                  auth, shared functions, header/footer, session detail partial
admin/                     admin dashboard, users, toilets, assign, history, session_detail
user/                      user dashboard, checkin, checkout, history
uploads/checkin/           uploaded "before" photos
uploads/checkout/          uploaded "after" photos
sql/schema.sql             database schema + seed admin account
index.php                  login page
change_password.php        forced/voluntary password change
logout.php
```

## Notes on security

- Passwords are hashed with PHP's `password_hash()` (bcrypt).
- All forms use CSRF tokens.
- All queries use PDO prepared statements (no SQL injection surface).
- Uploaded files are validated by real MIME type (not just extension),
  size-limited, renamed randomly on save, and the uploads folder blocks
  script execution via `.htaccess`.
- Every page enforces role-based access, and users can only check
  in/out or view history for toilets actually assigned to them.
