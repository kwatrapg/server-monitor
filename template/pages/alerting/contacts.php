<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>


<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Contacts'); ?><small> <?php _e('Manage contacts'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addContact",$perms)) { ?><a onClick='showM("?modal=contacts/add&reroute=alerting/contacts");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('NEW CONTACT'); ?></a><?php } ?></div>
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
										<th><?php _e('Group'); ?></th>
	                                    <th><?php _e('Name'); ?></th>
										<th><?php _e('Channels'); ?></th>
										<th><?php _e('Status'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($contacts as $contact) { if(!checkGroup($contact['groupid'])) continue; ?>
		                                <tr>
		                                    <td><?php echo $contact['id']; ?></td>
											<td><?php echo e(getSingleValue("app_groups","name",$contact['groupid'])); ?></td>
		                                    <td><?php echo e($contact['name']); ?></td>
											<td>
												<?php if($contact['email'] != "") { ?>
													<i class="fa fa-at fa-fw" data-toggle="tooltip" title="<?php _e('Email:'); ?> <?php echo e($contact['email']); ?>"></i>
												<?php } ?>

												<?php if($contact['mobilenumber'] != "") { ?>
													<i class="fa fa-mobile fa-fw" data-toggle="tooltip" title="<?php _e('Mobile Number:'); ?> <?php echo e($contact['mobilenumber']); ?>"></i>
												<?php } ?>

												<?php if($contact['pushbullet'] != "") { ?>
													<i class="fa fa-arrow-circle-o-right fa-fw" data-toggle="tooltip" title="<?php _e('Pushbullet:'); ?> <?php echo $contact['pushbullet']; ?>"></i>
												<?php } ?>

												<?php if($contact['twitter'] != "") { ?>
													<i class="fa fa-twitter fa-fw" data-toggle="tooltip" title="<?php _e('Twitter:'); ?> <?php echo $contact['twitter']; ?>"></i>
												<?php } ?>

												<?php if($contact['pushover'] != "") { ?>
													<i class="fa fa-caret-square-o-right fa-fw" data-toggle="tooltip" title="<?php _e('Pushover:'); ?> <?php echo $contact['pushover']; ?>"></i>
												<?php } ?>
											</td>
											<td>
												<?php if($contact['status'] == 1) { ?>
													<span class="label label-success"><?php _e("Active"); ?></span>
												<?php } ?>
												<?php if($contact['status'] == 0) { ?>
													<span class="label label-default"><?php _e("Inactive"); ?></span>
												<?php } ?>
											</td>

											<td>
												<div class='pull-right'>
													<div class="btn-group">
														 <?php if(in_array("editContact",$perms)) { ?><a href="#" onClick='showM("?modal=contacts/edit&reroute=alerting/contacts&routeid=&id=<?php echo $contact['id']; ?>&section=");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deleteContact",$perms)) { ?><a href="#" onClick='showM("?modal=contacts/delete&reroute=alerting/contacts&routeid=&id=<?php echo $contact['id']; ?>&section=");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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
