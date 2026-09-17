<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Command'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="serverid"><?php _e('Server'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="serverid" name="serverid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($command_servers as $server) { ?>
                        <option value='<?php echo $server['id']; ?>'><?php echo $server['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="<?php _e('e.g. disk backup check'); ?>">
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="command"><?php _e('Command'); ?> * <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Runs locally on the server via the agent, every collection cycle. A non-zero exit code opens an incident; exit 0 closes it.'); ?>"></i></label>
                <input type="text" class="form-control" id="command" name="command" required placeholder="test -f /var/backups/latest.tar.gz">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="timeout_seconds"><?php _e('Timeout (seconds)'); ?></label>
                <input type="number" min="1" class="form-control" id="timeout_seconds" name="timeout_seconds" value="30">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1'><?php _e('Active'); ?></option>
                    <option value='0'><?php _e('Inactive'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0'><?php _e('Once'); ?></option>
                    <option value='1440'><?php _e('Every 24 hours'); ?></option>
                    <option value='10080'><?php _e('Every 7 days'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="contacts"><?php _e('Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected here will receive notifications when this command fails.'); ?>"></i>
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

    </div>

    <input type="hidden" name="action" value="addCommand">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Command'); ?></button>
</div>

<script type="text/javascript">
    $(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });

    <?php // Not wrapped in $(function(){}) - this modal is injected via AJAX (showM/.load())
    // well after the page's own DOMContentLoaded already fired, and jQuery silently drops
    // a .ready() callback registered post-ready in that context instead of firing it. ?>
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
