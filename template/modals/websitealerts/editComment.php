<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Incident Comment'); ?></h4>
</div>

<div class="modal-body">
    
    <div class="form-group" id="comment-div">
        <label for="comment" id="comment_label"><?php _e('Comment'); ?></label>
        <input type="text" class="form-control" id="comment" name="comment" value="<?php echo $incident['comment'] ?>">
    </div>

    <input type="hidden" name="ignore" value="0">
    <div class="form-group">
        <div class="checkbox"><label><input type="checkbox" name="ignore" <?php if($incident['ignore'] == "1") echo 'checked="yes"'; ?> value="1"> <?php _e('Ignore from uptime calculation'); ?></label></div>
    </div>

    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
    <input type="hidden" name="action" value="editWebsiteIncidentComment">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
    <input type="hidden" name="section" value="<?php echo $_GET['section']; ?>">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success" ><?php _e('Save'); ?></button>
</div>
