/* Live password-policy checklist.
   Attaches to any input carrying data-password-checklist="<selector>" pointing at a
   <ul> whose <li data-rule="length|upper|lower|digit"> items get a "met" class as
   each rule passes. Rules mirror sm_password_policy_error() in includes/security.php
   — keep both in sync if the policy ever changes. */
(function ($) {
  if (!$) return;

  function evaluate($input) {
    var val = $input.val() || '';
    var $list = $($input.data('password-checklist'));
    if (!$list.length) return;
    $list.find('[data-rule]').each(function () {
      var met;
      switch ($(this).attr('data-rule')) {
        case 'length': met = val.length >= 12; break;
        case 'upper':  met = /[A-Z]/.test(val); break;
        case 'lower':  met = /[a-z]/.test(val); break;
        case 'digit':  met = /\d/.test(val); break;
        default:       met = false;
      }
      $(this).toggleClass('met', met);
    });
  }

  $(function () {
    $('[data-password-checklist]').each(function () {
      var $input = $(this);
      evaluate($input);
      $input.on('input keyup change', function () { evaluate($input); });
    });
  });
})(window.jQuery);
