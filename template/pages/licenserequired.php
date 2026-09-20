<?php
$__licenseStatus = License::status();
$__licensePortalUrl = trim((string) ($config['license_portal_url'] ?? ''));
$__canManage = in_array('manageSettings', $perms ?? [], true);

$__reasonMessages = [
	'not_configured'          => __('This installation does not have a valid license yet.'),
	'expired'                 => __('Your license has expired.'),
	'suspended'               => __('Your license has been suspended.'),
	'revoked'                 => __('Your license has been revoked.'),
	'not_found'               => __('The configured license key is not valid.'),
	'domain_mismatch'         => __('This license is not valid for this domain.'),
	'activation_limit_reached' => __('This license has reached its activation limit.'),
	'unreachable'             => __('Your license could not be verified right now. Please try again shortly.'),
];
$__reasonMessage = $__reasonMessages[$__licenseStatus['reason']] ?? __('A valid license is required to use this application.');
?>
<aside class="right-side">
	<section class="content-header">
		<h1><?php _e('License Required'); ?></h1>
	</section>

	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="box box-danger">
					<div class="box-header with-border">
						<h3 class="box-title"><i class="fa fa-lock fa-fw"></i> <?php _e('Your license is expired or invalid'); ?></h3>
					</div>
					<div class="box-body">
						<p class="lead"><?php echo e($__reasonMessage); ?></p>
						<?php if ($__canManage): ?>
							<p><?php _e('Please renew or update your license to continue using this application.'); ?></p>
						<?php else: ?>
							<p><?php _e('Please contact your administrator to renew or update the license for this installation.'); ?></p>
						<?php endif; ?>

						<?php if ($__licenseStatus['plan_name']): ?>
							<p class="text-muted"><?php echo sprintf(__('Last known plan: %s'), e($__licenseStatus['plan_name'])); ?></p>
						<?php endif; ?>
						<?php if ($__licenseStatus['expires_at']): ?>
							<p class="text-muted"><?php echo sprintf(__('Expired on: %s'), e($__licenseStatus['expires_at'])); ?></p>
						<?php endif; ?>

						<div style="margin-top:20px;">
							<?php if ($__licensePortalUrl !== ''): ?>
								<a href="<?php echo e($__licensePortalUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-flat btn-primary"><i class="fa fa-external-link"></i> <?php _e('Renew at Admin Portal'); ?></a>
							<?php endif; ?>
							<?php if ($__canManage): ?>
								<a href="?route=system/settings&section=license" class="btn btn-flat btn-default"><i class="fa fa-key"></i> <?php _e('Update License Key'); ?></a>
								<a href="?route=system/settings&qa=verifyLicense&csrf_token=<?php echo e(csrf_token()); ?>" class="btn btn-flat btn-default"><i class="fa fa-refresh"></i> <?php _e('Check Again'); ?></a>
							<?php endif; ?>
							<a href="?route=signout" class="btn btn-flat btn-link"><?php _e('Sign Out'); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</aside>
