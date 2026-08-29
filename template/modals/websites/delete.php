<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Delete Website'); ?></h4>
</div>

<div class="modal-body">
    <?php _e('Are you sure you want to delete this website?'); ?>

    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
    <input type="hidden" name="action" value="deleteWebsite">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><?php _e('No'); ?></button>
    <button type="submit" class="btn btn-flat btn-danger" ><?php _e('Yes'); ?></button>
</div>
