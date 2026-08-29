<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Servers'); ?><small> <?php _e('Manage servers'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addServer",$perms)) { ?><a onClick='showM("?modal=servers/add&reroute=servers");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('ADD SERVER'); ?></a><?php } ?></div>
		<div style="clear:both"></div>
	</section>
	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-primary">
                    <div class="box-body">
						<div class="table-responsive">
	                        <table id="dataTableAjax" class="table table-striped table-hover table-bordered">
	                            <thead>
	                                <tr>
										<th style="width:1%"></th>
	                                    <th style="width:1%"><?php _e('ID'); ?></th>
	                                    <th><?php _e('Name'); ?></th>
										<th><?php _e('Group'); ?></th>

										<th class="nosort" style="width:1%"><?php _e('OS'); ?></th>
										<th class="nosort" style="width:1%"><?php _e('CPU'); ?></th>
										<th class="nosort" style="width:1%"><?php _e('RAM'); ?></th>
										<th class="nosort" style="width:1%"><?php _e('Disk'); ?></th>
										<th class="nosort" style="width:1%"><?php _e('Load'); ?></th>
										<th class="nosort" style=""><?php _e('Net'); ?></th>
										<th class="nosort"><?php _e('Uptime'); ?></th>
										<th class="nosort"><?php _e('Last Seen'); ?></th>
										<th class="text-right nosort"></th>
	                                </tr>
	                            </thead>

							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section><!-- /.content -->
</aside><!-- /.right-side -->


<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript">
	$(document).ready(function() {
		$(".donut").peity("donut")

	});
</script>


<script type="text/javascript">
	$("#dataTableAjax").dataTable( {
		"ajax": '?json=servers',

		"initComplete": function(settings, json) {
	    	$(".donut").peity("donut");
	  	},

		"fnDrawCallback": function(settings) {
		    $(".donut").peity("donut");
		},

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
