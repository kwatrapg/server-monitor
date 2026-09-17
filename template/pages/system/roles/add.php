<!-- Right side column. Contains the navbar and content of the page -->
<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php _e('Roles'); ?><small> <?php _e('Manage roles'); ?></small></h1>
		<ol class="breadcrumb"><li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li><li><?php _e('People'); ?></li><li><a href="?route=people/roles"><?php _e('Roles'); ?></a></li><li class="active"><?php _e('Create'); ?></li></ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-primary">
          			<div class="box-header with-border"><h3 class="box-title"><?php _e('Create New Role'); ?></h3></div><!-- /.box-header -->
                    <div class="box-body">


						<div class="row roles">

							<form role="form" method="post" class="form-horizontal" style="padding:15px;" name="roleForm">
								<div class="col-md-12">
								    <div class="form-group">
								        <label for="name"><?php _e('Name'); ?> *</label>
								        <input type="text" class="form-control" id="name" name="name" required>
								    </div>
								</div>


								<div class="col-md-3">
									<h4>Servers</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addServer" <?php if(in_array("addServer",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editServer" <?php if(in_array("editServer",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteServer" <?php if(in_array("deleteServer",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewServers" <?php if(in_array("viewServers",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>

								</div>

								<div class="col-md-3">
									<h4>Websites</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addWebsite" <?php if(in_array("addWebsite",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editWebsite" <?php if(in_array("editWebsite",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteWebsite" <?php if(in_array("deleteWebsite",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewWebsites" <?php if(in_array("viewWebsites",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>

								</div>

								<div class="col-md-3">
									<h4>Check Service</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addCheck" <?php if(in_array("addCheck",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editCheck" <?php if(in_array("editCheck",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteCheck" <?php if(in_array("deleteCheck",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewChecks" <?php if(in_array("viewChecks",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Domains</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addDomain" <?php if(in_array("addDomain",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editDomain" <?php if(in_array("editDomain",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteDomain" <?php if(in_array("deleteDomain",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewDomains" <?php if(in_array("viewDomains",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>SSL Certificates</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addSsl" <?php if(in_array("addSsl",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editSsl" <?php if(in_array("editSsl",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteSsl" <?php if(in_array("deleteSsl",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewSsl" <?php if(in_array("viewSsl",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Server Logs</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="manageLogSources" <?php if(in_array("manageLogSources",$roleperms)) echo "checked"; ?> > <?php _e('Manage Sources'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editLogAlert" <?php if(in_array("editLogAlert",$roleperms)) echo "checked"; ?> > <?php _e('Manage Alerts'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewServerLogs" <?php if(in_array("viewServerLogs",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Contacts</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addContact" <?php if(in_array("addContact",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editContact" <?php if(in_array("editContact",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteContact" <?php if(in_array("deleteContact",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewContacts" <?php if(in_array("viewContacts",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>


								<div class="col-md-3">
									<h4>Groups</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addGroup" <?php if(in_array("addGroup",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editGroup" <?php if(in_array("editGroup",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteGroup" <?php if(in_array("deleteGroup",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewGroups" <?php if(in_array("viewGroups",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Pages</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addPage" <?php if(in_array("addPage",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editPage" <?php if(in_array("editPage",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deletePage" <?php if(in_array("deletePage",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewPages" <?php if(in_array("viewPages",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Users</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addUser" <?php if(in_array("addUser",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editUser" <?php if(in_array("editUser",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteUser" <?php if(in_array("deleteUser",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewUsers" <?php if(in_array("viewUsers",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Roles</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="addRole" <?php if(in_array("addRole",$roleperms)) echo "checked"; ?> > <?php _e('Add'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="editRole" <?php if(in_array("editRole",$roleperms)) echo "checked"; ?> > <?php _e('Edit'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="deleteRole" <?php if(in_array("deleteRole",$roleperms)) echo "checked"; ?> > <?php _e('Delete'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewRoles" <?php if(in_array("viewRoles",$roleperms)) echo "checked"; ?> > <?php _e('View'); ?></label></div>
								</div>

								<div class="col-md-3">
									<h4>Miscellaneous</h4>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="manageSettings" <?php if(in_array("manageSettings",$roleperms)) echo "checked"; ?> > <?php _e('Manage Settings'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewLogs" <?php if(in_array("viewLogs",$roleperms)) echo "checked"; ?> > <?php _e('View System Logs'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewAlertLogs" <?php if(in_array("viewAlertLogs",$roleperms)) echo "checked"; ?> > <?php _e('View Alert Logs'); ?></label></div>
									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="viewSystem" <?php if(in_array("viewSystem",$roleperms)) echo "checked"; ?> > <?php _e('Show System Menu'); ?></label></div>

									<div class="checkbox"><label><input type="checkbox" name="perms[]" value="search" <?php if(in_array("search",$roleperms)) echo "checked"; ?> > <?php _e('Search'); ?></label></div>
								</div>

								<input type="hidden" name="perms[]" value="Null">

								<input type="hidden" name="action" value="addRole">
								<input type="hidden" name="route" value="system/roles">
								<input type="hidden" name="routeid" value="">
								<input type="hidden" name="section" value="">

								<div class="col-md-12">
									<br><br><br>
									<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?php _e('Create'); ?></button>
									<a onclick="javascript:checkAll('roleForm', true);" href="javascript:void();" class="btn btn-default" ><i class="fa fa-check-square-o"></i> Check All</a>
									<a onclick="javascript:checkAll('roleForm', false);" href="javascript:void();" class="btn btn-default" ><i class="fa fa-square-o"></i> Uncheck All</a>
								</div>

							</form><!-- /.form -->

						</div>

					</div>
				</div>
			</div>
		</div>
	</section><!-- /.content -->
</aside><!-- /.right-side -->

<script type="text/javascript" language="javascript">// <![CDATA[
	function checkAll(formname, checktoggle)
	{
	  var checkboxes = new Array();
	  checkboxes = document[formname].getElementsByTagName('input');

	  for (var i=0; i<checkboxes.length; i++)  {
	    if (checkboxes[i].type == 'checkbox')   {
	      checkboxes[i].checked = checktoggle;
	    }
	  }
	}

</script>
