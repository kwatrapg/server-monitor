<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit Action'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <?php include __DIR__ . '/_form.php'; ?>
    </div>

    <input type="hidden" name="id" value="<?php echo e($action['id']); ?>">
    <input type="hidden" name="action" value="editServerAlertAction">
    <input type="hidden" name="route" value="<?php echo e($_GET['reroute'] ?? ''); ?>">
    <input type="hidden" name="routeid" value="<?php echo e($_GET['routeid'] ?? ''); ?>">
    <input type="hidden" name="section" value="actions">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success"><i class="fa fa-save"></i> <?php _e('Save'); ?></button>
</div>
