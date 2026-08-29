$(document).ajaxStart(function() { Pace.restart(); });

// --- CSRF (VAPT F-07) ------------------------------------------------------
// Attach the per-session token to every same-origin AJAX request and inject a
// hidden field into every form that does not already carry one.
var SM_CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
if (SM_CSRF) {
	$.ajaxSetup({
		headers: { 'X-CSRF-Token': SM_CSRF },
		crossDomain: false
	});
	$(function () {
		$('form').each(function () {
			if (!$(this).find('input[name="csrf_token"]').length) {
				$('<input>', { type: 'hidden', name: 'csrf_token', value: SM_CSRF }).appendTo(this);
			}
		});
		// Forms injected later (AdminLTE modals load content via .load()).
		$(document).on('submit', 'form', function () {
			if (!$(this).find('input[name="csrf_token"]').length) {
				$('<input>', { type: 'hidden', name: 'csrf_token', value: SM_CSRF }).appendTo(this);
			}
		});
	});
}

$(document).ready(function() {

	window.setTimeout(function() {
		$(".alert-auto").fadeTo(500, 0).slideUp(500, function(){
			$(this).remove();
		});
	}, 3000);

	$(".select2").select2();

	$(".select2tag").select2({
		tags: true,
		maximumSelectionLength: 1
	});

	 $(".select2tags").select2({
        tags: true
    });

	$('.summernoteLarge').summernote({height: 400});
	$('.summernote').summernote({height: 200});



});

var myRefreshTimeout;

function startAutorefresh(refreshPeriod) {
	myRefreshTimeout = setTimeout("window.location.reload();",refreshPeriod);
}

function stopAutorefresh() {
	clearTimeout(myRefreshTimeout);
	window.location.hash = 'stop'
}


function showM(url) {
	$('.modal-content').empty();

	$('.modal-content').load(url);
	$('#myModal').modal('show');
	stopAutorefresh();
}

function goBack() {
    window.history.back()
}


function Countdown(options) {
  var timer,
  instance = this,
  seconds = options.seconds || 10,
  updateStatus = options.onUpdateStatus || function () {},
  counterEnd = options.onCounterEnd || function () {};

  function decrementCounter() {
    updateStatus(seconds);
    if (seconds === 0) {
      counterEnd();
      instance.stop();
    }
    seconds--;
  }

  this.start = function () {
    clearInterval(timer);
    timer = 0;
    seconds = options.seconds;
    timer = setInterval(decrementCounter, 1000);
  };

  this.stop = function () {
    clearInterval(timer);
  };
}
