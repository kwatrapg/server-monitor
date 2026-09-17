<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit Log Alert'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">

        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Alert Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option></option>
                    <option value='matchcount' <?php if($logalert['type'] == "matchcount") echo "selected"; ?>><?php _e('Pattern Match Count'); ?></option>
                    <option value='levelcount' <?php if($logalert['type'] == "levelcount") echo "selected"; ?>><?php _e('Level Count (e.g. errors)'); ?></option>
                    <option value='absence' <?php if($logalert['type'] == "absence") echo "selected"; ?>><?php _e('Absence - no data received'); ?></option>
                    <option value='ratespike' <?php if($logalert['type'] == "ratespike") echo "selected"; ?>><?php _e('Rate Spike vs. baseline'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="sourceid"><?php _e('Source'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="sourceid" name="sourceid" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value="0" <?php if((int)$logalert['sourceid'] === 0) echo "selected"; ?>><?php _e('All sources on this server'); ?></option>
                    <?php foreach ($logsourceSiblingSources as $src) { ?>
                        <option value='<?php echo $src['id']; ?>' <?php if($src['id'] == $logalert['sourceid']) echo "selected"; ?>><?php echo $src['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="pattern"><?php _e('Pattern'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Pattern Match Count: substring to search for. Level Count: comma-separated levels, default error,crit. Ignored for Absence and Rate Spike.'); ?>"></i></label>
                <input type="text" class="form-control" id="pattern" name="pattern" value="<?php echo htmlspecialchars($logalert['pattern']); ?>">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group" id="comparison-div">
                <label for="comparison"><?php _e('Comparison'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="comparison" name="comparison" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='>=' <?php if($logalert['comparison'] == ">=") echo "selected"; ?>>&gt;=</option>
                    <option value='==' <?php if($logalert['comparison'] == "==") echo "selected"; ?>>==</option>
                    <option value='<=' <?php if($logalert['comparison'] == "<=") echo "selected"; ?>>&lt;=</option>
                    <option value='>' <?php if($logalert['comparison'] == ">") echo "selected"; ?>>&gt;</option>
                    <option value='<' <?php if($logalert['comparison'] == "<") echo "selected"; ?>>&lt;</option>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group" id="comparison_limit-div">
                <label for="comparison_limit"><?php _e('Threshold'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Count threshold, or the multiplier for Rate Spike (e.g. 3 = 3x baseline).'); ?>"></i></label>
                <input type="text" class="form-control" id="comparison_limit" name="comparison_limit" value="<?php echo htmlspecialchars($logalert['comparison_limit']); ?>">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="window_minutes"><?php _e('Window (minutes)'); ?></label>
                <input type="text" class="form-control" id="window_minutes" name="window_minutes" value="<?php echo $logalert['window_minutes']; ?>">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1' <?php if($logalert['status'] == 1) echo "selected"; ?>><?php _e('Active'); ?></option>
                    <option value='0' <?php if($logalert['status'] == 0) echo "selected"; ?>><?php _e('Inactive'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="contacts"><?php _e('Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected here will receive notifications for this alert.'); ?>"></i>
                    <a href="#" id="contacts-select-all" style="margin-left:8px"><?php _e('select all'); ?></a> /
                    <a href="#" id="contacts-select-none"><?php _e('none'); ?></a>
                </label>
                <select class="form-control select2tags select2-hidden-accessible" id="contacts" name="contacts[]" style="width: 100%;" multiple>
                    <?php foreach ($contacts as $contact) { ?>
                        <option value='<?php echo $contact['id']; ?>' <?php if(in_array($contact['id'], $selected_contacts)) echo "selected"; ?>><?php echo $contact['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0' <?php if($logalert['repeats'] == 0) echo "selected"; ?>><?php _e('Once'); ?></option>
                    <option value='1440' <?php if($logalert['repeats'] == 1440) echo "selected"; ?>><?php _e('Every 24 hours'); ?></option>
                    <option value='10080' <?php if($logalert['repeats'] == 10080) echo "selected"; ?>><?php _e('Every 7 days'); ?></option>
                </select>
            </div>
        </div>

    </div>

    <input type="hidden" name="serverid" value="<?php echo $logsourceServer['id']; ?>">
    <input type="hidden" name="id" value="<?php echo $logalert['id']; ?>">

    <input type="hidden" name="action" value="editLogAlert">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
    <input type="hidden" name="section" value="alerting">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success"><i class="fa fa-save"></i> <?php _e('Save'); ?></button>
</div>

<script type="text/javascript">
    $(".select2").select2();

    <?php // See add.php - not wrapped in $(function(){}), which silently never fires
    // for a .ready() callback registered from a script injected after page load via AJAX. ?>
    $(".select2tags").select2({
        tags: true
    });

    var allContactIds = [<?php foreach ($contacts as $contact) echo "'" . $contact['id'] . "',"; ?>];

    $("#contacts-select-all").on("click", function(e) {
        e.preventDefault();
        $("#contacts").val(allContactIds).trigger("change");
    });
    $("#contacts-select-none").on("click", function(e) {
        e.preventDefault();
        $("#contacts").val(null).trigger("change");
    });
</script>
