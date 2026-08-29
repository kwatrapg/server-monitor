<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('SSL Certificates'); ?><small> <?php _e('Manage SSL expiry monitoring'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addSsl",$perms)) { ?><a onClick='showM("?modal=ssl/add&reroute=ssl");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('ADD SSL CHECK'); ?></a><?php } ?></div>
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
										<th class="nosort"><?php _e('URL'); ?></th>
										<th class="nosort"><?php _e('Last Checked'); ?></th>
										<th class="nosort"><?php _e('Expiry Date'); ?></th>
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

<script type="text/javascript">
	$("#dataTableAjax").dataTable( {
		"ajax": '?json=ssl',

        "processing": true,
        "serverSide": true,

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
