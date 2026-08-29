<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Contact'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1'><?php _e('Active'); ?></option>
                    <option value='0'><?php _e('Inactive'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="groupid"><?php _e('Group'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="groupid" name="groupid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($groups as $group) { if(!checkGroup($group['id'])) continue; ?>
                        <option value='<?php echo $group['id']; ?>'><?php echo $group['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="name"><?php _e('Email Address'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Leave blank to disable email alerts for this contact.'); ?>"></i></label>
        <input type="email" class="form-control" id="email" name="email">
    </div>

    <div class="form-group">
        <label for="mobilenumber"><?php _e('Mobile Number'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Leave blank to disable SMS alerts for this contact.'); ?>"></i></label>
        <input type="text" class="form-control" id="mobilenumber" name="mobilenumber" placeholder="<?php _e('+12345678901'); ?>">
    </div>

    <div class="form-group">
        <label for="pushbullet"><?php _e('Pushbullet'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Leave blank to disable Pushbullet alerts for this contact.'); ?>"></i></label>
        <input type="text" class="form-control" id="pushbullet" name="pushbullet" placeholder="<?php _e('API Access Token'); ?>">
    </div>

    <div class="form-group">
        <label for="twitter"><?php _e('Twitter'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Leave blank to disable Twitter alerts for this contact.'); ?>"></i></label>
        <input type="text" class="form-control" id="twitter" name="twitter" placeholder="<?php _e('Twitter Username'); ?>">
    </div>

    <div class="form-group">
        <label for="pushover"><?php _e('Pushover'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Leave blank to disable Pushover alerts for this contact.'); ?>"></i></label>
        <input type="text" class="form-control" id="pushover" name="pushover" placeholder="<?php _e('Pushover User Key'); ?>">
    </div>

    <input type="hidden" name="action" value="addContact">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Contact'); ?></button>
</div>

<script type="text/javascript">
		$(".select2").select2();
</script>
