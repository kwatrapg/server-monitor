<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Check Services'); ?><small> <?php _e('Manage check services'); ?></small></h1>
		<div class="pull-right"><?php if(in_array("addCheck",$perms)) { ?><a onClick='showM("?modal=checks/add&reroute=checks");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('ADD CHECK'); ?></a><?php } ?></div>
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
										<th class="no-sort" style="width:1%"></th>
	                                    <th><?php _e('ID'); ?></th>
	                                    <th><?php _e('Name'); ?></th>
										<th><?php _e('Type'); ?></th>
										<th><?php _e('Group'); ?></th>
										<th><?php _e('Last Checked'); ?></th>
										<th><?php _e('Uptime'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($checks as $check) { ?>
		                                <tr>
											<td>
												<?php if($check['status'] == 1) { ?>
													<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
												<?php } elseif($check['status'] == 2) { ?>
													<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
												<?php } elseif($check['status'] == 3) { ?>
													<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
												<?php } else { ?>
													<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
												<?php } ?>
											</td>
		                                    <td><?php echo $check['id']; ?></td>
		                                    <td><?php echo $check['name']; ?></td>
											<td>
												<?php
													if($check['type'] == "tcp") { _e('TCP Port'); echo ": " . $check['port']; }
													if($check['type'] == "udp") { _e('UDP Port'); echo ": " . $check['port']; }
													if($check['type'] == "icmp") { _e('ICMP (Ping)'); echo ": " . $check['host']; }
													if($check['type'] == "dns") { _e('DNS Lookup'); echo ": " . $check['host']; }
													if($check['type'] == "blacklist") { _e('Blacklist Check'); echo ": " . $check['host']; }
												?>
											</td>
											<td><?php echo getSingleValue("app_groups","name",$check['groupid']); ?></td>
											<td><?php echo smartDate(Check::lastChecked($check['id'])); ?></td>
											<td>
												<span data-toggle="tooltip" title="<?php _e('Last 24 Hours'); ?> <?php echo Check::uptime($check['id'],"24h"); ?>%">
													<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"24h"); ?>/100</span>
												</span>

												<span data-toggle="tooltip" title="<?php _e('Last 7 Days'); ?> <?php echo Check::uptime($check['id'],"7days"); ?>%">
													<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"7days"); ?>/100</span>
												</span>

												<span data-toggle="tooltip" title="<?php _e('Last 30 Days'); ?> <?php echo Check::uptime($check['id'],"30days"); ?>%">
													<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"30days"); ?>/100</span>
												</span>
											</td>
											<td>
												<div class='pull-right'>
													<div class="btn-group">
														<a href="?route=checks/manage&id=<?php echo $check['id']; ?>" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>
														 <?php if(in_array("editCheck",$perms)) { ?><a href="#" onClick='showM("?modal=checks/edit&reroute=checks&routeid=&id=<?php echo $check['id']; ?>&section=");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deleteCheck",$perms)) { ?><a href="#" onClick='showM("?modal=checks/delete&reroute=checks&routeid=&id=<?php echo $check['id']; ?>&section=");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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


<script type="text/javascript">
	$(document).ready(function() {
		$(".donut").peity("donut")

	});
</script>
