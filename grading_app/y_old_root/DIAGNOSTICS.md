# Diagnostics Report — y_old_root

Generated: 2025-11-04

This document summarizes the automated/manual scan performed across the `y_old_root` folder of the grading_app repository. It lists bugs, security issues, fragile code patterns, example locations, and concrete fixes you can apply immediately.

If you want me to apply the low-risk fixes (logging instead of echoing exceptions, remove placeholder SRI, safe CONTENT_TYPE check), tell me and I'll patch the files and create a small commit with diffs.

---

Scope
- Files scanned (representative):
  - `dashboard.php`
  - `includes/config.php`, `includes/db.php`, `includes/header.php`, `includes/session.php`, `includes/users_list.php`
  - `teacher/*` AJAX endpoints (ajax_add_*.php, ajax_delete_*.php, ajax_grades.php)

High-level summary (priority)
- High
  - Sensitive exception messages printed to clients (DB errors) — information leakage.
  - No CSRF checks on many state-changing AJAX endpoints; logout performed via GET.
- Medium
  - Placeholder SRI attributes in CDN script/link tags (integrity="sha384-...") — invalid and may break loading.
  - Hard-coded absolute base paths (e.g., `/grading_app/...`) — brittle across deployments.
  - Multiple files call `session_start()` directly; session policies inconsistent.
- Low / Maintainability
  - Use of die()/exit() with raw messages in many places.
  - Some DOM/JS code assumes elements exist (no null checks).

Detailed findings, examples, and fixes

1) Information leakage: exception messages sent to clients (HIGH)
   - What: Several files catch `PDOException` or other exceptions and echo or include `$e->getMessage()` in output or JSON. This reveals internal details (DSN, SQL, driver messages).
   - Examples:
     - `y_old_root/includes/config.php` — exit("Database connection failed: " . $e->getMessage());
     - `y_old_root/includes/db.php` — die("Database connection failed: " . $e->getMessage());
     - `y_old_root/includes/users_list.php` — die("Query failed: " . $e->getMessage());
     - `y_old_root/teacher/ajax_delete_student.php` — echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
   - Why: Attackers can learn DB structure, driver versions, and possibly user info.
   - Fix (apply everywhere): Log the exception server-side and return a generic message to clients.
     - Example (PHP):
       ```php
       try {
         // PDO work
       } catch (PDOException $e) {
         error_log('DB error in ' . __FILE__ . ': ' . $e->getMessage());
         http_response_code(500);
         // For AJAX JSON endpoints:
         echo json_encode(['success' => false, 'error' => 'Internal server error.']);
         exit;
       }
       ```
     - For non-AJAX pages prefer a generic error page or redirect to a friendly message.

2) Missing CSRF protection on state-changing endpoints & logout via GET (HIGH)
   - What: AJAX endpoints that modify data do not validate CSRF tokens; logout links are GETs.
   - Examples:
     - `y_old_root/teacher/ajax_delete_student.php` (no CSRF check)
     - Header links: `<a href="/grading_app/includes/logout.php">` (GET logout)
   - Why: Actions can be forged by third-party sites (CSRF).
   - Fix:
     - Require and validate a CSRF token for all POST/DELETE actions (AJAX or forms). For AJAX, check X-CSRF-Token header against session token.
     - Change logout to POST form or a fetch POST with CSRF header. Validate token server-side before destroying session.
     - Example server-side check:
       ```php
       $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
       if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
         http_response_code(403); echo json_encode(['success'=>false,'error'=>'Invalid CSRF token']); exit;
       }
       ```

3) Placeholder/invalid SRI attributes for CDN assets (MEDIUM / Functional)
   - What: Header uses integrity="sha384-..." placeholders; these are invalid and may prevent resource loading.
   - Example: `y_old_root/includes/header.php`
   - Fix: Remove the integrity and crossorigin attributes in development, or replace them with correct SRI hashes for the exact CDN asset and version.
     - Quick fix: remove `integrity="sha384-..." crossorigin="anonymous"` from the `<link>` and `<script>` tags.

