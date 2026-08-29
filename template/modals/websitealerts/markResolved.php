<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Mark Resolved'); ?></h4>
</div>

<div class="modal-body">
    <p><?php _e('Are you sure you want to mark this incident as resolved?'); ?></p>

    <p class="text-muted"><?php _e('If the problem is not resolved a new incident will be created.'); ?></p>

    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
    <input type="hidden" name="action" value="markWebsiteIncident">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
    <input type="hidden" name="section" value="<?php echo $_GET['section']; ?>">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><?php _e('No'); ?></button>
    <button type="submit" class="btn btn-flat btn-success" ><?php _e('Yes'); ?></button>
</div>
