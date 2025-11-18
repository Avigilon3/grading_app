<?php

if (!function_exists('adminIsLoggedIn')) {
    function adminIsLoggedIn(): bool {
        
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            return false;
        }

        $role = $_SESSION['user']['role'] ?? '';
        return in_array($role, ['admin','mis','registrar','super_admin'], true);
    }
}

if (!function_exists('adminCurrentName')) {
    function adminCurrentName(): string {
        if (!empty($_SESSION['user']['name'])) {
            return $_SESSION['user']['name'];
        }

        $fn = $_SESSION['user']['first_name'] ?? '';
        $ln = $_SESSION['user']['last_name'] ?? '';
        $full = trim($fn . ' ' . $ln);
        if ($full !== '') {
            return $full;
        }

        return $_SESSION['user']['email'] ?? 'Admin';
    }
}

function requireAdminLogin() {
    if (!adminIsLoggedIn()) {
        // go to main login
        header('Location: ../../login.php?session=expired');
        exit;
    }
}

function renderCrudTabsScript(): void {
    echo '<script>' . "\n" . <<<'JS'
(function(){
  function activateTab(which) {
    document.querySelectorAll('#student-tabs .tab-link').forEach(function(b){
      b.classList.toggle('active', b.dataset.tab === which);
    });
    document.querySelectorAll('#student-tabs .tab-pane').forEach(function(p){
      p.classList.toggle('active', p.dataset.pane === which);
    });
  }

  function initStudentTabs(){
    var addTabBtn = document.getElementById('btn-add-tab');
    if (addTabBtn) {
      addTabBtn.addEventListener('click', function(e){
        e.preventDefault();
        activateTab('add');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }

    document.querySelectorAll('#student-tabs .tab-link').forEach(function(btn){
      btn.addEventListener('click', function(){
        activateTab(this.dataset.tab);
      });
    });

    document.querySelectorAll('.btn-edit').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var idEl = document.getElementById('edit-id');
        if (idEl && this.dataset.id != null) idEl.value = this.dataset.id;
        // Generic: map all data-* to inputs with id="edit-<key>"
        Object.keys(this.dataset).forEach(function(k){
          var v = btn.dataset[k];
          var target = document.getElementById('edit-' + k);
          if (!target) return;
          if (target.tagName === 'SELECT' || target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
            target.value = v;
          }
        });
        activateTab('edit');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        try { document.querySelector('[id^="edit-"]')?.focus(); } catch(e){}
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStudentTabs);
  } else {
    initStudentTabs();
  }
})();
JS
    . "\n" . '</script>';
}

// Back-compat wrapper
function renderStudentsTabsScript(): void { renderCrudTabsScript(); }

if (!function_exists('formatYearLabel')) {
    function formatYearLabel($value, array $labels)
    {
        if ($value === null || $value === '') {
            return '--';
        }
        return $labels[$value] ?? $value;
    }
}

if (!function_exists('formatSemesterLabel')) {
    function formatSemesterLabel($value, array $labels)
    {
        if ($value === null || $value === '') {
            return '--';
        }
        return $labels[$value] ?? $value;
    }
}

if (!function_exists('buildStudentSearchIndex')) {
    function buildStudentSearchIndex(array $row): string
    {
        $segments = [];
        foreach (['student_code', 'first_name', 'middle_name', 'last_name'] as $key) {
            if (!empty($row[$key])) {
                $segments[] = $row[$key];
            }
        }
        return strtolower(implode(' ', $segments));
    }
}
