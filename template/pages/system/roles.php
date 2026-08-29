<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>

<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>

<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Roles'); ?><small> <?php _e('Manage user roles'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addRole",$perms)) { ?><a href='?route=system/roles/add' class="btn btn-flat btn-primary btn-sm"><?php _e('NEW ROLE'); ?></a><?php } ?></div>
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
	                                    <th><?php _e('Role Name'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
								<tbody>
									<?php foreach ($roles as $role) { ?>
										<tr>
											<td><?php echo e($role['name']); ?></td>
											<td>
												<div class='pull-right'>
													<div class="btn-group">
														<?php if($role['id'] != 1) { ?>
														 	<?php if(in_array("editRole",$perms)) { ?><a href="?route=system/roles/edit&id=<?php echo $role['id']; ?>" class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 	<?php if(in_array("deleteRole",$perms)) { ?><a href="#" onClick='showM("?modal=roles/delete&reroute=system/roles&routeid=&id=<?php echo $role['id']; ?>&section=");return false' type="button" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
														<?php } ?>
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
