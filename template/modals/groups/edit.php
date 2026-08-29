<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit Group'); ?></h4>
</div>

<div class="modal-body">

    <div class="form-group">
        <label for="name"><?php _e('Name'); ?> *</label>
        <input type="text" class="form-control" id="name" name="name" value="<?php echo $group['name']; ?>" required>
    </div>

    <input type="hidden" name="id" value="<?php echo $group['id']; ?>">

    <input type="hidden" name="action" value="editGroup">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success"><i class="fa fa-save"></i> <?php _e('Save'); ?></button>
</div>
