<!-- Right side column. Contains the navbar and content of the page -->
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php _e('Search'); ?><small> <?php _e('Search system wide'); ?></small></h1>
		<ol class="breadcrumb"><li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li><li class="active"><?php _e('Search'); ?></li></ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-primary">
          			<div class="box-header with-border"><h3 class="box-title"></h3></div><!-- /.box-header -->
                    <div class="box-body">
						<form role="form" method="get" action="" class="form-inline">
							<div class="form-group" style="width:94%">
								<input type="text" class="form-control input-lg" style="width:100%" id="query" name="q" placeholder="<?php _e('Search String...'); ?>" value="<?php echo e($_GET['q'] ?? ''); ?>" required>
							</div>

							<input type="hidden" name="route" value="search">
							<button type="submit" class="btn btn-primary btn-lg"><?php _e('Search'); ?></button>
						</form>
						<div class="clear"></div>




						<?php if(!empty($servers)) { ?>
							<div class="row">
								<div class="col-xs-12"><h3><?php _e('Servers'); ?></h3></div>
								<?php foreach($servers as $item) { ?>
									<div class="col-xs-3"><div class="callout callout-gray">
										<h4>
											<?php if(in_array("viewServers",$perms)) { ?><a href="?route=servers/manage&id=<?php echo $item['id']; ?>"><?php } ?>
											<?php echo e($item['name']); ?>
											<?php if(in_array("viewServers",$perms)) { ?></a><?php } ?>
										</h4>

					                </div></div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if(!empty($websites)) { ?>
							<div class="row">
								<div class="col-xs-12"><h3><?php _e('Websites'); ?></h3></div>
								<?php foreach($websites as $item) { ?>
									<div class="col-xs-3"><div class="callout callout-gray">
										<h4>
											<?php if(in_array("viewWebsites",$perms)) { ?><a href="?route=websites/manage&id=<?php echo $item['id']; ?>"><?php } ?>
											<?php echo e($item['name']); ?>
											<?php if(in_array("viewWebsites",$perms)) { ?></a><?php } ?>
										</h4>
										<p><?php echo e($item['url']); ?></p>
					                </div></div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if(!empty($checks)) { ?>
							<div class="row">
								<div class="col-xs-12"><h3><?php _e('Check Service'); ?></h3></div>
								<?php foreach($checks as $item) { ?>
									<div class="col-xs-3"><div class="callout callout-gray">
										<h4>
											<?php if(in_array("viewChecks",$perms)) { ?><a href="?route=checks/manage&id=<?php echo $item['id']; ?>"><?php } ?>
											<?php echo e($item['name']); ?>
											<?php if(in_array("viewChecks",$perms)) { ?></a><?php } ?>
										</h4>
										<p><?php echo e($item['host']); ?></p>
									</div></div>
								<?php } ?>
							</div>
						<?php } ?>




					</div>
				</div>
			</div>

		</div>


	</section><!-- /.content -->
</aside><!-- /.right-side -->
