<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Delete Action'); ?></h4>
</div>

<div class="modal-body">
    <?php _e('Are you sure you want to delete this action?'); ?>

    <input type="hidden" name="id" value="<?php echo e($_GET['id'] ?? ''); ?>">
    <input type="hidden" name="action" value="deleteServerAlertAction">
    <input type="hidden" name="route" value="<?php echo e($_GET['reroute'] ?? ''); ?>">
    <input type="hidden" name="routeid" value="<?php echo e($_GET['routeid'] ?? ''); ?>">
    <input type="hidden" name="section" value="actions">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><?php _e('No'); ?></button>
    <button type="submit" class="btn btn-flat btn-danger" ><?php _e('Yes'); ?></button>
</div>
