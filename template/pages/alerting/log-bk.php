<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1 class="pull-left"><?php _e('Log'); ?><small> <?php _e('View alert log'); ?></small></h1>
		<div class="pull-right"></div>
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
										<th><?php _e('Date'); ?></th>
	                                    <th><?php _e('Contact'); ?></th>
										<th><?php _e('Message'); ?></th>
										<th><?php _e('Channels'); ?></th>
	                                </tr>
	                            </thead>
	                            <tbody>
									<?php foreach ($alertlog as $item) { ?>
		                                <tr>
		                                    <td><?php echo $item['id']; ?></td>
											<td><?php echo dateTimeDisplay($item['date']); ?></td>
		                                    <td><?php echo $item['contactname']; ?></td>
											<td><?php echo $item['message']; ?></td>
											<td>
												<?php if($item['email'] != "") { ?>
													<i class="fa fa-at fa-fw" data-toggle="tooltip" title="<?php _e('Email:'); ?> <?php echo $item['email']; ?>"></i>
												<?php } ?>

												<?php if($item['mobilenumber'] != "") { ?>
													<i class="fa fa-mobile fa-fw" data-toggle="tooltip" title="<?php _e('Mobile Number:'); ?> <?php echo $item['mobilenumber']; ?>"></i>
												<?php } ?>

												<?php if($item['pushbullet'] != "") { ?>
													<i class="fa fa-arrow-circle-o-right fa-fw" data-toggle="tooltip" title="<?php _e('Pushbullet:'); ?> <?php echo $item['pushbullet']; ?>"></i>
												<?php } ?>

												<?php if($item['twitter'] != "") { ?>
													<i class="fa fa-twitter fa-fw" data-toggle="tooltip" title="<?php _e('Twitter:'); ?> <?php echo $item['twitter']; ?>"></i>
												<?php } ?>

												<?php if($item['pushover'] != "") { ?>
													<i class="fa fa-caret-square-o-right fa-fw" data-toggle="tooltip" title="<?php _e('Pushover:'); ?> <?php echo $item['pushover']; ?>"></i>
												<?php } ?>
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
