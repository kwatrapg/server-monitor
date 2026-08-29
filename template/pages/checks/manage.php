<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo $check['name']; ?><small> <?php echo smartDate($latest['timestamp']); ?></small></h1>
		<ol class="breadcrumb">
            <li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li>
            <li><a href="?route=checks"><?php _e('Check Services'); ?></a></li>
            <li class="active"><?php echo $check['name']; ?></li>
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
                        <li <?php if ($section == "" or $section == "overview") echo 'class="active"'; ?> ><a href="?route=checks/manage&id=<?php echo $check['id']; ?>&section=" ><?php _e('Overview'); ?></a></li>
                        <li <?php if ($section == "alerting") echo 'class="active"'; ?> ><a href="?route=checks/manage&id=<?php echo $check['id']; ?>&section=alerting"><?php _e('Alerting'); ?></a></li>
						<li <?php if ($section == "incidents") echo 'class="active"'; ?> ><a href="?route=checks/manage&id=<?php echo $check['id']; ?>&section=incidents"><?php _e('Incidents'); ?></a></li>

						<div class="btn-group pull-right" style="padding:6px;">
							<?php if ($section == "alerting") { ?>
								<a data-toggle='tooltip' title='Add Alert' class="btn btn-primary btn-flat btn-sm " href="#" onClick='showM("?modal=checkalerts/add&reroute=checks/manage&routeid=<?php echo $check['id']; ?>");return false'><i class="fa fa-plus"></i> ADD ALERT</a>
							<?php } ?>

							<button type="button" class="btn btn-default btn-flat btn-sm  pull-right" id="daterange-btn">
								<i class="fa fa-calendar fa-fw"></i> <span><?php _e('Date Range'); ?></span> <i class="fa fa-caret-down fa-fw"></i>
							</button>
							<form role="form" method="post" enctype="multipart/form-data" id="rangeForm">
								<input type="hidden" name="action" value="setRange">

								<input type="hidden" name="range_start" id="range_start" value="">
								<input type="hidden" name="range_end" id="range_end" value="">
								<input type="hidden" name="range_label" id="range_label" value="">

								<input type="hidden" name="asset" value="check-<?php echo $_GET['id']; ?>">

								<input type="hidden" name="route" value="<?php echo $_GET['route']; ?>">
								<input type="hidden" name="routeid" value="<?php echo $_GET['id']; ?>">
								<input type="hidden" name="section" value="<?php if(!empty($_GET['section'])) echo $_GET['section']; ?>">
							</form>

						</div>


                    </ul>
                    <div class="tab-content">

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "") echo 'active'; ?>" id="overview">

							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been collected yet.'); ?>
								</div>


								<?php if($check['type'] == "callback") { ?>
									<h4>Successful URL</h4>
									<pre><?php echo baseURL(); ?>callback.php?key=<?php echo $check['host']; ?>&status=success</pre>

									<br>
									<h4>Unsuccessful URL</h4>
									<pre><?php echo baseURL(); ?>callback.php?key=<?php echo $check['host']; ?>&status=failure</pre>

								<?php } ?>


							<?php } else { ?>
	                            <div class='row'>

	                                <div class='col-md-8'>



										<?php if($check['type'] == "tcp" or $check['type'] == "udp" or $check['type'] == "icmp" or $check['type'] == "dns") { ?>
											<div class='row'>
												<div class='col-md-12'>
													<div class="chart">
														<canvas id="cjs-ov-performance-chart" style="height:280px"></canvas>
													</div>
												</div>
											</div>

											<div class="spacer"></div>
										<?php } ?>

										<?php if($check['type'] == "blacklist") { ?>


											<div class="box box-primary">
												<div class="box-header">
													<h3 class="box-title"><?php _e('Blacklists'); ?></h3>
													<div class="pull-right box-tools">
														<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
													</div>
												</div>

												<div class="box-body">
													<div class="table-responsive">
														<table class="table table-striped table-hover table-bordered">
															<thead>
																<tr>
																	<th><?php _e('Blacklist'); ?></th>
																	<th><?php _e('Status'); ?></th>

																</tr>
															</thead>
															<tbody>
																<?php foreach ($blacklists as $blacklist) {  ?>
																	<tr>
																		<td><?php echo $blacklist['host']; ?></td>
																		<td>
																			<?php if(in_array($blacklist['host'], $listedin)) { ?>
																				<span class="label label-danger"><?php _e("LISTED"); ?></span>
																			<?php } else { ?>
																				<span class="label label-success"><?php _e("OK"); ?></span>
																			<?php } ?>

																		</td>
																	</tr>
																<?php } ?>
															</tbody>
														</table>
													</div>

												</div>
											</div>

											<div class="spacer"></div>
										<?php } ?>




										<div class="box box-primary">
											<div class="box-header">
												<h3 class="box-title"><?php _e('Detailed Log'); ?> <small><?php _e('Last 100 Check Services'); ?></small></h3>
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
																<?php if($check['type'] == "tcp" or $check['type'] == "udp" or $check['type'] == "icmp" or $check['type'] == "dns") { ?>
																	<th><?php _e('Load Time'); ?></th>
																<?php } ?>
																<th><?php _e('Status'); ?></th>

															</tr>
														</thead>
														<tbody>
															<?php foreach ($detailed_log as $log) {  ?>
																<tr>
																	<td><?php echo dateTimeDisplay($log['timestamp']); ?> <small><?php echo smartDate($log['timestamp']); ?></small></td>
																	<?php if($check['type'] == "tcp" or $check['type'] == "udp" or $check['type'] == "icmp" or $check['type'] == "dns") { ?>
																		<td><?php echo $log['latency'].$latencyunit; ?></td>
																	<?php } ?>
																	<td>
																		<?php if($check['type'] == "dns") { ?>
																			<?php if($log['statuscode'] == 1) { ?><span class="label label-success"><?php _e("OK"); ?></span><?php } ?>
																			<?php if($log['statuscode'] == 0) { ?><span class="label label-danger"><?php _e("Failed"); ?></span><?php } ?>
																		<?php } elseif($check['type'] == "blacklist") { ?>
																			<?php if(count(unserialize($log['statuscode'])) == 0) { ?><span class="label label-success"><?php _e("OK"); ?></span><?php } ?>
																			<?php if(count(unserialize($log['statuscode'])) > 0) { ?><span class="label label-danger"><?php _e("Blacklisted"); ?></span><?php } ?>
																		<?php } elseif($check['type'] == "callback") { ?>
																			<?php if($log['statuscode'] == 1) { ?><span class="label label-success"><?php _e("Success"); ?></span><?php } ?>
																			<?php if($log['statuscode'] == 0) { ?><span class="label label-danger"><?php _e("Failed"); ?></span><?php } ?>
																		<?php } else { ?>
																			<?php if($log['statuscode'] == 1) { ?><span class="label label-success"><?php _e("Online"); ?></span><?php } ?>
																			<?php if($log['statuscode'] == 0) { ?><span class="label label-danger"><?php _e("Offline"); ?></span><?php } ?>
																		<?php } ?>
																	</td>
																</tr>
															<?php } ?>
														</tbody>
													</table>
												</div>

											</div>


											<?php if($check['type'] == "callback") { ?>

												<div class="spacer"></div>
												<div class="box box-primary">
													<div class="box-header">
														<h3 class="box-title"><?php _e('Reporting URLs'); ?></h3>
														<div class="pull-right box-tools">
															<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
														</div>
													</div>

													<div class="box-body">

														<h4>Successful URL</h4>
														<pre><?php echo baseURL(); ?>callback.php?key=<?php echo $check['host']; ?>&status=success</pre>

														<br>
														<h4>Unsuccessful URL</h4>
														<pre><?php echo baseURL(); ?>callback.php?key=<?php echo $check['host']; ?>&status=failure</pre>

													</div>
												</div>

											<?php } ?>

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
																				<?php if(in_array("editCheck",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
																			<?php } elseif($incident['status'] == 3) { ?>
																				<?php if(in_array("editCheck",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
																			<?php } else { ?>
																				<?php if(in_array("editCheck",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i><?php } ?>
																			<?php } ?>
																		</td>
																		<td>
																			<?php if($incident['type'] == "offline") _e('Check Offline'); ?>
																			<?php if($incident['type'] == "responsetime") _e('Response Time'); ?>
																			<?php if($incident['type'] == "blacklisted") _e('Listed In Blacklist'); ?>
																			<?php if($incident['type'] == "dnsfailed") _e('DNS Lookup Failed'); ?>
																			<?php if($incident['type'] == "unsuccessful") _e('Unsuccessful reported'); ?>

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

												</div>
											</div>
										<?php } ?>


	                                    <div class="box box-primary">
	            							<div class="box-header">
	            								<h3 class="box-title"><?php _e('Check Info'); ?></h3>
	            								<div class="pull-right box-tools">
	            									<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	            								</div>
	            							</div>

	            							<div class="box-body">
	            								<table id="websiteInfoTable" class="table table-striped table-hover">
	            									<tbody>

	            										<tr>
	            											<td><b><?php _e('Name'); ?></b></td>
	            											<td><?php echo $check['name']; ?></td>
	            										</tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Host'); ?></b></td>
	                                                        <td><?php echo $check['host']; ?></td>
	                                                    </tr>

														<tr>
															<td><b><?php _e('Check Type'); ?></b></td>
															<td>
																<?php
																	if($check['type'] == "tcp") { _e('TCP'); echo ": " . $check['port']; }
																	if($check['type'] == "udp") { _e('UDP'); echo ": " . $check['port']; }
																	if($check['type'] == "icmp") { _e('ICMP (Ping)'); }
																	if($check['type'] == "dns") { _e('DNS Lookup'); }
																	if($check['type'] == "blacklist") { _e('Blacklist Check'); }
																	if($check['type'] == "callback") { _e('Callback Check'); }
																?>
															</td>
														</tr>

														<tr>
															<td><b><?php _e('Port'); ?></b></td>
															<td>
																<?php
																	if($check['type'] == "tcp") { echo $check['port']; }
																	if($check['type'] == "udp") { echo $check['port']; }
																	if($check['type'] == "icmp") { _e('N/A'); }
																	if($check['type'] == "dns") { _e('N/A'); }
																	if($check['type'] == "blacklist") { _e('N/A'); }
																	if($check['type'] == "callback") { _e('N/A'); }
																?>
															</td>
														</tr>

														<tr>
															<td><b><?php _e('Timeout'); ?></b></td>
															<td><?php echo $check['timeout']; ?><?php _e('s'); ?></td>
														</tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Last Checked'); ?></b></td>
	                                                        <td><?php echo smartDate($latest['timestamp']); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Last Load Time'); ?></b></td>
	                                                        <td><?php echo $latest['latency']; ?><?php echo $latencyunit; ?></td>
	                                                    </tr>

	            									</tbody>
	            								</table>
	            							</div>
	            						</div>



										<?php if($check['type'] != "callback") { ?>
		                                    <div class="box box-primary">
		            							<div class="box-header">
		            								<h3 class="box-title"><?php _e('Uptime'); ?></h3>
		            								<div class="pull-right box-tools">
		            									<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
		            								</div>
		            							</div>

		            							<div class="box-body">
		            								<table id="websiteInfoTable" class="table table-striped table-hover">
		            									<tbody>



		            										<tr>
		            											<td><b><?php _e('Last 24 Hours'); ?></b></td>
		            											<td><?php echo Check::Uptime($check['id'], "24h"); ?> %</td>
		            										</tr>

															<tr>
																<td><b><?php _e('Last 7 Days'); ?></b></td>
																<td><?php echo Check::Uptime($check['id'], "7days"); ?> %</td>
															</tr>

															<tr>
																<td><b><?php _e('Last 30 Days'); ?></b></td>
																<td><?php echo Check::Uptime($check['id'], "30days"); ?> %</td>
															</tr>

															<tr>
																<td><b><?php _e('Last 12 Months'); ?></b></td>
																<td><?php echo Check::Uptime($check['id'], "12months"); ?> %</td>
															</tr>

															<tr>
																<td><b><?php _e('Selected Period'); ?></b></td>
																<td><?php echo Check::Uptime($check['id'], "selected"); ?> %</td>
															</tr>


		            									</tbody>
		            								</table>
		            							</div>
		            						</div>
										<?php } ?>




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
													<?php if($alert['type'] == "offline") _e('Check Offline'); ?>
													<?php if($alert['type'] == "responsetime") _e('Response Time'); ?>
													<?php if($alert['type'] == "blacklisted") _e('Listed In Blacklist'); ?>
													<?php if($alert['type'] == "dnsfailed") _e('DNS Lookup Failed'); ?>
													<?php if($alert['type'] == "unsuccessful") _e('Unsuccessful'); ?>
												</td>

												<td>
													<?php if($alert['type'] == "offline" || $alert['type'] == "blacklisted" || $alert['type'] == "dnsfailed" || $alert['type'] == "unsuccessful") { ?>
														<?php _e('N/A'); ?>
													<?php } else { ?>
														<?php echo $alert['comparison']; ?> <?php echo $alert['comparison_limit']; ?>
													<?php } ?>
												</td>

												<td><?php _e('If occurs'); ?> <?php echo $alert['occurrences']; ?> <?php _e('times'); ?>, <?php _e('alert:'); ?>
													<?php foreach ($selected_contacts as $selected_contact) { ?>
														<span class="label bg-gray"><?php echo getSingleValue("app_contacts", "name", $selected_contact); ?></span>&nbsp;
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
															 <?php if(in_array("editCheck",$perms)) { ?><a href="#" onClick='showM("?modal=checkalerts/edit&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
															 <?php if(in_array("editCheck",$perms)) { ?><a href="#" onClick='showM("?modal=checkalerts/delete&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
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
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } ?>
													<?php } else { ?>
														<?php if(in_array("editCheck",$perms)) { ?>
															<a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
														<?php } ?>
													<?php } ?>
												</td>
												<td>
													<?php if($incident['type'] == "offline") _e('Check Offline'); ?>
													<?php if($incident['type'] == "responsetime") _e('Response Time'); ?>
													<?php if($incident['type'] == "blacklisted") _e('Listed In Blacklist'); ?>
													<?php if($incident['type'] == "dnsfailed") _e('DNS Lookup Failed'); ?>
													<?php if($incident['type'] == "unsuccessful") _e('Unsuccessful'); ?>
												</td>

												<td>
													<?php if($incident['type'] == "offline" || $incident['type'] == "blacklisted" || $incident['type'] == "dnsfailed" || $incident['type'] == "unsuccessful") { ?>
														<?php _e('N/A'); ?>
													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
												<td>
													<?php if($incident['end_time'] != "0000-00-00 00:00:00") echo dateTimeDisplay($incident['end_time']); else { ?> <a href="#" onClick='showM("?modal=checkalerts/markResolved&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php _e("Mark Resolved"); ?></a>  <?php } ?>
												</td>

                                                <td>
													<?php  echo $incident['comment']; ?> <?php if($incident['ignore'] == '1') { ?> [<?php _e('IGNORED'); ?>]<?php } ?> <a href="#" onClick='showM("?modal=checkalerts/editComment&reroute=checks/manage&routeid=<?php echo $check['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><?php if($incident['comment'] == "") _e("Add"); else _e("Edit"); ?></a>
                                                    
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



<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<script type="text/javascript">
	$(document).ready(function() {

		var color1 = '#3e95cd';
		var color2 = '#6f6f6f';
		var color3 = '#61e843';
		var color4 = '#8e5ea2';
		var color5 = '#3cba9f';
		var color6 = '#e8c3b9';


		<?php if ($section == "" or $section == "overview") { ?>

			<?php if($check['type'] == "tcp" or $check['type'] == "udp" or $check['type'] == "icmp" or $check['type'] == "dns") { ?>
				// CPU CHART
				new Chart(document.getElementById("cjs-ov-performance-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['performance'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [{
						    data: [<?php foreach($charts['performance'] as $item) echo "'".$item['latency']."',"; ?>],
							label: '<?php _e('Load Time'); ?>',
						    borderColor: color1,
							backgroundColor: color1,
						    fill: false,
							pointRadius: 0,
	                        pointHoverRadius: 4,
						  }
						]
					},
					options: {
						title: { display: true, text: '<?php _e('Performance'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(1) + ' <?php echo $latencyunit; ?>'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php echo $latencyunit; ?>'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});
			<?php } ?>



		<?php } ?>


	});
</script>
