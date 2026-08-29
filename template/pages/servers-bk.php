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
	                        <table id="dataTablesFullNoOrder" class="table table-striped table-hover table-bordered">
	                            <thead>
	                                <tr>
										<th class="no-sort" style="width:1%"></th>
	                                    <th><?php _e('ID'); ?></th>
	                                    <th><?php _e('Name'); ?></th>
										<th><?php _e('Group'); ?></th>

										<th style="width:1%"><?php _e('OS'); ?></th>
										<th style="width:1%"><?php _e('CPU'); ?></th>
										<th style="width:1%"><?php _e('RAM'); ?></th>
										<th style="width:1%"><?php _e('Disk'); ?></th>
										<th style="width:1%"><?php _e('Load'); ?></th>
										<th style=""><?php _e('Net'); ?></th>
										<th><?php _e('Uptime'); ?></th>
										<th><?php _e('Last Seen'); ?></th>
										<th class="text-right"></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($servers as $server) { $latest = Server::latestData($server['id']); ?>

		                                <tr>
											<td>
												<?php if($server['status'] == 1) { ?>
													<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
												<?php } elseif($server['status'] == 2) { ?>
													<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
												<?php } elseif($server['status'] == 3) { ?>
													<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
												<?php } else { ?>
													<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
												<?php } ?>
											</td>
		                                    <td><?php echo $server['id']; ?></td>
		                                    <td><a href="?route=servers/manage-<?php echo $server['type']; ?>&id=<?php echo $server['id']; ?>"><?php echo $server['name']; ?></a></td>
											<td><?php echo getSingleValue("app_groups","name",$server['groupid']); ?></td>
											<?php if(!empty($latest)) { ?>

												<td>
													<?php
														$os = Server::extractData('os', $latest['data'], true);
														if(stripos($os, 'centos') !== false) { echo '<img src="template/images/centos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'cloudlinux') !== false) { echo '<img src="template/images/cloudlinux.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'coreos') !== false) { echo '<img src="template/images/coreos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'debian') !== false) { echo '<img src="template/images/debian.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'fedora') !== false) { echo '<img src="template/images/fedora.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'freebsd') !== false) { echo '<img src="template/images/freebsd.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'proxmox') !== false) { echo '<img src="template/images/proxmox.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'redhat') !== false) { echo '<img src="template/images/redhat.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'routeros') !== false) { echo '<img src="template/images/routeros.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'suse') !== false) { echo '<img src="template/images/suse.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'ubuntu') !== false) { echo '<img src="template/images/ubuntu.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														elseif(stripos($os, 'windows') !== false) { echo '<img src="template/images/windows.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
														else { echo '<img src="template/images/other.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
													?>
												</td>

												<?php $qstats = Server::quickStats($latest['data'], $server['type']); ?>

												<td>
													<span data-toggle="tooltip" title="<?php echo $qstats['cpuused']; ?><?php _e('% Used'); ?>">
														<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['cpuused']; ?>/100</span>
													</span>
												</td> <!-- cpu -->

												<td>
													<?php if($server['type'] == "linux") { ?>
														<span data-toggle="tooltip" data-html="true"
															title="<?php echo formatBytes($qstats['ramtotal']*1024); ?> <?php _e('Total'); ?> <br> <?php echo formatBytes($qstats['ramreal']*1024); ?> <?php _e('Used'); ?> <br> <?php echo formatBytes($qstats['ramfree']*1024); ?> <?php _e('Free'); ?> ">
															<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['ramreal']; ?>/<?php echo $qstats['ramtotal']; ?></span>
														</span>
													<?php } ?>

													<?php if($server['type'] == "windows") { ?>
														<span data-toggle="tooltip" data-html="true"
															title="<?php echo formatBytes($qstats['ramtotal']); ?> <?php _e('Total'); ?> <br> <?php echo formatBytes($qstats['ramreal']); ?> <?php _e('Used'); ?> <br> <?php echo formatBytes($qstats['ramfree']); ?> <?php _e('Free'); ?> ">
															<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['ramreal']; ?>/<?php echo $qstats['ramtotal']; ?></span>
														</span>
													<?php } ?>
												</td> <!-- ram -->

												<td>
													<span data-toggle="tooltip" title="<?php echo $qstats['totaldiskusedp']; ?><?php _e('% Used'); ?>">
														<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['totaldiskusedp']; ?>/100</span>
													</span>
												</td> <!-- disk -->

												<td>
													<?php if($server['type'] == "linux") { ?>
														<span data-toggle="tooltip" data-html="true" title="<?php echo $qstats['load1']; ?> <?php _e('1 Min'); ?> <br><?php echo $qstats['load5']; ?> <?php _e('5 Min'); ?> <br><?php echo $qstats['load15']; ?> <?php _e('15 Min'); ?> <br>">
															<?php echo $qstats['load1']; ?>
														</span>
													<?php } ?>
													<?php if($server['type'] == "windows") { ?>-<?php } ?>
												</td> <!-- load -->

												<td>
													<?php echo formatBytes($qstats['totalin']); ?><?php _e('/s'); ?> <i class="fa fa-long-arrow-down"></i><br>
													<?php echo formatBytes($qstats['totalout']); ?><?php _e('/s'); ?> <i class="fa fa-long-arrow-up"></i>
												</td>

												<td>
													<span data-toggle="tooltip" title="<?php _e('Last 24 Hours'); ?> <?php echo Server::uptimePercentage($server['id'],"24h"); ?>%">
														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"24h"); ?>/100</span>
													</span>

													<span data-toggle="tooltip" title="<?php _e('Last 7 Days'); ?> <?php echo Server::uptimePercentage($server['id'],"7days"); ?>%">
														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"7days"); ?>/100</span>
													</span>

													<span data-toggle="tooltip" title="<?php _e('Last 30 Days'); ?> <?php echo Server::uptimePercentage($server['id'],"30days"); ?>%">
														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"30days"); ?>/100</span>
													</span>
												</td>

												<td>
													<?php //echo Server::uptime(Server::extractData('uptime', $latest['data'], true)); ?>
													<?php echo smartDate($latest['timestamp']); ?></td>
												</td>


											<?php } ?>

											<?php if(empty($latest)) { ?>

												<td></td> <!-- os -->
												<td></td> <!-- cpu -->
												<td></td> <!-- ram -->
												<td></td> <!-- disk -->
												<td></td> <!-- load -->
												<td></td> <!-- net -->
												<td></td> <!-- uptime -->
												<td>
													<?php _e('-'); ?><br>
													<?php _e('Never Seen'); ?>
												</td> <!-- uptime -->

											<?php } ?>



											<td>
												<div class='pull-right'>
													<div class="btn-group">
														<a href="?route=servers/manage-<?php echo $server['type']; ?>&id=<?php echo $server['id']; ?>" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>
														 <?php if(in_array("editServer",$perms)) { ?><a href="#" onClick='showM("?modal=servers/edit&reroute=servers&routeid=&id=<?php echo $server['id']; ?>&section=");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
														 <?php if(in_array("deleteServer",$perms)) { ?><a href="#" onClick='showM("?modal=servers/delete&reroute=servers&routeid=&id=<?php echo $server['id']; ?>&section=");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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
