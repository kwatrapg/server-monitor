<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" language="javascript" src="template/assets/js/email-test.js"></script>


<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php _e('Settings'); ?><small> <?php _e('System configuration'); ?></small></h1>
		<ol class="breadcrumb"><li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li><li><?php _e('System'); ?></li><li class="active"><?php _e('Settings'); ?></li></ol>
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
                        <li <?php if ($section == "") echo 'class="active"'; ?> ><a href="#general" data-toggle="tab"><?php _e('General'); ?></a></li>
						<li <?php if ($section == "monitoring") echo 'class="active"'; ?> ><a href="#monitoring" data-toggle="tab"><?php _e('Monitoring'); ?></a></li>
						<li <?php if ($section == "localisation") echo 'class="active"'; ?> ><a href="#localisation" data-toggle="tab"><?php _e('Localisation'); ?></a></li>
                        <li <?php if ($section == "email") echo 'class="active"'; ?> ><a href="#email" data-toggle="tab"><?php _e('Email'); ?></a></li>
						<li <?php if ($section == "sms") echo 'class="active"'; ?> ><a href="#sms" data-toggle="tab"><?php _e('SMS Gateway'); ?></a></li>
						<li <?php if ($section == "twitter") echo 'class="active"'; ?> ><a href="#twitter" data-toggle="tab"><?php _e('Twitter'); ?></a></li>
						<li <?php if ($section == "pushover") echo 'class="active"'; ?> ><a href="#pushover" data-toggle="tab"><?php _e('Pushover'); ?></a></li>
						<li <?php if ($section == "license") echo 'class="active"'; ?> ><a href="#license" data-toggle="tab"><?php _e('License'); ?></a></li>
						<li <?php if ($section == "templates") echo 'class="active"'; ?> ><a href="#templates" data-toggle="tab"><?php _e('Notifications'); ?></a></li>
						<li <?php if ($section == "crons") echo 'class="active"'; ?> ><a href="#crons" data-toggle="tab"><?php _e('Cron Jobs'); ?></a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane <?php if ($section == "") echo 'active'; ?>" id="general">
							<form role="form" action="" method="post" id="generalSettingsForm" enctype="multipart/form-data">
								<div class="form-group">
									<label for="app_name" class="control-label"><?php _e('Application Name'); ?></label>
									<input class="form-control" id="app_name" value="<?php echo getConfigValue("app_name"); ?>" placeholder="Application Name" type="text" name="app_name" required>
									<p class="help-block"><?php _e('Application Name as it appears throughout the system'); ?></p>
								</div>
								<div class="form-group">
									<label for="app_url" class="control-label"><?php _e('Application URL'); ?></label>
									<input class="form-control" id="app_url" value="<?php echo getConfigValue("app_url"); ?>" placeholder="Application URL" type="text" name="app_url" required>
									<p class="help-block"><?php _e('Full installation URL including http:// or https:// (eg. https://monitor.example.com/)'); ?></p>
								</div>

								<div class="form-group">
									<label for="company_name" class="control-label"><?php _e('Company Name'); ?></label>
									<input class="form-control" id="company_name" value="<?php echo getConfigValue("company_name"); ?>" placeholder="Company Name" type="text" name="company_name" required>
									<p class="help-block"><?php _e('Company Name as it appears throughout the system'); ?></p>
								</div>
								<div class="form-group">
									<label for="company_details" class="control-label"><?php _e('Company Details'); ?></label>
									<textarea class="form-control" rows="3" placeholder="Company Details ..." id="company_details" name="company_details"><?php echo getConfigValue("company_details"); ?></textarea>
									<p class="help-block"><?php _e('Company Details used in the system for reports'); ?></p>
								</div>
								<div class="form-group">
									<label class="control-label"><?php _e('Logo'); ?></label><br>
									<?php if (file_exists($scriptpath . "/assets/logo.png")) { ?>
										<img src="assets/logo.png?v=<?php echo filemtime($scriptpath . "/assets/logo.png"); ?>" style="max-height:60px;max-width:240px;display:block;margin-bottom:8px;background:#f4f4f4;padding:4px;" alt="Logo">
										<a href="?qa=removeLogo&csrf_token=<?php echo e(csrf_token()); ?>" onclick="return confirm('<?php _e('Remove the custom logo?'); ?>');" class="text-danger"><i class="fa fa-trash"></i> <?php _e('Remove Logo'); ?></a>
										<br><br>
									<?php } ?>
									<input class="form-control" type="file" name="logo" accept="image/png,image/jpeg" style="max-width:400px;">
									<p class="help-block"><?php _e('Shown on the login page. PNG or JPG, max 2MB.'); ?></p>
								</div>

								<div class="form-group">
									<label class="control-label"><?php _e('Favicon'); ?></label><br>
									<?php if (file_exists($scriptpath . "/assets/icon.png")) { ?>
										<img src="assets/icon.png?v=<?php echo filemtime($scriptpath . "/assets/icon.png"); ?>" style="max-height:48px;max-width:48px;display:block;margin-bottom:8px;background:#f4f4f4;padding:4px;" alt="Favicon">
										<a href="?qa=removeFavicon&csrf_token=<?php echo e(csrf_token()); ?>" onclick="return confirm('<?php _e('Remove the custom favicon?'); ?>');" class="text-danger"><i class="fa fa-trash"></i> <?php _e('Remove Favicon'); ?></a>
										<br><br>
									<?php } ?>
									<input class="form-control" type="file" name="favicon" accept="image/png" style="max-width:400px;">
									<p class="help-block"><?php _e('Browser tab icon. PNG only, square image recommended (e.g. 64x64px), max 1MB.'); ?></p>
								</div>

								<div class="form-group">
									<label for="log_retention" class="control-label"><?php _e('System Log Retention'); ?></label>
									<input class="form-control" id="log_retention" value="<?php echo getConfigValue("log_retention"); ?>" placeholder="Log Retention (days)" type="number" name="log_retention" required>
									<p class="help-block"><?php _e('Delete system log entries older then (days)'); ?></p>
								</div>
								<div class="form-group">
									<label for="table_records" class="control-label"><?php _e('Record to Display per Page'); ?></label>
									<select class="form-control select2" id="table_records" name="table_records" style="width: 100%;">
										<option <?php if(getConfigValue("table_records") == "10") echo 'selected'; ?> value="10">10</option>
										<option <?php if(getConfigValue("table_records") == "25") echo 'selected'; ?> value="25">25</option>
										<option <?php if(getConfigValue("table_records") == "50") echo 'selected'; ?> value="50">50</option>
										<option <?php if(getConfigValue("table_records") == "100") echo 'selected'; ?> value="100">100</option>
									</select>
								</div>


								<div class="form-group">
									<label for="google_maps_api_key" class="control-label"><?php _e('Google Maps API Key'); ?></label>
									<input class="form-control" id="google_maps_api_key" value="<?php echo getConfigValue("google_maps_api_key"); ?>" placeholder="Google Maps API Key" name="google_maps_api_key">
									<p class="help-block"><?php _e('Adding a Google Maps API Key will switch the dashboard map to Google Maps and will allow you to set custom location for your assets.'); ?></p>
									<p class="help-block"><?php _e('You can optain an API key by creating a new project in'); ?> <a href="https://console.cloud.google.com/apis" target="_blank"><?php _e('Google Cloud Console'); ?></a>.<br>
									<?php _e('Enable Maps JavaScript API and Places API libraries for the newly created project. '); ?><br>
									<?php _e('It is recommended that you link your project to a billing account. The free tier offered by Google will suffice for normal usage.'); ?></p>
								</div>

								<div class="form-group">
									<div class="checkbox"><label><input type="checkbox" name="xss_filtering" <?php if(getConfigValue("xss_filtering") == "true") echo 'checked="yes"'; ?> value="true"> <?php _e('XSS Filtering (Recommended)'); ?></label></div>
								</div>


								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>
								<div style="clear:both;"></div>

								<input type="hidden" name="action" value="generalSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="">
							</form>
						</div><!-- /.tab-pane -->


						<div class="tab-pane <?php if ($section == "monitoring") echo 'active'; ?>" id="monitoring">
							<form role="form" action="" method="post" id="monitoringSettingsForm">



								<div class="form-group">
									<label for="check_timeout" class="control-label"><?php _e('Check Timeout (s)'); ?></label>
									<input class="form-control" id="check_timeout" value="<?php echo getConfigValue("check_timeout"); ?>" type="number" name="check_timeout" required>
									<p class="help-block"><?php _e('Default check timeout in seconds.'); ?></p>
								</div>

								<div class="form-group">
									<label for="history_retention" class="control-label"><?php _e('History Retention (days)'); ?></label>
									<input class="form-control" id="history_retention" value="<?php echo getConfigValue("history_retention"); ?>" type="number" name="history_retention" required>
							<!--		<input class="form-control" id="check_timeout" value="<?php echo getConfigValue("history_retention"); ?>" type="number" name="history_retention" required> -->
									<p class="help-block"><?php _e('Server, website, check & alert history older than this value will be deleted.'); ?></p>
								</div>

								<div class="form-group">
					                <label for="contacts"><?php _e('Default Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected will be used for default alerts when adding new server, website or check.'); ?>"></i></label>
					                <select class="form-control select2tags select2-hidden-accessible" id="default_contacts" name="default_contacts[]" style="width: 100%;" multiple>
					                    <?php foreach ($contacts as $contact) { ?>
					                        <option value='<?php echo $contact['id']; ?>' <?php if(in_array($contact['id'], $selected_contacts)) echo "selected"; ?> ><?php echo e($contact['name']); ?></option>
					                    <?php } ?>
					                </select>
					            </div>

								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>
								<div style="clear:both;"></div>

								<input type="hidden" name="action" value="monitoringSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="monitoring">
							</form>
						</div><!-- /.tab-pane -->




                        <div class="tab-pane <?php if ($section == "localisation") echo 'active'; ?>" id="localisation">
							<div class="row">
								<div class="col-md-6">
									<form role="form" action="" method="post" id="localisationSettingsForm">
										<div class="form-group">
										<label for="week_start" class="control-label"><?php _e('First Day of The Week'); ?></label>
											<select class="form-control select2" id="week_start" name="week_start" style="width: 100%;">
												<option <?php if(getConfigValue("week_start") == "1") echo 'selected'; ?> value="1"><?php _e('Monday'); ?></option>
												<option <?php if(getConfigValue("week_start") == "7") echo 'selected'; ?> value="7"><?php _e('Sunday'); ?></option>
											</select>
										</div>
										<div class="form-group">
											<label for="default_lang" class="control-label"><?php _e('Default Language'); ?></label>
											<select class="form-control select2" id="default_lang" name="default_lang" style="width: 100%;">
												<?php foreach ($languages as $language) { ?>
													<option <?php if(getConfigValue("default_lang") == $language['code']) echo 'selected'; ?> value="<?php echo $language['code']; ?>"><?php echo e($language['name']); ?></option>
												<?php } ?>
											</select>
										</div>

										<div class="form-group">
											<label for="timezone" class="control-label"><?php _e('Timezone'); ?></label>
											<select class="form-control select2" id="timezone" name="timezone" style="width: 100%;">
												<?php foreach ($tzlist as $key => $value) { ?>
													<option <?php if(getConfigValue("timezone") == $value) echo 'selected'; ?> value="<?php echo $value; ?>"><?php echo $key; ?></option>
												<?php } ?>
											</select>
										</div>

										<div class="form-group">
											<label for="date_format" class="control-label"><?php _e('Date Format'); ?></label>
											<select class="form-control select2" id="date_format" name="date_format" style="width: 100%;">
													<option <?php if(getConfigValue("date_format") == "d/m/Y;dd/mm/yyyy") echo 'selected'; ?> value="d/m/Y;dd/mm/yyyy">DD/MM/YYYY</option>
													<option <?php if(getConfigValue("date_format") == "d.m.Y;dd.mm.yyyy") echo 'selected'; ?> value="d.m.Y;dd.mm.yyyy">DD.MM.YYYY</option>
													<option <?php if(getConfigValue("date_format") == "d-m-Y;dd-mm-yyyy") echo 'selected'; ?> value="d-m-Y;dd-mm-yyyy">DD-MM-YYYY</option>
													<option <?php if(getConfigValue("date_format") == "m/d/Y;mm/dd/yyyy") echo 'selected'; ?> value="m/d/Y;mm/dd/yyyy">MM/DD/YYYY</option>
													<option <?php if(getConfigValue("date_format") == "Y/m/d;yyyy/mm/dd") echo 'selected'; ?> value="Y/m/d;yyyy/mm/dd">YYYY/MM/DD</option>
													<option <?php if(getConfigValue("date_format") == "Y-m-d;yyyy-mm-dd") echo 'selected'; ?> value="Y-m-d;yyyy-mm-dd">YYYY-MM-DD</option>
											</select>
										</div>

										<div class="form-group">
											<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
										</div>
										<div style="clear:both;"></div>

										<input type="hidden" name="action" value="localisationSettings">
										<input type="hidden" name="route" value="system/settings">
										<input type="hidden" name="section" value="localisation">
									</form>
								</div>
								<div class="col-md-6">
									<div class="box box-primary">
										<div class="box-header with-border">
											<h3 class="box-title"><i class="fa fa-language fa-fw"></i><?php _e('Languages'); ?></h3>
											<div class="box-tools pull-right">
												<a href="#" onClick='showM("?modal=languages/add&reroute=system/settings&routeid=&section=localisation");return false' class="btn btn-flat btn-primary btn-sm"><?php _e('NEW LANGUAGE'); ?></a>
											</div>
										</div>
										<div class="box-body">
											<div class="table-responsive">
												<table class="table table-striped table-hover">
													<thead>
														<tr>
															<th><?php _e('Code'); ?></th>
															<th><?php _e('Name'); ?></th>
															<th class="text-right"><?php _e('Actions'); ?></th>
														</tr>
													</thead>
													<tbody>
														<?php
														foreach ($languages as $language) {
														echo "<tr>";
															echo "<td>".$language['code']."</td>";
															echo "<td>".$language['name']."</td>";
															echo "<td>";
																if($language['id'] != 1) {
																	echo "<div class='pull-right'>
																		<a href='#' onClick='showM(\"?modal=languages/delete&reroute=system/settings&routeid=&id=".$language['id']."&section=localisation\");return false' class='btn-right text-red'><i class='fa fa-trash-o'></i></a>
																	</div>";
																}
															echo "</td>";
														echo "</tr>";
														}
														?>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div><!-- /.tab-pane -->

                        <div class="tab-pane <?php if ($section == "email") echo 'active'; ?>" id="email">
							<form role="form" action="" method="post" id="emailSettingsForm">
								<div class="form-group">
									<label for="email_from_address" class="control-label"><?php _e('Email From Address'); ?></label>
									<input class="form-control" id="email_from_address" value="<?php echo getConfigValue("email_from_address"); ?>" placeholder="<?php _e('Email From Address'); ?>" type="text" name="email_from_address" required>
								</div>
								<div class="form-group">
									<label for="email_from_name" class="control-label"><?php _e('Emails From Name'); ?></label>
									<input class="form-control" id="email_from_name" value="<?php echo getConfigValue("email_from_name"); ?>" placeholder="<?php _e('Emails From Name'); ?>" type="text" name="email_from_name" required>
								</div>
								<div class="form-group">
									<div class="checkbox"><label><input type="checkbox" name="email_smtp_enable" <?php if(getConfigValue("email_smtp_enable") == "true") echo 'checked="yes"'; ?> value="true"> <?php _e('Enable SMTP'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="email_smtp_auth" <?php if(getConfigValue("email_smtp_auth") == "true") echo 'checked="yes"'; ?> value="true"> <?php _e('SMTP Requires Authentication'); ?></label></div>
								</div>

								<div class="form-group">
									<label for="email_smtp_host" class="control-label"><?php _e('SMTP Host'); ?></label>
									<input class="form-control" id="email_smtp_host" value="<?php echo e(getConfigValue("email_smtp_host")); ?>" placeholder="SMTP Host" type="text" name="email_smtp_host">
								</div>
								<div class="form-group">
									<label for="email_smtp_port" class="control-label"><?php _e('SMTP Port'); ?></label>
									<input class="form-control" id="email_smtp_port" value="<?php echo e(getConfigValue("email_smtp_port")); ?>" placeholder="SMTP Port" type="text" name="email_smtp_port">
									<p class="help-block"></p>
								</div>
								<div class="form-group">
									<label for="email_smtp_username" class="control-label"><?php _e('SMTP Username'); ?></label>
									<input class="form-control" id="email_smtp_username" value="<?php echo e(getConfigValue("email_smtp_username")); ?>" placeholder="SMTP Username" type="text" name="email_smtp_username">
									<p class="help-block"></p>
								</div>
								<div class="form-group">
									<label for="email_smtp_password" class="control-label"><?php _e('SMTP Password'); ?></label>
									<input class="form-control" id="email_smtp_password" value="" autocomplete="new-password" placeholder="<?php echo getConfigValue("email_smtp_password") !== '' ? __('Leave blank to keep the current password') : __('SMTP Password'); ?>" type="password" name="email_smtp_password">
									<p class="help-block"></p>
								</div>
								<div class="form-group">
									<label for="email_smtp_security" class="control-label"><?php _e('SMTP Security'); ?></label>

										<select class="form-control" id="email_smtp_security" name="email_smtp_security">
											<option <?php if(getConfigValue("email_smtp_security") == "") echo 'selected'; ?> value=""><?php _e('None'); ?></option>
											<option <?php if(getConfigValue("email_smtp_security") == "SSL") echo 'selected'; ?> value="SSL"><?php _e('SSL'); ?></option>
											<option <?php if(getConfigValue("email_smtp_security") == "TLS") echo 'selected'; ?> value="TLS"><?php _e('TLS'); ?></option>
										</select>
									<p class="help-block"></p>
								</div>

								<div class="form-group">
									<label for="email_smtp_username"><?php _e('SMTP Authentication Domain'); ?></label>
									<input class="form-control" id="email_smtp_domain" value="<?php echo getConfigValue("email_smtp_domain"); ?>" placeholder="SMTP Authentication Domain" type="text" name="email_smtp_domain">
									<p class="help-block"><?php _e('Use in case of connecting to an Exchange server or similar server requiring NTLM authentication.'); ?></p>
								</div>

								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>

								<div style="clear:both;"></div>
								<input type="hidden" name="action" value="emailSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="email">
							</form>

							<hr>

							<h4><?php _e('Test Connection'); ?></h4>
							<p class="text-muted"><?php _e('Sends a sample test email using the currently saved connection settings above, so you can confirm delivery is working. Save your changes first if you just edited the settings above.'); ?></p>
							<div class="form-group">
								<label for="email_test_to" class="control-label"><?php _e('Send Test Email To'); ?></label>
								<input class="form-control" id="email_test_to" value="<?php echo e($liu['email']); ?>" placeholder="test@example.com" type="email" style="max-width:400px;">
							</div>
							<div class="form-group">
								<button type="button" id="testEmailBtn" class="btn btn-flat btn-default"><i class="fa fa-paper-plane-o"></i> <?php _e('Test Connection'); ?></button>
								<span id="testEmailSpinner" style="display:none; margin-left:10px;"><i class="fa fa-spinner fa-spin"></i> <?php _e('Sending test email...'); ?></span>
								<div id="testEmailResult" style="margin-top:10px;"></div>
							</div>

                        </div><!-- /.tab-pane -->



                        <div class="tab-pane <?php if ($section == "sms") echo 'active'; ?>" id="sms">
							<form role="form" action="" method="post" id="smsSettingsForm">
								<div class="form-group">
									<label for="sms_provider" class="control-label"><?php _e('SMS Provider'); ?></label>

										<select class="form-control select2" id="sms_provider" name="sms_provider" style="width: 100%;">
											<option <?php if(getConfigValue("sms_provider") == "clickatell") echo 'selected'; ?> value="clickatell">Clickatell</option>
											<option <?php if(getConfigValue("sms_provider") == "smsglobal") echo 'selected'; ?> value="smsglobal">SMS Global</option>
											<option <?php if(getConfigValue("sms_provider") == "twilio") echo 'selected'; ?> value="twilio">Twilio</option>
											<option <?php if(getConfigValue("sms_provider") == "1s2u") echo 'selected'; ?> value="1s2u">1s2u</option>
											<option <?php if(getConfigValue("sms_provider") == "messagebird") echo 'selected'; ?> value="messagebird">MessageBird</option>
										</select>

								</div>

								<div class="form-group">
									<label for="sms_user" class="control-label"><?php _e('Username/Account SID'); ?></label>
									<input class="form-control" id="sms_user" value="<?php echo getConfigValue("sms_user"); ?>" type="text" name="sms_user">
								</div>
								<div class="form-group">
									<label for="sms_password" class="control-label"><?php _e('Password/Auth Token/Access Key'); ?></label>
									<input class="form-control" id="sms_password" value="<?php echo getConfigValue("sms_password"); ?>" type="password" name="sms_password">
								</div>


								<div class="form-group">
									<label for="sms_api_id" class="control-label"><?php _e('API ID'); ?></label>
									<input class="form-control" id="sms_api_id" value="<?php echo getConfigValue("sms_api_id"); ?>" type="text" name="sms_api_id">
									<p class="help-block"><?php _e('Only for Clickatell'); ?></p>
								</div>

								<div class="form-group">
									<label for="sms_from" class="control-label"><?php _e('From Name/Number/Originator'); ?></label>
									<input class="form-control" id="sms_from" value="<?php echo getConfigValue("sms_from"); ?>" type="text" name="sms_from">
									<p class="help-block"><?php _e('For SMS Global, Twillio, 1s2u and Messagebird'); ?></p>
								</div>

								<div class="form-group">
										<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>
								<div style="clear:both;"></div>

								<input type="hidden" name="action" value="smsSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="sms">
							</form>
                        </div><!-- /.tab-pane -->


                        <div class="tab-pane <?php if ($section == "twitter") echo 'active'; ?>" id="twitter">
							<form role="form" action="" method="post" id="twitterSettingsForm">
								<div class="form-group">
									<label for="twitter_apikey" class="control-label"><?php _e('Consumer Key (API Key)'); ?></label>
									<input class="form-control" id="twitter_apikey" value="<?php echo getConfigValue("twitter_apikey"); ?>" placeholder="<?php _e('Consumer Key (API Key)'); ?>" type="text" name="twitter_apikey">
								</div>
								<div class="form-group">
									<label for="twitter_apisecret" class="control-label"><?php _e('Consumer Secret (API Secret)'); ?></label>
									<input class="form-control" id="twitter_apisecret" value="<?php echo getConfigValue("twitter_apisecret"); ?>" placeholder="<?php _e('Consumer Secret (API Secret)'); ?>" type="text" name="twitter_apisecret">
								</div>

								<div class="form-group">
									<label for="twitter_token" class="control-label"><?php _e('Access Token'); ?></label>
									<input class="form-control" id="twitter_token" value="<?php echo getConfigValue("twitter_token"); ?>" placeholder="<?php _e('Access Token'); ?>" type="text" name="twitter_token">
								</div>
								<div class="form-group">
									<label for="twitter_tokensecret" class="control-label"><?php _e('Access Token Secret'); ?></label>
									<input class="form-control" id="twitter_tokensecret" value="<?php echo getConfigValue("twitter_tokensecret"); ?>" placeholder="<?php _e('Access Token Secret'); ?>" type="text" name="twitter_tokensecret">
								</div>


								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>

								<div style="clear:both;"></div>
								<input type="hidden" name="action" value="twitterSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="twitter">
							</form>
                        </div><!-- /.tab-pane -->


						<div class="tab-pane <?php if ($section == "pushover") echo 'active'; ?>" id="pushover">
							<form role="form" action="" method="post" id="pushoverSettingsForm">

								<div class="form-group">
									<label for="twitter_apikey" class="control-label"><?php _e('Pushover Application API Token'); ?></label>
									<input class="form-control" id="pushover_apitoken" value="<?php echo getConfigValue("pushover_apitoken"); ?>" placeholder="<?php _e('Pushover Application API Token'); ?>" type="text" name="pushover_apitoken">
								</div>

								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button></div>
								</div>

								<div style="clear:both;"></div>
								<input type="hidden" name="action" value="pushoverSettings">
								<input type="hidden" name="route" value="system/settings">
								<input type="hidden" name="section" value="pushover">
							</form>
                        </div><!-- /.tab-pane -->

						<div class="tab-pane <?php if ($section == "license") echo 'active'; ?>" id="license">
							<?php if (!$licenseStatus['enabled']): ?>
								<div class="alert alert-warning alert-auto"><?php _e('LICENSE_API_URL is not configured on this server. A license is required to use this application — set LICENSE_API_URL and LICENSE_HMAC_SECRET in .env before a license key can be verified here.'); ?></div>
							<?php else: ?>
								<?php
									$badgeClass = $licenseStatus['valid'] ? 'label-success' : 'label-warning';
									$badgeText = $licenseStatus['valid'] ? __('Active') : ucfirst(str_replace('_', ' ', (string) $licenseStatus['reason']));
								?>
								<div class="form-group">
									<label class="control-label"><?php _e('Status'); ?></label><br>
									<span class="label <?php echo $badgeClass; ?>"><?php echo e($badgeText); ?></span>
									<?php if ($licenseStatus['plan_name']): ?>
										&nbsp; <span class="label label-default"><?php echo e($licenseStatus['plan_name']); ?></span>
									<?php endif; ?>
									<?php if ($licenseStatus['expires_at']): ?>
										<p class="help-block"><?php echo sprintf(__('Expires: %s'), e($licenseStatus['expires_at'])); ?></p>
									<?php endif; ?>
									<?php if ($licenseStatus['checked_at']): ?>
										<p class="help-block"><?php echo sprintf(__('Last checked: %s (%s)'), e($licenseStatus['checked_at']), e($licenseStatus['source'])); ?></p>
									<?php endif; ?>
								</div>

								<form role="form" action="" method="post" id="licenseSettingsForm">
									<div class="form-group">
										<label for="license_key" class="control-label"><?php _e('License Key'); ?></label>
										<input class="form-control" id="license_key" value="<?php echo e(License::getKey()); ?>" placeholder="SNTR-XXXX-XXXX-XXXX-XXXX" type="text" name="license_key" autocomplete="off">
										<p class="help-block"><?php _e('Enter the license key issued for this installation. Saving re-checks it immediately.'); ?></p>
									</div>

									<div class="form-group">
										<div class="pull-right" style="margin:10px 0px;">
											<a href="?route=system/settings&section=license&qa=verifyLicense&csrf_token=<?php echo e(csrf_token()); ?>" class="btn btn-flat btn-default"><?php _e('Verify Now'); ?></a>
											<button type='submit' class="btn btn-flat btn-success"><?php _e('Save Changes'); ?></button>
										</div>
									</div>
									<div style="clear:both;"></div>

									<input type="hidden" name="action" value="licenseSettings">
									<input type="hidden" name="route" value="system/settings">
									<input type="hidden" name="section" value="license">
								</form>

								<hr>
								<h4><?php _e('Plan Limits'); ?></h4>
								<table class="table table-striped table-hover">
									<thead><tr><th><?php _e('Resource'); ?></th><th><?php _e('Used'); ?></th><th><?php _e('Limit'); ?></th></tr></thead>
									<tbody>
										<tr><td><?php _e('Servers'); ?></td><td><?php echo (int) $licenseUsage['max_servers']['used']; ?></td><td><?php echo $licenseUsage['max_servers']['limit'] === null ? __('Unlimited') : (int) $licenseUsage['max_servers']['limit']; ?></td></tr>
										<tr><td><?php _e('Websites'); ?></td><td><?php echo (int) $licenseUsage['max_websites']['used']; ?></td><td><?php echo $licenseUsage['max_websites']['limit'] === null ? __('Unlimited') : (int) $licenseUsage['max_websites']['limit']; ?></td></tr>
										<tr><td><?php _e('Checks'); ?></td><td><?php echo (int) $licenseUsage['max_checks']['used']; ?></td><td><?php echo $licenseUsage['max_checks']['limit'] === null ? __('Unlimited') : (int) $licenseUsage['max_checks']['limit']; ?></td></tr>
										<tr><td><?php _e('Domains'); ?></td><td><?php echo (int) $licenseUsage['max_domains']['used']; ?></td><td><?php echo $licenseUsage['max_domains']['limit'] === null ? __('Unlimited') : (int) $licenseUsage['max_domains']['limit']; ?></td></tr>
										<tr><td><?php _e('SSL'); ?></td><td><?php echo (int) $licenseUsage['max_ssl']['used']; ?></td><td><?php echo $licenseUsage['max_ssl']['limit'] === null ? __('Unlimited') : (int) $licenseUsage['max_ssl']['limit']; ?></td></tr>
									</tbody>
								</table>
							<?php endif; ?>
                        </div><!-- /.tab-pane -->

                        <div class="tab-pane <?php if ($section == "templates") echo 'active'; ?>" id="templates">


							<div class="text-center">

								<h3>Users</h3>
								<a onClick='showM("?modal=notifications/edit&id=1&reroute=system/settings&section=templates");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('New User'); ?></a>
								<a onClick='showM("?modal=notifications/edit&id=2&reroute=system/settings&section=templates");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('Password Reset'); ?></a>
								<br>

								<h3>Alerts</h3>
								<a onClick='showM("?modal=notifications/edit&id=3&reroute=system/settings&section=templates");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('Incident Alert'); ?></a>
								<a onClick='showM("?modal=notifications/edit&id=3&reroute=system/settings&section=templates");return false' data-toggle="modal" class="btn btn-flat btn-primary btn-sm"><?php _e('Incident Unresolved'); ?></a>
							</div>
							<br>


                        </div><!-- /.tab-pane -->


						<div class="tab-pane <?php if ($section == "crons") echo 'active'; ?>" id="crons">
							<div>

								<h4><?php _e('System Cron Job'); ?></h4>
								<pre>*/3 * * * * GET <?php echo baseURL(); ?>crons/cron.php >/dev/null 2>&1</pre>
								<p><?php _e('or'); ?></p>
								<pre>*/3 * * * * php -q <?php echo $scriptpath; ?><?php echo DIRECTORY_SEPARATOR; ?>crons<?php echo DIRECTORY_SEPARATOR; ?>cron.php >/dev/null 2>&1</pre>

								<p class="text-muted">
									<?php _e('This cron job will check websites and checks, process alerts and send notifications if needed, every 3 minutes.'); ?>
								</p>
								<br><br>


							</div>
							<br>
                        </div><!-- /.tab-pane -->

                    </div><!-- /.tab-content -->
                </div><!-- nav-tabs-custom -->
            </div><!-- /.col-->
        </div><!-- ./row -->




	</section><!-- /.content -->
</aside><!-- /.right-side -->
