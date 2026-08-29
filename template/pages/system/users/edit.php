<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
<script type="text/javascript" language="javascript" src="template/assets/plugins/datatables/media/js/jquery.dataTables.js"></script>
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo e($user['name']); ?><small> <?php _e('Edit user'); ?></small></h1>
		<ol class="breadcrumb">
			<li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li>
			<li><?php _e('System'); ?></li><li class="active"><?php _e('Users'); ?></li>
		</ol>
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

							<form role="form" method="post">

								<div class="form-group">
									<label for="roleid" class="control-label"><?php _e('Role'); ?></label>
									<select class="form-control select2 select2-hidden-accessible" id="roleid" name="roleid" style="width: 100%;" tabindex="-1" aria-hidden="true">
										<?php foreach ($roles as $role) { ?>
											<option value='<?php echo $role['id']; ?>' <?php if($role['id'] == $user['roleid']) echo "selected"; ?> ><?php echo e($role['name']); ?></option>
										<?php } ?>
									</select>
								</div>


								<div class="form-group">
									<label for="servers"><?php _e('Allowed Groups'); ?></label>
									<select class="form-control select2tags select2-hidden-accessible" id="groups" name="groups[]" style="width: 100%;" multiple>
										<option value='0' <?php if(in_array("0", $current_groups)) echo "selected"; ?> ><?php _e('All Groups'); ?></option>
										<?php foreach ($groups as $group) { ?>
											<option value='<?php echo $group['id']; ?>' <?php if(in_array($group['id'], $current_groups)) echo "selected"; ?> ><?php echo e($group['name']); ?></option>
										<?php } ?>
									</select>
								</div>

								<div class="form-group">
									<label for="name" class="control-label"><?php _e('Name'); ?> *</label>
									<input type="text" class="form-control" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
								</div>

								<div class="form-group">
									<label for="email" class="control-label"><?php _e('Email Address'); ?> *</label>
									<input type="email" class="form-control" id="email" name="email" value="<?php echo e($user['email']); ?>" required>
								</div>

								<div class="form-group">
									<label for="password" class="control-label"><?php _e('Password'); ?></label>
									<input type="password" class="form-control" id="password" name="password" placeholder="">
									<p class="help-block"><?php _e('Enter only if you want to change current password.'); ?></p>
								</div>

								<div class="form-group">
									<label for="lang" class="control-label"><?php _e('Language'); ?></label>
									<select class="form-control select2 select2-hidden-accessible" id="lang" name="lang" style="width: 100%;" tabindex="-1" aria-hidden="true">
										<?php foreach ($languages as $language) { ?>
											<option <?php if($user['lang'] == $language['code']) echo 'selected'; ?> value="<?php echo $language['code']; ?>"><?php echo e($language['name']); ?></option>
										<?php } ?>
									</select>
								</div>

								<div class="form-group">
									<label for="theme" class="control-label"><?php _e('Theme'); ?></label>
									<select class="form-control select2 select2-hidden-accessible" id="theme" name="theme" style="width: 100%;" tabindex="-1" aria-hidden="true">
										<option value="skin-dark" <?php if($liu['theme']=="skin-dark") echo "selected"; ?>><?php _e('Dark'); ?></option>
										<option value="skin-blue" <?php if($user['theme']=="skin-blue") echo "selected"; ?>><?php _e('Blue'); ?></option>
										<option value="skin-black" <?php if($user['theme']=="skin-black") echo "selected"; ?>><?php _e('Black'); ?></option>
										<option value="skin-purple" <?php if($user['theme']=="skin-purple") echo "selected"; ?>><?php _e('Purple'); ?></option>
										<option value="skin-green" <?php if($user['theme']=="skin-green") echo "selected"; ?>><?php _e('Green'); ?></option>
										<option value="skin-red" <?php if($user['theme']=="skin-red") echo "selected"; ?>><?php _e('Red'); ?></option>
										<option value="skin-yellow" <?php if($user['theme']=="skin-yellow") echo "selected"; ?>><?php _e('Yellow'); ?></option>
										<option value="skin-blue-light" <?php if($user['theme']=="skin-blue-light") echo "selected"; ?>><?php _e('Blue Light'); ?></option>
										<option value="skin-black-light" <?php if($user['theme']=="skin-black-light") echo "selected"; ?>><?php _e('Black Light'); ?></option>
										<option value="skin-purple-light" <?php if($user['theme']=="skin-purple-light") echo "selected"; ?>><?php _e('Purple Light'); ?></option>
										<option value="skin-green-light" <?php if($user['theme']=="skin-green-light") echo "selected"; ?>><?php _e('Green Light'); ?></option>
										<option value="skin-red-light" <?php if($user['theme']=="skin-red-light") echo "selected"; ?>><?php _e('Red Light'); ?></option>
										<option value="skin-yellow-light" <?php if($user['theme']=="skin-yellow-light") echo "selected"; ?>><?php _e('Yellow Light'); ?></option>
									</select>
								</div>

								<div class="form-group">
									<label for="sidebar" class="control-label"><?php _e('Sidebar'); ?></label>
									<select class="form-control select2 select2-hidden-accessible" id="sidebar" name="sidebar" style="width: 100%;" tabindex="-1" aria-hidden="true">
										<option value="opened" <?php if($user['sidebar']=="opened") echo "selected"; ?>><?php _e('Opened'); ?></option>
										<option value="collapsed" <?php if($user['sidebar']=="collapsed") echo "selected"; ?>><?php _e('Collapsed'); ?></option>
									</select>
								</div>

								<div class="form-group">
									<label for="layout" class="control-label"><?php _e('Layout'); ?></label>
									<select class="form-control select2 select2-hidden-accessible" id="layout" name="layout" style="width: 100%;" tabindex="-1" aria-hidden="true">
										<option value="" <?php if($user['layout']== "") echo "selected"; ?>><?php _e('Standard'); ?></option>
										<option value="fixed" <?php if($user['layout']=="fixed") echo "selected"; ?>><?php _e('Fixed'); ?></option>
										<option value="layout-boxed" <?php if($user['layout']=="layout-boxed") echo "selected"; ?>><?php _e('Boxed'); ?></option>
									</select>
								</div>

								<div class="form-group">
									<label for="notes" class="control-label"><?php _e('Notes'); ?></label>
									<textarea class="form-control summernote" id="notes" name="notes"><?php echo e($user['notes']); ?></textarea>
								</div>

								<input type="hidden" name="action" value="editUser">
								<input type="hidden" name="route" value="system/users/edit">
								<input type="hidden" name="routeid" value="<?php echo $user['id']; ?>">
								<input type="hidden" name="id" value="<?php echo $user['id']; ?>">


								<div class="form-group">
									<div class="pull-right" style="margin:10px 0px;"><button type='submit' class="btn btn-flat btn-success"></i><?php _e('Save Changes'); ?></button></div>
								</div>
								<div style="clear:both;"></div>

							</form><!-- /.form -->

					</div>
				</div>
			</div>
		</div>







	</section><!-- /.content -->
</aside><!-- /.right-side -->
