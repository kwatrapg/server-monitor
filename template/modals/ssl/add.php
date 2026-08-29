<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add SSL Check'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="<?php _e('Name for easy identification'); ?>">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="groupid"><?php _e('Group'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="groupid" name="groupid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($groups as $group) { if(!checkGroup($group['id'])) continue; ?>
                        <option value='<?php echo $group['id']; ?>'><?php echo e($group['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="url"><?php _e('Website URL'); ?> *</label>
                <input type="text" class="form-control" id="url" name="url" required placeholder="<?php _e('https://www.mydomain.com'); ?>" data-validation="url" data-validation-error-msg="<?php _e('Incorrect URL! (eg. https://www.google.com)'); ?>">
            </div>
        </div>

    </div>

    <input type="hidden" name="on_map" value="0">
    <input type="hidden" name="lat" value="">
    <input type="hidden" name="lng" value="">

    <input type="hidden" name="action" value="addSsl">
    <input type="hidden" name="route" value="<?php echo e($_GET['reroute'] ?? ''); ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add SSL Check'); ?></button>
</div>

<script type="text/javascript">
    $(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });
</script>
