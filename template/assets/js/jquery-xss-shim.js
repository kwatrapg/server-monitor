/* jQuery < 3.5 XSS hardening — official workaround for CVE-2020-11022 / CVE-2020-11023.
   Neutralises the self-closing-tag / <option> rewrite that enabled the bypass.
   Remove once the front-end is migrated to jQuery >= 3.7 (see docs/REMEDIATION.md). */
(function ($) {
  if (!$ || !$.fn || !$.fn.jquery) return;
  var rxhtmlTag = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi;
  $.htmlPrefilter = function (html) {
    return html.replace(rxhtmlTag, "<$1></$2>");
  };
})(window.jQuery);
