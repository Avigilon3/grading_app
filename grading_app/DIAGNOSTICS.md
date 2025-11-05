# Repository Diagnostics — grading_app

Generated: 2025-11-04

This file summarizes an automated + manual scan of the repository. It highlights security issues, fragile patterns, and functional problems found across modules (notably `y_old_root`, `admin`, `professor`, `student`). For each finding I list example files, why it matters, and concrete fixes.

TL;DR — Highest priority items
- Remove or redact exception messages sent to clients (search for `getMessage()` used in echo/die/exit and JSON responses).
- Add CSRF validation to all state-changing endpoints (AJAX or form POSTs). Convert logout links to POST-based flows.
- Replace placeholder SRI attributes in CDN tags (or remove them) so resources don't break.
- Avoid hard-coded absolute base paths like `/grading_app/...` — use `BASE_URL` or relative URLs.

Summary of patterns found (examples)
- Unsanitized/unchecked input usage: multiple places use `$_GET[...]` and `$_POST[...]` directly. Example files:
  - `y_old_root/teacher/grading_system.php` (uses `$_GET['delete_subject']`, `$_GET['search']`, and many `$_POST` fields)
  - `y_old_root/teacher/ajax_grades.php` (uses `$_GET['search']`)
  - Various AJAX endpoints read `$_POST` without explicit CSRF checks.

- Exception messages printed to the client (information leakage):
  - `y_old_root/includes/config.php` — exit("Database connection failed: " . $e->getMessage())
  - `y_old_root/includes/db.php` — die("Database connection failed: " . $e->getMessage())
  - `y_old_root/includes/users_list.php` — die("Query failed: " . $e->getMessage())
  - AJAX endpoints under `y_old_root/teacher/` echo JSON including `$e->getMessage()`.

- Placeholder SRI attributes in CDN tags:
  - `y_old_root/includes/header.php` contains integrity="sha384-..." placeholders on Bootstrap and Chart.js tags.

- Hard-coded absolute paths:
  - `y_old_root/includes/header.php` uses `/grading_app/includes/logout.php` and href `/grading_app/index.php`.

- Session usage inconsistency:
  - Many files call `session_start()` directly (found in multiple `y_old_root` pages and teacher AJAX endpoints). There is a secure `includes/session.php` module but it's not always used.

- Minor issues:
  - Some JS code assumes DOM elements exist (no null checks in theme toggle). Fix by guarding DOM access.
  - `strpos($_SERVER['CONTENT_TYPE'], ...)` used without `??` fallback — may raise notices.

Why these matter
- Exception message leakage helps attackers map the database, find SQL errors or other sensitive info.
- Missing CSRF makes destructive actions triggerable from third-party pages.
- Placeholder SRI values may break resources and cause user-visible issues.
- Hard-coded paths reduce portability and make moving the app or deploying under a different base URL error-prone.

Concrete fixes (copy/paste)

1) Replace exception output with logging + generic messages

Before (bad):
```php
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}
```

After (good):
```php
} catch (PDOException $e) {
  error_log('DB connect error: ' . $e->getMessage());
  http_response_code(500);
  // For web pages:
  echo 'Internal server error.';
  // For JSON endpoints:
  // echo json_encode(['success' => false, 'error' => 'Internal server error.']);
  exit;
}
```

2) Add CSRF validation to AJAX endpoints

Client: include CSRF token in header or body (header example used across this repo):
```js
fetch('ajax_delete_student.php', {
  method: 'POST',
  headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
  body: new URLSearchParams({ user_id: id })
});
```

Server: validate token before performing action:
```php
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
  exit;
}
```

3) Convert logout to POST

Header (replace GET link with form):
```php
<form method="POST" action="<?= htmlspecialchars(BASE_URL . '/includes/logout.php') ?>" style="display:inline">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
  <button class="btn btn-outline-light">Logout</button>
</form>
```

Server: `logout.php` should validate CSRF token before `session_destroy()`.

4) Remove placeholder SRI

Change e.g.:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
```
to:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

5) Use BASE_URL or relative URLs instead of hard-coded `/grading_app` paths

Define a central `BASE_URL` and use `<?= htmlspecialchars(BASE_URL . '/path') ?>` or use relative links in JS (e.g., `fetch('includes/session.php'...)`).

6) Defensive checks and minor fixes
- Use `$contentType = $_SERVER['CONTENT_TYPE'] ?? '';` before doing strpos checks.
- Wrap `document.getElementById('themeToggle')` in a null-check before calling `addEventListener`.

Recommended immediate plan (safe, low-risk edits)
1. Replace client-visible exception messages with logging + generic response across `y_old_root` and other modules (low-risk, high-value).
2. Remove placeholder SRI attributes (very low-risk).
3. Fix CONTENT_TYPE checks and add null guards in header JS (very low-risk).

Moderate/next steps
4. Add CSRF validation to AJAX endpoints and convert logout to POST.
5. Replace absolute base paths with `BASE_URL` usage.
6. Centralize session and DB bootstrapping in `core/` and update includes to use the central loader.

If you want, I can apply the safe low-risk changes now and open a second pass to add CSRF checks in AJAX endpoints. Tell me which set of changes to apply and I will implement them and attach diffs. If you want a prioritized patch set, I recommend starting with the exception redaction + placeholder SRI removal.

---
Appendix: files with notable matches (non-exhaustive)
- `y_old_root/includes/config.php` — prints DB error
- `y_old_root/includes/db.php` — prints DB error
- `y_old_root/includes/users_list.php` — die with query error
- `y_old_root/includes/header.php` — placeholder SRI and absolute paths
- `y_old_root/includes/session.php` — content-type check; CSRF handler exists for JSON but other endpoints need validation
- `y_old_root/teacher/ajax_*.php` — many echo DB error messages in JSON (see earlier list)
- `y_old_root/teacher/grading_system.php` — uses many GET/POST parameters, search fields output via `htmlspecialchars` in at least one place but other POSTs must be validated/escaped server-side when used in DB queries
- `student/includes/users_list.php`, `student/pages/profile.php` — some die() with exceptions
- `admin/includes/config.php` (added earlier) — provides helpers; consider standardizing other modules to use similar helpers
