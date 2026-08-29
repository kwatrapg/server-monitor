<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>

<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>

<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Group'); ?><small> <?php _e('Manage groups'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addGroup",$perms)) { ?><a onClick='showM("?modal=groups/add&reroute=system/groups");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('NEW GROUP'); ?></a><?php } ?></div>
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
	                        <table id="dataTablesFullNoOrder" class="table table-striped table-hover table-bordered">
	                            <thead>
	                                <tr>
	                                    <th><?php _e('ID'); ?></th>
	                                    <th><?php _e('Name'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($groups as $group) { ?>
		                                <tr>
		                                    <td><?php echo $group['id']; ?></td>
		                                    <td><?php echo $group['name']; ?></td>
											<td>
												<div class='pull-right'>
													<div class="btn-group">
														 <?php if(in_array("editGroup",$perms)) { ?><a href="#" onClick='showM("?modal=groups/edit&reroute=system/groups&routeid=&id=<?php echo $group['id']; ?>&section=");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deleteGroup",$perms)) { ?><a href="#" onClick='showM("?modal=groups/delete&reroute=system/groups&routeid=&id=<?php echo $group['id']; ?>&section=");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
													</div>
												</div>
											</td>
		                                </tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section><!-- /.content -->
</aside><!-- /.right-side -->
