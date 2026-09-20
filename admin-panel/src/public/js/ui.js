// Small, generic UI behaviors driven by data attributes, loaded as an
// external file (not inline <script>/onXxx="" attributes) because the app's
// Content-Security-Policy is script-src 'self' + script-src-attr 'none' —
// both inline scripts and inline event handler attributes are silently
// blocked by the browser and never run.
document.addEventListener('DOMContentLoaded', function () {
  // Checkbox-driven show/hide: data-toggles="<id>" on a checkbox shows/hides
  // the element with that id based on whether it's checked.
  document.querySelectorAll('[data-toggles]').forEach(function (checkbox) {
    var target = document.getElementById(checkbox.getAttribute('data-toggles'));
    if (!target) return;
    var shownDisplay = checkbox.getAttribute('data-toggle-display') || 'block';
    function sync() { target.style.display = checkbox.checked ? shownDisplay : 'none'; }
    checkbox.addEventListener('change', sync);
    sync();
  });

  // Confirm-before-submit: data-confirm="<message>" on a <form> asks for
  // confirmation before it submits, cancelling the submit if declined.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
