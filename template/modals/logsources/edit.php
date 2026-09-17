<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit Log Source'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="serverid"><?php _e('Server'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="serverid" name="serverid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($logsource_servers as $server) { ?>
                        <option value='<?php echo $server['id']; ?>' <?php if($server['id'] == $logsource['serverid']) echo "selected"; ?>><?php echo $server['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $logsource['name']; ?>" required>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="path_glob"><?php _e('Path / Glob'); ?> *</label>
                <input type="text" class="form-control" id="path_glob" name="path_glob" value="<?php echo $logsource['path_glob']; ?>" required>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="mode"><?php _e('Collection Mode'); ?></label>
                <select class="form-control" id="mode" name="mode">
                    <option value="disabled" <?php if($logsource['mode'] == 'disabled') echo "selected"; ?>><?php _e('Disabled'); ?></option>
                    <option value="errors_only" <?php if($logsource['mode'] == 'errors_only') echo "selected"; ?>><?php _e('Errors Only'); ?></option>
                    <option value="filtered" <?php if($logsource['mode'] == 'filtered') echo "selected"; ?>><?php _e('Filtered'); ?></option>
                    <option value="everything" <?php if($logsource['mode'] == 'everything') echo "selected"; ?>><?php _e('Everything'); ?></option>
                </select>
                <p class="help-block"><?php _e('Errors Only keeps warn/error/critical lines. Filtered applies the include/exclude patterns below.'); ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="multiline_pattern"><?php _e('Multiline First-Line Pattern'); ?></label>
                <input type="text" class="form-control" id="multiline_pattern" name="multiline_pattern" value="<?php echo $logsource['multiline_pattern']; ?>" placeholder="<?php _e('optional'); ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="include_regex"><?php _e('Include Pattern'); ?></label>
                <input type="text" class="form-control" id="include_regex" name="include_regex" value="<?php echo $logsource['include_regex']; ?>" placeholder="<?php _e('optional, only used in Filtered mode'); ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="exclude_regex"><?php _e('Exclude Pattern'); ?></label>
                <input type="text" class="form-control" id="exclude_regex" name="exclude_regex" value="<?php echo $logsource['exclude_regex']; ?>" placeholder="<?php _e('optional, only used in Filtered mode'); ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="rate_limit"><?php _e('Rate Limit (lines/min)'); ?></label>
                <input type="number" min="0" class="form-control" id="rate_limit" name="rate_limit" value="<?php echo $logsource['rate_limit']; ?>" placeholder="0 = unlimited">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="sample_rate"><?php _e('Sample Rate'); ?></label>
                <input type="number" min="1" class="form-control" id="sample_rate" name="sample_rate" value="<?php echo $logsource['sample_rate']; ?>" placeholder="<?php _e('1 = ship everything, 10 = ship 1 in 10'); ?>">
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="labels"><?php _e('Extra Labels'); ?></label>
                <textarea class="form-control" id="labels" name="labels" rows="2" placeholder="<?php _e("one per line, key=value"); ?>"><?php echo $logsource_labels; ?></textarea>
            </div>
        </div>

    </div>

    <input type="hidden" name="id" value="<?php echo $logsource['id']; ?>">
    <input type="hidden" name="action" value="editLogSource">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success"><i class="fa fa-save"></i> <?php _e('Save'); ?></button>
</div>

<script type="text/javascript">
    $(".select2").select2();
</script>
