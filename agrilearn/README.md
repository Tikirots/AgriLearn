# AgriLearn
A Progressive Web Application-Based Training Portal and Controlled Learning
Management System for Trainees of Masaganang Bukid Agricultural Learning Center.

## What's included
Core features from the concept paper's objectives:
1. **Trainee Enrollment & Approval** — trainees register, apply to a training
   program, and an admin approves/rejects/marks completion.
2. **Trainee Records Management** — admin can view full trainee profiles,
   enrollment history, and activity logs.
3. **Controlled Module Visibility** — admin uploads full training modules,
   but each one stays hidden until toggled visible; only visible modules
   are viewable by approved trainees, streamed inline (no download link).
4. **Trainee Activity Recording** — admin can log activities per trainee;
   module views are also auto-logged when a trainee opens a module.
5. **Certificate Generation with QR Code Verification** — once an
   enrollment is marked "completed," admin generates a certificate with a
   unique code. The QR code links to a public verification page
   (`certificate_verify.php`) that anyone can scan to confirm authenticity.

## Requirements
- PHP 8.0+ with PDO MySQL extension
- MySQL / MariaDB
- A web server (Apache/XAMPP/Nginx) — or PHP's built-in server for testing

## Setup
1. Copy this `agrilearn` folder into your web server's document root
   (e.g. `htdocs/agrilearn` for XAMPP).
2. Import `database.sql` into MySQL:
   ```
   mysql -u root -p < database.sql
   ```
3. Edit `config/db.php` if your MySQL username/password differ from the
   defaults (`root` / empty password).
4. Edit `config/config.php`:
   - `BASE_URL` — the sub-path where the app is served (default `/agrilearn`)
   - `SITE_URL` — the full public URL, used to build the QR verification link
5. Visit `http://localhost/agrilearn/reset_admin.php` **once** in your
   browser to set a working admin password (default admin123), then
   **delete `reset_admin.php`**.
6. Log in at `http://localhost/agrilearn/auth/login.php` with
   `admin` / `admin123` and change the password via your MySQL client
   (there's no in-app change-password screen yet — see "Ideas to extend"
   below).
7. Make sure `uploads/modules`, `uploads/photos`, and
   `uploads/certificates` are writable by the web server
   (`chmod -R 775 uploads` on Linux).

## Default accounts
- **Admin/Trainer:** username `admin`, password `admin123` (set via
  `reset_admin.php`, see step 5 above)
- **Trainees:** self-register at `auth/register.php`

## How the controlled visibility works
Every module is uploaded with `is_visible = 0` (hidden) by default. The
admin toggles visibility per module from **Admin → Programs → Modules**.
Trainees can only ever see and stream modules where `is_visible = 1`
**and** they hold an approved enrollment for that module's program —
both checks happen server-side on every request, not just in the UI.

## How the certificate QR verification works
When admin issues a certificate, a unique code (e.g. `AGL-9F3D2A1B-2026`)
is generated and stored. The QR code embedded on the certificate encodes
a link to `certificate_verify.php?code=...`, a public page (no login
required) that looks up the code and displays the certificate details —
so anyone (e.g. an employer) can scan and confirm it's genuine.

## Notes & limitations (matching the concept paper's stated scope)
- No online quizzes/assessments — evaluations remain face-to-face.
- Screenshot prevention is a best-effort deterrent only (see
  `assets/js/script.js`); it cannot be guaranteed on every device/OS.
- QR codes are rendered via the free api.qrserver.com service at request
  time — the deployment server needs outbound internet access. Swap in a
  local QR library (e.g. endroid/qr-code via Composer) if you need this
  to work fully offline.

## Ideas to extend
- Add a "change password" / "forgot password" flow.
- Add file size limits and virus scanning on module uploads.
- Add PDF certificate generation (e.g. TCPDF/mPDF) instead of the
  print-to-PDF browser page.
- Add pagination and CSV export on Trainee Records / Activities.
- Convert to a true installable PWA with a service worker + offline
  caching (the manifest.json is already scaffolded).
