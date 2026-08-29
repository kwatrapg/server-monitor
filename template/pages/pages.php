<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>




<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Pages'); ?><small> <?php _e('Manage pages'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addPage",$perms)) { ?><a onClick='showM("?modal=pages/add&reroute=pages");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('ADD PAGE'); ?></a><?php } ?></div>
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
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($pages as $page) { if(!checkGroup($page['groupid'])) continue; ?>
		                                <tr>
		                                    <td><?php echo $page['id']; ?></td>
											<td><?php echo getSingleValue("app_groups","name",$page['groupid']); ?></td>
		                                    <td><?php echo $page['name']; ?></td>
											<td>
												<div class='pull-right'>
													<div class="btn-group">
														<a href="?route=publicpage&key=<?php echo $page['pagekey']; ?>" class="btn btn-primary btn-flat btn-sm" target="_blank"><i class="fa fa-eye"></i></a>

														 <?php if(in_array("editPage",$perms)) { ?><a href="#" onClick='showM("?modal=pages/edit&reroute=pages&routeid=&id=<?php echo $page['id']; ?>&section=");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deletePage",$perms)) { ?><a href="#" onClick='showM("?modal=pages/delete&reroute=pages&routeid=&id=<?php echo $page['id']; ?>&section=");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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