4) Absolute base paths / brittle URLs (MEDIUM)
   - What: Files use absolute paths like `/grading_app/includes/logout.php` and JS fetches to `/grading_app/includes/session.php`.
   - Example: `y_old_root/includes/header.php` uses absolute URLs.
   - Fix: Use a `BASE_URL` constant (set in central `core/config/config.php`) or use relative paths. Example:
     - In PHP: `<a href="<?= htmlspecialchars(BASE_URL . '/includes/logout.php') ?>">Logout</a>`
     - In JS: `fetch('includes/session.php', { ... })` (relative)

5) Inconsistent session handling and configuration (MEDIUM)
   - What: Many files call `session_start()` directly. There is an `includes/session.php` with secure session settings, but not always used consistently.
   - Fix: Centralize session bootstrap in one include (e.g., `includes/session.php` or `core/session.php`) and `require_once` it at the top of pages. Ensure session cookie flags (httponly, secure, samesite) set once.

6) Direct die()/exit() with debug text (MEDIUM)
   - What: Many scripts end with `die()`/`exit()` that output internal messages. Replace with logging and user-friendly errors.
   - Example: `y_old_root/includes/users_list.php` uses die("Query failed: " . $e->getMessage());

7) Content-Type check can raise a notice (LOW)
   - What: `strpos($_SERVER['CONTENT_TYPE'], 'application/json')` used without null coalesce.
   - Fix: Use `$contentType = $_SERVER['CONTENT_TYPE'] ?? '';` then `strpos($contentType, 'application/json') !== false`.

8) Minor JS issues: DOM element access without null checks (LOW)
   - Example: theme toggle code in header uses `document.getElementById('themeToggle')` and calls `addEventListener` without verifying element exists.
   - Fix: guard with `if (toggle) { ... }`.

9) Misc / Maintainability
   - Mixed naming conventions for helper functions and helpers duplicated across modules (admin/professor/student/y_old_root). Consider centralizing helpers in `core/`.
   - Move database credentials out of per-folder includes to the central `core/config/config.php` or environment variables.

Recommended immediate action plan (safe, quick wins)
1. Replace all direct printing of exception messages with server-side logging + generic responses (low-risk). Files to patch first:
   - `y_old_root/includes/config.php`
   - `y_old_root/includes/db.php`
   - `y_old_root/includes/users_list.php`
   - AJAX endpoints under `y_old_root/teacher/*.php`
2. Remove placeholder SRI attributes from `y_old_root/includes/header.php` (very low-risk).
3. Fix CONTENT_TYPE checks in `y_old_root/includes/session.php` (very low-risk).
4. Replace logout GET links with a POST form using CSRF (medium-risk; will change client behavior).
5. Add CSRF validation to AJAX endpoints (medium-risk).

Longer-term improvements
- Centralize session & DB bootstrap in `core/` and update modules to include it.
- Replace absolute paths with `BASE_URL` constant and consolidate configuration.
- Add automated tests for critical endpoints, and a small security checklist in CI to scan for exception leakage and reachable endpoints without CSRF.

Checklist for fixes (apply and verify)
- [ ] Replace exception echo/die with error_log + generic message.
- [ ] Remove placeholder SRI attributes.
- [ ] Make logout POST with CSRF.
- [ ] Add CSRF checks to AJAX endpoints.
- [ ] Replace absolute URLs with `BASE_URL` or relative paths.
- [ ] Centralize session start and config.

If you want, I can apply the low-risk fixes now (I will update the files, run a quick static check, and attach the diffs). Tell me to proceed and I'll implement them in the repository.

---

Notes
- This diagnostics file focuses on `y_old_root` because it contains older code patterns and several examples of exception messaging and direct session usage. There are similar patterns in other modules (admin/professor/student) that should be audited and normalized.

Contact me with the set of fixes you'd like me to apply and I will patch them and provide a follow-up verification report.
