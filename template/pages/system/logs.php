<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php _e('Logs'); ?><small> <?php _e('View application logs'); ?></small></h1>
		<ol class="breadcrumb"><li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li><li><?php _e('System'); ?></li><li class="active"><?php _e('Logs'); ?></li></ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
	    <div class='row'>
            <div class='col-md-12'>
                <!-- Custom Tabs -->
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li <?php if ($section == "" or $section == "system") echo 'class="active"'; ?> ><a href="?route=system/logs&section=system"><?php _e('Activity Log'); ?></a></li>
						<li <?php if ($section == "email") echo 'class="active"'; ?> ><a href="?route=system/logs&section=email"><?php _e('Email Message Log'); ?></a></li>
						<li <?php if ($section == "sms") echo 'class="active"'; ?> ><a href="?route=system/logs&section=sms"><?php _e('SMS Message Log'); ?></a></li>
						<li <?php if ($section == "cron") echo 'class="active"'; ?> ><a href="?route=system/logs&section=cron"><?php _e('Cron Log'); ?></a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane <?php if ($section == "" or $section == "system") echo 'active'; ?>" id="system">
							<div class="table-responsive">
								<table id="dataTableAjaxActivityLog" class="table table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('ID'); ?></th>
											<th><?php _e('User'); ?></th>
											<th><?php _e('IP Address'); ?></th>
											<th><?php _e('Description'); ?></th>
											<th><?php _e('Timestamp'); ?></th>
										</tr>
									</thead>

								</table>
							</div>
						</div><!-- /.tab-pane -->

                        <div class="tab-pane <?php if ($section == "email") echo 'active'; ?>" id="email">
							<div class="table-responsive">
								<table id="dataTableAjaxEmailLog" class="table table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('ID'); ?></th>
											<th><?php _e('User'); ?></th>
											<th><?php _e('Email'); ?></th>
											<th><?php _e('Subject'); ?></th>
											<th><?php _e('Timestamp'); ?></th>
										</tr>
									</thead>

								</table>
							</div>
                        </div><!-- /.tab-pane -->

						<div class="tab-pane <?php if ($section == "sms") echo 'active'; ?>" id="sms">
							<div class="table-responsive">
								<table id="dataTableAjaxSmsLog" class="table table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('ID'); ?></th>
											<th><?php _e('Timestamp'); ?></th>
											<th class="nosort"><?php _e('Mobile'); ?></th>
											<th class="nosort"><?php _e('Text'); ?></th>
										</tr>
									</thead>

								</table>
							</div>
                        </div><!-- /.tab-pane -->

						<div class="tab-pane <?php if ($section == "cron") echo 'active'; ?>" id="cron">
							<div class="table-responsive">
								<table id="dataTableAjaxCronlog" class="table table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('ID'); ?></th>
											<th><?php _e('Timestamp'); ?></th>
											<th class="nosort"><?php _e('Data'); ?></th>
										</tr>
									</thead>

								</table>
							</div>
                        </div><!-- /.tab-pane -->


                    </div><!-- /.tab-content -->
                </div><!-- nav-tabs-custom -->
            </div><!-- /.col-->
        </div><!-- ./row -->


	</section><!-- /.content -->
</aside><!-- /.right-side -->

<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>

<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>




<script type="text/javascript">
	$("#dataTableAjaxActivityLog").dataTable( {
		"ajax": '?json=activitylog',

        "processing": true,
        "serverSide": true,
		//"ordering": false,

		"order": [],
		"pageLength": <?php echo getConfigValue("table_records"); ?>,
		"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
		"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
		"oLanguage": {
			"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
			"sEmptyTable": "<?php _e('No entries to show'); ?>",
			"sZeroRecords": "<?php _e('Nothing found'); ?>",
			"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
			"sInfoEmpty": "",
			"oPaginate": {
				"sNext": "<?php _e('Next'); ?>",
				"sPrevious": "<?php _e('Previous'); ?>",
				"sFirst": "<?php _e('First Page'); ?>",
				"sLast": "<?php _e('Last Page'); ?>"
			}
		},
		"columnDefs": [ { "orderable": false, "targets": 'nosort' } ]
	});
</script>

<script type="text/javascript">
	$("#dataTableAjaxEmailLog").dataTable( {
		"ajax": '?json=emaillog',

        "processing": true,
        "serverSide": true,
		//"ordering": false,

		"order": [],
		"pageLength": <?php echo getConfigValue("table_records"); ?>,
		"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
		"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
		"oLanguage": {
			"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
			"sEmptyTable": "<?php _e('No entries to show'); ?>",
			"sZeroRecords": "<?php _e('Nothing found'); ?>",
			"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
			"sInfoEmpty": "",
			"oPaginate": {
				"sNext": "<?php _e('Next'); ?>",
				"sPrevious": "<?php _e('Previous'); ?>",
				"sFirst": "<?php _e('First Page'); ?>",
				"sLast": "<?php _e('Last Page'); ?>"
			}
		},
		"columnDefs": [ { "orderable": false, "targets": 'nosort' } ]
	});
</script>

<script type="text/javascript">
	$("#dataTableAjaxSmsLog").dataTable( {
		"ajax": '?json=smslog',

        "processing": true,
        "serverSide": true,
		//"ordering": false,

		"order": [],
		"pageLength": <?php echo getConfigValue("table_records"); ?>,
		"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
		"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
		"oLanguage": {
			"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
			"sEmptyTable": "<?php _e('No entries to show'); ?>",
			"sZeroRecords": "<?php _e('Nothing found'); ?>",
			"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
			"sInfoEmpty": "",
			"oPaginate": {
				"sNext": "<?php _e('Next'); ?>",
				"sPrevious": "<?php _e('Previous'); ?>",
				"sFirst": "<?php _e('First Page'); ?>",
				"sLast": "<?php _e('Last Page'); ?>"
			}
		},
		"columnDefs": [ { "orderable": false, "targets": 'nosort' } ]
	});
</script>


<script type="text/javascript">
	$("#dataTableAjaxCronlog").dataTable( {
		"ajax": '?json=cronlog',

        "processing": true,
        "serverSide": true,
		//"ordering": false,

		"order": [],
		"pageLength": <?php echo getConfigValue("table_records"); ?>,
		"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
		"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
		"oLanguage": {
			"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
			"sEmptyTable": "<?php _e('No entries to show'); ?>",
			"sZeroRecords": "<?php _e('Nothing found'); ?>",
			"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
			"sInfoEmpty": "",
			"oPaginate": {
				"sNext": "<?php _e('Next'); ?>",
				"sPrevious": "<?php _e('Previous'); ?>",
				"sFirst": "<?php _e('First Page'); ?>",
				"sLast": "<?php _e('Last Page'); ?>"
			}
		},
		"columnDefs": [ { "orderable": false, "targets": 'nosort' } ]
	});
</script>
