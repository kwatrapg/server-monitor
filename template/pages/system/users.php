<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Users'); ?><small> <?php _e('Manage user accounts'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addUser",$perms)) { ?><a onClick='showM("?modal=users/add&reroute=system/users");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('NEW USER'); ?></a><?php } ?></div>
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
	                                    <th><?php _e('Name'); ?></th>
	                                    <th><?php _e('Email'); ?></th>
										<th><?php _e('Role'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($users as $user) { ?>
		                                <tr>
		                                    <td><?php echo $user['name']; ?></td>
		                                    <td><?php echo $user['email']; ?></td>
											<td><?php echo getSingleValue("core_roles","name",$user['roleid']); ?></td>
											<td>
												<div class='pull-right'>
													<div class="btn-group">
														 <?php if(in_array("editUser",$perms)) { ?><a href="?route=system/users/edit&id=<?php echo $user['id']; ?>" class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deleteUser",$perms)) { ?><a href="#" onClick='showM("?modal=users/delete&reroute=system/users&routeid=&id=<?php echo $user['id']; ?>&section=");return false' type="button" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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
