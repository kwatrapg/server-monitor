<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Action'); ?></h4>
</div>

<div class="modal-body">

    <?php if (empty($alerts)) { ?>
        <p><?php _e('This server has no alerts yet. Add an alert on the Alerting tab first, then attach an action to it.'); ?></p>
    <?php } else { ?>

    <div class="row">
        <?php include __DIR__ . '/_form.php'; ?>
    </div>

    <?php } ?>

    <input type="hidden" name="serverid" value="<?php echo e($server['id']); ?>">
    <input type="hidden" name="action" value="addServerAlertAction">
    <input type="hidden" name="route" value="<?php echo e($_GET['reroute'] ?? ''); ?>">
    <input type="hidden" name="routeid" value="<?php echo e($_GET['routeid'] ?? ''); ?>">
    <input type="hidden" name="section" value="actions">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <?php if (!empty($alerts)) { ?>
        <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Action'); ?></button>
    <?php } ?>
</div>
