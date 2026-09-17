<!-- Right side column. Contains the navbar and content of the page -->
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php _e('Dashboard'); ?></h1>
		<ol class="breadcrumb"><li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li><li class="active"><?php _e('Dashboard'); ?></li></ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)):
			$statusicon = ['success' => 'fa-check', 'danger' => 'fa-times-circle', 'warning' => 'fa-exclamation-triangle', 'info' => 'fa-info-circle'];
		?>
				<div class="row"><div class='col-md-8'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><i class="fa <?php echo e($statusicon[$statusmessage["type"]] ?? 'fa-info-circle'); ?>"></i> <?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>

		<?php if(file_exists("install") == 1): ?>
			  <div class="row"><div class='col-md-8'><div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i> <b><?php _e('Plese delete the "install" directory!'); ?></b></div></div></div>
	    <?php endif; ?>

		<!-- KPI tiles -->
		<div class="kpi-grid">
			<a href="?route=servers" class="kpi-card kpi-blue">
				<div class="kpi-icon"><i class="fa fa-server"></i></div>
				<div>
					<span class="kpi-label"><?php _e('Servers'); ?></span>
					<span class="kpi-value"><?php echo $servers_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>

			<a href="?route=websites" class="kpi-card kpi-purple">
				<div class="kpi-icon"><i class="fa fa-globe"></i></div>
				<div>
					<span class="kpi-label"><?php _e('Websites'); ?></span>
					<span class="kpi-value"><?php echo $websites_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>

			<?php if(in_array("viewDomains",$perms)) { ?>
			<a href="?route=domains" class="kpi-card kpi-teal">
				<div class="kpi-icon"><i class="fa fa-id-card"></i></div>
				<div>
					<span class="kpi-label"><?php _e('Domains'); ?></span>
					<span class="kpi-value"><?php echo $domains_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>
			<?php } ?>

			<?php if(in_array("viewSsl",$perms)) { ?>
			<a href="?route=ssl" class="kpi-card kpi-maroon">
				<div class="kpi-icon"><i class="fa fa-lock"></i></div>
				<div>
					<span class="kpi-label"><?php _e('SSL Certificates'); ?></span>
					<span class="kpi-value"><?php echo $ssl_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>
			<?php } ?>

			<a href="?route=checks" class="kpi-card kpi-green">
				<div class="kpi-icon"><i class="fa fa-check-circle"></i></div>
				<div>
					<span class="kpi-label"><?php _e('Check Services'); ?></span>
					<span class="kpi-value"><?php echo $checks_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>

			<a href="?route=alerting/contacts" class="kpi-card kpi-yellow">
				<div class="kpi-icon"><i class="fa fa-users"></i></div>
				<div>
					<span class="kpi-label"><?php _e('Contacts'); ?></span>
					<span class="kpi-value"><?php echo $contacts_count; ?></span>
				</div>
				<span class="kpi-link"><?php _e('View all'); ?> <i class="fa fa-angle-right"></i></span>
			</a>
		</div>
		<!-- /.kpi-grid -->

		<!-- Overview charts -->
		<div class="row overview-row">
			<div class="col-md-4">
				<div class="box chart-card">
					<div class="box-header with-border">
						<h3 class="box-title"><i class="fa fa-pie-chart"></i> <?php _e('Status Overview'); ?></h3>
					</div>
					<div class="box-body">
						<div class="donut-wrap">
							<canvas id="statusOverviewChart"></canvas>
							<div class="donut-center">
								<span><?php echo $dashboard_total_up + $dashboard_total_down; ?></span>
								<small><?php _e('Total'); ?></small>
							</div>
						</div>
						<ul class="chart-legend-list">
							<li><span class="dot dot-green"></span><?php _e('Up'); ?><b><?php echo $dashboard_total_up; ?></b></li>
							<li><span class="dot dot-red"></span><?php _e('Down'); ?><b><?php echo $dashboard_total_down; ?></b></li>
						</ul>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="box chart-card">
					<div class="box-header with-border">
						<h3 class="box-title"><i class="fa fa-circle-o-notch"></i> <?php _e('Monitor Distribution'); ?></h3>
					</div>
					<div class="box-body">
						<div class="donut-wrap">
							<canvas id="assetDistributionChart"></canvas>
							<div class="donut-center">
								<span><?php echo array_sum(array_column($dashboard_distribution,'count')); ?></span>
								<small><?php _e('Monitors'); ?></small>
							</div>
						</div>
						<ul class="chart-legend-list">
							<?php foreach ($dashboard_distribution as $d) { ?>
								<li><span class="dot <?php echo e($d['dot']); ?>"></span><?php echo e($d['label']); ?><b><?php echo $d['count']; ?></b></li>
							<?php } ?>
						</ul>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="box chart-card">
					<div class="box-header with-border">
						<h3 class="box-title"><i class="fa fa-bar-chart"></i> <?php _e('Overall Health'); ?></h3>
					</div>
					<div class="box-body">
						<div class="health-chart-wrap">
							<canvas id="categoryHealthChart"></canvas>
						</div>
						<ul class="chart-legend-list">
							<li><span class="dot dot-green"></span><?php _e('Up'); ?></li>
							<li><span class="dot dot-red"></span><?php _e('Down'); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<!-- /.overview-row -->

		<div class="row dashboard-overview">
	<div class="col-md-8">


			<div class="col-md-4">
				<div class="box box-primary">
					<div class="box-header ">
						<h3 class="box-title"><i class="fa fa-server"></i> <?php _e('Servers Overview'); ?> <?php if(count($main_servers_unresolved) > 0) { ?><span class="badge-count badge-count-alert"><?php echo count($main_servers_unresolved); ?></span><?php } else { ?><span class="badge-count badge-count-ok"><?php _e('OK'); ?></span><?php } ?></h3>
						<div class="pull-right box-tools">
							<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
						</div>
					</div>

					<div class="box-body">
						<?php if($main_servers_unresolved) { ?>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Server'); ?></th>
											<th><?php _e('Incident'); ?></th>
											<th><?php _e('Start Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($main_servers_unresolved as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-<?php echo e(getSingleValue("app_servers","type",$incident['serverid'])); ?>&routeid=<?php echo $incident['serverid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage&routeid=<?php echo $incident['serverid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
													<?php } else { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage&routeid=<?php echo $incident['serverid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i><?php } ?>
													<?php } ?>
												</td>
												<td><?php echo e(getSingleValue("app_servers","name",$incident['serverid'])); ?></td>
												<td>
													<?php if($incident['type'] == "nodata") _e('No Data'); ?>
													<?php if($incident['type'] == "cpu") _e('CPU Usage %'); ?>
													<?php if($incident['type'] == "cpuio") _e('CPU IO Wait %'); ?>
													<?php if($incident['type'] == "load1min") _e('System Load 1 Min'); ?>
													<?php if($incident['type'] == "load5min") _e('System Load 5 Min'); ?>
													<?php if($incident['type'] == "load15min") _e('System Load 15 Min'); ?>
													<?php if($incident['type'] == "service") _e('Service/Process Not Running'); ?>

													<?php if($incident['type'] == "ram") _e('RAM Usage %'); ?>
													<?php if($incident['type'] == "ramMB") _e('RAM Usage MB'); ?>
													<?php if($incident['type'] == "swap") _e('Swap Usage %'); ?>
													<?php if($incident['type'] == "swapMB") _e('Swap Usage MB'); ?>
													<?php if($incident['type'] == "disk") _e('Disk Usage % (Aggregated)'); ?>
													<?php if($incident['type'] == "diskGB") _e('Disk Usage GB (Aggregated)'); ?>
													<?php
														if(strpos($incident['type'],'disk:') !== false) {
															$disk_text = explode(":",$incident['type'],2);
															_e('Disk Usage %:'); echo " " . $disk_text[1];
														}
													?>

													<?php
														if(strpos($incident['type'],'diskGB:') !== false) {
															$disk_text = explode(":",$incident['type'],2);
															_e('Disk Usage GB:'); echo " " . $disk_text[1];
														}
													?>

                                                    <?php if($incident['type'] == "mdadmDegraded") _e('MDADM Degraded'); ?>

													<?php if($incident['type'] == "connections") _e('Connections'); ?>
													<?php if($incident['type'] == "ssh") _e('SSH Sessions'); ?>
													<?php if($incident['type'] == "ping") _e('Ping Latency'); ?>
													<?php if($incident['type'] == "netdl") _e('Network Download Speed MB/s'); ?>
													<?php if($incident['type'] == "netup") _e('Network Upload Speed MB/s'); ?>

													<?php if($incident['type'] == "nodata") { ?>

													<?php } elseif($incident['type'] == "service") { ?>
														<b><?php echo $incident['comparison_limit']; ?></b>

													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } else { ?>
							<div class="callout callout-ok">
								<p class="lead"><i class="icon fa fa-check text-green"></i> <?php _e("Hooray! All servers are healthy.") ?></p>
							</div>
						<?php } ?>
					</div>
				</div>


				<div class="box box-primary">
					<div class="box-header ">
						<h3 class="box-title"><i class="fa fa-globe"></i> <?php _e('Websites Overview'); ?> <?php if(count($main_websites_unresolved) > 0) { ?><span class="badge-count badge-count-alert"><?php echo count($main_websites_unresolved); ?></span><?php } else { ?><span class="badge-count badge-count-ok"><?php _e('OK'); ?></span><?php } ?></h3>
						<div class="pull-right box-tools">
							<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
						</div>
					</div>

					<div class="box-body">
						<?php if($main_websites_unresolved) { ?>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Website'); ?></th>
											<th><?php _e('Incident'); ?></th>
											<th><?php _e('Start Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($main_websites_unresolved as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editWebsite",$perms)) { ?>
															<a href="#" onClick='showM("?modal=websitealerts/markResolved&reroute=websites/manage&routeid=<?php echo $incident['websiteid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editWebsite",$perms)) { ?>
															<a href="#" onClick='showM("?modal=websitealerts/markResolved&reroute=websites/manage&routeid=<?php echo $incident['websiteid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
													<?php } else { ?>
														<?php if(in_array("editWebsite",$perms)) { ?>
															<a href="#" onClick='showM("?modal=websitealerts/markResolved&reroute=websites/manage&routeid=<?php echo $incident['websiteid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i><?php } ?>
													<?php } ?>
												</td>
												<td><?php echo e(getSingleValue("app_websites","name",$incident['websiteid'])); ?></td>
												<td>
													<?php if($incident['type'] == "responsecode") _e('HTTP Response Code'); ?>
													<?php if($incident['type'] == "loadtime") _e('Load Time'); ?>
													<?php if($incident['type'] == "searchstringmissing") _e('Search String Missing'); ?>

													<?php if($incident['type'] == "searchstringmissing") { ?>

													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } else { ?>
							<div class="callout callout-ok">
								<p class="lead"><i class="icon fa fa-check text-green"></i> <?php _e("Hooray! All websites are healthy.") ?></p>
							</div>
						<?php } ?>
					</div>
				</div>


				<div class="box box-primary">
					<div class="box-header ">
						<h3 class="box-title"><i class="fa fa-check-circle"></i> <?php _e('Check Services Overview'); ?> <?php if(count($main_checks_unresolved) > 0) { ?><span class="badge-count badge-count-alert"><?php echo count($main_checks_unresolved); ?></span><?php } else { ?><span class="badge-count badge-count-ok"><?php _e('OK'); ?></span><?php } ?></h3>
						<div class="pull-right box-tools">
							<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
						</div>
					</div>

					<div class="box-body">
						<?php if($main_checks_unresolved) { ?>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Check Service'); ?></th>
											<th><?php _e('Incident'); ?></th>
											<th><?php _e('Start Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($main_checks_unresolved as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $incident['incidentid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $incident['incidentid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
													<?php } else { ?>
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $incident['incidentid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i><?php } ?>
													<?php } ?>
												</td>
												<td><?php echo e(getSingleValue("app_checks","name",$incident['checkid'])); ?></td>
												<td>
													<?php if($incident['type'] == "offline") _e('Service Offline'); ?>
													<?php if($incident['type'] == "responsetime") _e('Response Time'); ?>
													<?php if($incident['type'] == "blacklisted") _e('Listed In Blacklist'); ?>
													<?php if($incident['type'] == "dnsfailed") _e('DNS Lookup Failed'); ?>
													<?php if($incident['type'] == "unsuccessful") _e('Unsuccessful'); ?>
													<?php if($incident['type'] == "offline" || $incident['type'] == "blacklisted" || $incident['type'] == "dnsfailed" || $incident['type'] == "unsuccessful") { ?>
													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } else { ?>
							<div class="callout callout-ok">
								<p class="lead"><i class="icon fa fa-check text-green"></i> <?php _e("Hooray! All checks are healthy.") ?></p>
							</div>
						<?php } ?>
					</div>
				</div>


			</div>


			<div class="col-md-4">

				<?php if(in_array("viewDomains",$perms)) { ?>
				<div class="box box-primary">
					<div class="box-header ">
						<h3 class="box-title"><i class="fa fa-id-card"></i> <?php _e('Domains Overview'); ?> <?php if(count($main_domains_unresolved) > 0) { ?><span class="badge-count badge-count-alert"><?php echo count($main_domains_unresolved); ?></span><?php } else { ?><span class="badge-count badge-count-ok"><?php _e('OK'); ?></span><?php } ?></h3>
						<div class="pull-right box-tools">
							<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
						</div>
					</div>

					<div class="box-body">
						<?php if($main_domains_unresolved) { ?>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Domain'); ?></th>
											<th><?php _e('Incident'); ?></th>
											<th><?php _e('Start Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($main_domains_unresolved as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 2) { ?>
														<?php if(in_array("editDomain",$perms)) { ?>
															<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $incident['domainid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editDomain",$perms)) { ?>
															<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $incident['domainid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
													<?php } ?>
												</td>
												<td><?php echo e(getSingleValue("app_domains","name",$incident['domainid'])); ?></td>
												<td>
													<?php if($incident['type'] == "expiringsoon") _e('Expiring Soon'); ?>
													<?php if($incident['type'] == "expired") _e('Already Expired'); ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } else { ?>
							<div class="callout callout-ok">
								<p class="lead"><i class="icon fa fa-check text-green"></i> <?php _e("Hooray! All domains are healthy.") ?></p>
							</div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

			</div>


			<div class="col-md-4">

				<?php if(in_array("viewSsl",$perms)) { ?>
				<div class="box box-primary">
					<div class="box-header ">
						<h3 class="box-title"><i class="fa fa-lock"></i> <?php _e('SSL Certificates Overview'); ?> <?php if(count($main_ssl_unresolved) > 0) { ?><span class="badge-count badge-count-alert"><?php echo count($main_ssl_unresolved); ?></span><?php } else { ?><span class="badge-count badge-count-ok"><?php _e('OK'); ?></span><?php } ?></h3>
						<div class="pull-right box-tools">
							<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
						</div>
					</div>

					<div class="box-body">
						<?php if($main_ssl_unresolved) { ?>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('SSL Check'); ?></th>
											<th><?php _e('Incident'); ?></th>
											<th><?php _e('Start Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($main_ssl_unresolved as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 2) { ?>
														<?php if(in_array("editSsl",$perms)) { ?>
															<a href="#" onClick='showM("?modal=sslalerts/markResolved&reroute=ssl/manage&routeid=<?php echo $incident['sslid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editSsl",$perms)) { ?>
															<a href="#" onClick='showM("?modal=sslalerts/markResolved&reroute=ssl/manage&routeid=<?php echo $incident['sslid']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
													<?php } ?>
												</td>
												<td><?php echo e(getSingleValue("app_ssl","name",$incident['sslid'])); ?></td>
												<td>
													<?php if($incident['type'] == "expiringsoon") _e('Expiring Soon'); ?>
													<?php if($incident['type'] == "expired") _e('Already Expired'); ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						<?php } else { ?>
							<div class="callout callout-ok">
								<p class="lead"><i class="icon fa fa-check text-green"></i> <?php _e("Hooray! All SSL certificates are healthy.") ?></p>
							</div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

			</div>

		</div>
		</div><!-- /.row.dashboard-overview -->



	</section><!-- /.content -->
</aside><!-- /.right-side -->

<?php if(!$isGoogleMaps) { ?>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {

			/* jVector Maps
		     * ------------
		     * Create a world map with markers
		     */
		    $('#world-map-markers').vectorMap({
		      	map              : 'world_mill_en',
		      	normalizeFunction: 'polynomial',
		      	hoverOpacity     : 0.7,
		      	hoverColor       : false,
		      	backgroundColor  : 'transparent',
		      	regionStyle      : {
			        initial      : {
			          	fill            : 'rgba(210, 214, 222, 1)',
			          	'fill-opacity'  : 1,
			          	stroke          : 'none',
			          	'stroke-width'  : 0,
			          	'stroke-opacity': 1
			        },
			        hover        : {
			          	'fill-opacity': 0.7,
			          	cursor        : 'pointer'
			        },
			        selected     : {
			          	fill: 'yellow'
			        },
			        selectedHover: {}
		        },
		      	markerStyle      : {
		        	initial: {
		          	fill  : '#00a65a',
		          	stroke: '#111'
		        	}
		      	},
				markers          : [
					<?php foreach($checks as $check) {
						$hasGeodata = false;
						if($check['lat'] != "" && $check['lng'] != "") $hasGeodata = true;

						if($hasGeodata) {

							$lat = $check['lat'];
							$lng = $check['lng'];

							if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
							if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

							if($check['status'] == 0) $fill = "#CCCCCC";
							if($check['status'] == 1) $fill = "#00a65a";
							if($check['status'] == 2) $fill = "#f39c12";
							if($check['status'] == 3) $fill = "#dd4b39";

							echo "{ latLng: [".$lat.", ".$lng."], name: '".__("Service: ") . $check['name']."', style: {fill: '".$fill."'} },";
						}

					}
					?>
					<?php foreach($servers as $server) {
						$hasGeodata = false;
						if($server['lat'] != "" && $server['lng'] != "") $hasGeodata = true;

						if($hasGeodata) {

							$lat = $server['lat'];
							$lng = $server['lng'];

							if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
							if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

							if($server['status'] == 0) $fill = "#CCCCCC";
							if($server['status'] == 1) $fill = "#00a65a";
							if($server['status'] == 2) $fill = "#f39c12";
							if($server['status'] == 3) $fill = "#dd4b39";

							echo "{ latLng: [".$lat.", ".$lng."], name: '".__("Server: ") . $server['name']."', style: {fill: '".$fill."'} },";
						}


					}
					?>
					<?php foreach($websites as $website) {
						$hasGeodata = false;
						if($website['lat'] != "" && $website['lng'] != "") $hasGeodata = true;

						if($hasGeodata) {

							$lat = $website['lat'];
							$lng = $website['lng'];

							if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
							if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

							if($website['status'] == 0) $fill = "#CCCCCC";
							if($website['status'] == 1) $fill = "#00a65a";
							if($website['status'] == 2) $fill = "#f39c12";
							if($website['status'] == 3) $fill = "#dd4b39";

							echo "{ latLng: [".$lat.", ".$lng."], name: '".__("Website: ") . $website['name']."', style: {fill: '".$fill."'} },";
						}


					}
					?>

				]
			});

		});
	</script>
<?php } ?>

<?php if($isGoogleMaps) { ?>
	<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo getConfigValue("google_maps_api_key"); ?>"></script>
	<script type="text/javascript">
		var mlocations = [
			<?php foreach($checks as $check) {
				$hasGeodata = false;
				if($check['lat'] != "" && $check['lng'] != "") $hasGeodata = true;

				if($hasGeodata) {

					$lat = $check['lat'];
					$lng = $check['lng'];

					if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
					if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

					if($check['status'] == 0) $icon = "check-gray";
					if($check['status'] == 1) $icon = "check-green";
					if($check['status'] == 2) $icon = "check-orange";
					if($check['status'] == 3) $icon = "check-red";

					echo "['".__("Service: ") . $check['name']."', ".$lat.",".$lng.", 2, '".$icon."', '".__("Service: ") . $check['name']."'],";
				}

			}
			?>

			<?php foreach($servers as $server) {
				$hasGeodata = false;
				if($server['lat'] != "" && $server['lng'] != "") $hasGeodata = true;

				if($hasGeodata) {

					$lat = $server['lat'];
					$lng = $server['lng'];

					if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
					if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

					if($server['status'] == 0) $icon = "server-gray";
					if($server['status'] == 1) $icon = "server-green";
					if($server['status'] == 2) $icon = "server-orange";
					if($server['status'] == 3) $icon = "server-red";

					echo "['".__("Server: ") . $server['name']."', ".$lat.",".$lng.", 2, '".$icon."', '".__("Server: ") . $server['name']."'],";
				}


			}
			?>
			<?php foreach($websites as $website) {
				$hasGeodata = false;
				if($website['lat'] != "" && $website['lng'] != "") $hasGeodata = true;

				if($hasGeodata) {

					$lat = $website['lat'];
					$lng = $website['lng'];

					if(!is_decimal($lat)) $lat = $lat . "." . rand(1000,9999);
					if(!is_decimal($lng)) $lng = $lng . "." . rand(1000,9999);

					if($website['status'] == 0) $icon = "website-gray";
					if($website['status'] == 1) $icon = "website-green";
					if($website['status'] == 2) $icon = "website-orange";
					if($website['status'] == 3) $icon = "website-red";

					echo "['".__("Website: ") . $website['name']."', ".$lat.",".$lng.", 2, '".$icon."', '".__("Website: ") . $website['name']."'],";
					//echo "{ latLng: [".$lat.", ".$lng."], name: '".__("Website: ") . $website['name']."', style: {fill: '".$fill."'} },";
				}


			}
			?>
		];

		var mapCenter = new google.maps.LatLng(0, 0);
		var bounds = new google.maps.LatLngBounds();

		function initialize() {
			var mapData = {
				center:mapCenter,
				zoom:2,
				mapTypeId:google.maps.MapTypeId.ROADMAP
			};

			var map = new google.maps.Map(document.getElementById("googleMap"),mapData);

			for (i = 0; i < mlocations.length; i++) {
				var infowindow = new google.maps.InfoWindow({
					content: mlocations[i][5]
				});

				var marker = new google.maps.Marker({
					position: new google.maps.LatLng(mlocations[i][1], mlocations[i][2]),
					map: map,
					title: mlocations[i][0],
					icon:'template/images/'+mlocations[i][4]+'.png',
					infowindow: infowindow
				});
				bounds.extend(marker.position);

				marker.addListener('click', function() {
					this.infowindow.open(map, this);
				});


				marker.setMap(map);
			}

			map.fitBounds(bounds);

		}

		google.maps.event.addDomListener(window, 'load', initialize);



	</script>
<?php } ?>

<script type="text/javascript">
	$(function() {
		var statusData = {
			up: <?php echo (int) $dashboard_total_up; ?>,
			down: <?php echo (int) $dashboard_total_down; ?>
		};

		var distribution = <?php echo json_encode($dashboard_distribution, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
		var health = <?php echo json_encode($dashboard_health, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

		var gridColor = 'rgba(255,255,255,.06)';
		var tickColor = '#8b93a7';

		Chart.defaults.global.defaultFontColor = tickColor;
		Chart.defaults.global.defaultFontFamily = "'Source Sans Pro', sans-serif";

		if (statusData.up + statusData.down > 0) {
			new Chart(document.getElementById('statusOverviewChart'), {
				type: 'doughnut',
				data: {
					labels: [<?php echo json_encode(__('Up')); ?>, <?php echo json_encode(__('Down')); ?>],
					datasets: [{
						data: [statusData.up, statusData.down],
						backgroundColor: ['#17c98d', '#f0576a'],
						borderWidth: 0
					}]
				},
				options: {
					cutoutPercentage: 68,
					legend: { display: false },
					tooltips: { enabled: true }
				}
			});
		}

		if (distribution.length) {
			new Chart(document.getElementById('assetDistributionChart'), {
				type: 'doughnut',
				data: {
					labels: distribution.map(function(d) { return d.label; }),
					datasets: [{
						data: distribution.map(function(d) { return d.count; }),
						backgroundColor: distribution.map(function(d) { return d.color; }),
						borderWidth: 0
					}]
				},
				options: {
					cutoutPercentage: 68,
					legend: { display: false },
					tooltips: { enabled: true }
				}
			});
		}

		if (health.length) {
			new Chart(document.getElementById('categoryHealthChart'), {
				type: 'bar',
				data: {
					labels: health.map(function(h) { return h.label; }),
					datasets: [
						{
							label: <?php echo json_encode(__('Up')); ?>,
							data: health.map(function(h) { return h.up; }),
							backgroundColor: '#17c98d',
							borderRadius: 4,
							maxBarThickness: 22
						},
						{
							label: <?php echo json_encode(__('Down')); ?>,
							data: health.map(function(h) { return h.down; }),
							backgroundColor: '#f0576a',
							borderRadius: 4,
							maxBarThickness: 22
						}
					]
				},
				options: {
					legend: { display: false },
					scales: {
						xAxes: [{ gridLines: { color: gridColor, drawBorder: false }, ticks: { fontColor: tickColor } }],
						yAxes: [{ gridLines: { color: gridColor, drawBorder: false }, ticks: { fontColor: tickColor, beginAtZero: true, precision: 0 } }]
					}
				}
			});
		}
	});
</script>
