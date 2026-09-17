<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo $logsource['name']; ?><small> <?php echo $logsourceServer['name']; ?></small></h1>
		<ol class="breadcrumb">
			<li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li>
			<li><a href="?route=logs/sources"><?php _e('Log Sources'); ?></a></li>
			<li class="active"><?php echo $logsource['name']; ?></li>
		</ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>

		<div class="row">
			<div class="col-md-12">

				<div class="nav-tabs-custom">
					<ul class="nav nav-tabs">
						<li <?php if ($section == "" or $section == "overview") echo 'class="active"'; ?>><a href="?route=logs/sources/manage&id=<?php echo $logsource['id']; ?>&section="><?php _e('Overview'); ?></a></li>
						<li <?php if ($section == "alerting") echo 'class="active"'; ?>><a href="?route=logs/sources/manage&id=<?php echo $logsource['id']; ?>&section=alerting"><?php _e('Alerting'); ?></a></li>
						<li <?php if ($section == "incidents") echo 'class="active"'; ?>><a href="?route=logs/sources/manage&id=<?php echo $logsource['id']; ?>&section=incidents"><?php _e('Incidents'); ?></a></li>

						<div class="btn-group pull-right" style="padding:6px;">
							<?php if ($section == "alerting" && in_array("editLogAlert",$perms)) { ?>
								<a data-toggle='tooltip' title='<?php _e('Add Alert'); ?>' class="btn btn-primary btn-flat btn-sm" href="#" onClick='showM("?modal=logalerts/add&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&section=alerting");return false'><i class="fa fa-plus"></i> <?php _e('ADD ALERT'); ?></a>
							<?php } ?>
						</div>
					</ul>

					<div class="tab-content">

						<!-- OVERVIEW -->
						<div class="tab-pane <?php if ($section == "" or $section == "overview") echo 'active'; ?>" id="overview">

							<div class="row">
								<div class="col-md-9">
									<strong><?php _e('Server'); ?>:</strong> <a href="?route=servers/manage-<?php echo $logsourceServer['type']; ?>&id=<?php echo $logsourceServer['id']; ?>"><?php echo $logsourceServer['name']; ?></a>
									&nbsp;&middot;&nbsp;
									<strong><?php _e('Path'); ?>:</strong> <code><?php echo htmlspecialchars($logsource['path_glob']); ?></code>
									&nbsp;&middot;&nbsp;
									<strong><?php _e('Mode'); ?>:</strong> <?php echo __(ucwords(str_replace('_',' ',$logsource['mode']))); ?>
								</div>
								<div class="col-md-3 text-right">
									<button type="button" class="btn btn-default btn-flat btn-sm" id="daterange-btn">
										<i class="fa fa-calendar fa-fw"></i> <span><?php _e('Date Range'); ?></span> <i class="fa fa-caret-down fa-fw"></i>
									</button>
									<form role="form" method="post" enctype="multipart/form-data" id="rangeForm">
										<input type="hidden" name="action" value="setRange">
										<input type="hidden" name="range_start" id="range_start" value="">
										<input type="hidden" name="range_end" id="range_end" value="">
										<input type="hidden" name="range_label" id="range_label" value="">
										<input type="hidden" name="asset" value="logsource-<?php echo $logsource['id']; ?>">
										<input type="hidden" name="route" value="logs/sources/manage">
										<input type="hidden" name="routeid" value="<?php echo $logsource['id']; ?>">
										<input type="hidden" name="section" value="">
									</form>
								</div>
							</div>

							<hr>

							<div class="chart">
								<canvas id="log-volume-chart" style="height:180px"></canvas>
							</div>

							<hr>

							<div class="row" style="margin-bottom:10px">
								<div class="col-md-6">
									<div class="btn-group" data-toggle="buttons">
										<?php foreach (['debug'=>'Debug','info'=>'Info','notice'=>'Notice','warn'=>'Warn','error'=>'Error','crit'=>'Critical'] as $lvl => $lbl) { ?>
											<label class="btn btn-default btn-flat btn-sm">
												<input type="checkbox" class="log-level-filter" value="<?php echo $lvl; ?>" autocomplete="off"> <?php _e($lbl); ?>
											</label>
										<?php } ?>
									</div>
									<span class="text-muted" style="margin-left:6px"><?php _e('none checked = all levels'); ?></span>
								</div>
								<div class="col-md-4">
									<input type="text" class="form-control input-sm" id="log-text-filter" placeholder="<?php _e('Search log text...'); ?>">
								</div>
								<div class="col-md-2 text-right">
									<button type="button" class="btn btn-flat btn-default btn-sm" id="log-live-toggle">
										<i class="fa fa-play"></i> <?php _e('Live'); ?>
									</button>
								</div>
							</div>

							<div id="log-lines" class="log-lines-container"></div>
							<div class="text-center" style="margin-top:8px">
								<button type="button" class="btn btn-flat btn-default btn-sm" id="log-load-more"><?php _e('Load older lines'); ?></button>
							</div>

						</div>
						<!-- /OVERVIEW -->

						<!-- ALERTING -->
						<div class="tab-pane <?php if ($section == "alerting") echo 'active'; ?>" id="alerting">
							<div class="table-responsive">
								<table id="dataTablesFullNoOrder2" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('Scope'); ?></th>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Condition'); ?></th>
											<th><?php _e('Window'); ?></th>
											<th><?php _e('Action'); ?></th>
											<th><?php _e('Status'); ?></th>
											<th class="text-right"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($logAlerts as $alert) { $selected_contacts = unserialize($alert['contacts']); if(empty($selected_contacts)) $selected_contacts = []; ?>
											<tr>
												<td>
													<?php if ((int)$alert['sourceid'] === 0) { _e('All sources'); } else { echo getSingleValue("app_servers_logsources","name",$alert['sourceid']); } ?>
												</td>
												<td>
													<?php if($alert['type'] == "matchcount") _e('Pattern Match Count'); ?>
													<?php if($alert['type'] == "levelcount") _e('Level Count'); ?>
													<?php if($alert['type'] == "absence") _e('Absence (no data)'); ?>
													<?php if($alert['type'] == "ratespike") _e('Rate Spike'); ?>
												</td>
												<td>
													<?php if($alert['type'] == "matchcount") { ?>
														"<?php echo htmlspecialchars($alert['pattern']); ?>" <?php echo $alert['comparison']; ?> <?php echo $alert['comparison_limit']; ?>
													<?php } elseif($alert['type'] == "levelcount") { ?>
														[<?php echo htmlspecialchars($alert['pattern'] ?: 'error,crit'); ?>] <?php echo $alert['comparison']; ?> <?php echo $alert['comparison_limit']; ?>
													<?php } elseif($alert['type'] == "absence") { ?>
														<?php _e('N/A'); ?>
													<?php } elseif($alert['type'] == "ratespike") { ?>
														&gt; <?php echo $alert['comparison_limit']; ?>x <?php _e('baseline'); ?>
													<?php } ?>
												</td>
												<td><?php echo $alert['window_minutes']; ?> <?php _e('min'); ?></td>
												<td>
													<?php _e('alert:'); ?>
													<?php foreach ($selected_contacts as $selected_contact) { ?>
														<span class="label bg-gray"><?php echo getSingleValue("app_contacts", "name", $selected_contact); ?></span>&nbsp;
													<?php } ?>
												</td>
												<td>
													<?php if($alert['status'] == 1) { ?><span class="label label-success"><?php _e("Active"); ?></span><?php } ?>
													<?php if($alert['status'] == 0) { ?><span class="label label-default"><?php _e("Inactive"); ?></span><?php } ?>
												</td>
												<td>
													<div class='pull-right'>
														<div class="btn-group">
															<?php if(in_array("editLogAlert",$perms)) { ?><a href="#" onClick='showM("?modal=logalerts/edit&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false' class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
															<?php if(in_array("editLogAlert",$perms)) { ?><a href="#" onClick='showM("?modal=logalerts/delete&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
														</div>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						<!-- /ALERTING -->

						<!-- INCIDENTS -->
						<div class="tab-pane <?php if ($section == "incidents") echo 'active'; ?>" id="incidents">
							<div class="table-responsive">
								<table id="dataTablesFullNoOrder3" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Value'); ?></th>
											<th><?php _e('Sample Line'); ?></th>
											<th><?php _e('Start Time'); ?></th>
											<th><?php _e('End Time'); ?></th>
											<th><?php _e('Comment'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($logIncidents as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editLogAlert",$perms)) { ?><a href="#" onClick='showM("?modal=logalerts/markResolved&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editLogAlert",$perms)) { ?><a href="#" onClick='showM("?modal=logalerts/markResolved&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a><?php } ?>
													<?php } ?>
												</td>
												<td>
													<?php if($incident['type'] == "matchcount") _e('Pattern Match Count'); ?>
													<?php if($incident['type'] == "levelcount") _e('Level Count'); ?>
													<?php if($incident['type'] == "absence") _e('Absence (no data)'); ?>
													<?php if($incident['type'] == "ratespike") _e('Rate Spike'); ?>
												</td>
												<td><?php echo htmlspecialchars($incident['value']); ?></td>
												<td><code style="white-space:normal;word-break:break-all;"><?php echo htmlspecialchars($incident['sample_line']); ?></code></td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
												<td>
													<?php if($incident['end_time'] != "0000-00-00 00:00:00") echo dateTimeDisplay($incident['end_time']); else { ?> <a href="#" onClick='showM("?modal=logalerts/markResolved&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php _e("Mark Resolved"); ?></a> <?php } ?>
												</td>
												<td>
													<?php echo $incident['comment']; ?> <?php if($incident['ignore'] == '1') { ?> [<?php _e('IGNORED'); ?>]<?php } ?> <a href="#" onClick='showM("?modal=logalerts/editComment&reroute=logs/sources/manage&routeid=<?php echo $logsource['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php if($incident['comment'] == "") _e("Add"); else _e("Edit"); ?></a>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
						<!-- /INCIDENTS -->

					</div>
				</div>

			</div>
		</div>

	</section><!-- /.content -->
</aside><!-- /.right-side -->

<style type="text/css">
	.log-lines-container {
		max-height: 520px;
		overflow-y: auto;
		background: #1e1e1e;
		color: #d4d4d4;
		font-family: Menlo, Consolas, "Courier New", monospace;
		font-size: 12px;
		padding: 8px;
		border-radius: 3px;
	}
	.log-line { white-space: pre-wrap; word-break: break-all; padding: 1px 0; border-bottom: 1px solid #2a2a2a; }
	.log-ts { color: #808080; margin-right: 8px; }
	.log-level-badge { display:inline-block; min-width:52px; text-align:center; margin-right:8px; padding:0 4px; border-radius:2px; font-weight:bold; }
	.log-level-debug  .log-level-badge { background:#3a3a3a; color:#bbb; }
	.log-level-info   .log-level-badge { background:#264f78; color:#cfe8ff; }
	.log-level-notice .log-level-badge { background:#2d5c3f; color:#c8f0d4; }
	.log-level-warn   .log-level-badge { background:#7a5c00; color:#ffe9a8; }
	.log-level-error  .log-level-badge { background:#7a1f1f; color:#ffc9c9; }
	.log-level-crit   .log-level-badge { background:#a10000; color:#ffffff; }
	.log-level-unknown .log-level-badge { background:#3a3a3a; color:#bbb; }
	.log-repeat { color:#808080; margin-left:6px; }
	.log-empty { color:#808080; padding:8px; }
	#log-live-toggle.active { background:#d9534f; color:#fff; border-color:#d43f3a; }
</style>

<script type="text/javascript">
(function() {
	var serverid = <?php echo (int)$logsourceServer['id']; ?>;
	var sourceid = <?php echo (int)$logsource['id']; ?>;
	var pollBaseSeconds = <?php echo max(1, (int)getConfigValue('log_live_poll_seconds')); ?>;
	var rangeStart = "<?php echo $start; ?>";
	var rangeEnd = "<?php echo $end; ?>";

	var MAX_LINES = 2000;
	var LEVEL_ORDER = ['crit','error','warn','notice','info','debug','unknown'];
	var LEVEL_COLORS = {
		crit:'#a10000', error:'#c0392b', warn:'#e6a817', notice:'#2d8659', info:'#3e95cd', debug:'#888888', unknown:'#555555'
	};

	// Firefox (and some other browsers) restore checkbox .checked state on a plain
	// refresh regardless of autocomplete="off" - these filters have no server-side
	// "checked" state at all (nothing persists them), so a restored checkbox here is
	// always stale relative to what's actually being requested below. Force every
	// level filter back to unchecked on load so the visible state always matches
	// "no filter = all levels", independent of browser history restoration.
	$('.log-level-filter').prop('checked', false).closest('label.btn').removeClass('active');

	var $lines = $('#log-lines');
	var oldestCursor = null;   // for "load older" (backward search pagination)
	var liveOn = false;
	var liveCursor = null;
	var liveTimer = null;
	var livePollMs = pollBaseSeconds * 1000;
	var liveIdleTicks = 0;
	var chart = null;

	function escapeHtml(s) {
		return $('<div>').text(s == null ? '' : String(s)).html();
	}

	function selectedLevels() {
		var levels = [];
		$('.log-level-filter:checked').each(function() { levels.push($(this).val()); });
		return levels;
	}

	function currentText() {
		return $('#log-text-filter').val() || '';
	}

	function baseParams() {
		var params = { serverid: serverid, sourceid: sourceid };
		var levels = selectedLevels();
		if (levels.length) params['levels'] = levels;
		var text = currentText();
		if (text) params.text = text;
		return params;
	}

	function levelClass(level) {
		return LEVEL_ORDER.indexOf(level) !== -1 ? level : 'unknown';
	}

	function renderLine(row, prepend) {
		var cls = levelClass(row.level);
		var html = '<div class="log-line log-level-' + cls + '">' +
			'<span class="log-ts">' + escapeHtml(row.ts) + '</span>' +
			'<span class="log-level-badge">' + escapeHtml((row.level || 'unknown').toUpperCase()) + '</span>' +
			'<span class="log-msg">' + escapeHtml(row.message) + '</span>' +
			(row.repeat_count > 1 ? '<span class="log-repeat">&times;' + row.repeat_count + '</span>' : '') +
			'</div>';
		if (prepend) $lines.prepend(html); else $lines.append(html);
	}

	function trimBuffer() {
		var $children = $lines.children('.log-line');
		if ($children.length > MAX_LINES) {
			$children.slice(0, $children.length - MAX_LINES).remove();
		}
	}

	function isScrolledToBottom() {
		var el = $lines[0];
		return (el.scrollHeight - el.scrollTop - el.clientHeight) < 40;
	}

	function loadInitial() {
		$lines.empty();
		var params = $.extend(baseParams(), { from: rangeStart, to: rangeEnd, limit: 500, direction: 'backward' });
		$.getJSON('?json=logsearch', params, function(data) {
			if (!data || !data.rows || !data.rows.length) {
				$lines.html('<div class="log-empty"><?php echo addslashes(__("No log lines in this range.")); ?></div>');
				oldestCursor = null;
				return;
			}
			// search() returns newest-first; render in ascending order so oldest is on top
			var rows = data.rows.slice().reverse();
			for (var i = 0; i < rows.length; i++) renderLine(rows[i], false);
			oldestCursor = data.rows[data.rows.length - 1].ts_nanos;
			$lines.scrollTop($lines[0].scrollHeight);
		}).fail(function() {
			$lines.html('<div class="log-empty"><?php echo addslashes(__("Could not reach the log backend.")); ?></div>');
		});
	}

	function loadOlder() {
		var params = $.extend(baseParams(), { to: rangeStart ? rangeStart : rangeEnd, from: '2000-01-01 00:00:00', limit: 200, direction: 'backward' });
		// step further back than the oldest line currently shown
		if (oldestCursor) {
			var ms = Math.floor(parseInt(oldestCursor, 10) / 1000000) - 1;
			params.to = new Date(ms).toISOString();
		}
		$.getJSON('?json=logsearch', params, function(data) {
			if (!data || !data.rows || !data.rows.length) return;
			var el = $lines[0];
			var prevHeight = el.scrollHeight;
			var rows = data.rows.slice().reverse();
			for (var i = 0; i < rows.length; i++) renderLine(rows[i], true);
			oldestCursor = data.rows[data.rows.length - 1].ts_nanos;
			el.scrollTop = el.scrollHeight - prevHeight;
		});
	}

	function loadHistogram() {
		var params = $.extend({ serverid: serverid, sourceid: sourceid, from: rangeStart, to: rangeEnd }, (function(){
			var lv = selectedLevels(); return {};
		})());
		$.getJSON('?json=loghistogram', params, function(data) {
			renderChart(data && data.buckets ? data.buckets : []);
		});
	}

	function renderChart(buckets) {
		// footer.php's Chart.bundle.min.js is a normal blocking <script> tag lower in the
		// page, but this can still run before it's finished in practice (the histogram AJAX
		// response can win the race against a large bundle download) - retry briefly rather
		// than throw ReferenceError and silently lose the chart.
		if (typeof Chart === 'undefined') {
			setTimeout(function() { renderChart(buckets); }, 100);
			return;
		}

		var labels = [];
		var seen = {};
		for (var i = 0; i < buckets.length; i++) { labels.push(buckets[i].ts); }

		var datasets = [];
		LEVEL_ORDER.forEach(function(level) {
			var data = buckets.map(function(b) { return (b.levels && b.levels[level]) ? b.levels[level] : 0; });
			if (data.every(function(v) { return v === 0; })) return;
			datasets.push({
				label: level,
				backgroundColor: LEVEL_COLORS[level],
				data: data,
			});
		});

		var ctx = document.getElementById('log-volume-chart');
		if (chart) chart.destroy();
		chart = new Chart(ctx, {
			type: 'bar',
			data: { labels: labels, datasets: datasets },
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					xAxes: [{ stacked: true, ticks: { maxTicksLimit: 12 } }],
					yAxes: [{ stacked: true, ticks: { beginAtZero: true, precision: 0 } }],
				},
				legend: { display: true, position: 'bottom' },
				tooltips: { mode: 'index', intersect: false },
			}
		});
	}

	function applyFilters() {
		loadInitial();
		loadHistogram();
		if (liveOn) { liveCursor = null; }
	}

	// ---- Live tail: cursor polling, backoff, hidden-tab pause, scroll-aware ----

	function liveTick() {
		if (!liveOn) return;
		if (document.hidden) { scheduleLiveTick(); return; }

		var params = $.extend(baseParams(), { cursor: liveCursor || '', limit: 200 });
		$.getJSON('?json=logtail', params, function(data) {
			if (data && data.rows && data.rows.length) {
				var atBottom = isScrolledToBottom();
				for (var i = 0; i < data.rows.length; i++) renderLine(data.rows[i], false);
				trimBuffer();
				if (atBottom) $lines.scrollTop($lines[0].scrollHeight);
				liveCursor = data.cursor;
				liveIdleTicks = 0;
				livePollMs = pollBaseSeconds * 1000;
			} else {
				liveIdleTicks++;
				// back off to ~10s when idle
				if (liveIdleTicks > 2) livePollMs = Math.max(livePollMs, 10000);
			}
		}).always(function() {
			scheduleLiveTick();
		});
	}

	function scheduleLiveTick() {
		if (!liveOn) return;
		liveTimer = setTimeout(liveTick, livePollMs);
	}

	function startLive() {
		liveOn = true;
		liveCursor = null;
		liveIdleTicks = 0;
		livePollMs = pollBaseSeconds * 1000;
		$('#log-live-toggle').addClass('active').html('<i class="fa fa-stop"></i> <?php echo addslashes(__("Live")); ?>');
		liveTick();
	}

	function stopLive() {
		liveOn = false;
		if (liveTimer) { clearTimeout(liveTimer); liveTimer = null; }
		$('#log-live-toggle').removeClass('active').html('<i class="fa fa-play"></i> <?php echo addslashes(__("Live")); ?>');
	}

	$('#log-live-toggle').on('click', function() {
		if (liveOn) stopLive(); else startLive();
	});

	document.addEventListener('visibilitychange', function() {
		if (!liveOn) return;
		if (!document.hidden) {
			// wake up immediately instead of waiting out a long backoff interval
			if (liveTimer) { clearTimeout(liveTimer); }
			liveTick();
		}
	});

	$('.log-level-filter').on('change', applyFilters);
	var textFilterTimer = null;
	$('#log-text-filter').on('input', function() {
		clearTimeout(textFilterTimer);
		textFilterTimer = setTimeout(applyFilters, 400);
	});
	$('#log-load-more').on('click', loadOlder);

	loadInitial();
	loadHistogram();
})();
</script>
