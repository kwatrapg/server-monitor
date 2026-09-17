<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Log Alert'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">

        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Alert Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option></option>
                    <option value='matchcount'><?php _e('Pattern Match Count'); ?></option>
                    <option value='levelcount'><?php _e('Level Count (e.g. errors)'); ?></option>
                    <option value='absence'><?php _e('Absence - no data received'); ?></option>
                    <option value='ratespike'><?php _e('Rate Spike vs. baseline'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="sourceid"><?php _e('Source'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="sourceid" name="sourceid" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value="0"><?php _e('All sources on this server'); ?></option>
                    <?php foreach ($logsourceSiblingSources as $src) { ?>
                        <option value='<?php echo $src['id']; ?>' <?php if($src['id'] == $logsource['id']) echo "selected"; ?>><?php echo $src['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="pattern"><?php _e('Pattern'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Pattern Match Count: substring to search for. Level Count: comma-separated levels, default error,crit. Ignored for Absence and Rate Spike.'); ?>"></i></label>
                <input type="text" class="form-control" id="pattern" name="pattern" placeholder="<?php _e('e.g. OutOfMemoryError, or error,crit'); ?>">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group" id="comparison-div">
                <label for="comparison"><?php _e('Comparison'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="comparison" name="comparison" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='>=' selected>&gt;=</option>
                    <option value='=='>==</option>
                    <option value='<='>&lt;=</option>
                    <option value='>'>&gt;</option>
                    <option value='<'>&lt;</option>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group" id="comparison_limit-div">
                <label for="comparison_limit"><?php _e('Threshold'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Count threshold, or the multiplier for Rate Spike (e.g. 3 = 3x baseline).'); ?>"></i></label>
                <input type="text" class="form-control" id="comparison_limit" name="comparison_limit" value="1">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="window_minutes"><?php _e('Window (minutes)'); ?></label>
                <input type="text" class="form-control" id="window_minutes" name="window_minutes" value="5">
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1'><?php _e('Active'); ?></option>
                    <option value='0'><?php _e('Inactive'); ?></option>
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
                        <option value='<?php echo $contact['id']; ?>'><?php echo $contact['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0'><?php _e('Once'); ?></option>
                    <option value='1440'><?php _e('Every 24 hours'); ?></option>
                    <option value='10080'><?php _e('Every 7 days'); ?></option>
                </select>
            </div>
        </div>

    </div>

    <input type="hidden" name="serverid" value="<?php echo $logsourceServer['id']; ?>">

    <input type="hidden" name="action" value="addLogAlert">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
    <input type="hidden" name="section" value="alerting">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Alert'); ?></button>
</div>

<script type="text/javascript">
    $(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });

    <?php // Not wrapped in $(function(){}) - this modal is injected via AJAX (showM/.load())
    // well after the page's own DOMContentLoaded already fired, and jQuery silently drops
    // a .ready() callback registered post-ready in that context instead of firing it. The
    // unwrapped call above (for the single-selects) works reliably; this must match it. ?>
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
