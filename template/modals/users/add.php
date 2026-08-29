<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add User'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">

        <div class="col-md-4">
            <div class="form-group">
                <label for="roleid"><?php _e('Role'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="roleid" name="roleid" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <?php foreach ($roles as $role) { ?>
                        <option value='<?php echo $role['id']; ?>'><?php echo $role['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-8">
            <div class="form-group">
                <label for="servers"><?php _e('Allowed Groups'); ?></label>
                <select class="form-control select2tags select2-hidden-accessible" id="groups" name="groups[]" style="width: 100%;" multiple>
                    <option value='0' selected><?php _e('All Groups'); ?></option>
                    <?php foreach ($groups as $group) { ?>
                        <option value='<?php echo $group['id']; ?>'><?php echo $group['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

    </div>


    <div class="form-group">
        <label for="name"><?php _e('Name'); ?> *</label>
        <input type="text" class="form-control" id="name" name="name" autocomplete="off" required>
    </div>
    <div class="form-group">
        <label for="email"><?php _e('Email Address'); ?> *</label>
        <input type="email" class="form-control" id="email" name="email" autocomplete="off" required>
    </div>


    <div class="form-group">
        <label for="password"><?php _e('Password'); ?> *</label>
        <input type="password" class="form-control" id="password" name="password" autocomplete="off" required>
    </div>
    <div class="form-group"><div class="checkbox"><label><input type="checkbox" name="notification" value="true" checked="yes"> <?php _e('Send new staff account email notification'); ?></label></div></div>
    <input type="hidden" name="action" value="addUser">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add User'); ?></button>
</div>
<script type="text/javascript">
		//$(".select2").select2();


        $(".select2").select2({
            placeholder: "<?php _e('Please select'); ?>"
        });

        $(function() { $(".select2tags").select2({
            tags: true
        }); });


</script>
