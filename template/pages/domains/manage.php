<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo e($domain['name']); ?><small> <?php echo smartDate($latest['timestamp']); ?></small></h1>
		<ol class="breadcrumb">
            <li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li>
            <li><a href="?route=domains"><?php _e('Domains'); ?></a></li>
            <li class="active"><?php echo e($domain['name']); ?></li>
        </ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
	    <div class='row'>
            <div class='col-md-12'>
                <!-- Custom Tabs -->

                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li <?php if ($section == "" or $section == "overview") echo 'class="active"'; ?> ><a href="?route=domains/manage&id=<?php echo $domain['id']; ?>&section=" ><?php _e('Overview'); ?></a></li>
                        <li <?php if ($section == "alerting") echo 'class="active"'; ?> ><a href="?route=domains/manage&id=<?php echo $domain['id']; ?>&section=alerting"><?php _e('Alerting'); ?></a></li>
						<li <?php if ($section == "incidents") echo 'class="active"'; ?> ><a href="?route=domains/manage&id=<?php echo $domain['id']; ?>&section=incidents"><?php _e('Incidents'); ?></a></li>

						<div class="btn-group pull-right" style="padding:6px;">
							<?php if ($section == "alerting") { ?>
								<a data-toggle='tooltip' title='Add Alert' class="btn btn-primary btn-flat btn-sm " href="#" onClick='showM("?modal=domainalerts/add&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>");return false'><i class="fa fa-plus"></i> ADD ALERT</a>
							<?php } ?>
						</div>


                    </ul>
                    <div class="tab-content">

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "") echo 'active'; ?>" id="overview">

							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available yet. The next scheduled check will populate this domain\'s WHOIS expiry information.'); ?>
								</div>
							<?php } else { ?>
	                            <div class='row'>

	                                <div class='col-md-8'>

										<div class='row'>
											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-performance-chart" style="height:280px"></canvas>
												</div>
											</div>
										</div>

										<div class="spacer"></div>

										<div class="box box-primary">
											<div class="box-header">
												<h3 class="box-title"><?php _e('Detailed Log'); ?> <small><?php _e('Last 100 Checks'); ?></small></h3>
												<div class="pull-right box-tools">
													<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
												</div>
											</div>

											<div class="box-body">
												<div class="table-responsive">
													<table id="dataTablesFullNoOrder2" class="table table-striped table-hover table-bordered">
														<thead>
															<tr>
																<th><?php _e('Date'); ?></th>
																<th><?php _e('Expiry Date'); ?></th>
																<th><?php _e('Days Remaining'); ?></th>
																<th><?php _e('Registrar'); ?></th>
															</tr>
														</thead>
														<tbody>
															<?php foreach ($detailed_log as $log) {  ?>
																<tr>
																	<td><?php echo dateTimeDisplay($log['timestamp']); ?> <small><?php echo smartDate($log['timestamp']); ?></small></td>
																	<td><?php echo $log['expiry_date']; ?></td>
																	<td>
																		<?php if((int)$log['days_remaining'] <= 0) { ?><span class="label label-danger"><?php echo $log['days_remaining']; ?></span>
																		<?php } elseif((int)$log['days_remaining'] <= 30) { ?><span class="label label-warning"><?php echo $log['days_remaining']; ?></span>
																		<?php } else { ?><span class="label label-success"><?php echo $log['days_remaining']; ?></span><?php } ?>
																	</td>
																	<td><?php echo $log['registrar']; ?></td>
																</tr>
															<?php } ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>

	                                </div>

	                                <div class='col-md-4'>

										<?php if(!empty($unresolved_incidents)) { ?>
											<div class="box box-<?php echo $unresolved_status; ?> box-solid">
												<div class="box-header with-border">
													<h3 class="box-title"><?php _e('Incidents'); ?></h3>
													<div class="pull-right box-tools">
														<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
													</div>
												</div>

												<div class="box-body">
													<div class="table-responsive">
														<table class="table table-striped table-hover table-bordered">
															<thead>
																<tr>
																	<th class="no-sort" style="width:1%"></th>
																	<th><?php _e('Incident'); ?></th>
																	<th><?php _e('Start Time'); ?></th>
																</tr>
															</thead>
															<tbody>
																<?php foreach ($unresolved_incidents as $incident) { ?>
																	<tr>
																		<td>
																			<?php if($incident['status'] == 1) { ?>
																				<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
																			<?php } elseif($incident['status'] == 2) { ?>
																				<?php if(in_array("editDomain",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
																			<?php } elseif($incident['status'] == 3) { ?>
																				<?php if(in_array("editDomain",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
																			<?php } else { ?>
																				<i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
																			<?php } ?>
																		</td>
																		<td>
																			<?php if($incident['type'] == "expiringsoon") _e('Expiring Soon'); ?>
																			<?php if($incident['type'] == "expired") _e('Expired'); ?>
																		</td>
																		<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
																	</tr>
																<?php } ?>
															</tbody>
														</table>
													</div>
												</div>
											</div>
										<?php } ?>


	                                    <div class="box box-primary">
	            							<div class="box-header">
	            								<h3 class="box-title"><?php _e('Domain Info'); ?></h3>
	            								<div class="pull-right box-tools">
	            									<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	            								</div>
	            							</div>

	            							<div class="box-body">
	            								<table id="websiteInfoTable" class="table table-striped table-hover">
	            									<tbody>

	            										<tr>
	            											<td><b><?php _e('Name'); ?></b></td>
	            											<td><?php echo e($domain['name']); ?></td>
	            										</tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Domain'); ?></b></td>
	                                                        <td><?php echo $domain['domain']; ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Registrar'); ?></b></td>
	                                                        <td><?php echo $latest['registrar']; ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Expiry Date'); ?></b></td>
	                                                        <td><?php echo $latest['expiry_date']; ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Days Remaining'); ?></b></td>
	                                                        <td><?php echo $latest['days_remaining']; ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Last Checked'); ?></b></td>
	                                                        <td><?php echo smartDate($latest['timestamp']); ?></td>
	                                                    </tr>

	            									</tbody>
	            								</table>
	            							</div>
	            						</div>


	                                </div>

	                            </div>

							<?php } ?>

                        </div>
                        <!-- /.tab-pane -->


                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "alerting") echo 'active'; ?>" id="alerting">
							<div class="table-responsive">
								<table id="dataTablesFullNoOrder2" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Comparison'); ?></th>
											<th><?php _e('Action'); ?></th>
											<th><?php _e('Status'); ?></th>
											<th class="text-right"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($alerts as $alert) { $selected_contacts = unserialize($alert['contacts']); if(empty($selected_contacts)) $selected_contacts = []; ?>
											<tr>
												<td>
													<?php if($alert['type'] == "expiringsoon") _e('Expiring Soon'); ?>
													<?php if($alert['type'] == "expired") _e('Already Expired'); ?>
												</td>

												<td><?php echo $alert['comparison']; ?> <?php echo $alert['comparison_limit']; ?> <?php _e('days'); ?></td>

												<td><?php _e('If occurs'); ?> <?php echo $alert['occurrences']; ?> <?php _e('times'); ?>, <?php _e('alert:'); ?>
													<?php foreach ($selected_contacts as $selected_contact) { ?>
														<span class="label bg-gray"><?php echo e(getSingleValue("app_contacts", "name", $selected_contact)); ?></span>&nbsp;
													<?php } ?>
												</td>

												<td>
													<?php if($alert['status'] == 1) { ?>
														<span class="label label-success"><?php _e("Active"); ?></span>
													<?php } ?>
													<?php if($alert['status'] == 0) { ?>
														<span class="label label-default"><?php _e("Inactive"); ?></span>
													<?php } ?>
												</td>
												<td>
													<div class='pull-right'>
														<div class="btn-group">
															 <?php if(in_array("editDomain",$perms)) { ?><a href="#" onClick='showM("?modal=domainalerts/edit&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
															 <?php if(in_array("editDomain",$perms)) { ?><a href="#" onClick='showM("?modal=domainalerts/delete&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
														</div>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>

                        </div>
                        <!-- /.tab-pane -->

						<!-- tab-pane -->
						<div class="tab-pane <?php if ($section == "incidents") echo 'active'; ?>" id="incidents">

							<div class="table-responsive">
								<table id="dataTablesFullNoOrder3" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Comparison'); ?></th>
											<th><?php _e('Start Time'); ?></th>
											<th><?php _e('End Time'); ?></th>
                                            <th><?php _e('Comment'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($incidents as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editDomain",$perms)) { ?>
															<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editDomain",$perms)) { ?>
															<a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } ?>
													<?php } else { ?>
														<i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
													<?php } ?>
												</td>
												<td>
													<?php if($incident['type'] == "expiringsoon") _e('Expiring Soon'); ?>
													<?php if($incident['type'] == "expired") _e('Already Expired'); ?>
												</td>

												<td><?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?> <?php _e('days'); ?></td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
												<td>
													<?php if($incident['end_time'] != "0000-00-00 00:00:00") echo dateTimeDisplay($incident['end_time']); else { ?> <a href="#" onClick='showM("?modal=domainalerts/markResolved&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php _e("Mark Resolved"); ?></a>  <?php } ?>
												</td>

                                                <td>
													<?php  echo $incident['comment']; ?> <?php if($incident['ignore'] == '1') { ?> [<?php _e('IGNORED'); ?>]<?php } ?> <a href="#" onClick='showM("?modal=domainalerts/editComment&reroute=domains/manage&routeid=<?php echo $domain['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php if($incident['comment'] == "") _e("Add"); else _e("Edit"); ?></a>

                                                </td>

											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>


						</div>
						<!-- /.tab-pane -->


                    </div><!-- /.tab-content -->
                </div><!-- nav-tabs-custom -->
            </div><!-- /.col-->
        </div><!-- ./row -->


	</section><!-- /.content -->
</aside><!-- /.right-side -->


<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<script type="text/javascript">
	$(document).ready(function() {

		var color1 = '#3e95cd';

		<?php if ($section == "" or $section == "overview") { ?>

			new Chart(document.getElementById("cjs-ov-performance-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['performance'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [{
					    data: [<?php foreach($charts['performance'] as $item) echo "'".$item['latency']."',"; ?>],
						label: '<?php _e('Days Remaining'); ?>',
					    borderColor: color1,
						backgroundColor: color1,
					    fill: false,
						pointRadius: 0,
                        pointHoverRadius: 4,
					  }
					]
				},
				options: {
					title: { display: true, text: '<?php _e('Days Until Expiry'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' <?php _e('days'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('days'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});

		<?php } ?>


	});
</script>
