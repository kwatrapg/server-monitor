$(document).ajaxStart(function() { Pace.restart(); });

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
